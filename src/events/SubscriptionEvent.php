<?php
namespace presseddigital\listit\events;

use yii\base\Event;

class SubscriptionEvent extends Event
{
    public $subscription;
    public $isNew;
}
