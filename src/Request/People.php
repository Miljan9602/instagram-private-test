<?php

namespace InstagramAPI\Request;

use InstagramAPI\Constants;
use InstagramAPI\Exception\RequestHeadersTooLargeException;
use InstagramAPI\Exception\ThrottledException;
use InstagramAPI\Response;
use InstagramAPI\Signatures;
use InstagramAPI\Utils;

/**
 * Functions related to finding, exploring and managing relations with people.
 */
class People extends RequestCollection
{
    /**
     * Get details about a specific user via their numerical UserPK ID.
     *
     * NOTE: The real app uses this particular endpoint for _all_ user lookups
     * except "@mentions" (where it uses `getInfoByName()` instead).
     *
     * @param string      $userId     Numerical UserPK ID.
     * @param string|null $module     From which app module (page) you have opened the profile. One of (incomplete):
     *                                "comment_likers",
     *                                "comment_owner",
     *                                "followers",
     *                                "following",
     *                                "likers_likers_media_view_profile",
     *                                "likers_likers_photo_view_profile",
     *                                "likers_likers_video_view_profile",
     *                                "newsfeed",
     *                                "self_followers",
     *                                "self_following",
     *                                "self_likers_self_likers_media_view_profile",
     *                                "self_likers_self_likers_photo_view_profile",
     *                                "self_likers_self_likers_video_view_profile".
     * @param string|null $entrypoint Entrypoint.
     * @param bool        $isPrefetch Is prefetch.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function getInfoById(
        $userId,
        $module = null,
        $entrypoint = null,
        $isPrefetch = false
    ) {
        $request = $this->ig->request("users/{$userId}/info/");

        if ($entrypoint !== null) {
            $request->addParam('entry_point', $entrypoint);
        }

        if ($module !== null) {
            $request->addParam('from_module', $module);
        }
        if ($isPrefetch === true) {
            $request->addParam('is_prefetch', 'true');
        }

        return $request->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Get details about a specific user via their numerical UserPK ID Stream.
     *
     * NOTE: The real app uses this particular endpoint for _all_ user lookups
     * except "@mentions" (where it uses `getInfoByName()` instead).
     *
     * @param string      $userId     Numerical UserPK ID.
     * @param string|null $module     From which app module (page) you have opened the profile. One of (incomplete):
     *                                "comment_likers",
     *                                "comment_owner",
     *                                "followers",
     *                                "following",
     *                                "likers_likers_media_view_profile",
     *                                "likers_likers_photo_view_profile",
     *                                "likers_likers_video_view_profile",
     *                                "newsfeed",
     *                                "self_followers",
     *                                "self_following",
     *                                "self_likers_self_likers_media_view_profile",
     *                                "self_likers_self_likers_photo_view_profile",
     *                                "self_likers_self_likers_video_view_profile",
     *                                "reel_search_item_header".
     * @param string|null $entrypoint Entrypoint.
     * @param bool        $isPrefetch Is prefetch.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function getInfoByIdStream(
        $userId,
        $module = 'search_typeahead',
        $entrypoint = 'profile',
        $isPrefetch = false
    ) {
        $request = $this->ig->request("users/{$userId}/info_stream/")
            ->setSignedPost(false)
            ->addPost('is_prefetch', $isPrefetch === true ? 'true' : 'false');

        if ($entrypoint !== null) {
            $request->addPost('entry_point', $entrypoint);
        }

        if ($module !== null) {
            $request->addPost('from_module', $module);
        }

        $request->addPost('_uuid', $this->ig->uuid)
                ->addPost('is_app_start', 'true');

        return $request->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Get details about a specific user via their username.
     *
     * NOTE: The real app only uses this endpoint for profiles opened via "@mentions".
     *
     * @param string $username Username as string (NOT as a numerical ID).
     * @param string $module   From which app module (page) you have opened the profile.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     *
     * @see People::getInfoById() For the list of supported modules.
     */
    public function getInfoByName(
        $username,
        $module = 'feed_timeline'
    ) {
        return $this->ig->request("users/{$username}/usernameinfo/")
            ->addParam('from_module', $module)
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Get details about a specific user via their username.
     *
     * NOTE: The real app only uses this endpoint for profiles opened via URL.
     *
     * @param string $username Username as string (NOT as a numerical ID).
     * @param string $module   From which app module (page) you have opened the profile.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function getInfoByNameStream(
        $username,
        $module = 'deep_link_util'
    ) {
        return $this->ig->request("users/{$username}/usernameinfo_stream/")
            ->setSignedPost(false)
            ->addPost('is_prefetch', 'false')
            ->addPost('entry_point', 'profile')
            ->addPost('from_module', $module)
            ->addPost('_uuid', $this->ig->uuid)
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Get the numerical UserPK ID for a specific user via their username.
     *
     * This is just a convenient helper function. You may prefer to use
     * People::getInfoByName() instead, which lets you see more details.
     *
     * @param string $username Username as string (NOT as a numerical ID).
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return string Their numerical UserPK ID.
     *
     * @see People::getInfoByName()
     */
    public function getUserIdForName(
        $username
    ) {
        return $this->getInfoByName($username)->getUser()->getPk();
    }

    /**
     * Get user details about your own account.
     *
     * Also try Account::getCurrentUser() instead, for account details.
     *
     * @param string|null $module     From which app module (page) you have opened the profile.
     * @param string|null $entrypoint Entrypoint.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     *
     * @see Account::getCurrentUser()
     */
    public function getSelfInfo(
        $module = 'self_profile',
        $entrypoint = null
    ) {
        $response = $this->getInfoById($this->ig->account_id, $module, $entrypoint);
        if ($response->getUser()->getFbidV2() !== null) {
            $this->ig->settings->set('fbid_v2', $response->getUser()->getFbidV2());
        }
        if ($response->getUser()->getPhoneNumber() !== null) {
            $this->ig->settings->set('phone_number', $response->getUser()->getPhoneNumber());
        }

        return $response;
    }

    /**
     * Get user info graphql query.
     *
     * @param string $userId     Numerical UserPK ID.
     * @param string $fromModule From which app module (page) you have opened the profile.
     * @param string $entrypoint Target module.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function getUserInfoQuery(
        $userId,
        $fromModule = 'search_typeahead',
        $entrypoint = 'profile'
    ) {
        $data = [
            'user_id'       => $userId,
            'use_defer'     => false,
            'from_module'   => $fromModule,
            'entry_point'   => $entrypoint,
        ];

        $response = $this->ig->internal->sendGraph('2019601596199014297027407026', $data, 'ProfileUserInfo', 'xdt_users__info', 'false', 'pando', true);
        $arr = $response->asArray();
        if (isset($arr['data'])) {
            $data = $arr['data'];
            foreach ($data as $k => $v) {
                if ($k === '1$xdt_users__info(entry_point:$entry_point,from_module:$from_module,user_id:$user_id)') {
                    return new Response\UserInfoResponse($data[$k]);
                }
            }
        }

        return $response;
    }

    /**
     * Get about this account info.
     *
     * This is only valid for verified accounts.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AboutThisAccountResponse
     */
    public function getAboutThisAccountInfo(
        $userId
    ) {
        return $this->ig->request('bloks/apps/com.instagram.interactions.about_this_account/')
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('target_user_id', $userId)
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\AboutThisAccountResponse());
    }

    /**
     * Get other people's recent activities related to you and your posts.
     *
     * This feed has information about when people interact with you, such as
     * liking your posts, commenting on your posts, tagging you in photos or in
     * comments, people who started following you, etc.
     *
     * @param bool        $prefetch   Indicates if request is called due to prefetch.
     * @param bool        $markAsSeen
     * @param string      $feedType   Feed type.
     * @param string|null $maxId      Next "maximum ID", used for pagination.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\ActivityNewsResponse
     */
    public function getRecentActivityInbox(
        $prefetch = false,
        $markAsSeen = false,
        $feedType = 'all',
        $maxId = null
    ) {
        $request = $this->ig->request('news/inbox/')
                            ->addParam('could_truncate_feed', 'true')
                            ->addParam('mark_as_seen', $markAsSeen)
                            // ->addParam('feed_type', $feedType)
                            ->addParam('timezone_offset', ($this->ig->getTimezoneOffset() !== null) ? $this->ig->getTimezoneOffset() : date('Z'))
                            ->addParam('timezone_name', ($this->ig->getTimezoneName() !== null) ? $this->ig->getTimezoneName() : date_default_timezone_get());

        if ($maxId !== null) {
            $request->addParam('max_id', $maxId);
        }
        if ($prefetch) {
            $request->addHeader('X-IG-Prefetch-Request', 'foreground')
                    ->addHeader('X-Ig-304-Eligible', 'true')
                    ->addParam('could_truncate_feed', 'true');
        }

        return $request->getResponse(new Response\ActivityNewsResponse());
    }

    /**
     * Get news inbox seen.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\ActivityNewsResponse
     */
    public function getNewsInboxSeen()
    {
        return $this->ig->request('news/inbox_seen/')
                        ->setSignedPost(false)
                        // ->addUnsignedPost('_csrftoken', $this->ig->client->getToken())
                        ->addUnsignedPost('_uuid', $this->ig->uuid)
                        ->getResponse(new Response\ActivityNewsResponse());
    }

    /**
     * Send action news log.
     *
     * @param $newsPk The news PK. Example: "t+g+XAtK5RaGUdeQeL/V5roIgEM="
     * @param $tuuid  The news UUID.
     * @param $action Action to perform: "hide" or "click".
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function sendNewsLog(
        $newsPk,
        $tuuid,
        $action = 'click'
    ) {
        if (!in_array($action, ['click', 'hide'], true)) {
            throw new \InvalidArgumentException('Invalid action value.');
        }

        return $this->ig->request('news/log/')
            ->setSignedPost(false)
            ->addPost('action', $action)
            ->addPost('pk', $newsPk)
            ->addPost('tuuid', $tuuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get news feed with recent activities by accounts you follow.
     *
     * This feed has information about the people you follow, such as what posts
     * they've liked or that they've started following other people.
     *
     * @param string|null $maxId Next "maximum ID", used for pagination.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FollowingRecentActivityResponse
     */
    public function getFollowingRecentActivity(
        $maxId = null
    ) {
        $activity = $this->ig->request('news/');
        if ($maxId !== null) {
            $activity->addParam('max_id', $maxId);
        }

        return $activity->checkDeprecatedVersion('114.0.0.13.120')
            ->getResponse(new Response\FollowingRecentActivityResponse());
    }

    /**
     * Retrieve bootstrap user data (autocompletion user list).
     *
     * WARNING: This is a special, very heavily throttled API endpoint.
     * Instagram REQUIRES that you wait several minutes between calls to it.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\BootstrapUsersResponse|null Will be NULL if throttled by Instagram.
     */
    public function getBootstrapUsers()
    {
        $surfaces = [
            'autocomplete_user_list',
            'coefficient_besties_list_ranking',
            'coefficient_rank_recipient_user_suggestion',
            'coefficient_ios_section_test_bootstrap_ranking',
            'coefficient_direct_recipients_ranking_variant_2',
        ];

        try {
            $request = $this->ig->request('scores/bootstrap/users/')
                ->addParam('surfaces', json_encode($surfaces));

            return $request->getResponse(new Response\BootstrapUsersResponse());
        } catch (ThrottledException $e) {
            // Throttling is so common that we'll simply return NULL in that case.
            return null;
        }
    }

    /**
     * Show a user's friendship status with you.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipsShowResponse
     */
    public function getFriendship(
        $userId
    ) {
        return $this->ig->request("friendships/show/{$userId}/")->getResponse(new Response\FriendshipsShowResponse());
    }

    /**
     * Show multiple users' friendship status with you.
     *
     * @param string|string[] $userList List of numerical UserPK IDs.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipsShowManyResponse
     */
    public function getFriendships(
        $userList
    ) {
        if (is_array($userList)) {
            $userList = implode(',', $userList);
        }

        return $this->ig->request('friendships/show_many/')
            ->setSignedPost(false)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('user_ids', $userList)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\FriendshipsShowManyResponse());
    }

