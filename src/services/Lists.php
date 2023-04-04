<?php

namespace presseddigital\listit\services;

use Craft;
use craft\base\Component;
use presseddigital\listit\models\ListModel;

use presseddigital\listit\models\Subscription;
use presseddigital\listit\records\Subscription as SubscriptionRecord;

class Lists extends Component
{
    // Private
    // =========================================================================

    private $_gotAllLists = false;
    private $_listsByHandle = [];

    // Public Methods
    // =========================================================================

    public function getAllLists(int $siteId = null)
    {
        if ($this->_gotAllLists && $this->_listsByHandle !== null) {
            return $this->_listsByHandle;
        }

        $subscriptions = Subscription::find()
            ->groupBy('list')
            ->orderBy('list ASC')
            ->siteId($siteId ?? Craft::$app->getSites()->getCurrentSite()->id)
            ->all();

        $lists = [];
        foreach ($subscriptions as $subscription) {
            $lists[$subscription->list] = $this->_createListFromSubscription($subscription);
        }
        $this->_gotAllLists = true;
        return $this->_listsByHandle = $lists;
    }

    public function getListByHandle(string $handle, int $siteId = null)
    {
        if (isset($this->_listsByHandle[$handle]) || $this->_gotAllLists) {
            return $this->_listsByHandle[$handle] ?? false;
        }

        $subscription = Subscription::find()
            ->list($handle)
            ->siteId($siteId ?? Craft::$app->getSites()->getCurrentSite()->id)
            ->one();

        return $this->_listsByHandle[$handle] = ($subscription ? $this->_createListFromSubscription($subscription) : false);
    }

    public function deleteListByHandle(string $handle, int $siteId = null)
    {
        return (bool)SubscriptionRecord::deleteAll([
            'list' => $handle,
            'siteId' => $siteId ?? Craft::$app->getSites()->getCurrentSite()->id,
        ]);
    }

    // Private Methods
    // =========================================================================

    private function _createListFromSubscription(Subscription $subscription)
    {
        return new ListModel([
            'handle' => $subscription->list,
            'elementType' => $subscription->getElementType() ?? false,
        ]);
    }
}
