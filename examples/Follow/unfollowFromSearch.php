<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../../vendor/autoload.php';

// ///// CONFIG ///////
$username = '';
$password = '';
$debug = true;
$truncatedDebug = false;
// ////////////////////

// ////////////////////
$usernameToFollow = 'selenagomez';
// ////////////////////

$ig = new InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    // Explore and search session, will be used for the Graph API events.
    $searchSession = InstagramAPI\Signatures::generateUUID();

    $topicData =
    [
        'topic_cluster_title'       => 'For You',
        'topic_cluster_id'          => 'explore_all:0',
        'topic_cluster_type'        => 'explore_all',
        'topic_cluster_session_id'  => $searchSession,
        'topic_nav_order'           => 0,
    ];

    $ig->event->sendNavigation('main_search', 'feed_timeline', 'explore_popular', null, null, $topicData);
    $ig->discover->getNullStateDynamicSections();
    $ig->discover->getExploreFeed('explore_all:0', $searchSession);
    $timeToSearch = mt_rand(2000, 3500);
    sleep($timeToSearch / 1000);
    $searchResponse = $ig->discover->search($usernameToFollow);
    $ig->event->sendNavigation('button', 'explore_popular', 'blended_search');

    $searchResults = $searchResponse->getList();
    $rankToken = $searchResponse->getRankToken();
    $resultList = [];
    $resultTypeList = [];
    $position = 0;
    $found = false;
    $userId = null;
    foreach ($searchResults as $searchResult) {
        if ($searchResult->getUser() !== null) {
            $resultList[] = $searchResult->getUser()->getPk();
            if ($searchResult->getUser()->getUsername() === $usernameToFollow) {
                $found = true;
                $userId = $searchResult->getUser()->getPk();
            }
            $resultTypeList[] = 'USER';
        } elseif ($searchResult->getHashtag() !== null) {
            $resultList[] = $searchResult->getHashtag()->getId();
            $resultTypeList[] = 'HASHTAG';
        } else {
            $resultList[] = $searchResult->getPlace()->getLocation()->getPk();
            $resultTypeList[] = 'PLACE';
        }
        if ($found !== true) {
            $position++;
        }
    }
    $ig->event->sendSearchResults($usernameToFollow, $resultList, $resultTypeList, $rankToken, $searchSession, 'blended_search');
    $ig->event->sendSearchResultsPage($usernameToFollow, $userId, $resultList, $resultTypeList, $rankToken, $searchSession, $position, 'USER', 'blended_search');
    $ig->discover->registerRecentSearchClick('user', $userId);
    $friendship = $ig->people->getFriendship($userId);
    $followStatus = $friendship->getFriendshipStatus()->getFollowing();
    $suggestions = $ig->people->getInfoById($userId, 'search_users')->getUser()->getChainingSuggestions();

    if ($suggestions !== null) {
        for ($i = 0; $i < 4; $i++) {
            $ig->event->sendSimilarUserImpression($userId, $suggestions[$i]->getPk());
            $ig->event->sendSimilarEntityImpression($userId, $suggestions[$i]->getPk());
        }
    }
    // When we clicked the user, we are navigating from 'blended_search' to 'profile'.
    $ig->event->sendNavigation(
        'search_result',
        'blended_search',
        'profile',
        null,
        null,
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
    $ig->event->sendNavigation(
        'button',
        'profile',
        'profile',
        null,
        null,
        [
            'rank_token'            => null,
            'query_text'            => $queryUser,
            'search_session_id'     => $searchSession,
            'selected_type'         => 'user',
            'position'              => 0,
            'username'              => $queryUser,
            'user_id'               => $userId,
            'class_selector'        => 'ProfileMediaTabFragment',
        ]
    );
    $traySession = InstagramAPI\Signatures::generateUUID();
    $ig->highlight->getUserFeed($userId);
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
    $ig->event->reelTrayRefresh(
        [
            'tray_session_id'   => $traySession,
            'tray_refresh_time' => number_format(mt_rand(100, 500) / 1000, 3),
        ],
        'network'
    );

    try {
        $ig->internal->getQPFetch();
    } catch (Exception $e) {
    }
    sleep(mt_rand(1, 2));
    $ig->event->sendProfileView($userId);
    $ig->event->sendFollowButtonTapped(
        $userId,
        'profile',
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
                'module'        => 'feed_timeline',
                'click_point'   => 'main_search',
            ],
        ],
        null,
        true
    );

    if ($followStatus !== false) {
        $ig->people->unfollow($userId);
        $ig->event->sendProfileAction(
            'unfollow',
            $userId,
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
                    'module'        => 'feed_timeline',
                    'click_point'   => 'main_search',
                ],
            ]
        );
        $ig->event->forceSendBatch();
    }
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
