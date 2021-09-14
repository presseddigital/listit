<?php
namespace presseddigital\listit\db;

use presseddigital\listit\Listit;
use presseddigital\listit\db\Table;
use presseddigital\listit\models\Subscription;
use presseddigital\listit\helpers\ElementHelper;

use Craft;
use craft\db\Query;
use craft\db\ElementQueryInterface;
use craft\db\Table as CraftTable;
use craft\db\QueryAbortedException;
use craft\helpers\Db;
use craft\helpers\ArrayHelper;
use craft\base\ElementInterface;
use craft\models\Site;
use craft\elements\User;
use yii\base\InvalidArgumentException;

class SubscriptionQuery extends Query
{
	// Properties
	// =========================================================================

	public $query;

    protected $defaultOrderBy = ['subscriptions.dateCreated' => SORT_DESC];

	public $id;
    public $list;
    public $subscriberId;
    public $elementId;
    public $siteId;
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    public $elementType;

    public $subscriberCriteria;
    public $elementCriteria;

    public $asArray = false;

    public $withAll = true;
    public $withSubscribers;
    public $withElements;

    // Public Methods
    // =========================================================================

	public function __set($name, $value)
    {
        switch ($name)
        {
            case 'subscriber':
            case 'element':
            case 'site':
            {
            	$this->$name($value);
            	break;
            }
            default:
            {
                parent::__set($name, $value);
                break;
            }
        }
    }

    // Parameter setters
    // =========================================================================

    public function id($value)
    {
        $this->id = $value;
        return $this;
    }

    public function list($value)
    {
        $this->list = $value;
        return $this;
    }

    public function subscriber($value)
    {
        if($value === null || !$value)
    	{
    		$this->subscriberId = null;
    	}
    	else if($value instanceof User)
    	{
    		$this->subscriberId = $value->id;
    	}
    	else if($value instanceof UserQuery)
    	{
    		$this->subscriberId = $value->ids();
    	}
        else
        {
            $this->subscriberId = $value;
        }

        return $this;
    }

    public function subscriberId($value)
    {
        $this->subscriberId = $value;
        return $this;
    }

    public function elementType(string $value = null)
    {
        $this->elementType = $value ? ElementHelper::normalizeClassName($value) : null;
        return $this;
    }

	public function element($value)
    {
    	if ($value !== null && (is_bool($value) || !$value))
    	{
    		$this->elementId = $value ? ':notempty:' : ':empty:';
    	}
    	else if ($value instanceof ElementInterface)
    	{
    		$this->elementId = $value->id;
    	}
    	else if ($value instanceof ElementQueryInterface)
    	{
    		$this->elementId = $value->ids();
    	}
        else
        {
            $this->elementId = $value;
        }

        return $this;
    }

    public function elementId($value)
    {
        $this->elementId = $value;
        return $this;
    }

    public function anyElement()
    {
        $this->elementId = ':notempty:';
        return $this;
    }

    public function site($value)
    {
        switch (true)
	    {
        	case $value === '*' || $value === null:
        	{
        		$this->siteId = $value;
        		break;
        	}
        	case $value instanceof Site:
        	{
        		$this->siteId = $value->id;
        		break;
        	}
        	case (is_numeric($value)):
        	{
        		$site = Craft::$app->getSites()->getSiteById($value);
	            if (!$site) throw new InvalidArgumentException('Invalid site id: ' . $value);
	            $this->siteId = $site->id;
        		break;
        	}
        	case (is_string($value)):
        	{
        		$site = Craft::$app->getSites()->getSiteByHandle($value);
	            if (!$site) throw new InvalidArgumentException('Invalid site handle: ' . $value);
	            $this->siteId = $site->id;
        		break;
        	}
        	default:
        	{
        		if ($not = (strtolower(reset($value)) === 'not'))
        		{
        		    array_shift($value);
        		}
        		$this->siteId = [];
        		foreach (Craft::$app->getSites()->getAllSites() as $site)
        		{
        		    if (in_array($site->handle, $value, true) === !$not)
        		    {
        		        $this->siteId[] = $site->id;
        		    }
        		}
        		if (empty($this->siteId))
        		{
        		    throw new InvalidArgumentException('Invalid site param: [' . ($not ? 'not, ' : '') . implode(', ', $value) . ']');
        		}
        		break;
        	}
        }
        return $this;
    }

    public function siteId($value)
    {
        if (is_array($value) && strtolower(reset($value)) === 'not')
        {
            array_shift($value);
            $this->siteId = [];
            foreach (Craft::$app->getSites()->getAllSites() as $site)
            {
                if (!in_array($site->id, $value, false))
                {
                    $this->siteId[] = $site->id;
                }
            }
        }
        else
        {
            $this->siteId = $value;
        }
        return $this;
    }

    public function anySite()
    {
        $this->siteId = '*';
        return $this;
    }

    public function dateCreated($value)
    {
        $this->dateCreated = $value;
        return $this;
    }

