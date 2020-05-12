<?php
namespace presseddigital\listit\models;

use presseddigital\listit\Listit;
use presseddigital\listit\helpers\StringHelper;
use presseddigital\listit\models\Subscription;

use Craft;
use craft\base\Model;
use craft\helpers\App;

class ListModel extends Model
{
    // Private Properties
    // =========================================================================

    private $_name;

    // Public Properties
    // =========================================================================

    public $handle;
    public $elementType;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            [['handle'], 'string'],
            [['handle'], 'required'],
        ];
    }

    public function setName(string $name = null)
    {
        $this->_name = $name;
    }

    public function getName()
    {
        return $this->_name ?? StringHelper::labelize($this->handle);
    }

    public function getDisplayElementType()
    {
        return $this->elementType ? ucwords(App::humanizeClass($this->elementType)) : '-';
    }

    public function getSubscriptions()
    {
        return Subscription::find()->handle($this->handle);
    }

    public function getSubscribers()
    {
        return Subscription::findSubscribers()->handle($this->handle);
    }

    public function getElements()
    {
        return Subscription::findElements()->handle($this->handle);
    }
}
