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
$query = '';
$videoFilename = '';
// ////////////////////

$ig = new InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    $clientContext = InstagramAPI\Utils::generateClientContext();
    $sessionId = InstagramAPI\Signatures::generateUUID();
    $ig->event->sendNavigation('on_launch_direct_inbox', 'feed_timeline', 'direct_inbox');
    $users = $ig->people->search($query, [], null, 'direct_recipient_list_page')->getUsers();

    foreach ($users as $key => $value) {
        if ($value->getUsername() === $query) {
            $position = $key;
            $userId = $value->getPk();
            break;
        }
    }

    $ig->event->sendDirectUserSearchPicker($query);
    $ig->event->sendDirectUserSearchPicker($query);
    $ig->event->sendDirectUserSearchPicker($query);

    $groupSession = InstagramAPI\Signatures::generateUUID();
    $ig->event->sendDirectUserSearchSelection($userId, $position, $groupSession);
    $ig->event->sendGroupCreation($groupSession);
    $ig->event->sendNavigation('button', 'direct_inbox', 'direct_thread', null, null, ['user_id' => $userId]);
    $ig->event->sendEnterDirectThread(null, $sessionId);

    $recipients = [
        'users' => [
            $userId,
        ],
    ];

    $ig->direct->sendAudio($recipients, $videoFilename, ['client_context' => $clientContext]);
    $ig->event->sendDirectMessageIntentOrAttempt('send_intent', $clientContext, 'voice_media', [$userId]);
    $ig->event->sendDirectMessageIntentOrAttempt('send_attempt', $clientContext, 'voice_media', [$userId]);
    $ig->event->sendDirectMessageIntentOrAttempt('sent', $clientContext, 'voice_media', [$userId]);

    // $ig->event->sendNavigation('back', 'direct_thread', 'direct_inbox');
    // $ig->event->sendNavigation('back', 'direct_inbox', 'feed_timeline');
    $ig->event->forceSendBatch();
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
