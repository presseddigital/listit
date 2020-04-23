<?php
namespace presseddigital\listit\records;

use presseddigital\listit\Listit;
use presseddigital\listit\db\Table;
use craft\db\ActiveRecord;
use craft\records\User;
use craft\records\Element;
use craft\validators\HandleValidator;
use yii\db\ActiveQueryInterface;


/**
 * Subscription record.
 *
 * @property int $id
 * @property string $list
 * @property int $subscriberId
 * @property int $siteId
 * @property int $elementId
 * @property array $metadata
 */
class Subscription extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::SUBSCRIPTIONS;
    }

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
        	[['list'], 'string', 'max' => 255],
        	[['list'], HandleValidator::class, 'reservedWords' => []],
            [['list'], 'unique', 'targetAttribute' => ['list', 'subscriberId', 'siteId', 'elementId']],
        	[['id', 'subscriberId', 'siteId', 'list'], 'required'],
        ];
    }

    public function getSubscriber(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'subscriberId']);
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'elementId']);
    }

}
