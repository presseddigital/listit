<?php
namespace presseddigital\listit\controllers;

use presseddigital\listit\Listit;

use Craft;
use craft\web\Controller;

class SettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        return $this->renderTemplate('listit/settings', [
            'settings' => Listit::$settings,
        ]);
    }
}
