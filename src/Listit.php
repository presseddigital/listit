<?php

namespace presseddigital\listit;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;

use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use presseddigital\listit\models\Settings;
use presseddigital\listit\plugin\RoutesTrait;
use presseddigital\listit\plugin\ServicesTrait;
use presseddigital\listit\web\twig\CraftVariableBehavior;
use presseddigital\listit\web\twig\Extension;
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

    use RoutesTrait;
    use ServicesTrait;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;
        self::$settings = $this->getSettings();
        self::$view = Craft::$app->getView();

        $this->name = self::$settings->pluginNameOverride;
        $this->hasCpSection = self::$settings->hasCpSectionOverride;

        $this->_setPluginComponents();
        $this->_registerCpRoutes();

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->attachBehavior('listit', CraftVariableBehavior::class);
        });

        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions['Listit'] = [
                'listit:editOtherUsersSubscriptions' => [
                    'label' => self::t('Edit other users subcriptions'),
                ],
                'listit:deleteOtherUsersSubscriptions' => [
                    'label' => self::t('Delete other users subcriptions'),
                ],
                'listit:deleteLists' => [
                    'label' => self::t('Delete lists'),
                ],
            ];
        });

        self::$view->registerTwigExtension(new Extension());

        self::info(self::t('{name} plugin loaded', ['name' => $this->name]), __METHOD__);
    }

    public function afterInstallPlugin(PluginEvent $event)
    {
        if ($event->plugin === self::$plugin && Craft::$app->getRequest()->isCpRequest) {
            Craft::$app->controller->redirect(UrlHelper::cpUrl('listit/about'))->send();
        }
    }

    public function getSettingsResponse()
    {
        return Craft::$app->controller->redirect(UrlHelper::cpUrl('listit/settings'));
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel()
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================
}
