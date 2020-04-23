<?php
namespace presseddigital\listit\models;

use presseddigital\listit\Listit;
use presseddigital\listit\db\SubscriptionQuery;

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

    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'subscriberId', 'siteId', 'elementId'], 'number', 'integerOnly' => true ];
        $rules[] = [['id', 'subscriberId', 'siteId', 'list'], 'required'];
        // $rules[] = [['elementId'], 'validateSubscriberId'];
        // $rules[] = [['elementId'], 'validateElementId'];
        return $rules;
    }

    // Public Methods
    // =========================================================================

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
