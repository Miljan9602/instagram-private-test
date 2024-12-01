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

$ig = new InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    $ig->event->sendNavigationTabClicked('main_home', 'main_profile', 'feed_timeline');
    $ig->event->sendNavigation('main_profile', 'feed_timeline', 'self_profile');

    $traySession = InstagramAPI\Signatures::generateUUID();

    $ig->highlight->getSelfUserFeed();
    $ig->people->getSelfInfo();
    $userFeed = $ig->timeline->getSelfUserFeed();
    $ig->story->getArchiveBadgeCount();
    $items = $userFeed->getItems();

    $items = array_slice($items, 0, 6);
    $ig->event->preparePerfWithImpressions($items, 'self_profile');

    $ig->event->sendNavigation('button', 'self_profile', 'bottom_sheet_profile');
    $ig->event->sendNavigation('button', 'bottom_sheet_profile', 'settings_category_options');
    $ig->event->sendNavigation('button', 'settings_category_options', 'security_options');

    $ig->account->getSecurityInfo();
    $code = $ig->account->getTOTPCode()->getTotpSeed();

    // Use the following TOTP with you preferred app, for example, Google authenticator.
    // It will generate a 6 digit code that you will need to insert in the next steps.
    echo 'TOTP Code: '.$code;

    echo "\nInsert OTP (6-digit) code: ";
    $top = trim(fgets(STDIN));

    $ig->account->enableTOTPAuthentication($code);

    // forceSendBatch() should be only used if you are "closing" the app so all the events that
    // are queued will be sent. Batch event will automatically be sent when it reaches 50 events.
    $ig->event->forceSendBatch();
} catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
