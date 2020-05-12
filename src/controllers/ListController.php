<?php
namespace presseddigital\listit\controllers;

use presseddigital\listit\Listit;
use presseddigital\listit\models\Subscription;

use presseddigital\listit\services\Lists;

use Craft;
use craft\web\Controller;
use craft\helpers\AdminTable;
use craft\elements\User;
use yii\web\NotFoundHttpException;

class ListController extends Controller
{
    // Protected Properties
    // =========================================================================

    protected $allowAnonymous = [];
    protected $list;

    // Public Methods
    // =========================================================================

    public function actionLists()
    {
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $page = $request->getParam('page', 1);
        $sort = $request->getParam('sort', null);
        $limit = $request->getParam('per_page', 10);
        $offset = ($page - 1) * $limit;

        $lists = Listit::$plugin->getLists()->getAllLists();

        $rows = [];
        foreach ($lists as $order) {
            $rows[] = [
                'id' => $order->id,
                'title' => $order->reference,
                'url' => $order->getCpEditUrl(),
                'date' => $order->dateOrdered->format('D jS M Y'),
                'total' => Craft::$app->getFormatter()->asCurrency($order->getTotalPaid(), $order->currency, [], [], false),
                'orderStatus' => $order->getOrderStatusHtml(),
            ];
        }

        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($page, $total, $limit),
            'data' => $rows,
        ]);
    }

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
            return $this->_failureResponse($subscription, Craft::t('listit', 'Subscription could not be saved'));
        }
        return $this->_successResponse($subscription);
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
            return $this->_failureResponse($subscription, Craft::t('listit', 'Subscription could not be deleted'));
        }

        return $this->_successResponse();
    }

    // Follow
    // =========================================================================

    public function actionFollow()
    {
        $this->list = Lists::FOLLOW_LIST_HANDLE;
        return $this->actionSubscribe();
    }

    public function actionUnFollow()
    {
        $this->list = Lists::FOLLOW_LIST_HANDLE;
        return $this->actionUnsubscribe();
    }

    // Favourite
    // =========================================================================

    public function actionFavourite()
    {
        $this->list = Lists::FAVOURITE_LIST_HANDLE;
        return $this->actionSubscribe();
    }

    public function actionUnFavourite()
    {
        $this->list = Lists::FAVOURITE_LIST_HANDLE;
        return $this->actionUnsubscribe();
    }

    // Favorite (US Spelling)
    // =========================================================================

    public function actionFavorite()
    {
        return $this->actionFavourite();
    }

    public function actionUnFavorite()
    {
        return $this->actionUnFavourite();
    }

    // Like
    // =========================================================================

    public function actionLike()
    {
        $this->list = Lists::LIKE_LIST_HANDLE;
        return $this->actionSubscribe();
    }

    public function actionUnLike()
    {
        $this->list = Lists::LIKE_LIST_HANDLE;
        return $this->actionUnsubscribe();
    }

    // Star
    // =========================================================================

    public function actionStar()
    {
        $this->list = Lists::STAR_LIST_HANDLE;
        return $this->actionSubscribe();
    }

    public function actionUnStar()
    {
        $this->list = Lists::STAR_LIST_HANDLE;
        return $this->actionUnsubscribe();
    }

    // Bookmark
    // =========================================================================

    public function actionBookmark()
    {
        $this->list = Lists::BOOKMARK_LIST_HANDLE;
        return $this->actionSubscribe();
    }

    public function actionUnBookmark()
    {
        $this->list = Lists::BOOKMARK_LIST_HANDLE;
        return $this->actionUnsubscribe();
    }

    // Private Methods
    // =========================================================================

    private function _getSubscription()
    {
        $subscriptionId = Craft::$app->getRequest()->getBodyParam('subscriptionId');
        if($subscriptionId)
        {
            $subscription = Listit::$plugin->getSubscriptions()->getSubscritionById((int)$subscriptionId);
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
        return $this->list ?? Craft::$app->getRequest()->getBodyParam('list', null);
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

    private function _successResponse($subscription = null, string $message = '')
    {
        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            return $this->asJson([
                'success' => true,
                'message' => $message,
                'subscription' => $subscription->toArray() ?? [],
            ]);
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'subscription' => $subscription,
        ]);

        Craft::$app->getSession()->setNotice($message ?? Listit::t('Subscription updated.'));

        return $this->redirectToPostedUrl();
    }

    private function _failureResponse($subscription = null, string $error = '')
    {
        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            return $this->asJson([
                'success' => false,
                'error' => $error,
                'errors' => $subscription->getErrors() ?? [],
            ]);
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'subscription' => $subscription,
        ]);

        Craft::$app->getSession()->setError($error ?? Listit::t('Unable to update subscription.'));

        return null;
    }

}
