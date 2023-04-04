<?php

namespace presseddigital\listit\controllers;

use craft\web\Controller;

use presseddigital\listit\Listit;

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
