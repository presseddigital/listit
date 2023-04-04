<?php

namespace presseddigital\listit\plugin;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

trait RoutesTrait
{
    // Private Methods
    // =========================================================================

    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['listit'] = ['template' => 'listit/index'];
            $event->rules['listit/settings'] = 'listit/settings';
            $event->rules['listit/lists'] = ['template' => 'listit/lists/index'];
            $event->rules['listit/lists/<handle:{handle}>'] = ['template' => 'listit/lists/list'];
        });
    }
}
