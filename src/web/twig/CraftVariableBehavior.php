<?php
namespace presseddigital\listit\web\twig;

use presseddigital\listit\Listit;
use presseddigital\listit\models\Subscription;
use presseddigital\listit\db\SubscriptionQuery;

use Craft;
use yii\base\Behavior;

class CraftVariableBehavior extends Behavior
{



    // Set up as {{ listit.subscriptions() }} {{ listit.lists() }} {{ listit.plugin.subscriptions.getSubscriptionById() }}





    public $listit;

    public function init()
    {
        parent::init();

        $this->listit = Listit::$plugin;
    }

    public function subscriptions($criteria = null): SubscriptionQuery
    {
        $query = Subscription::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }
        return $query;
    }
}