    /**
     * Show the least interacted with from the following tab from users profile.
     *
     * @param string $rankToken The list UUID. You must use the same value for all pages of the list.
     * @param string $query     Query for filtering users.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\LeastInteractedWithResponse
     */
    public function getLeastInteractedWith(
        $rankToken,
        $query = ''
    ) {
        return $this->ig->request('friendships/smart_groups/least_interacted_with/')
            ->addParam('search_surface', 'follow_list_page')
            ->addParam('query', $query)
            ->addParam('enable_groups', true)
            ->addParam('rank_token', $rankToken)
            ->getResponse(new Response\LeastInteractedWithResponse());
    }

    /**
     * Unfollow chaining count.
     *
     * This function is only called right after following a user.
     * It will return the number of similar accounts you follow.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UnfollowChainingCountResponse
     */
    public function getUnfollowChainingCount(
        $userId
    ) {
        return $this->ig->request("friendships/unfollow_chaining_count/{$userId}/")
                        ->getResponse(new Response\UnfollowChainingCountResponse());
    }

    /**
     * Unfollow chaining.
     *
     * This function can be only called (optionally) right after unfollowing a user.
     * It will return similar accounts of the user it was just unfollowed.
     *
     * @param string $userId    Numerical UserPK ID.
     * @param string $rankToken The list UUID. You must use the same value for all pages of the list.
     * @param string $query     Query for filtering users.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UnfollowChainingResponse
     */
    public function getUnfollowChaining(
        $userId,
        $rankToken,
        $query = ''
    ) {
        return $this->ig->request("friendships/unfollow_chaining/{$userId}/")
                        ->addParam('search_surface', 'follow_list_page')
                        ->addParam('query', $query)
                        ->addParam('enable_groups', 'true')
                        ->addParam('rank_token', $rankToken)
                        ->getResponse(new Response\UnfollowChainingResponse());
    }

