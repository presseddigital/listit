<?php

namespace presseddigital\listit\plugin;

trait ServicesTrait
{
    // Public Methods
    // =========================================================================

    public function getSubscriptions()
    {
        return $this->get('subscriptions');
    }

    public function getLists()
    {
        return $this->get('lists');
    }

    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $this->setComponents([
            'subscriptions' => [
                'class' => \presseddigital\listit\services\Subscriptions::class,
            ],
            'lists' => [
                'class' => \presseddigital\listit\services\Lists::class,
            ],
        ]);
    }
}
