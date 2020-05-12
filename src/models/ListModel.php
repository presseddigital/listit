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
    private $_subscriptions;

    // Public Properties
    // =========================================================================

    public $handle;
    public $elementType;

    // Public Methods
    // =========================================================================

    public static function subscriptions()
    {
        return Subscription::find()
            ->list($this->handle);
    }

    public static function subscribers()
    {
        return Subscription::findSubscribers()
            ->list($this->handle);
    }

    public static function elements()
    {
        return Subscription::findElements()
            ->list($this->handle);
    }

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

    public function getElementTypeLabel()
    {
        return $this->elementType ? ucwords(App::humanizeClass($this->elementType)) : '';
    }

    public function getTotalSubscriptions()
    {
        return self::subscriptions()->count();
    }

}