    /**
     * Get list of pending friendship requests.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FollowerAndFollowingResponse
     */
    public function getPendingFriendships()
    {
        $data = [
            'is_pando'                              => true,
            '_request_data'                         => [
                'forced_user_id'                    => null,
                'include_follow_requests_summary'   => false,
                'response_without_su'               => 'false',
            ],
        ];

        $response = $this->ig->internal->sendGraph('33138429310749575540181764401', $data, 'PendingFollows', 'xdt_api__v1__friendships__pending', 'false', 'pando', true);
        $arr = $response->asArray();
        if (isset($arr['data'])) {
            $data = $arr['data'];
            foreach ($data as $k => $v) {
                if ($k === '1$xdt_api__v1__friendships__pending(_request_data:$_request_data)') {
                    return new Response\FollowerAndFollowingResponse($data[$k]);
                }
            }
        }

        return $response;
    }

    /**
     * Approve a friendship request.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function approveFriendship(
        $userId
    ) {
        return $this->ig->request("friendships/approve/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->addPost('radio_type', $this->ig->radio_type)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Reject a friendship request.
     *
     * Note that the user can simply send you a new request again, after your
     * rejection. If they're harassing you, use People::block() instead.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function rejectFriendship(
        $userId
    ) {
        return $this->ig->request("friendships/ignore/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->addPost('radio_type', $this->ig->radio_type)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Reject all friendship request.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function rejectAllFriendshipRequests()
    {
        return $this->ig->request('friendships/remove_all/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('radio_type', $this->ig->radio_type)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Remove one of your followers.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function removeFollower(
        $userId
    ) {
        return $this->ig->request("friendships/remove_follower/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->addPost('radio_type', $this->ig->radio_type)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Mark user over age in order to see sensitive content.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function markUserOverage(
        $userId
    ) {
        return $this->ig->request("friendships/mark_user_overage/{$userId}/feed/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Get list of who a user is following.
     *
     * @param string      $userId      Numerical UserPK ID.
     * @param string      $rankToken   The list UUID. You must use the same value for all pages of the list.
     * @param string|null $searchQuery Limit the userlist to ones matching the query.
     * @param string|null $maxId       Next "maximum ID", used for pagination.
     * @param string|null $order       Search order. Latest followings: 'date_followed_latest',
     *                                 earliest followings: 'date_followed_earliest'.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FollowerAndFollowingResponse
     *
     * @see Signatures::generateUUID() To create a UUID.
     * @see examples/rankTokenUsage.php For an example.
     */
    public function getFollowing(
        $userId,
        $rankToken,
        $searchQuery = null,
        $maxId = null,
        $order = null
    ) {
        Utils::throwIfInvalidRankToken($rankToken);
        $request = $this->ig->request("friendships/{$userId}/following/")
            ->addParam('includes_hashtags', true)
            ->addParam('rank_token', $rankToken)
            ->addParam('rank_mutual', 0)
            ->addParam('target_id', $userId);
        if ($order !== null) {
            if ($order !== 'date_followed_earliest' && $order !== 'date_followed_latest') {
                throw new \InvalidArgumentException('Invalid order type.');
            }
            $request->addParam('order', $order);
        }
        if ($searchQuery !== null) {
            $request->addParam('query', $searchQuery);
        }
        if ($maxId !== null) {
            $request->addParam('max_id', $maxId);
        }

        return $request->getResponse(new Response\FollowerAndFollowingResponse());
    }

