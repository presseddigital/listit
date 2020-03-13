<?php
namespace fruitstudios\listit\events;

use craft\base\ElementInterface;
use yii\base\Event;

class SubscriptionEvent extends Event
{
    public $subscription;
}
