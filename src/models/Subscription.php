<?php
namespace presseddigital\listit\models;

use presseddigital\listit\Listit;
use presseddigital\listit\db\SubscriptionQuery;

use Craft;
use craft\base\Model;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\Json;

class Subscription extends Model
{
    // Private Properties
    // =========================================================================

    private $_subscriber;
    private $_element;

    // Public Properties
    // =========================================================================

    public $id;
    public $subscriberId;
    public $elementId;
    public $list;
    public $siteId;
    public $metadata;
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    public $elementType;

    // Static Methods
    // =========================================================================

    public static function find(): SubscriptionQuery
    {
        return new SubscriptionQuery();
    }

    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'subscriberId', 'siteId', 'elementId'], 'number', 'integerOnly' => true ];
        $rules[] = [['subscriberId', 'siteId', 'list'], 'required'];
        $rules[] = [['elementId'], 'validateElement'];
        return $rules;
    }

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        if ($this->siteId === null)
        {
            $this->siteId = Craft::$app->getSites()->getPrimarySite()->id;
        }

        if ($this->metadata !== null)
        {
            $this->metadata = Json::decode($this->metadata, true);
        }
    }

    public function validateElement()
    {
        if(!$this->list) return;

        $list = Listit::$plugin->getLists()->getListByHandle($this->list);
        if(!$list) return;

        if($list->elementType)
        {
            // Element must exist and match supplied type
            $element = Craft::$app->getElements()->getElementById($this->elementId, $list->elementType);
            if(!$element)
            {
                $this->addError('elementId', Listit::t('Please supply a valid {element}', [
                    'element' => strtolower($list->elementTypeLabel)
                ]));
                return;
            }
        }
        else
        {
            // Element must not exist
            if($this->elementId)
            {
                $this->addError('elementId', Listit::t('You cannot use an element with this list'));
            }
        }
    }

    public function setElementType(string $elementType)
    {
        $this->elementType = $elementType;
    }

    public function getElementType()
    {
        if($this->elementType !== null)
        {
            return $this->elementType;
        }

        $element = $this->getElement();
        if(!$element)
        {
            return $this->elementType = false;
        }

        return $this->elementType = get_class($element);
    }

    public function getDisplayElementType()
    {
        return $this->elementType ? ucwords(App::humanizeClass($this->elementType)) : '-';
    }

    public function beforeValidate()
    {
        if (!$this->subscriberId)
        {
            $this->subscriberId = Craft::$app->getUser()->getId();
        }

        return parent::beforeValidate();
    }

    public function setSubscriber(User $subscriber = null)
    {
        $this->_subscriber = $subscriber;
    }

    public function getSubscriber()
    {
        if($this->_subscriber !== null)
        {
            return $this->_subscriber;
        }
        return $this->_subscriber = Craft::$app->getUsers()->getUserById((int)$this->subscriberId);
    }

    public function setElement(ElementInterface $element = null)
    {
        $this->_element = $element;
    }

    public function getElement()
    {
        if($this->_element !== null)
        {
            return $this->_element;
        }

        if(!$this->elementId)
        {
            return $this->_element = false;
        }

        return $this->_element = Craft::$app->getElements()->getElementById((int)$this->elementId);
    }
}