    /**
     * Get list of who a user is followed by.
     *
     * @param string      $userId      Numerical UserPK ID.
     * @param string      $rankToken   The list UUID. You must use the same value for all pages of the list.
     * @param string      $searchQuery Limit the userlist to ones matching the query.
     * @param string|null $maxId       Next "maximum ID", used for pagination.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FollowerAndFollowingResponse
     *
     * @see Signatures::generateUUID() To create a UUID.
     * @see examples/rankTokenUsage.php For an example.
     */
    public function getFollowers(
        $userId,
        $rankToken,
        $searchQuery = '',
        $maxId = null
    ) {
        Utils::throwIfInvalidRankToken($rankToken);
        $request = $this->ig->request("friendships/{$userId}/followers/")
            ->addParam('search_surface', 'follow_list_page')
            ->addParam('rank_token', $rankToken)
            ->addParam('enable_groups', 'true')
            ->addParam('query', $searchQuery);

        if ($maxId !== null) {
            $request->addParam('max_id', $maxId);
        }

        return $request->getResponse(new Response\FollowerAndFollowingResponse());
    }

    /**
     * Get followers by graphql query.
     *
     * @param string      $userId      Numerical UserPK ID.
     * @param string      $rankToken   The list UUID. You must use the same value for all pages of the list.
     * @param string      $searchQuery Limit the userlist to ones matching the query.
     * @param string|null $maxId       Next "maximum ID", used for pagination.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getFollowersQuery(
        $userId,
        $rankToken = '',
        $searchQuery = '',
        $maxId = null
    ) {
        $data = [
            'user_id'                   => $userId,
            'exclude_unused_fields'     => false,
            'request_data'              => [
                'enableGroups'          => true,
                'rank_token'            => $rankToken,
            ],
            'query'                     => $searchQuery,
            'search_surface'            => 'follow_list_page',
            'include_biography'         => false,
            'include_unseen_count'      => false,
        ];

        if ($maxId !== null) {
            $data['max_id'] = $maxId;
        }

        $response = $this->ig->internal->sendGraph('28479704797148835226143011613', $data, 'FollowersList', 'xdt_api__v1__friendships__followers', 'false', 'pando', true, true);
        $arr = $response->asArray();
        if (isset($arr['data'])) {
            $data = $arr['data'];
            foreach ($data as $k => $v) {
                if (is_array($data[$k])) {
                    return new Response\FollowerAndFollowingResponse($data[$k]);
                }
            }
        }

        return $response;
    }

    /**
     * Get following by graphql query.
     *
     * @param string      $userId      Numerical UserPK ID.
     * @param string      $rankToken   The list UUID. You must use the same value for all pages of the list.
     * @param string      $searchQuery Limit the userlist to ones matching the query.
     * @param string|null $maxId       Next "maximum ID", used for pagination.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getFollowingQuery(
        $userId,
        $rankToken = '',
        $searchQuery = '',
        $maxId = null
    ) {
        $data = [
            'user_id'                   => $userId,
            'exclude_unused_fields'     => false,
            'query'                     => $searchQuery,
            'include_biography'         => false,
            'include_unseen_count'      => false,
            'request_data'              => [
                'search_surface'        => 'follow_list_page',
                'rank_token'            => $rankToken,
                'includes_hashtags'     => true,
            ],
            'enable_groups'             => true,
        ];

        if ($maxId !== null) {
            $data['max_id'] = $maxId;
        }
        $response = $this->ig->internal->sendGraph('161046392812034960803496431073', $data, 'FollowingList', 'xdt_api__v1__friendships__following', 'false', 'pando', true, true);
        $arr = $response->asArray();
        if (isset($arr['data'])) {
            $data = $arr['data'];
            foreach ($data as $k => $v) {
                if (is_array($data[$k])) {
                    return new Response\FollowerAndFollowingResponse($data[$k]);
                }
            }
        }

        return $response;
    }

    /**
     * Get list of who you are following.
     *
     * @param string      $rankToken   The list UUID. You must use the same value for all pages of the list.
     * @param string|null $searchQuery Limit the userlist to ones matching the query.
     * @param string|null $maxId       Next "maximum ID", used for pagination.
     * @param string|null $order       Search order. Latest followings: 'date_followed_latest',
     *                                 earliest followings: 'date_followed_earliest'.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FollowerAndFollowingResponse
     *
     * @see Signatures::generateUUID() To create a UUID.
     * @see examples/rankTokenUsage.php For an example.
     */
    public function getSelfFollowing(
        $rankToken,
        $searchQuery = null,
        $maxId = null,
        $order = null
    ) {
        return $this->getFollowing($this->ig->account_id, $rankToken, $searchQuery, $maxId, $order);
    }

    /**
     * Get list of your own followers.
     *
     * @param string      $rankToken   The list UUID. You must use the same value for all pages of the list.
     * @param string|null $searchQuery Limit the userlist to ones matching the query.
     * @param string|null $maxId       Next "maximum ID", used for pagination.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FollowerAndFollowingResponse
     *
     * @see Signatures::generateUUID() To create a UUID.
     * @see examples/rankTokenUsage.php For an example.
     */
    public function getSelfFollowers(
        $rankToken,
        $searchQuery = null,
        $maxId = null
    ) {
        return $this->getFollowers($this->ig->account_id, $rankToken, $searchQuery, $maxId);
    }

