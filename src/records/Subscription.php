<?php
namespace presseddigital\listit\records;

use presseddigital\listit\Listit;

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