    public function dateUpdated($value)
    {
        $this->dateUpdated = $value;
        return $this;
    }

    public function uid($value)
    {
        $this->uid = $value;
        return $this;
    }

    public function asArray(bool $value = true)
    {
        $this->asArray = $value;
        return $this;
    }

    public function elementCriteria(array $value = null)
    {
        $this->elementCriteria = $value;
        return $this;
    }

    public function subscriberCriteria(array $value = null)
    {
        $this->subscriberCriteria = $value;
        return $this;
    }

    public function withAll($value = true)
    {
        $this->withAll = $value;
        return $this;
    }

    public function withElements($value = true)
    {
        $this->withElements = $value;
        return $this;
    }

    public function withSubscribers($value = true)
    {
        $this->withSubscribers = $value;
        return $this;
    }

    // Query
    // =========================================================================

    public function prepare($builder)
    {
        // Ensure there is a list if we are working with elements
        if((!$this->list || $this->list == '*') && $this->elementCriteria)
        {
            throw new InvalidArgumentException('List required if working with element criteria');
        }

        $this->query = (new Query())
            ->from([Table::SUBSCRIPTIONS . ' subscriptions'])
            ->leftJoin(CraftTable::ELEMENTS.' elements', '[[elements.id]] = [[subscriptions.elementId]]')
            ->andWhere($this->where)
            ->offset($this->offset)
            ->limit($this->limit)
            ->orderBy($this->orderBy ? $this->orderBy : $this->defaultOrderBy)
            ->addParams($this->params);

        $select = array_merge((array)$this->select);
        if(empty($select))
        {
            $select = [
                'subscriptions.id' => 'subscriptions.id',
                'subscriptions.list' => 'subscriptions.list',
                'subscriptions.subscriberId' => 'subscriptions.subscriberId',
                'subscriptions.elementId' => 'subscriptions.elementId',
                'subscriptions.siteId' => 'subscriptions.siteId',
                'subscriptions.dateCreated' => 'subscriptions.dateCreated',
                'subscriptions.dateUpdated' => 'subscriptions.dateUpdated',
                'subscriptions.metadata' => 'subscriptions.metadata',
                'subscriptions.uid' => 'subscriptions.uid',
                'elementType' => 'elements.type',
            ];
        }
        $this->query->select($select);

        if ($this->distinct)
        {
            $this->query->distinct();
        }

        if ($this->id)
        {
            $this->query->andWhere(Db::parseParam('subscriptions.id', $this->id));
        }

        if ($this->list !== '*' && $this->list)
        {
            $this->query->andWhere(Db::parseParam('subscriptions.list', $this->list));
        }

        if ($this->siteId !== '*')
        {
        	$siteId = $this->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
            $this->query->andWhere(['subscriptions.siteId' => $siteId]);
        }

        // Restrict results by subscriber criteria
        // - fallback to the subscriberId value (if supplied)
        if ($this->subscriberCriteria)
        {
            $subscribersQuery = User::find()
                ->select('subscriptions.subscriberId')
                ->innerJoin(Table::SUBSCRIPTIONS.' subscriptions', '[[elements.id]] = [[subscriptions.subscriberId]]')
                ->limit(null);
            Craft::configure($subscribersQuery, $this->subscriberCriteria);
            $this->query->andWhere(['in', 'subscriptions.subscriberId', $subscribersQuery]);
        }
        elseif($this->subscriberId)
        {
            $this->query->andWhere(Db::parseParam('subscriptions.subscriberId', $this->subscriberId));
        }

        // Restrict results by element criteria
        // - fallback to the elementId value (if supplied)
        if ($this->elementCriteria && $elementType = $this->_determineElementType())
        {
            $elementQuery = $elementType::find()
                ->select('subscriptions.elementId')
                ->innerJoin(Table::SUBSCRIPTIONS.' subscriptions', '[[elements.id]] = [[subscriptions.elementId]]')
                ->limit(null);
            Craft::configure($elementQuery, $this->elementCriteria);
            $this->query->andWhere(['in', 'subscriptions.elementId', $elementQuery]);
        }
        elseif ($this->elementId)
        {
            $this->query->andWhere(Db::parseParam('subscriptions.elementId', $this->elementId));
        }

        if($this->elementType)
        {
            $this->query->andWhere(Db::parseParam('elements.type', $this->elementType));
        }

        // Dates
        if ($this->dateCreated)
        {
            $this->query->andWhere(Db::parseDateParam('subscriptions.dateCreated', $this->dateCreated));
        }

        if ($this->dateUpdated)
        {
            $this->query->andWhere(Db::parseDateParam('subscriptions.dateUpdated', $this->dateUpdated));
        }

        // Uid
        if ($this->uid)
        {
            $this->query->andWhere(Db::parseParam('subscriptions.uid', $this->uid));
        }

        // Group By
        if ($this->groupBy)
        {
            $this->query->groupBy = $this->groupBy;
        }

        return $this->query;
    }

