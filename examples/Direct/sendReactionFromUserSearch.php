<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../../vendor/autoload.php';

/////// CONFIG ///////
$username = '';
$password = '';
$debug = true;
$truncatedDebug = false;
//////////////////////

//////////////////////
$queryUser = 'selenagomez'; // :)
$reaction = '🎉';
//////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    // Explore and search session, will be used for the Graph API events.
    $searchSession = \InstagramAPI\Signatures::generateUUID();

    $topicData =
    [
        'topic_cluster_title'       => 'For You',
        'topic_cluster_id'          => 'explore_all:0',
        'topic_cluster_type'        => 'explore_all',
        'topic_cluster_session_id'  => $searchSession,
        'topic_nav_order'           => 0,
    ];

    // Send navigation from 'feed_timeline' to 'explore_popular'.
    $ig->event->sendNavigation('main_search', 'feed_timeline', 'explore_popular', null, null, $topicData);

    // Send navigation from 'explore_popular' to 'explore_popular'.
    $ig->event->sendNavigation('explore_topic_load', 'explore_popular', 'explore_popular', null, null, $topicData);

    // Get explore feed sections and items.
    $sectionalItems = $ig->discover->getExploreFeed('explore_all:0', $searchSession)->getSectionalItems();

    $ig->event->prepareAndSendExploreImpression('explore_all:0', $searchSession, $sectionalItems);

    // Get suggested searches and recommendations from Instagram.
    $ig->event->sendNavigation('button', 'explore_popular', 'blended_search');

    $ig->discover->getNullStateDynamicSections();

    // Time spent to search.
    $timeToSearch = mt_rand(2000, 3500);
    sleep($timeToSearch / 1000);

    // Search query and parse results.
    $searchResponse = $ig->discover->search($queryUser);
    $searchResults = $searchResponse->getList();

    $rankToken = $searchResponse->getRankToken();
    $resultList = [];
    $resultTypeList = [];
    $position = 0;
    $found = false;

    // We are now classifying each result into a hashtag or user result.
    foreach ($searchResults as $searchResult) {
        if ($searchResult->getHashtag() !== null) {
            $resultList[] = $searchResult->getHashtag()->getId();
            $resultTypeList[] = 'HASHTAG';
        } elseif ($searchResult->getUser() !== null) {
            $resultList[] = $searchResult->getUser()->getPk();
            // We will save the data when the result matches our query.
            // Hashtag ID is required in the next steps for Graph API and
            // like().
            if ($searchResult->getUser()->getUsername() === $queryUser) {
                $userId = $searchResult->getUser()->getPk();
                // This request tells Instagram that we have clicked in this specific user.
                $ig->discover->registerRecentSearchClick('user', $userId);
                // When this flag is set to true, position won't increment
                // anymore. We are using this to track the result position.
                $found = true;
            }
            $resultTypeList[] = 'USER';
        } else {
            $resultList[] = $searchResult->getPlace()->getLocation()->getPk();
            $resultTypeList[] = 'PLACE';
        }
        if ($found !== true) {
            $position++;
        }
    }

    // Send restults from search.
    $ig->event->sendSearchResults($queryUser, $resultList, $resultTypeList, $rankToken, $searchSession, 'blended_search');
    // Send selected result from results.
    $ig->event->sendSearchResultsPage($queryUser, $userId, $resultList, $resultTypeList, $rankToken, $searchSession, $position, 'USER', 'blended_search');

    // When we clicked the user, we are navigating from 'blended_search' to 'profile'.
    $ig->event->sendNavigation('button', 'blended_search', 'profile', null, null,
        [
            'rank_token'            => null,
            'query_text'            => $queryUser,
            'search_session_id'     => $searchSession,
            'selected_type'         => 'user',
            'position'              => 0,
            'username'              => $queryUser,
            'user_id'               => $userId,
        ]
    );
    $ig->event->sendProfileView($userId);

    $ig->people->getFriendship($userId);
    $ig->highlight->getUserFeed($userId);
    $ig->people->getInfoById($userId, 'blended_search');
    $ig->story->getUserStoryFeed($userId);
    $userFeed = $ig->timeline->getUserFeed($userId);
    $items = $userFeed->getItems();

    $c = 0;
    foreach ($items as $item) {
        if ($c === 5) {
            break;
        }
        $ig->event->sendThumbnailImpression('instagram_thumbnail_impression', $item, 'profile');
        $c++;
    }
    $ig->event->sendProfileAction('tap_follow_details', $userId,
        [
            [
                'module'        => 'blended_search',
                'click_point'   => 'search_result',
            ],
            [
                'module'        => 'blended_search',
                'click_point'   => 'button',
            ],
            [
                'module'        => 'explore_popular',
                'click_point'   => 'button',
            ],
            [
                'module'        => 'explore_popular',
                'click_point'   => 'explore_topic_load',
            ],
            [
                'module'        => 'feed_timeline',
                'click_point'   => 'main_search',
            ],
    ], ['module' => 'profile']);

    $ig->event->sendNavigation('button', 'profile', 'unified_follow_lists');
    $ig->event->sendProfileAction('tap_followers', $userId,
        [
            [
                'module'        => 'profile',
                'click_point'   => 'button',
            ],
            [
                'module'        => 'blended_search',
                'click_point'   => 'search_result',
            ],
            [
                'module'        => 'blended_search',
                'click_point'   => 'button',
            ],
            [
                'module'        => 'explore_popular',
                'click_point'   => 'button',
            ],
            [
                'module'        => 'explore_popular',
                'click_point'   => 'explore_topic_load',
            ],
            [
                'module'        => 'feed_timeline',
                'click_point'   => 'main_search',
            ],
    ], ['module' => 'profile']);

    $ig->event->sendNavigation('followers', 'unified_follow_lists', 'unified_follow_lists', null, null,
        [
            'source_tab'    => 'followers',
            'dest_tab'      => 'followers',
        ]
    );

    $ig->discover->surfaceWithSu($userId);

    $rankToken = \InstagramAPI\Signatures::generateUUID();
    $followers = $ig->people->getFollowers($userId, $rankToken);

    $followerList = [];
    foreach ($followers->getUsers() as $follower) {
        $followerList[] = $follower->getPk();
    }

    $ig->people->getFriendships($followerList);
    $ig->discover->markSuSeen();
    $ig->discover->getAyml();
    $ig->people->getFriendships($followerList);

    $ig->event->sendNavigation('button', 'unified_follow_lists', 'profile');

    $ig->event->sendProfileView($followerList[0]);

    $ig->people->getFriendship($followerList[0]);
    $ig->highlight->getUserFeed($followerList[0]);
    $ig->people->getInfoById($followerList[0]);
    $storyFeed = $ig->story->getUserStoryFeed($followerList[0]);
    $userFeed = $ig->timeline->getUserFeed($followerList[0]);
    $items = $userFeed->getItems();

    $c = 0;
    foreach ($items as $item) {
        if ($c === 5) {
            break;
        }
        $ig->event->sendThumbnailImpression('instagram_thumbnail_impression', $item, 'profile');
        $c++;
    }

    if ($storyFeed->getReel() === null) {
        echo 'User has no active stories';
        exit();
    }

    $storyItems = $storyFeed->getReel()->getItems();
    $following = $storyFeed->getReel()->getUser()->getFriendshipStatus()->getFollowing();
    $ig->event->sendNavigation('button', 'profile', 'reel_profile');

    $viewerSession = \InstagramAPI\Signatures::generateUUID();
    $traySession = \InstagramAPI\Signatures::generateUUID();
    $rankToken = \InstagramAPI\Signatures::generateUUID();

    $ig->event->sendOrganicReelImpression($storyItems[0], $viewerSession, $traySession, $rankToken, $following, 'reel_profile');
    $ig->event->sendOrganicMediaImpression($storyItems[0], 'reel_profile', ['story_ranking_token' => $rankToken, 'tray_session_id' => $traySession, 'viewer_session_id' => $viewerSession]);
    $ig->story->markMediaSeen([$storyItems[0]]);
    $ig->event->sendOrganicViewedImpression($storyItems[0], 'reel_profile', $viewerSession, $traySession, $rankToken);

    $recipients = [
        'users' => [
            $followerList[0],
        ],
    ];
    if ($storyItems[0]->getMediaType() === 1) {
        $mediaType = 'photo';
    } else {
        $mediaType = 'video';
    }

    $clientContext = \InstagramAPI\Utils::generateClientContext();

    $ig->direct->sendStoryReaction($recipients, $reaction, $storyItems[0]->getId(), ['client_context' => $clientContext]);

    $ig->event->sendDirectMessageIntentOrAttempt('send_intent', $clientContext, 'reel_share', [$userId]);
    $ig->event->sendDirectMessageIntentOrAttempt('send_attempt', $clientContext, 'reel_share', [$userId]);
    $ig->event->sendDirectMessageIntentOrAttempt('sent', $clientContext, 'reel_share', [$userId]);

    $ig->event->sendNavigation('back', 'reel_profile', 'profile');

    // forceSendBatch() should be only used if you are "closing" the app so all the events that
    // are queued will be sent. Batch event will automatically be sent when it reaches 50 events.
    $ig->event->forceSendBatch();
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
