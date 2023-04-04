<?php

namespace presseddigital\listit\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\User;
use craft\validators\HandleValidator;
use presseddigital\listit\db\Table;
use presseddigital\listit\Listit;
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
            [['list'], 'unique', 'targetAttribute' => ['list', 'subscriberId', 'siteId', 'elementId'], 'message' => Listit::t('Subscription already exists')],
            [['subscriberId', 'siteId', 'list'], 'required'],
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
