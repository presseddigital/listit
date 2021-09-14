<?php
namespace presseddigital\listit\web\twig;

use presseddigital\listit\models\Subscription;
use presseddigital\listit\db\SubscriptionQuery;

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
}
