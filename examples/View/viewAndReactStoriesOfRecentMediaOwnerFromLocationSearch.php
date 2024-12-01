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
$queryLocation = 'Paris'; // :)
$reaction = '🎉';
// ////////////////////

$ig = new InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    // Search/explore session, will be used for the Graph API events.
    $searchSession = InstagramAPI\Signatures::generateUUID();

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

    $ig->event->sendNavigation('button', 'blended_search', 'search_places');

    $locationItems = $ig->location->findPlaces($queryLocation)->getItems();
    $rankToken = InstagramAPI\Signatures::generateUUID();

    $resultList = [];
    $resultTypeList = [];
    $position = 0;
    $found = false;
    $locationId = null;
    foreach ($locationItems as $locationItem) {
        $resultList[] = $locationItem->getLocation()->getFacebookPlacesId();
        $resultTypeList[] = 'PLACE';

        if ($locationItem->getTitle() === $queryLocation) {
            $found = true;
            $locationId = $locationItem->getLocation()->getFacebookPlacesId();
        }

        if ($found !== true) {
            $position++;
        }
    }

    // Send restults from search.
    $ig->event->sendSearchResults($queryLocation, $resultList, $resultTypeList, $rankToken, $searchSession, 'blended_search');
    $ig->event->sendSearchResults($queryLocation, $resultList, $resultTypeList, $rankToken, $searchSession, 'search_places');
    // Send selected result from results.
    $ig->event->sendSearchResultsPage($queryLocation, $locationId, $resultList, $resultTypeList, $rankToken, $searchSession, $position, 'PLACE', 'search_places');

    $ig->discover->registerRecentSearchClick('place', $locationId);

    $ig->event->sendNavigation(
        'search_result',
        'search_places',
        'feed_location',
        null,
        null,
        [
            'rank_token'        => $rankToken,
            'query_text'        => $queryLocation,
            'search_session_id' => $searchSession,
            'search_tab'        => 'search_places',
            'selected_type'     => 'place',
            'position'          => $position,
            'entity_page_name'  => $queryLocation,
            'entity_page_id'    => $locationId,
        ]
    );

    // Generate a random rank token.
    $rankToken = InstagramAPI\Signatures::generateUUID();
    // Get sections and items.
    $sectionResponse = $ig->location->getFeed($locationId, $rankToken);
    $sections = $sectionResponse->getSections();
    // These requests are also sent to emulate app behaviour.
    $ig->location->getInfo($locationId);
    $ig->location->getStoryFeed($locationId);

    foreach ($sections as $section) {
        if ($section->getLayoutType() === 'media_grid') {
            foreach ($section->getLayoutContent()->getMedias() as $media) {
                if ($media->getMedia()->getMediaType() === 1) {
                    $candidates = $media->getMedia()->getImageVersions2()->getCandidates();
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

                    $ig->event->sendPerfPercentPhotosRendered('feed_location', $media->getMedia()->getId(), [
                        'is_grid_view'                      => true,
                        'image_heigth'                      => $smallCandidate->getHeight(),
                        'image_width'                       => $smallCandidate->getWidth(),
                        'load_time'                         => $ig->client->bandwidthM,
                        'image_size_kb'                     => $imageSize,
                        'estimated_bandwidth'               => $ig->client->bandwidthB,
                        'estimated_bandwidth_totalBytes_b'  => $ig->client->totalBytes,
                        'estimated_bandwidth_totalTime_ms'  => $ig->client->totalTime,
                    ]);
                    $ig->event->sendThumbnailImpression('instagram_thumbnail_impression', $media->getMedia(), 'feed_location');
                }
            }
        }
    }

    // Get sections and items (RECENT).
    $sectionResponse = $ig->location->getFeed($locationId, $rankToken, 'recent');
    $sections = $sectionResponse->getSections();
    // These requests are also sent to emulate app behaviour.
    $ig->location->getInfo($locationId);
    $ig->location->getStoryFeed($locationId);

    $items = [];
    foreach ($sections as $section) {
        if ($section->getLayoutType() === 'media_grid') {
            foreach ($section->getLayoutContent()->getMedias() as $media) {
                if ($media->getMedia()->getMediaType() === 1) {
                    $candidates = $media->getMedia()->getImageVersions2()->getCandidates();
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

                    $ig->event->sendPerfPercentPhotosRendered('feed_location', $media->getMedia()->getId(), [
                        'is_grid_view'                      => true,
                        'image_heigth'                      => $smallCandidate->getHeight(),
                        'image_width'                       => $smallCandidate->getWidth(),
                        'load_time'                         => $ig->client->bandwidthM,
                        'image_size_kb'                     => $imageSize,
                        'estimated_bandwidth'               => $ig->client->bandwidthB,
                        'estimated_bandwidth_totalBytes_b'  => $ig->client->totalBytes,
                        'estimated_bandwidth_totalTime_ms'  => $ig->client->totalTime,
                    ]);
                    $ig->event->sendThumbnailImpression('instagram_thumbnail_impression', $media->getMedia(), 'feed_location');
                }
                $items[] = $media->getMedia();
            }
        }
    }

    $ig->event->sendThumbnailImpression('instagram_thumbnail_click', $items[0], 'feed_location');
    $ig->event->sendNavigation('button', 'feed_location', 'feed_contextual_location');

    $itemCounter = 0;
    for ($itemCounter; $itemCounter < 5; $itemCounter++) {
        for ($i = 0; $i < 5; $i++) {
            $storyFeed = $ig->story->getUserStoryFeed($items[$itemCounter]->getUser()->getPk());
            if ($storyFeed->getReel() === null) {
                // User has no active stories
                continue;
            }

            $storyItems = $storyFeed->getReel()->getItems();
            $following = $storyFeed->getReel()->getUser()->getFriendshipStatus()->getFollowing();
            $ig->event->sendNavigation('button', 'feed_contextual_location', 'reel_location_feed');

            $viewerSession = InstagramAPI\Signatures::generateUUID();
            $traySession = InstagramAPI\Signatures::generateUUID();
            $rankToken = InstagramAPI\Signatures::generateUUID();

            $ig->event->sendReelPlaybackEntry($items[$itemCounter]->getUser()->getPk(), $viewerSession, $traySession, 'reel_location_feed');

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
                    ],
                    'reel_location_feed'
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
                    ],
                    'reel_location_feed'
                );

                $ig->event->sendOrganicTimespent(
                    $storyItem,
                    $following,
                    mt_rand(1000, 2000),
                    'reel_location_feed',
                    [],
                    [
                        'tray_session_id'        => $traySession,
                        'viewer_session_id'      => $viewerSession,
                        'following'              => $following,
                        'reel_size'              => $reelsize,
                        'reel_location_feed'     => $cnt,
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
                    ],
                    'reel_location_feed'
                );

                $ig->event->sendOrganicReelImpression($storyItem, $viewerSession, $traySession, $rankToken, true, 'reel_location_feed');
                $ig->event->sendOrganicMediaImpression(
                    $storyItem,
                    'reel_location_feed',
                    [
                        'story_ranking_token'   => $rankToken,
                        'tray_session_id'       => $traySession,
                        'viewer_session_id'     => $viewerSession,
                    ]
                );
                $ig->event->sendOrganicViewedImpression($storyItem, 'reel_location_feed', $viewerSession, $traySession, $rankToken);

                $cnt++;
            }

            sleep(mt_rand(1, 3));

            $userId = end($storyItems)->getUser()->getPk();
            $recipients = [
                'users' => [
                    $userId,
                ],
            ];
            if (end($storyItems)->getMediaType() === 1) {
                $mediaType = 'photo';
            } else {
                $mediaType = 'video';
            }

            $clientContext = InstagramAPI\Utils::generateClientContext();

            $ig->direct->sendStoryReaction($recipients, $reaction, end($storyItems)->getId(), ['client_context' => $clientContext]);

            $ig->event->sendDirectMessageIntentOrAttempt('send_intent', $clientContext, 'reel_share', [$userId]);
            $ig->event->sendDirectMessageIntentOrAttempt('send_attempt', $clientContext, 'reel_share', [$userId]);
            $ig->event->sendDirectMessageIntentOrAttempt('sent', $clientContext, 'reel_share', [$userId]);

            sleep(mt_rand(1, 3));

            $ig->story->markMediaSeen($storyItems);
            $ig->event->sendReelPlaybackNavigation(end($storyItems), $viewerSession, $traySession, $rankToken, 'reel_location_feed');
            $ig->event->sendReelSessionSummary(
                end($storyItems),
                $viewerSession,
                $traySession,
                'reel_location_feed',
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
            $ig->event->sendNavigation('back', 'reel_location_feed', 'feed_contextual_location');
        }
    }

    $ig->event->forceSendBatch();
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