    /**
     * Search for Instagram users.
     *
     * @param string         $query         The username or full name to search for.
     * @param string[]|int[] $excludeList   Array of numerical user IDs (ie "4021088339")
     *                                      to exclude from the response, allowing you to skip users
     *                                      from a previous call to get more results.
     * @param string|null    $rankToken     A rank token from a first call response.
     * @param mixed          $searchSurface
     *
     * @throws \InvalidArgumentException                  If invalid query or
     *                                                    trying to exclude too
     *                                                    many user IDs.
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SearchUserResponse
     *
     * @see SearchUserResponse::getRankToken() To get a rank token from the response.
     * @see examples/paginateWithExclusion.php For an example.
     */
    public function search(
        $query,
        array $excludeList = [],
        $rankToken = null,
        $searchSurface = 'user_serp'
    ) {
        // Do basic query validation.
        if (!is_string($query) || $query === '') {
            throw new \InvalidArgumentException('Query must be a non-empty string.');
        }

        $request = $this->_paginateWithExclusion(
            $this->ig->request('fbsearch/account_serp/')
                ->addParam('query', $query)
                ->addParam('timezone_offset', ($this->ig->getTimezoneOffset() !== null) ? $this->ig->getTimezoneOffset() : date('Z'))
                ->addParam('search_surface', $searchSurface)
                ->addParam('count', 30),
            $excludeList,
            $rankToken
        );

        try {
            /** @var Response\SearchUserResponse $result */
            $result = $request->getResponse(new Response\SearchUserResponse());
        } catch (RequestHeadersTooLargeException $e) {
            $result = new Response\SearchUserResponse([
                'has_more'    => false,
                'num_results' => 0,
                'users'       => [],
                'rank_token'  => $rankToken,
            ]);
        }

        return $result;
    }

    /**
     * Get business account details.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AccountDetailsResponse
     */
    public function getAccountDetails(
        $userId
    ) {
        return $this->ig->request("users/{$userId}/account_details/")
            ->getResponse(new Response\AccountDetailsResponse());
    }

    /**
     * Get a business account's former username(s).
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FormerUsernamesResponse
     */
    public function getFormerUsernames(
        $userId
    ) {
        return $this->ig->request("users/{$userId}/former_usernames/")
            ->getResponse(new Response\FormerUsernamesResponse());
    }

    /**
     * Get a business account's shared follower base with similar accounts.
     *
     * @param string $userId Numerical UserPk ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SharedFollowersResponse
     */
    public function getSharedFollowers(
        $userId
    ) {
        return $this->ig->request("users/{$userId}/shared_follower_accounts/")
            ->getResponse(new Response\SharedFollowersResponse());
    }

    /**
     * Get a business account's active ads on feed.
     *
     * @param string      $targetUserId Numerical UserPk ID.
     * @param string|null $maxId        Next "maximum ID", used for pagination.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\ActiveFeedAdsResponse
     */
    public function getActiveFeedAds(
        $targetUserId,
        $maxId = null
    ) {
        return $this->_getActiveAds($targetUserId, '35', $maxId);
    }

    /**
     * Get a business account's active ads on stories.
     *
     * @param string      $targetUserId Numerical UserPk ID.
     * @param string|null $maxId        Next "maximum ID", used for pagination.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\ActiveReelAdsResponse
     */
    public function getActiveStoryAds(
        $targetUserId,
        $maxId = null
    ) {
        return $this->_getActiveAds($targetUserId, '49', $maxId);
    }

    /**
     * Helper function for getting active ads for business accounts.
     *
     * @param string      $targetUserId Numerical UserPk ID.
     * @param string      $pageType     Content-type id(?) of the ad. 35 is feed ads and 49 is story ads.
     * @param string|null $maxId        Next "maximum ID", used for pagination.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response
     */
    protected function _getActiveAds(
        $targetUserId,
        $pageType,
        $maxId = null
    ) {
        $request = $this->ig->request('ads/view_ads/')
            ->setSignedPost(false)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('target_user_id', $targetUserId)
            ->addPost('page_type', $pageType);
        if ($maxId !== null) {
            $request->addPost('next_max_id', $maxId);
        }
        $request->addPost('ig_user_id', $this->ig->account_id);

        switch ($pageType) {
            case '35':
                return $request->getResponse(new Response\ActiveFeedAdsResponse());
                break;
            case '49':
                return $request->getResponse(new Response\ActiveReelAdsResponse());
                break;
            default:
                throw new \InvalidArgumentException('Invalid page type.');
        }
    }

    /**
     * Search for users by linking your address book to Instagram.
     *
     * WARNING: You must unlink your current address book before you can link
     * another one to search again, otherwise you will just keep getting the
     * same response about your currently linked address book every time!
     *
     * @param array  $contacts
     * @param string $source
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\LinkAddressBookResponse
     *
     * @see People::unlinkAddressBook()
     */
    public function linkAddressBook(
        array $contacts,
        $source = 'account_creation'
    ) {
        return $this->ig->request('address_book/link/')
            ->setIsBodyCompressed(true)
            ->setSignedPost(false)
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('contacts', json_encode($contacts))
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('device_id', $this->ig->uuid)
            ->addPost('module', 'find_friends_contacts')
            ->addPost('source', $source)
            ->addPost('_uuid', $this->ig->uuid)
            ->getResponse(new Response\LinkAddressBookResponse());
    }

