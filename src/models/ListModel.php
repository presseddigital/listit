<?php

namespace presseddigital\listit\models;

use craft\base\Model;

use craft\helpers\App;
use presseddigital\listit\helpers\StringHelper;

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

    public function rules(): array
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

    public function getElementType()
    {
        return $this->elementType;
    }

    public function getElementTypeLabel()
    {
        return $this->elementType ? ucwords(App::humanizeClass($this->elementType)) : '';
    }

    public function getTotalSubscriptions()
    {
        return self::findSubscriptions()->count();
    }

    public function findSubscriptions()
    {
        return Subscription::find()->list($this->handle);
    }
}
