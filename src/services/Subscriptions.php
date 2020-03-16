<?php
namespace presseddigital\listit\services;

use presseddigital\listit\Listit;
use presseddigital\listit\models\Subscription;
use presseddigital\listit\records\Subscription as SubscriptionRecord;
use presseddigital\listit\events\SubscriptionEvent;

use Craft;
use craft\base\Component;
use craft\db\Query;

use yii\db\StaleObjectException;

class Subscriptions extends Component
{
    // Constants
    // =========================================================================

    const EVENT_ADDED_TO_LIST = 'addedToList';
    const EVENT_REMOVED_FROM_LIST = 'removedFromList';

    // Public Methods
    // =========================================================================

    public function createSubscription($attributes = [])
    {
        // $subscription = new Subscription();
        // $subscription->setAttributes($attributes);
        return $this->_createSubscription($attributes);
    }

    public function getSubscription(array $criteria = null)
    {
        $subscriptionRecord = SubscriptionRecord::findOne($criteria);
        return $this->_createSubscription($subscriptionRecord);
    }

    public function getSubscriptions(array $criteria = [], array $select = null)
    {
        if($select)
        {
            return (new Query())
                ->select($select)
                ->from([SubscriptionRecord::tableName()])
                ->where($criteria)
                ->all();
        }
        else
        {
            $subscriptionRecords = SubscriptionRecord::find()
                ->where($criteria)
                ->all();

            $subscriptionModels = [];
            if($subscriptionRecords)
            {
                foreach ($subscriptionRecords as $subscriptionRecord)
                {
                    $subscriptionModels[] = $this->_createSubscription($subscriptionRecord);
                }
            }
            return $subscriptionModels;
        }
    }

    public function getSubscriptionsColumn(array $criteria = [], string $column)
    {
        return (new Query())
            ->select($column)
            ->from([SubscriptionRecord::tableName()])
            ->where($criteria)
            ->column();
    }

    public function saveSubscription(Subscription $subscription, $surpressEvents = false)
    {
        if (!$subscription->validate()) {
            Craft::info('Subscription not saved due to validation error.', __METHOD__);
            return false;
        }

        $subscriptionRecord = SubscriptionRecord::findOne([
            'subscriberId' => $subscription->subscriberId,
            'elementId' => $subscription->elementId,
            'list' => $subscription->list,
            'siteId' => $subscription->siteId
        ]);

        if($subscriptionRecord) {
            $subscription = $this->_createSubscription($subscriptionRecord);
            return true;
        }

        $subscriptionRecord = new SubscriptionRecord();
        $subscriptionRecord->setAttributes($subscription->getAttributes(), false);
        if(!$subscriptionRecord->save(false))
        {
            return false;
        }

        $subscriptionModel = $this->_createSubscription($subscriptionRecord);

        if (!$surpressEvents)
        {
            $this->trigger(self::EVENT_ADDED_TO_LIST, new SubscriptionEvent([
                'subscription' => $subscriptionModel
            ]));
        }

        return true;
    }

    public function deleteSubscription($subscriptionId, $surpressEvents = false)
    {
        $subscriptionRecord = SubscriptionRecord::findOne($subscriptionId);

        if($subscriptionRecord) {
            try {

                $subscriptionModel = $this->_createSubscription($subscriptionRecord);
                $subscriptionRecord->delete();

                if(!$surpressEvents)
                {
                     $this->trigger(self::EVENT_REMOVED_FROM_LIST, new SubscriptionEvent([
                        'subscription' => $subscriptionModel
                    ]));
                }

            } catch (StaleObjectException $e) {
                Craft::error($e->getMessage(), __METHOD__);
            } catch (\Exception $e) {
                Craft::error($e->getMessage(), __METHOD__);
            } catch (\Throwable $e) {
                Craft::error($e->getMessage(), __METHOD__);
            }
        }

        return true;
    }

    // Private Methods
    // =========================================================================

    private function _createSubscription($config = null)
    {
        if (!$config) {
            return null;
        }

        if($config instanceof Subscription)
        {
            $config = $subscriptionRecord->toArray([
                'id',
                'subscriberId',
                'elementId',
                'siteId',
                'list',
                'dateCreated'
            ]);
        }

        $subscription = new Subscription($config);
        return $subscription;
    }



}
