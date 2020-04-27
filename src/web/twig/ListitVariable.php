<?php
namespace presseddigital\listit\web\twig;

use presseddigital\listit\Listit;
use presseddigital\listit\web\twig\ListitVariableTrait;
use presseddigital\listit\models\Subscription;
use presseddigital\listit\db\SubscriptionQuery;

use yii\di\ServiceLocator;

class ListitVariable extends ServiceLocator
{
    // Traits
    // =========================================================================

    use ListitVariableTrait;

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

}
