<?php
namespace presseddigital\listit\web\twig;

use presseddigital\listit\Listit;
use presseddigital\listit\models\Subscription;
use presseddigital\listit\db\SubscriptionQuery;

use yii\di\ServiceLocator;

class ListitVariable extends ServiceLocator
{
    // Properties
    // =========================================================================

    public $plugin;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        $this->plugin = Listit::$plugin;
    }

    // Subscriptions
    // =========================================================================

    public function subscriptions($criteria = null): SubscriptionQuery
    {
        $query = Subscription::find();
        if ($criteria)
        {
            Craft::configure($query, $criteria);
        }
        return $query;
    }

}
