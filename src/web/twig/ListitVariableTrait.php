<?php
namespace presseddigital\listit\web\twig;

use presseddigital\listit\models\Subscription;
use presseddigital\listit\db\SubscriptionQuery;
use presseddigital\listit\db\SubscriberQuery;
use presseddigital\listit\db\ElementQuery;

use Craft;

trait ListitVariableTrait
{
    // Subscriptions
    // =========================================================================

    public function subscriptions(array $criteria = null): SubscriptionQuery
    {
        $query = Subscription::find();
        if ($criteria)
        {
            Craft::configure($query, $criteria);
        }
        return $query;
    }

    // Subscribers
    // =========================================================================

    public function subscribers(array $criteria = null): SubscriberQuery
    {
        $query = Subscription::findSubscribers();
        if ($criteria)
        {
            Craft::configure($query, $criteria);
        }
        return $query;
    }

    // Elements
    // =========================================================================

    public function elements(array $criteria = null): ElementQuery
    {
        $query = Subscription::findElements();
        if ($criteria)
        {
            Craft::configure($query, $criteria);
        }
        return $query;
    }

}
