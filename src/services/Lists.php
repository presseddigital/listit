<?php
namespace presseddigital\listit\services;

use presseddigital\listit\Listit;
use presseddigital\listit\models\Subscription;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\records\Element as ElementRecord;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\MatrixBlock;
use craft\models\Site;
use craft\helpers\ArrayHelper;
use craft\db\Query;

class Lists extends Component
{
    // Constants
    // =========================================================================

    const FOLLOW_LIST_HANDLE = 'follow';
    const FRIEND_LIST_HANDLE = 'friend';
    const STAR_LIST_HANDLE = 'star';
    const BOOKMARK_LIST_HANDLE = 'bookmark';
    const LIKE_LIST_HANDLE = 'like';
    const FAVOURITE_LIST_HANDLE = 'favourite';


    // Public Methods
    // =========================================================================

    public function isOnList(array $params)
    {
        $list = $this->_getList($params);
        $element = $this->_getElement($params);
        $owner = $this->_getOwner($params);
        $site = $this->_getSite($params);

        if(!$list || !$owner || !$element || !$site)
        {
            return false;
        }

        $criteria = [
            'list' => $list,
            'subscriberId' => $owner->id,
            'elementId' => $element->id,
        ];

        return Listit::$plugin->subscriptions->getSubscription($criteria);
    }

    public function getSubscriptions($paramsOrList)
    {
        $list = $this->_getList($paramsOrList);
        $owner = $this->_getOwner($paramsOrList);
        $site = $this->_getSite($paramsOrList);

        if(!$list || !$owner || !$site)
        {
            return [];
        }

        $criteria = [
            'subscriberId' => $owner->id,
            'siteId' => $site->id,
            'list' => $list
        ];

        return Listit::$plugin->subscriptions->getSubscriptions($criteria);
    }

    public function getOwnerIds($params)
    {
        $list = $this->_getList($params);
        $element = $this->_getElement($params);
        $site = $this->_getSite($params);

        if(!$list || !$element || !$site)
        {
            return [];
        }

        $criteria = [
            'elementId' => $element->id,
            'siteId' => $site->id,
            'list' => $list
        ];

        return Listit::$plugin->subscriptions->getSubscriptionsColumn($criteria, 'subscriberId');
    }

    public function getOwners($params)
    {
        $subscriberIds = $this->getOwnerIds($params);

        $query = $this->_getElementQuery(User::class, ($params['criteria'] ?? []));
        return $query
            ->id($subscriberIds)
            ->all();
    }

    public function getElementIds($params)
    {
        $list = $this->_getList($params);
        $owner = $this->_getOwner($params);
        $site = $this->_getSite($params);

        if(!$list || !$owner || !$site)
        {
            return [];
        }

        $criteria = [
            'subscriberId' => $owner->id,
            'list' => $list,
            'siteId' => $site->id,
        ];

        return Listit::$plugin->subscriptions->getSubscriptionsColumn($criteria, 'elementId');
    }

    public function getElements($params)
    {
        $elementIds = $this->getElementIds($params);
        if(!$elementIds)
        {
            return [];
        }

        // Get craft element rows
        $type = $params['type'] ?? false;
        if($type)
        {
            $elements = (new Query())
                ->select(['id', 'type'])
                ->from([ElementRecord::tableName()])
                ->where([
                    'id' => $elementIds,
                    'type' => $type
                ])
                ->all();

            return $this->_getElementQuery($type, $params['criteria'] ?? [])
                ->id($elementIds)
                ->all();
        }
        else
        {
            // TODO: Is this over kill, is it even needed???
            $elementsToReturn = $elementIds;

            $elements = (new Query())
                ->select(['id', 'type'])
                ->from([ElementRecord::tableName()])
                ->where([
                    'id' => $elementIds,
                ])
                ->all();

            $elementIdsByType = [];
            foreach ($elements as $element)
            {
                $elementIdsByType[$element['type']][] = $element['id'];
            }

            foreach ($elementIdsByType as $elementType => $ids)
            {
                $criteria = ['id' => $ids];
                $elements = $this->_getElementQuery($elementType, $criteria)->all();

                foreach ($elements as $element)
                {
                    $key = array_search($element->id, $elementIds);
                    $elementsToReturn[$key] = $element;
                }
            }

            return $elementsToReturn;
        }
    }

