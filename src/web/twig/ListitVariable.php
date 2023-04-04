<?php

namespace presseddigital\listit\web\twig;

use presseddigital\listit\Listit;

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
