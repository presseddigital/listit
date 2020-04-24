<?php
namespace presseddigital\listit;

use presseddigital\listit\plugin\PluginTrait;
use presseddigital\listit\web\twig\CraftVariableBehavior;
use presseddigital\listit\web\twig\Extension;

use Craft;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

class Listit extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;
    public static $settings;
    public static $view;
    public static $variable;

    // Static Methods
    // =========================================================================

    public static function t(string $message, array $params = [], string $language = null, string $file = 'listit')
    {
        return Craft::t($file, $message, $params, $language);
    }

    public static function info(string $message, $category = 'plugin\listit')
    {
        return Craft::info($message, $category);
    }

    public static function error(string $message, $category = 'plugin\listit')
    {
        return Craft::error($message, $category);
    }

    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.0';

    // Traits
    // =========================================================================

    use PluginTrait;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;
        self::$view = Craft::$app->getView();

        $this->_setPluginComponents();

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->attachBehavior('listit', CraftVariableBehavior::class);
        });

        self::$view->registerTwigExtension(new Extension());

        Craft::info(Craft::t('listit', '{name} plugin loaded', ['name' => $this->name] ), __METHOD__);
    }

    // Private Methods
    // =========================================================================

}
