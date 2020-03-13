<?php
namespace fruitstudios\listit\records;

use fruitstudios\listit\Listit;

use Craft;
use craft\db\ActiveRecord;

class Subscription extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    public static function tableName()
    {
        return '{{%listit}}';
    }
}
