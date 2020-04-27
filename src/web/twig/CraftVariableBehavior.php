<?php
namespace presseddigital\listit\web\twig;

use presseddigital\listit\Listit;
use presseddigital\listit\web\twig\ListitVariableTrait;

use yii\base\Behavior;

class CraftVariableBehavior extends Behavior
{
    // Traits
    // =========================================================================

    use ListitVariableTrait;

    // Properties
    // =========================================================================

    public $listit;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        $this->listit = Listit::$plugin;
    }

}
