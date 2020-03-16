<?php
namespace presseddigital\listit;

use presseddigital\listit\services\Lists;
use presseddigital\listit\services\Subscriptions;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

class Listit extends Plugin
{

    // Constants
    // =========================================================================

    const FOLLOW_LIST_HANDLE = 'follow';
    const STAR_LIST_HANDLE = 'star';
    const BOOKMARK_LIST_HANDLE = 'bookmark';
    const LIKE_LIST_HANDLE = 'like';
    const FAVOURITE_LIST_HANDLE = 'favourite';


    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.1';


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->setComponents([
            'subscriptions' => Subscriptions::class,
            'lists' => Lists::class,
        ]);

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $variable = $event->sender;
                $variable->set('listit', Lists::class);
            }
        );


        Craft::info(
            Craft::t(
                'listit',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

}