    public function populate($rows)
    {
        if (empty($rows)) return [];
        return $this->_createSubscriptions($rows);
    }

    // Execution functions
    // =========================================================================

    public function ids($db = null, string $column = 'id')
    {
        $select = $this->select;
        $this->select = ['subscriptions.'.$column => 'subscriptions.'.$column];
        $result = parent::column($db);
        $this->select($select);
        return array_unique($result);
    }

    public function one($db = null)
    {
        if($row = parent::one($db))
        {
            $subscriptions = $this->populate([$row]);
            return reset($subscriptions) ?: null;
        }
        return null;
    }

    public function elementIds($db = null)
    {
        return $this->_elementsQuery()->ids();
    }

    public function allElements($db = null)
    {
        return $this->elements($db);
    }

    public function elements($db = null)
    {
        return $this->_elementsQuery()->all();
    }

    public function subscriberIds($db = null)
    {
        return $this->_subscribersQuery()->ids();
    }

    public function subscribers($db = null)
    {
        return $this->_subscribersQuery()->all();
    }

    public function allSubscribers($db = null)
    {
        return $this->subscribers($db);
    }

    // Private Methods
    // -------------------------------------------------------------------------

    private function _subscribersQuery(): Query
    {
        $subscribers = User::find()
            ->limit($this->limit)
            ->andWhere([
                'in',
                'elements.id',
                (clone $this)->select('subscriptions.subscriberId')->subscriberCriteria(null)->limit(null)
            ]);

        if($this->subscriberCriteria)
        {
            Craft::configure($subscribers, $this->subscriberCriteria);
        }

        return $subscribers;
    }

    private function _elementsQuery(): Query
    {
        $elementType = $this->_determineElementType();
        if(!$elementType)
        {
            throw new InvalidArgumentException('Invalid element type');
        }

        $elements = $elementType::find()
            ->limit($this->limit)
            ->andWhere([
                'in',
                'elements.id',
                (clone $this)->select('subscriptions.elementId')->elementCriteria(null)->limit(null)
            ]);

        if($this->elementCriteria)
        {
            Craft::configure($elements, $this->elementCriteria);
        }

        return $elements;
    }

    private function _createSubscriptions(array $rows)
    {
        $subscriptions = [];

        if ($this->asArray === true)
        {
            if ($this->indexBy === null)
            {
                return $rows;
            }

            foreach ($rows as $row)
            {
            	$key = is_string($this->indexBy) ? $row[$this->indexBy] : call_user_func($this->indexBy, $row);
                $subscriptions[$key] = $row;
            }

            return $subscriptions;
        }

        foreach ($rows as $row)
        {
            $subscription = new Subscription($row);

            if ($this->indexBy === null)
            {
                $subscriptions[] = $subscription;
            }
            else
            {
            	$key = is_string($this->indexBy) ? $subscription->{$this->indexBy} : call_user_func($this->indexBy, $subscription);
                $subscriptions[$key] = $subscription;
            }
        }

        $this->_eagerLoadSubscriptionElements($subscriptions);

        return $subscriptions;
    }


    private function _eagerLoadSubscriptionElements(array $subscriptions)
    {
        // Anything to eager-load?
        if (empty($subscriptions)) return;

        // Build an eager loading map or elements by type
        $eagerLoadingMap = [];
        foreach ($subscriptions as $subscription)
        {
            // Subscribers
            if($this->withAll || $this->withSubscribers)
            {
                $eagerLoadingMap[User::class][] = $subscription->subscriberId;
            }

            // Elements
            if(($this->withAll || $this->withElements) && $subscription->elementType && $subscription->elementId)
            {
                $eagerLoadingMap[$subscription->elementType][] = $subscription->elementId;
            }
        }

        // Nothing found to eager-load
        if (empty($eagerLoadingMap)) return;

        // Load elements indexed by id
        $elementsById = [];
        foreach ($eagerLoadingMap as $elementType => $elementIds)
        {
            $elements = $elementType::find()
                ->id(array_values(array_unique($elementIds)))
                ->limit(null)
                ->offset(null)
                ->orderBy(null)
                ->indexBy('id');
            $elementsById += $elements->all();
        }

        // Set elements on subscription models
        foreach($subscriptions as &$subscription)
        {
            if(isset($elementsById[$subscription->subscriberId]))
            {
                $subscription->setSubscriber($elementsById[$subscription->subscriberId]);
            }

            if(isset($elementsById[$subscription->elementId]))
            {
                $subscription->setElement($elementsById[$subscription->elementId]);
            }
        }
    }

    private function _determineElementType()
    {
        if($this->elementType)
        {
            return $this->elementType;
        }

        $list = Listit::$plugin->getLists()->getListByHandle($this->list);
        return $list->elementType ?? null;
    }

    private function _getElementsByTypeByIds(string $elementType, array $ids)
    {
        return $elementType::find()
            ->id(array_values(array_unique($ids)))
            ->orderBy(null)
            ->limit(null)
            ->offset(null)
            ->all();
    }

}
