<?php
namespace presseddigital\listit\controllers;

use presseddigital\listit\Listit;
use presseddigital\listit\models\Subscription;
use presseddigital\listit\services\Lists;

use Craft;
use craft\web\Controller;
use craft\helpers\AdminTable;
use craft\helpers\Json;
use craft\elements\User;
use yii\web\NotFoundHttpException;

class ListController extends Controller
{
    // Protected Properties
    // =========================================================================

    protected $allowAnonymous = [];

    // Public Methods
    // =========================================================================

    public function actionSubscribe()
    {
        $this->requireLogin();

        // Check subscriber permission
        $subscriber = $this->_getSubscriber();
        if($subscriber && $subscriber->id != Craft::$app->getUser()->getIdentity()->id)
        {
            $this->requirePermission('listit:editOtherUsersSubscriptions');
        }

        // Create subscription
        $subscription = new Subscription();
        $subscription->list = $this->_getList();
        $subscription->subscriberId = $this->_getSubscriber()->id ?? null;
        $subscription->siteId = $this->_getSite()->id ?? null;
        $subscription->elementId = $this->_getElement()->id ?? null;
        $subscription->metadata = Craft::$app->getRequest()->getBodyParam('metadata', []);

        // Save subscription
        if (!Listit::$plugin->getSubscriptions()->saveSubscription($subscription))
        {
            return $this->_handleSubscriptionFailure($subscription, Craft::t('listit', 'Subscription could not be saved'));
        }
        return $this->_handleSubscriptionSuccess($subscription);
    }

    public function actionUnsubscribe()
    {
        $this->requireLogin();

        // Get subscription
        $subscription = $this->_getSubscription();

        // Can delete
        if($subscription->subscriberId != Craft::$app->getUser()->getIdentity()->id)
        {
            $this->requirePermission('listit:deleteOtherUsersSubscriptions');
        }

        // Delete subscription
        if (!Listit::$plugin->getSubscriptions()->deleteSubscription($subscription))
        {
            return $this->_handleSubscriptionFailure($subscription, Craft::t('listit', 'Subscription could not be deleted'));
        }

        return $this->_handleSubscriptionSuccess();
    }

    public function actionSubscriptions()
    {
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $list = $request->getRequiredParam('list');
        $page = $request->getParam('page', 1);
        $limit = $request->getParam('per_page', 10);
        $offset = ($page - 1) * $limit;

        $subscriptions = Subscription::find()
            ->list($list)
            ->subscriberId($this->_getSubscriber()->id ?? null)
            ->elementId($this->_getElement()->id ?? null)
            ->siteId($this->_getSite()->id ?? null)
            ->offset($offset)
            ->limit($limit);

        $total = $subscriptions->count();

        $rows = [];
        foreach ($subscriptions->all() as $subscription)
        {
            $subscriber = Craft::$app->getView()->renderTemplate('_elements/element', [
                'element' => $subscription->subscriber,
            ]);

            $element = '';
            if ($subscription->element)
            {
                $element = Craft::$app->getView()->renderTemplate('_elements/element', [
                    'element' => $subscription->element,
                ]);
            }

            $rows[] = [
                'id' => $subscription->id,
                'title' => $subscription->subscriber->fullName,
                'url' => $subscription->subscriber->getCpEditUrl(),
                'status' => $subscription->subscriber->status,
                'date' => $subscription->dateUpdated->format('jS M Y'),
                'subscriber' => $subscriber,
                'element' => $element,
                'detail' => [
                    'handle' => '<span data-icon="info"></span>',
                    'content' => '<pre class="pane"><code>'.Json::encode($subscription->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).'</code></pre>'
                ]
            ];
        }

        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($page, $total, $limit),
            'data' => $rows,
        ]);
    }

    public function actionDeleteList()
    {
        $this->requireLogin();

        // Can delete
        $this->requirePermission('listit:deleteLists');
        $handle = Craft::$app->getRequest()->getRequiredBodyParam('id');

        // Delete list
        if (!Listit::$plugin->getLists()->deleteListByHandle($handle))
        {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('listit', 'List could not be deleted'),
                'errors' => [],
            ]);
        }

        return $this->asJson([
            'success' => true,
            'message' => Craft::t('listit', 'List deleted'),
        ]);
    }

    // Private Methods
    // =========================================================================

    private function _getSubscription()
    {
        $subscriptionId = Craft::$app->getRequest()->getBodyParam('subscriptionId') ?? Craft::$app->getRequest()->getBodyParam('id');
        if($subscriptionId)
        {
            $subscription = Listit::$plugin->getSubscriptions()->getSubscriptionById((int)$subscriptionId);
        }
        else
        {
            $subscription = Subscription::find()
                ->list($this->_getList())
                ->subscriberId($this->_getSubscriber()->id ?? null)
                ->elementId($this->_getElement()->id ?? null)
                ->siteId($this->_getSite()->id ?? null)
                ->one();
        }

        if(!$subscription)
        {
            throw new NotFoundHttpException(Listit::t('Subscription not found'));
        }

        return $subscription;
    }

    private function _getList()
    {
        return Craft::$app->getRequest()->getBodyParam('list', null);
    }

    private function _getSubscriber()
    {
        if($subscriberId = Craft::$app->getRequest()->getBodyParam('subscriberId'))
        {
            return Craft::$app->getUsers()->getUserById((int)$subscriberId);
        }
        return Craft::$app->getUser()->getIdentity();
    }

    private function _getSite()
    {
        if($siteId = Craft::$app->getRequest()->getBodyParam('siteId'))
        {
            return Craft::$app->getSites()->getSiteById((int)$siteId);
        }
        return Craft::$app->getSites()->getCurrentSite();
    }

    private function _getElement()
    {
        $request = Craft::$app->getRequest();
        $elementId = $request->getBodyParam('elementId');
        $siteId = $request->getBodyParam('siteId');

        if($elementId)
        {
            $element = Craft::$app->getElements()->getElementById((int)$elementId, $siteId);
            if(!$element)
            {
                throw new NotFoundHttpException(Listit::t('Element not found'));
            }
            return $element;
        }
        return null;
    }

    private function _handleSubscriptionSuccess($subscription = null, string $message = '')
    {
        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            return $this->asJson([
                'success' => true,
                'message' => $message,
                'subscription' => $subscription ? $subscription->toArray() : [],
            ]);
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'subscription' => $subscription,
        ]);

        Craft::$app->getSession()->setNotice($message ?? Listit::t('Subscription updated.'));

        return $this->redirectToPostedUrl();
    }

    private function _handleSubscriptionFailure($subscription = null, string $error = '')
    {
        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            return $this->asJson([
                'success' => false,
                'error' => $error,
                'errors' => $subscription ? $subscription->getErrors() : [],
            ]);
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'subscription' => $subscription,
        ]);

        Craft::$app->getSession()->setError($error ?? Listit::t('Unable to update subscription.'));

        return null;
    }

}
