<?php

namespace presseddigital\listit\web\twig;

use Craft;
use presseddigital\listit\db\SubscriptionQuery;

use presseddigital\listit\models\Subscription;

trait ListitVariableTrait
{
    // Subscriptions
    // =========================================================================

    public function subscriptions(array $criteria = null): SubscriptionQuery
    {
        $query = Subscription::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }
        return $query;
    }
}