    public function getEntries($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => Entry::class
        ]);

        return $this->getElements($params);
    }

    public function getUsers($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => User::class
        ]);

        return $this->getElements($params);
    }

    public function getTags($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => Tag::class
        ]);

        return $this->getElements($params);
    }

    public function getCategories($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => Category::class
        ]);

        return $this->getElements($params);
    }

    public function getMatrixBlocks($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => MatrixBlock::class
        ]);

        return $this->getElements($params);
    }

    // Add / Remove
    // =========================================================================

    public function addToList($params, $surpressEvents = false)
    {
        $list = $this->_getList($params);
        if(!$list)
        {
            return false;
        }

        $element = $this->_getElement($params);
        $owner = $this->_getOwner($params);
        $site = $this->_getSite($params);

        // Create Subscription
        $subscription = Listit::$plugin->subscriptions->createSubscription([
            'list' => $list,
            'subscriberId' => $owner->id ?? null,
            'elementId' => $element->id ?? null,
            'siteId' => $site->id ?? null,
        ]);

        // Save Subscription
        return Listit::$plugin->subscriptions->saveSubscription($subscription, $surpressEvents);
    }

    public function removeFromList($params, $surpressEvents = false)
    {
        $list = $this->_getList($params);
        if(!$list)
        {
            return false;
        }

        $element = $this->_getElement($params);
        $owner = $this->_getOwner($params);
        $site = $this->_getSite($params);

        // Subscription
        $subscription = Listit::$plugin->subscriptions->getSubscription([
            'list' => $list,
            'subscriberId' => $owner->id ?? null,
            'elementId' => $element->id ?? null,
            'siteId' => $site->id ?? null,
        ]);

        if (!$subscription)
        {
            return true;
        }

        // Delete Subscription
        return Listit::$plugin->subscriptions->deleteSubscription($subscription->id, $surpressEvents);
    }


    // Favourite
    // =========================================================================

    public function favourite($paramsOrElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->addToList($params, $surpressEvents);
    }

    public function unFavourite($paramsOrElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->removeFromList($params, $surpressEvents);
    }

    public function isFavourited($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->isOnList($params);
    }

    public function getFavourites($paramsOrOwner)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->getSubscriptions($params);
    }

    public function getFavouritedElements($paramsOrOwner)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->getElements($params);
    }


    // Favorite (US Spelling)
    // =========================================================================

    public function favorite($paramsOrElement, $surpressEvents = false)
    {
        return $this->favourite($paramsOrElement);
    }

    public function unFavorite($paramsOrElement, $surpressEvents = false)
    {
        return $this->unFavourite($paramsOrElement);
    }

    public function isFavorited($paramsOrElement)
    {
        return $this->isFavourited($paramsOrElement);
    }

    public function getFavorites($paramsOrOwner)
    {
        return $this->getFavourites($paramsOrOwner);
    }

    public function getFavoritedElements($paramsOrOwner)
    {
        return $this->getFavouritedElements($paramsOrOwner);
    }


    // Like
    // =========================================================================

    public function like($paramsOrElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->addToList($params, $surpressEvents);
    }

    public function unLike($paramsOrElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->removeFromList($params, $surpressEvents);
    }

    public function isLiked($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->isOnList($params);
    }

    public function getLikes($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->getSubscriptions($params);
    }

    public function getLikedElements($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->getElements($params);
    }


    // Follow
    // =========================================================================

    public function follow($paramsOrUserElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrUserElement, 'element', [
            'list' => self::FOLLOW_LIST_HANDLE
        ]);
        return $this->addToList($params, $surpressEvents);
    }

    public function unFollow($paramsOrUserElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrUserElement, 'element', [
            'list' => self::FOLLOW_LIST_HANDLE
        ]);
        return $this->removeFromList($params, $surpressEvents);
    }

    public function isFollowing($paramsOrUserElement)
    {
        $params = $this->_convertToParamsArray($paramsOrUserElement, 'element', [
            'list' => self::FOLLOW_LIST_HANDLE
        ]);
        return $this->isOnList($params);
    }

    public function isFollower($paramsOrUserElement)
    {
        // Use the supplied element, which should be a user element or grab the current user to check against
        $params = $this->_convertToParamsArray($paramsOrUserElement, 'owner', [
            'list' => self::FOLLOW_LIST_HANDLE,
            'element' => $paramsOrUserElement['element'] ?? Craft::$app->getUser()->getIdentity(),
        ]);

        return $this->isOnList($params);
    }

    public function getFollowingIds($paramsOrUserElement = null)
    {
        $user = $this->_getUserOrCurrent($paramsOrUserElement['user'] ?? $paramsOrUserElement);
        if(!$user)
        {
            return [];
        }

        return $this->getElementIds([
            'list' => self::FOLLOW_LIST_HANDLE,
            'owner' => $user,
            'criteria' => $paramsOrUserElement['criteria'] ?? [],
        ]);
    }

    public function getFollowing($paramsOrUserElement = null)
    {
        $elementIds = $this->getFollowingIds($paramsOrUserElement);

        $query = $this->_getElementQuery(User::class, ($paramsOrUserElement['criteria'] ?? []));
        return $query
            ->id($elementIds)
            ->all();
    }

    public function getFollowerIds($paramsOrUserElement = null)
    {
        $user = $this->_getUserOrCurrent($paramsOrUserElement['user'] ?? $paramsOrUserElement);
        if(!$user)
        {
            return [];
        }

        return $this->getOwnerIds([
            'list' => self::FOLLOW_LIST_HANDLE,
            'element' => $user,
            'criteria' => $paramsOrUserElement['criteria'] ?? [],
        ]);
    }

    public function getFollowers($paramsOrUserElement = null)
    {
        $subscriberIds = $this->getFollowerIds($paramsOrUserElement);

        $query = $this->_getElementQuery(User::class, ($paramsOrUserElement['criteria'] ?? []));
        return $query
            ->id($subscriberIds)
            ->all();
    }

    // Friend
    // =========================================================================

    public function addFriend($paramsOrUserElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrUserElement, 'element', [
            'list' => self::FRIEND_LIST_HANDLE
        ]);
        return $this->addToList($params, $surpressEvents);
    }

    public function removeFriend($paramsOrUserElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrUserElement, 'element', [
            'list' => self::FRIEND_LIST_HANDLE
        ]);
        return $this->removeFromList($params, $surpressEvents);
    }

    public function isOutgoingFriendRequest($paramsOrUserElement)
    {
        $params = $this->_convertToParamsArray($paramsOrUserElement, 'element', [
            'list' => self::FRIEND_LIST_HANDLE
        ]);
        return $this->isOnList($params);
    }

    public function isIncomingFriendRequest($paramsOrUserElement)
    {
        // Use the supplied element, which should be a user element or grab the current user to check against
        $params = $this->_convertToParamsArray($paramsOrUserElement, 'owner', [
            'list' => self::FRIEND_LIST_HANDLE,
            'element' => $paramsOrUserElement['element'] ?? Craft::$app->getUser()->getIdentity(),
        ]);

        return $this->isOnList($params);
    }

    public function isFriend($paramsOrUserElement)
    {
        return $this->isOutgoingFriendRequest($paramsOrUserElement) && $this->isIncomingFriendRequest($paramsOrUserElement);
    }


    public function getOutgoingFriendRequestIds($paramsOrUserElement = null)
    {
        $user = $this->_getUserOrCurrent($paramsOrUserElement['user'] ?? $paramsOrUserElement);
        if(!$user)
        {
            return [];
        }

        return $this->getElementIds([
            'list' => self::FRIEND_LIST_HANDLE,
            'owner' => $user,
            'criteria' => $paramsOrUserElement['criteria'] ?? [],
        ]);
    }

    public function getOutgoingFriendRequests($paramsOrUserElement = null)
    {
        $elementIds = $this->getOutgoingFriendRequestIds($paramsOrUserElement);
        $friendIds = $this->getFriendIds($paramsOrUserElement);

        $query = $this->_getElementQuery(User::class, ($paramsOrUserElement['criteria'] ?? []));
        return $query
            ->id(array_diff($elementIds, $friendIds))
            ->all();
    }

    public function getIncomingFriendRequestIds($paramsOrUserElement = null)
    {
        $user = $this->_getUserOrCurrent($paramsOrUserElement['user'] ?? $paramsOrUserElement);
        if(!$user)
        {
            return [];
        }

        return $this->getOwnerIds([
            'list' => self::FRIEND_LIST_HANDLE,
            'element' => $user,
            'criteria' => $paramsOrUserElement['criteria'] ?? [],
        ]);
    }

    public function getIncomingFriendRequests($paramsOrUserElement = null)
    {
        $subscriberIds = $this->getIncomingFriendRequestIds($paramsOrUserElement);
        $friendIds = $this->getFriendIds($paramsOrUserElement);

        $query = $this->_getElementQuery(User::class, ($paramsOrUserElement['criteria'] ?? []));
        return $query
            ->id(array_diff($subscriberIds, $friendIds))
            ->all();
    }

    public function getFriendIds($paramsOrUserElement = null)
    {
        $incomingFriendRequestIds = $this->getIncomingFriendRequestIds($paramsOrUserElement);
        $outgoingFriendRequestIds = $this->getOutgoingFriendRequestIds($paramsOrUserElement);

        return array_intersect($incomingFriendRequestIds, $outgoingFriendRequestIds);
    }

    public function getFriends($paramsOrUserElement = null)
    {
        $friendIds = $this->getFriendIds($paramsOrUserElement);
        $query = $this->_getElementQuery(User::class, ($paramsOrUserElement['criteria'] ?? []));
        return $query
            ->id($friendIds)
            ->all();
    }

    // Star
    // =========================================================================

    public function star($paramsOrElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->addToList($params, $surpressEvents);
    }

    public function unStar($paramsOrElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->removeFromList($params, $surpressEvents);
    }

    public function isStared($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->isOnList($params);
    }

    public function getStars($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->getSubscriptions($params);
    }

    public function getStarredElements($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->getElements($params);
    }


    // Bookmark
    // =========================================================================

    public function bookmark($paramsOrElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->addToList($params, $surpressEvents);
    }

    public function unBookmark($paramsOrElement, $surpressEvents = false)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->removeFromList($params, $surpressEvents);
    }

    public function isBookmarked($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->isOnList($params);
    }

    public function getBookmarks($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->getSubscriptions($params);
    }

    public function getBookmarkedElements($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->getElements($params);
    }

    // Private Methods
    // =========================================================================

    private function _convertToParamsArray($value, string $key, array $extend = [])
    {
        $params = is_array($value) ? $value : [$key => $value];
        return array_merge($params, $extend);
    }

    private function _getList($paramsOrList = null)
    {
        return is_string($paramsOrList) ? $paramsOrList : ($paramsOrList['list'] ?? false);
    }

    private function _getOwner($paramsOrOwner = null)
    {

        $ownerOrOwnerId = false;
        if($paramsOrOwner)
        {
            $ownerOrOwnerId = is_array($paramsOrOwner) ? ($paramsOrOwner['owner'] ?? false) : $paramsOrOwner;
        }

        $owner = $ownerOrOwnerId ? $ownerOrOwnerId : Craft::$app->getUser()->getIdentity();
        if($owner instanceof User)
        {
            return $owner;
        }

        return $ownerOrOwnerId ? Craft::$app->getUsers()->getUserById((int) $ownerOrOwnerId) : false;
    }

    private function _getElement($paramsOrElement = null)
    {
        $elementOrElementId = false;
        if($paramsOrElement)
        {
            $elementOrElementId = is_array($paramsOrElement) ? ($paramsOrElement['element'] ?? false) : $paramsOrElement;
        }

        if($elementOrElementId instanceof Element)
        {
            return $elementOrElementId;
        }

        return $elementOrElementId ? Craft::$app->getElements()->getElementById((int) $elementOrElementId) : false;
    }

    private function _getSite($paramsOrSite = null)
    {
        $siteOrSiteId = false;
        if($paramsOrSite)
        {
            $siteOrSiteId = is_array($paramsOrSite) ? ($paramsOrSite['site'] ?? false) : $paramsOrSite;
        }

        $site = $siteOrSiteId ? $siteOrSiteId : Craft::$app->getSites()->getCurrentSite();
        if($site instanceof Site)
        {
            return $site;
        }

        return $siteOrSiteId ? Craft::$app->getSites()->getSiteById((int) $siteOrSiteId) : false;
    }

    private function _getUserOrCurrent($user = null)
    {
        $user = $user ? $user : Craft::$app->getUser()->getIdentity();
        if($user instanceof User)
        {
            return $user;
        }
        return $user ? Craft::$app->getUsers()->getUserById((int) $user) : false;
    }

    private function _getElementQuery($elementType, array $criteria): ElementQueryInterface
    {
        $query = $elementType::find();
        Craft::configure($query, $criteria);
        return $query;
    }
}
