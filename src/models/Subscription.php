<?php
namespace presseddigital\listit\models;

use presseddigital\listit\Listit;
use presseddigital\listit\db\SubscriptionQuery;
use presseddigital\listit\db\SubscriberQuery;
use presseddigital\listit\db\ElementQuery;

use Craft;
use craft\base\Model;

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

    // Static Methods
    // =========================================================================

    public static function find(): SubscriptionQuery
    {
        return new SubscriptionQuery();
    }

    public static function findSubscribers(): SubscriberQuery
    {
        return new SubscriberQuery();
    }

    public static function findElements(): ElementQuery
    {
        return new ElementQuery();
    }

    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'subscriberId', 'siteId', 'elementId'], 'number', 'integerOnly' => true ];
        $rules[] = [['subscriberId', 'siteId', 'list'], 'required'];
        // $rules[] = [['elementId'], 'validateSubscriberId'];
        // $rules[] = [['elementId'], 'validateElementId'];
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
    }

    // public function validateElementId()
    // {
    //     if($this->list && $this->elementId)
    //     {
    //         $element = Craft::$app->getElements()->getElementById((int)$this->elementId);
    //         if(!$element)
    //         {
    //             $this->addError('elementId', Craft::t('Element not found'));
    //             return;
    //         }
    //     }
    // }

    public function beforeValidate()
    {
        if (!$this->subscriberId)
        {
            $this->subscriberId = Craft::$app->getUser()->getId();
        }

        return parent::beforeValidate();
    }

    public function getSubscriber()
    {
        if($this->_subscriber !== null)
        {
            return $this->_subscriber;
        }
        return $this->_subscriber = Craft::$app->getUsers()->getUserById($this->subscriberId);
    }

    public function getElement()
    {
        if($this->_element !== null)
        {
            return $this->_element;
        }
        return $this->_element = Craft::$app->getElements()->getElementById($this->elementId);
    }
}
