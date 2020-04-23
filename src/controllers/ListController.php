<?php
namespace presseddigital\listit\controllers;

use presseddigital\listit\Listit;
use presseddigital\listit\models\Subscription;

use presseddigital\listit\services\Lists;

use Craft;
use craft\web\Controller;
use craft\elements\User;

class ListController extends Controller
{
    // Protected Properties
    // =========================================================================

    protected $allowAnonymous = [];

    protected $list;

    // Public Methods
    // =========================================================================

    public function actionSubscribe()
    {
        $this->requireLogin();

        // Check subscriber permission
        $subscriber = $this->_getSubscriber();
        if($subscriber && $subscriber->id != Craft::$app->getUser()->getIdentity()->id)
        {
            $this->requireAdmin();
        }

        // Check element
        $element = $this->_getElement();
        if($element === false)
        {
            return $this->_failureResponse(false, [
                'error' => Craft::t('listit', 'Supplied element could not be found')
            ]);
        }

        // Create subscription
        $subscription = new Subscription();
        $subscription->list = $this->_getList();
        $subscription->subscriberId = $this->_getSubscriber()->id ?? null;
        $subscription->siteId = $this->_getSite()->id ?? null;
        $subscription->elementId = $element->id ?? null;
        $subscription->metadata = $request->getBodyParam('metadata', []);

        // Save subscription
        if (!Listit::$plugin->getSubscriptions()->saveSubscription($subscription))
        {
            return $this->_failureResponse($subscription);
        }
        return $this->_successResponse($subscription);
    }

    public function actionUnsubscribe()
    {
        $this->requireLogin();

        // Get subscription
        $subscription = false;
        if($id = Craft::$app->getRequest()->getBodyParam('id'))
        {
            $subscription = Listit::$plugin->getSubscriptions()->getSubscritionById((int)$id);
        }
        else
        {
            $element = $this->_getElement();
            if($element === false)
            {
                return $this->_failureResponse(false, [
                    'error' => Craft::t('listit', 'Supplied element could not be found')
                ]);
            }

            $subscription = Subscription::find()
                ->list($request->getBodyParam('list', null))
                ->elementId($this->_getElement()->id ?? null)
                ->siteId($this->_getSite()->id ?? null)
                ->one();
        }

        if (!$subscription)
        {
            return $this->_failureResponse(false, ['error' => Craft::t('listit', 'Subscription does not exist')]);
        }

        // Check permissions
        if($subscription->getSubscriber()->id != Craft::$app->getUser()->getIdentity()->id)
        {
            $this->requireAdmin();
        }

        // Delete subscription
        if (!Listit::$plugin->getSubscriptions()->deleteSubscription($subscription))
        {
            return $this->_failureResponse($subscription, ['error' => Craft::t('listit', 'Subscription could not be deleted')]);
        }

        return $this->_success();
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

    private function _getList()
    {
        return $this->list ?? Craft::$app->getRequest()->getBodyParam('list', null);
    }

    private function _getSubscriber()
    {
        if($subscriberId = Craft::$app->getRequest()->getBodyParam('subscriberId', false))
        {
            return Craft::$app->getUsers()->getUserById((int)$subscriberId);
        }
        return Craft::$app->getUser()->getIdentity();
    }

    private function _getSite()
    {
        if($siteId = Craft::$app->getRequest()->getBodyParam('siteId', false))
        {
            return Craft::$app->getSites()->getSiteById((int)$siteId);
        }
        return Craft::$app->getSites()->getCurrentSite();
    }

    private function _getElement()
    {
        if($elementId = Craft::$app->getRequest()->getBodyParam('elementId', false))
        {
            return Craft::$app->getElements()->getElementById((int)$elementId);
        }
        return null;
    }

    private function _success($subscription = null, array $result = [])
    {
        $result['success'] = true;

        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            if($subscription instanceof Subscription)
            {
                $result['subscription'] = $subscription->toArray();
            }
            return $this->asJson($result);
        }

        $result['subscription'] = $subscription;
        Craft::$app->getUrlManager()->setRouteParams([
            'listit' => $result
        ]);

        return $this->redirectToPostedUrl();
    }

    private function _failureResponse($subscription = null, array $result = [])
    {
        $result['success'] = false;

        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            if($subscription instanceof Subscription)
            {
                $result['errors'] = $subscription->getErrors();
            }
            return $this->asJson($result);
        }

        $result['subscription'] = $subscription;
        Craft::$app->getUrlManager()->setRouteParams([
            'listit' => $result
        ]);

        return null;
    }

}
