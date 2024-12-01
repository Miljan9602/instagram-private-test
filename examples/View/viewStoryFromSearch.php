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
    $ig->people->getFriendship($userId);
    $suggestions = $ig->people->getInfoById($userId, 'search_users')->getUser()->getChainingSuggestions();

    if ($suggestions !== null) {
        for ($i = 0; $i < 4; $i++) {
            $ig->event->sendSimilarUserImpression($userId, $suggestions[$i]->getPk());
            $ig->event->sendSimilarEntityImpression($userId, $suggestions[$i]->getPk());
        }
    }
    $ig->event->sendNavigation(
        'button',
        'search_users',
        'profile',
        null,
        null,
        [
            'rank_token'        => $rankToken,
            'query_text'        => $usernameToFollow,
            'search_session_id' => $searchSession,
            'selected_type'     => 'user',
            'position'          => 0,
            'username'          => $usernameToFollow,
            'user_id'           => $userId,
        ]
    );
    $traySession = InstagramAPI\Signatures::generateUUID();
    $ig->highlight->getUserFeed($userId);
    $storyFeed = $ig->story->getUserStoryFeed($userId);
    if ($storyFeed->getReel() === null) {
        echo 'User has no active stories';
        exit;
    }
    $storyItems = $storyFeed->getReel()->getItems();
    $userFeed = $ig->timeline->getUserFeed($userId);
    $items = $userFeed->getItems();
    $following = $storyFeed->getReel()->getUser()->getFriendshipStatus()->getFollowing();

    $c = 0;
    foreach ($items as $item) {
        if ($c === 5) {
            break;
        }
        if ($item->getMediaType() === 1) {
            $candidates = $item->getImageVersions2()->getCandidates();
            $smallCandidate = end($candidates);

            $imageResponse = $ig->request($smallCandidate->getUrl());

            if (isset($imageResponse->getHttpResponse()->getHeaders()['x-encoded-content-length'])) {
                $imageSize = $imageResponse->getHttpResponse()->getHeaders()['x-encoded-content-length'][0];
            } elseif (isset($imageResponse->getHttpResponse()->getHeaders()['Content-Length'])) {
                $imageSize = $imageResponse->getHttpResponse()->getHeaders()['Content-Length'][0];
            } elseif (isset($imageResponse->getHttpResponse()->getHeaders()['content-length'])) {
                $imageSize = $imageResponse->getHttpResponse()->getHeaders()['content-length'][0];
            } else {
                continue;
            }

            $ig->event->sendPerfPercentPhotosRendered('profile', $item->getId(), [
                'is_grid_view'                      => true,
                'image_heigth'                      => $smallCandidate->getHeight(),
                'image_width'                       => $smallCandidate->getWidth(),
                'load_time'                         => $ig->client->bandwidthM,
                'image_size_kb'                     => $imageSize,
                'estimated_bandwidth'               => $ig->client->bandwidthB,
                'estimated_bandwidth_totalBytes_b'  => $ig->client->totalBytes,
                'estimated_bandwidth_totalTime_ms'  => $ig->client->totalTime,
            ]);
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
    sleep(2);
    $ig->event->sendProfileView($userId);

    if (empty($storyItems)) {
        echo 'User has no stories';
        exit;
    }

    $ig->event->sendNavigation('button', 'profile', 'reel_profile');

    $viewerSession = InstagramAPI\Signatures::generateUUID();
    $traySession = InstagramAPI\Signatures::generateUUID();
    $rankToken = InstagramAPI\Signatures::generateUUID();

    $ig->event->sendReelPlaybackEntry($userId, $viewerSession, $traySession);

    $reelsize = count($storyItems);
    $cnt = 0;

    $photosConsumed = 0;
    $videosConsumed = 0;

    foreach ($storyItems as $storyItem) {
        if ($storyItem->getMediaType() == 2) {
            $videosConsumed++;
        } else {
            $photosConsumed++;
        }

        $ig->event->sendOrganicMediaSubImpression(
            $storyItem,
            [
                'tray_session_id'   => $traySession,
                'viewer_session_id' => $viewerSession,
                'following'         => $following,
                'reel_size'         => $reelsize,
                'reel_position'     => $cnt,
            ]
        );

        $ig->event->sendOrganicViewedSubImpression(
            $storyItem,
            $viewerSession,
            $traySession,
            [
                'tray_session_id'   => $traySession,
                'viewer_session_id' => $viewerSession,
                'following'         => $following,
                'reel_size'         => $reelsize,
                'reel_position'     => $cnt,
            ]
        );

        $ig->event->sendOrganicTimespent(
            $storyItem,
            $following,
            mt_rand(1000, 2000),
            'reel_profile',
            [],
            [
                'tray_session_id'   => $traySession,
                'viewer_session_id' => $viewerSession,
                'following'         => $following,
                'reel_size'         => $reelsize,
                'reel_position'     => $cnt,
            ]
        );

        $ig->event->sendOrganicVpvdImpression(
            $storyItem,
            [
                'tray_session_id'       => $traySession,
                'viewer_session_id'     => $viewerSession,
                'following'             => $following,
                'reel_size'             => $reelsize,
                'reel_position'         => $cnt,
                'client_sub_impression' => 1,
            ]
        );

        $ig->event->sendOrganicReelImpression($storyItem, $viewerSession, $traySession, $rankToken, true, 'reel_profile');
        $ig->event->sendOrganicMediaImpression(
            $storyItem,
            'reel_profile',
            [
                'story_ranking_token'   => $rankToken,
                'tray_session_id'       => $traySession,
                'viewer_session_id'     => $viewerSession,
            ]
        );
        $ig->event->sendOrganicViewedImpression($storyItem, 'reel_profile', $viewerSession, $traySession, $rankToken);

        $cnt++;
    }

    sleep(mt_rand(1, 3));

    $ig->story->markMediaSeen($storyItems);
    $ig->event->sendReelPlaybackNavigation(end($storyItems), $viewerSession, $traySession, $rankToken);
    $ig->event->sendReelSessionSummary(
        $item,
        $viewerSession,
        $traySession,
        'reel_profile',
        [
            'tray_session_id'               => $traySession,
            'viewer_session_id'             => $viewerSession,
            'following'                     => $following,
            'reel_size'                     => $reelsize,
            'reel_position'                 => count($storyItems) - 1,
            'is_last_reel'                  => 1,
            'photos_consumed'               => $photosConsumed,
            'videos_consumed'               => $videosConsumed,
            'viewer_session_media_consumed' => count($storyItems),
        ]
    );

    $ig->event->sendNavigation('back', 'reel_profile', 'profile');
    // forceSendBatch() should be only used if you are "closing" the app so all the events that
    // are queued will be sent. Batch event will automatically be sent when it reaches 50 events.
    $ig->event->forceSendBatch();
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
