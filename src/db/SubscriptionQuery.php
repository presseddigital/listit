<?php
namespace presseddigital\listit\db;

use presseddigital\listit\db\Table;
use presseddigital\listit\models\Subscription;

use Craft;
use craft\db\Query;
use craft\db\ElementQueryInterface;
use craft\helpers\Db;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\models\Site;
use yii\base\InvalidArgumentException;

class SubscriptionQuery extends Query
{
	// Properties
	// =========================================================================

	public $query;

	public $id;
    public $list;
    public $subscriberId;
    public $elementId;
    public $siteId;
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    public $asArray = false;

    public $elementType;

    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

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
            }
        }
    }

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
        switch (true)
	    {
        	case $value === null || !$value:
        	{
        		$this->subscriberId = null;
        		break;
        	}
        	case $value instanceof User:
        	{
        		$this->subscriberId = $value->id;
        		break;
        	}
        	case $value instanceof UserQuery:
        	{
        		$this->subscriberId = $value->ids();
        		break;
        	}
        	default:
        	{
        		$this->subscriberId = $value;
        		break;
        	}
        }

        return $this;
    }

    public function subscriberId(int $value = null)
    {
        $this->subscriberId = $value;
        return $this;
    }

	public function element($value)
    {
        switch (true)
	    {
	    	case $value !== null && (is_bool($value) || !$value):
        	{
        		$this->elementId = $value ? ':notempty:' : ':empty:';
        		break;
        	}
        	case $value instanceof ElementInterface:
        	{
        		$this->elementId = $value->id;
        		break;
        	}
        	case $value instanceof ElementQueryInterface:
        	{
        		$this->elementId = $value->ids();
        		break;
        	}
        	default:
        	{
        		$this->elementId = $value;
        		break;
        	}
        }

        return $this;
    }

    public function elementId(int $value = null)
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
        $this->siteId = $value;
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

    // Query
    // -------------------------------------------------------------------------

    public function prepare($builder)
    {
        $select = array_merge((array)$this->select);
        if(empty($select))
        {
        	$select = [
                'subscriptions.id',
                'subscriptions.list',
                'subscriptions.subscriberId',
                'subscriptions.elementId',
                'subscriptions.siteId',
                'subscriptions.dateCreated',
                'subscriptions.dateUpdated',
                'subscriptions.dateCreated',
                'subscriptions.uid',
            ];
        }

        $this->query = (new Query())
            ->select($select)
            ->from([Table::SUBSCRIPTIONS . ' subscriptions']);

        if ($this->id)
        {
            $this->query->andWhere(Db::parseParam('subscriptions.id', $this->id));
        }

        if ($this->list)
        {
            $this->query->andWhere(Db::parseParam('subscriptions.list', $this->list));
        }

        if ($this->siteId !== '*')
        {
        	$siteId = $this->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
            $this->query->andWhere(['subscriptions.siteId' => $siteId]);
        }

        if ($this->subscriberId)
        {
        	$this->query->andWhere(Db::parseParam('subscriptions.subscriberId', $this->subscriberId));
        }

        if ($this->elementId)
        {
        	$this->query->andWhere(Db::parseParam('subscriptions.elementId', $this->elementId));
        }

    	// TODO: @sam - Restirct results by elementType
        //            - Join the elements table and restrict by type(s) supplied

        if ($this->dateCreated)
        {
            $this->query->andWhere(Db::parseDateParam('subscriptions.dateCreated', $this->dateCreated));
        }

        if ($this->dateUpdated)
        {
            $this->query->andWhere(Db::parseDateParam('subscriptions.dateUpdated', $this->dateUpdated));
        }

        if ($this->uid)
        {
            $this->query->andWhere(Db::parseParam('subscriptions.uid', $this->uid));
        }

        return $this->query;
    }

    public function populate($rows)
    {
        if (empty($rows))
        {
            return [];
        }

        return $this->_createSubscriptions($rows);
    }

    public function one($db = null)
    {
        if ($row = parent::one($db))
        {
            $subscriptions = $this->populate([$row]);
            return reset($subscriptions) ?: null;
        }

        return null;
    }

    public function ids($db = null): array
    {
        $select = $this->select;
        $this->select = ['subscriptions.id' => 'subscriptions.id'];
        $result = $this->column($db);
        $this->select($select);
        return $result;
    }


    // Private Methods
    // -------------------------------------------------------------------------

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

        // Is this something we can do?
        // if ($this->with)
        // {
        //     Craft::$app->getElements()->eagerLoadElements($this->elementType, $elements, $this->with);
        // }

        return $subscriptions;
    }


}
