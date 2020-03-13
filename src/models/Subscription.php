<?php
namespace fruitstudios\listit\models;

use fruitstudios\listit\Listit;

use Craft;
use craft\base\Model;

class Subscription extends Model
{
    // Private Properties
    // =========================================================================

    private $_owner;
    private $_element;

    // Public Properties
    // =========================================================================

    public $id;
    public $ownerId;
    public $elementId;
    public $list;
    public $siteId;
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            [['ownerId', 'elementId', 'siteId'], 'integer'],
            [['list'], 'string'],
            [['ownerId', 'elementId', 'siteId', 'list'], 'required'],
        ];
    }

    public function getOwner()
    {
        if(is_null($this->_owner))
        {
            $this->_owner = Craft::$app->getUsers()->getUserById($this->ownerId);
        }
        return $this->_owner;
    }

    public function getElement()
    {
        if(is_null($this->_element))
        {
            $this->_element = Craft::$app->getElements()->getElementById($this->elementId);
        }
        return $this->_element;
    }
}