    /**
     * Unlink your address book from Instagram.
     *
     * @param bool $userInitiated.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UnlinkAddressBookResponse
     */
    public function unlinkAddressBook(
        $userInitiated = true
    ) {
        return $this->ig->request('address_book/unlink/')
            ->setSignedPost(false)
            ->addPost('user_initiated', ($userInitiated) ? 'true' : 'false')
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\UnlinkAddressBookResponse());
    }

    /**
     * Discover new people via Facebook's algorithm.
     *
     * This matches you with other people using multiple algorithms such as
     * "friends of friends", "location", "people using similar hashtags", etc.
     *
     * @param string      $module From which app module (page) accesed.
     * @param string|null $maxId  Next "maximum ID", used for pagination.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\DiscoverPeopleResponse
     */
    public function discoverPeople(
        $module = 'discover_people',
        $maxId = null
    ) {
        $request = $this->ig->request('discover/ayml/')
            ->setSignedPost(false)
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('module', $module)
            ->addPost('_uuid', $this->ig->uuid);
        // ->addPost('_csrftoken', $this->ig->client->getToken())
        // ->addPost('paginate', true);

        if ($this->ig->isExperimentEnabled('54870', 33, false)) {
            $request->addPost('max_number_to_display', $this->ig->getExperimentParam('54870', 32, 0));
        }

        if ($maxId !== null) {
            $request->addPost('max_id', $maxId);
        }

        return $request->getResponse(new Response\DiscoverPeopleResponse());
    }

    /**
     * Get suggested users related to a user.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SuggestedUsersResponse
     */
    public function getSuggestedUsers(
        $userId
    ) {
        return $this->ig->request('discover/chaining/')
            ->addParam('target_id', $userId)
            ->getResponse(new Response\SuggestedUsersResponse());
    }

    /**
     * Get suggested users via account badge.
     *
     * @param string|null $module (optional) From which app module (page) accesed.
     *
     * This is the endpoint for when you press the "user icon with the plus
     * sign" on your own profile in the Instagram app. Its amount of suggestions
     * matches the number on the badge, and it usually only has a handful (1-4).
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SuggestedUsersBadgeResponse
     */
    public function getSuggestedUsersBadge(
        $module = 'discover_people'
    ) {
        $request = $this->ig->request('discover/profile_su_badge/')
            ->addPost('_uuid', $this->ig->uuid);
        // ->addPost('_csrftoken', $this->ig->client->getToken());

        if ($module !== null) {
            $request->addPost('module', $module);
        }

        return $request->getResponse(new Response\SuggestedUsersBadgeResponse());
    }

    /**
     * Hide suggested user, so that they won't be suggested again.
     *
     * You must provide the correct algorithm for the user you want to hide,
     * which can be seen in their "algorithm" value in People::discoverPeople().
     *
     * Here is a probably-outdated list of algorithms and their meanings:
     *
     * - realtime_chaining_algorithm = ?
     * - realtime_chaining_ig_coeff_algorithm = ?
     * - tfidf_city_algorithm = Popular people near you.
     * - hashtag_interest_algorithm = Popular people on similar hashtags as you.
     * - second_order_followers_algorithm = Popular.
     * - super_users_algorithm = Popular.
     * - followers_algorithm = Follows you.
     * - ig_friends_of_friends_from_tao_laser_algorithm = ?
     * - page_rank_algorithm = ?
     *
     * TODO: Do more research about this function and document it properly.
     *
     * @param string $userId    Numerical UserPK ID.
     * @param string $algorithm Which algorithm to hide the suggestion from;
     *                          must match that user's "algorithm" value in
     *                          functions like People::discoverPeople().
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SuggestedUsersResponse
     */
    public function hideSuggestedUser(
        $userId,
        $algorithm
    ) {
        return $this->ig->request('discover/aysf_dismiss/')
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addParam('target_id', $userId)
            ->addParam('algorithm', $algorithm)
            ->getResponse(new Response\SuggestedUsersResponse());
    }

    /**
     * Bulk follow. This query is used during registration process.
     *
     * @param string[] $userIds Array of Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipsShowManyResponse
     */
    public function bulkFollow(
        array $userIds
    ) {
        $data = [
            'data' => [
                'user_ids'          => $userIds,
                'request_from_nux'  => true,
            ],
        ];

        $response = $this->ig->internal->sendGraph('39921106346623902447469060860', $data, 'FriendshipBulkFollowRequest', 'xdt_create_many', 'false', 'pando', true);
        $arr = $response->asArray();
        if (isset($arr['data'])) {
            $data = $arr['data'];
            foreach ($data as $k => $v) {
                if ($k === '1$xdt_create_many(data:$data)') {
                    return new Response\FriendshipsShowManyResponse($data[$k]);
                }
            }
        }

        return $response;
    }

    /**
     * Follow a user.
     *
     * @param string      $userId                     Numerical UserPK ID.
     * @param string|null $mediaId                    The media ID in Instagram's internal format (ie "3482384834_43294").
     * @param string|null $loggingInfoToken           The logging info token associated to the media.
     * @param string      $containerModule            Container module.
     * @param string      $includeFollowFrictionCheck Include follow friction check. Either '1' or '0'.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function follow(
        $userId,
        $mediaId = null,
        $loggingInfoToken = null,
        $containerModule = 'profile',
        $includeFollowFrictionCheck = '1'
    ) {
        $request = $this->ig->request("friendships/create/{$userId}/")
            ->addPost('include_follow_friction_check', $includeFollowFrictionCheck)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->addPost('nav_chain', $this->ig->getNavChain())
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('container_module', $containerModule);

        if ($this->ig->getIsAndroid()) {
            $request->addPost('radio_type', $this->ig->radio_type);
        }

        if ($mediaId !== null) {
            $request->addPost('media_id', $mediaId)
                    ->addPost('media_id_attribution', $mediaId);
        }

        if ($loggingInfoToken !== null) {
            $request->addPost('ranking_info_token', $loggingInfoToken);
        }

        return $request->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Unfollow a user.
     *
     * @param string      $userId  Numerical UserPK ID.
     * @param string|null $mediaId The media ID in Instagram's internal format (ie "3482384834_43294").
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function unfollow(
        $userId,
        $mediaId = null
    ) {
        $request = $this->ig->request("friendships/destroy/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->addPost('nav_chain', $this->ig->getNavChain())
            ->addPost('radio_type', $this->ig->radio_type);

        if ($mediaId !== null) {
            $request->addPost('media_id_attribution', $mediaId);
        }

        return $request->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Enable high priority for a user you are following.
     *
     * When you mark someone as favorite, you will receive app push
     * notifications when that user uploads media, and their shared
     * media will get higher visibility. For instance, their stories
     * will be placed at the front of your reels-tray, and their
     * timeline posts will stay visible for longer on your homescreen.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function favorite(
        $userId
    ) {
        return $this->ig->request("friendships/favorite/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Disable high priority for a user you are following.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function unfavorite(
        $userId
    ) {
        return $this->ig->request("friendships/unfavorite/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Turn on IGTV notifications.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function favoriteForTv(
        $userId
    ) {
        return $this->ig->request("friendships/favorite_for_igtv/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Turn off IGTV notifications.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function unfavoriteForTv(
        $userId
    ) {
        return $this->ig->request("friendships/unfavorite_for_igtv/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Turn on story notifications.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function favoriteForStories(
        $userId
    ) {
        return $this->ig->request("friendships/favorite_for_stories/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Turn off story notifications.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function unfavoriteForStories(
        $userId
    ) {
        return $this->ig->request("friendships/unfavorite_for_stories/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Report a user as spam.
     *
     * @param string $userId     Numerical UserPK ID.
     * @param string $sourceName (optional) Source app-module of the report.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function report(
        $userId,
        $sourceName = 'profile'
    ) {
        return $this->ig->request("users/{$userId}/flag_user/")
            ->addPost('reason_id', 1)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->addPost('source_name', $sourceName)
            ->addPost('is_spam', true)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Block a user.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function block(
        $userId
    ) {
        return $this->ig->request("friendships/block/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Restrict a user account.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function restrict(
        $userId
    ) {
        return $this->ig->request('restrict_action/restrict/')
            ->setSignedPost(false)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('target_user_id', $userId)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Unrestrict a user account.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function unrestrict(
        $userId
    ) {
        return $this->ig->request('restrict_action/unrestrict/')
            ->setSignedPost(false)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('target_user_id', $userId)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Mute stories, posts or both from a user.
     *
     * It prevents user media from showing up in the timeline and/or story feed.
     *
     * @param string $userId Numerical UserPK ID.
     * @param string $option Selection of what type of media are going to be muted.
     *                       Available options: 'story', 'post' or 'all'.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function muteUserMedia(
        $userId,
        $option
    ) {
        return $this->_muteOrUnmuteUserMedia($userId, $option, 'friendships/mute_posts_or_story_from_follow/');
    }

    /**
     * Unmute stories, posts or both from a user.
     *
     * @param string $userId Numerical UserPK ID.
     * @param string $option Selection of what type of media are going to be muted.
     *                       Available options: 'story', 'post' or 'all'.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function unmuteUserMedia(
        $userId,
        $option
    ) {
        return $this->_muteOrUnmuteUserMedia($userId, $option, 'friendships/unmute_posts_or_story_from_follow/');
    }

    /**
     * Helper function to mute user media.
     *
     * @param string $userId   Numerical UserPK ID.
     * @param string $option   Selection of what type of media are going to be muted.
     *                         Available options: 'story', 'post' or 'all'.
     * @param string $endpoint API endpoint for muting/unmuting user media.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     *
     * @see People::muteUserMedia()
     * @see People::unmuteUserMedia()
     */
    protected function _muteOrUnmuteUserMedia(
        $userId,
        $option,
        $endpoint
    ) {
        $request = $this->ig->request($endpoint)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id);
        // ->addPost('_csrftoken', $this->ig->client->getToken());

        switch ($option) {
            case 'story':
                $request->addPost('target_reel_author_id', $userId);
                break;
            case 'post':
                $request->addPost('target_posts_author_id', $userId);
                break;
            case 'all':
                $request->addPost('target_reel_author_id', $userId);
                $request->addPost('target_posts_author_id', $userId);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid muting option.', $option));
        }

        return $request->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Unblock a user.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     */
    public function unblock(
        $userId
    ) {
        return $this->ig->request("friendships/unblock/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_id', $userId)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Get a list of all blocked users.
     *
     * @param string|null $maxId Next "maximum ID", used for pagination.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\BlockedListResponse
     */
    public function getBlockedList(
        $maxId = null
    ) {
        $request = $this->ig->request('users/blocked_list/');
        if ($maxId !== null) {
            $request->addParam('max_id', $maxId);
        }

        return $request->getResponse(new Response\BlockedListResponse());
    }

    /**
     * Block a user's ability to see your stories.
     *
     * @param string $userId Numerical UserPK ID.
     * @param string $source (optional) The source where this request was triggered.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     *
     * @see People::muteFriendStory()
     */
    public function blockMyStory(
        $userId,
        $source = 'profile'
    ) {
        return $this->ig->request("friendships/block_friend_reel/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('source', $source)
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Unblock a user so that they can see your stories again.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     *
     * @see People::unmuteFriendStory()
     */
    public function unblockMyStory(
        $userId
    ) {
        return $this->ig->request("friendships/unblock_friend_reel/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('source', 'profile')
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Get the list of users who are blocked from seeing your stories.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\BlockedReelsResponse
     */
    public function getBlockedStoryList()
    {
        return $this->ig->request('friendships/blocked_reels/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\BlockedReelsResponse());
    }

    /**
     * Mute a friend's stories, so that you no longer see their stories.
     *
     * This hides them from your reels tray (the "latest stories" bar on the
     * homescreen of the app), but it does not block them from seeing *your*
     * stories.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     *
     * @see People::blockMyStory()
     */
    public function muteFriendStory(
        $userId
    ) {
        return $this->ig->request("friendships/mute_friend_reel/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Unmute a friend's stories, so that you see their stories again.
     *
     * This does not unblock their ability to see *your* stories.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FriendshipResponse
     *
     * @see People::unblockMyStory()
     */
    public function unmuteFriendStory(
        $userId
    ) {
        return $this->ig->request("friendships/unmute_friend_reel/{$userId}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\FriendshipResponse());
    }

    /**
     * Get the list of users on your close friends list.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CloseFriendsResponse
     */
    public function getCloseFriends()
    {
        return $this->ig->request('stories/private_stories/members/')
            ->addParam('is_list_creation', 'false')
            ->addParam('pagination_enabled', 'true')
            ->addParam('page_size', 40)
            ->getResponse(new Response\CloseFriendsResponse());
    }

    /**
     * Add user to your close friends list.
     *
     * @param string $userId User ID to add to your close friends list.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function addCloseFriend(
        $userId
    ) {
        return $this->ig->request('stories/private_stories/add_member/')
            ->setSignedPost(false)
            ->addPost('source', 'audience_selection')
            ->addPost('module', 'settings')
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('user_id', $userId)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Remove user from your close friends list.
     *
     * @param string $userId User ID to add to your close friends list.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function removeCloseFriend(
        $userId
    ) {
        return $this->ig->request('stories/private_stories/remove_member/')
            ->setSignedPost(false)
            ->addPost('source', 'audience_selection')
            ->addPost('module', 'settings')
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('user_id', $userId)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Gets a list of ranked users to display in Android's share UI.
     *
     * @param mixed $nullState
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SharePrefillResponse
     */
    public function getSharePrefill(
        $nullState = false
    ) {
        if ($this->ig->isExperimentEnabled('68215', 4, false)) {
            $views = [
                'reshare_share_sheet'                   => 'direct_target',
                'direct_user_search_keypressed'         => 'direct_target',
                'direct_user_search_nullstate'          => 'direct_target',
                'direct_inbox_active_now'               => 'direct_target',
                'call_recipients'                       => 'direct_target',
                'direct_ibc_inbox_discovery'            => 'direct_target',
                'direct_ibc_inbox_invitations'          => 'direct_target',
            ];
        } else {
            $views = [
                'reshare_share_sheet'                   => 'direct_target',
                'direct_user_search_keypressed'         => 'direct_target',
                'story_share_sheet'                     => 'direct_target',
                'direct_user_search_nullstate'          => 'direct_target',
                'direct_inbox_active_now'               => 'direct_target',
                'forwarding_recipient_sheet'            => 'direct_target',
                'call_recipients'                       => 'direct_target',
                'direct_ibc_inbox_discovery'            => 'direct_target',
                'direct_ibc_inbox_invitations'          => 'direct_target',
            ];
        }

        if (!$this->ig->isExperimentEnabled('55958', 28, false)) {
            unset($views['direct_ibc_inbox_discovery']);
        }

        if (!$this->ig->isExperimentEnabled('55958', 25, false)) {
            unset($views['direct_ibc_inbox_invitations']);
        }

        if ($this->ig->isExperimentEnabled('69705', 0, false)) {
            unset($views['forwarding_recipient_sheet']);
            unset($views['story_share_sheet']);
        }

        $request = $this->ig->request('banyan/banyan/')
            ->addParam('is_private_share', false)
            ->addParam('views', ($nullState === false) ? '["direct_ibc_nullstate"]' : json_encode(array_keys($views)));

        if ($this->ig->isExperimentEnabled('54280', 0, false)) {
            $request->addParam('IBCShareSheetParams', json_encode(['size' => max($this->ig->getExperimentParam('54280', 2, 5), (int) $this->ig->getExperimentParam('54280', 5, 3))]));
        }
        $request->addParam('is_real_time', false);

        return $request->getResponse(new Response\SharePrefillResponse());
    }

    /**
     * Gets a list of users who's stories or posts you mute.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\MutedUsersResponse
     */
    public function getMutedUsers()
    {
        return $this->ig->request('bloks/apps/com.instagram.growth.screens.muted_users/')
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\MutedUsersResponse());
    }

    /**
     * Get non expired friend requests.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\NonExpiredRequestsInfoResponse
     */
    public function getNonExpiredFriendRequests()
    {
        return $this->ig->request('trusted_friend/get_non_expired_requests_info/')
            ->getResponse(new Response\NonExpiredRequestsInfoResponse());
    }

    /**
     * Get creator info.
     *
     * @param string $userId      Numerical UserPK ID.
     * @param string $surfaceType Platform.
     * @param string $entrypoint  Entry point.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function getCreatorInfo(
        $userId,
        $surfaceType = 'android',
        $entrypoint = 'self_profile'
    ) {
        return $this->ig->request('creator/creator_info/')
            ->addParam('entry_point', $entrypoint)
            ->addParam('surface_type', $surfaceType)
            ->addParam('user_id', $userId)
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Get limited interactions reminder.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getLimitedInteractionsReminder()
    {
        return $this->ig->request('users/get_limited_interactions_reminder/')
            ->addParam('signed_body', Signatures::generateSignature(json_encode((object) []).'.{}'))
            ->getResponse(new Response\GenericResponse());
    }
}
