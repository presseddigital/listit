<?php
namespace presseddigital\listit\models;

use presseddigital\listit\services\Lists;

use craft\base\Model;
use craft\elements\User;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $pluginNameOverride = 'Listit';
	public $hasCpSectionOverride = false;

	public $lists = [];

    // Public Methods
    // =========================================================================

	public function rules(): array
    {
        return [
            ['pluginNameOverride', 'string'],
            ['pluginNameOverride', 'default', 'value' => 'Listit'],
            ['hasCpSectionOverride', 'boolean'],
            ['hasCpSectionOverride', 'default', 'value' => false],
            ['lists', 'default', 'value' => []],
        ];
    }

    public function setLists($lists)
    {
    	return $lists;
    }

    public function getLists()
    {
    	return [
    		[
    			'name' => 'Maybes',
    			'handle' => 'maybes',
    			'elementType' => 'sadfasdfasdf',
    		]
    	];

    }

}
