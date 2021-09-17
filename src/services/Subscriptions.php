<?php
namespace presseddigital\listit\services;

use presseddigital\listit\models\Subscription;
use presseddigital\listit\records\Subscription as SubscriptionRecord;
use presseddigital\listit\events\SubscriptionEvent;

use Craft;
use craft\base\Component;

class Subscriptions extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SAVE_SUBSCRIPTION = 'beforeSaveSubscription';
    const EVENT_AFTER_SAVE_SUBSCRIPTION = 'afterSaveSubscription';
    const EVENT_AFTER_DELETE_SUBSCRIPTION = 'afterDeleteSubscription';

    // Public Methods
    // =========================================================================

    public function getSubscriptionById(int $id, $siteId = null, array $criteria = [])
    {
        $query = Subscription::find()
            ->id($id)
            ->site($siteId);

        Craft::configure($query, $criteria);
        return $query->one();
    }

    public function saveSubscription(Subscription $subscriptionModel, bool $runValidation = true, bool $surpressEvents = false)
    {
        $isNewSubscription = !$subscriptionModel->id;

        if ($subscriptionModel->id)
        {
            $subscriptionRecord = SubscriptionRecord::findOne($subscriptionModel->id);
            if (!$subscriptionRecord)
            {
                throw new InvalidArgumentException('No subscription exists with the ID “{id}”', ['id' => $subscriptionModel->id]);
            }
        }
        else
        {
            $subscriptionRecord = new SubscriptionRecord();
        }

        if (!$surpressEvents && $this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SUBSCRIPTION))
        {
            $event = new SubscriptionEvent([
                'subscription' => $subscriptionModel,
                'isNew' => $isNewSubscription
            ]);
            $this->trigger(self::EVENT_BEFORE_SAVE_SUBSCRIPTION, $event);
        }

        if ($runValidation && !$subscriptionModel->validate())
        {
            Craft::info('Subscription could not save due to validation error.', __METHOD__);
            return false;
        }

        $subscriptionRecord->subscriberId = $subscriptionModel->subscriberId;
        $subscriptionRecord->elementId = $subscriptionModel->elementId;
        $subscriptionRecord->list = $subscriptionModel->list;
        $subscriptionRecord->siteId = $subscriptionModel->siteId;
        $subscriptionRecord->metadata = $subscriptionModel->metadata;

        if (!$subscriptionRecord->save())
        {
            $subscriptionModel->addErrors($subscriptionRecord->getErrors());
            return false;
        }

        if ($isNewSubscription)
        {
            $subscriptionModel->id = $subscriptionRecord->id;
        }

        if (!$surpressEvents && $this->hasEventHandlers(self::EVENT_AFTER_SAVE_SUBSCRIPTION))
        {
            $event = new SubscriptionEvent([
                'subscription' => $subscriptionModel,
                'isNew' => $isNewSubscription
            ]);
            $this->trigger(self::EVENT_AFTER_SAVE_SUBSCRIPTION, $event);
        }

        return true;
    }

    public function deleteSubscription(Subscription $subscription, bool $surpressEvents = false): bool
    {
        $subscriptionRecord = SubscriptionRecord::findOne($subscription->id);
        if(!$subscriptionRecord)
        {
            return false;
        }

        $result = (bool)$subscriptionRecord->delete();

        if (!$surpressEvents && $this->hasEventHandlers(self::EVENT_AFTER_DELETE_SUBSCRIPTION))
        {
            $event = new SubscriptionEvent([
                'subscription' => $subscription,
                'isNew' => false
            ]);
            $this->trigger(self::EVENT_AFTER_DELETE_SUBSCRIPTION, $event);
        }

        return $result;
    }

    public function deleteSubscriptionById(int $id, bool $surpressEvents = false): bool
    {
        $subscription = $this->getSubscriptionById($id);
        if(!$subscription)
        {
            return false;
        }
        return $this->deleteSubscription($subscription, $surpressEvents);
    }

}
