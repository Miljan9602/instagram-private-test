<?php

/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedConstantInspection */
/** @noinspection PhpComposerExtensionStubsInspection */
require __DIR__.'/config.php';
if (VENDOR_PATH === '') {
    Utils::log("\e[31m[x] Storage path: Please configure VENDOR_PATH in config.php.\e[0m");
    exit;
}
require_once VENDOR_PATH.'/autoload.php';

use InstagramAPI\Instagram;
use InstagramAPI\Request;

class Utils
{
    /**
     * Checks the current version code against the server's version code.
     *
     * @param string $current The current version code.
     * @param string $flavor  The current version flavor.
     *
     * @return bool Returns true if update is available.
     */
    public static function checkForUpdate(
        string $current,
        string $flavor,
    ): bool {
        if ($flavor == 'custom') {
            self::log("Update: You're running an in-dev build; Please note update checks will not work!");

            return false;
        }

        return (int) json_decode(file_get_contents("https://raw.githubusercontent.com/JRoy/InstagramLive-PHP/update/$flavor.json"), true)['versionCode'] > (int) $current;
    }

    /**
     * Checks if the script is using dev-master.
     *
     * @return bool Returns true if composer is using dev-master
     */
    public static function isApiDevMaster(): bool
    {
        clearstatcache();
        if (!file_exists('composer.lock')) {
            return false;
        }

        // Don't override private API
        foreach (@json_decode(file_get_contents('composer.json'), true)['require'] as $key => $value) {
            if (strpos($key, '-private/instagram') !== false) {
                return true;
            }
        }

        $pass = false;
        foreach (@json_decode(file_get_contents('composer.lock'), true)['packages'] as $package) {
            if (@$package['name'] === 'mgp25/instagram-php'
                && @$package['version'] === 'dev-master'
                && @$package['source']['reference'] === @explode('#', @json_decode(file_get_contents('composer.json'), true)['require']['mgp25/instagram-php'])[1]) {
                $pass = true;
                break;
            }
        }

        return $pass;
    }

    /**
     * Sanitizes a stream key for clip command on Windows.
     *
     * @param string $streamKey The stream key to sanitize.
     *
     * @return string The sanitized stream key.
     */
    public static function sanitizeStreamKey(
        $streamKey,
    ): string {
        return str_replace('&', '^^^&', $streamKey);
    }

    /**
     * Logs information about the current environment.
     *
     * @param string  $exception Exception message to log.
     * @param Request $request   Request object to log.
     */
    public static function dump(
        ?string $exception = null,
        ?Request $request = null,
    ) {
        clearstatcache();
        self::log('===========BEGIN DUMP===========');
        self::log('InstagramLive-PHP Version: '.(defined('scriptVersion') ? scriptVersion : 'Unknown'));
        self::log('InstagramLive-PHP Flavor: '.(defined('scriptFlavor') ? scriptFlavor : 'Unknown'));
        self::log('Instagram-API Version: '.@json_decode(file_get_contents('composer.json'), true)['require']['mgp25/instagram-php']);
        self::log('Operating System: '.PHP_OS);
        self::log('PHP Version: '.PHP_VERSION);
        self::log('PHP Runtime: '.php_sapi_name());
        self::log('PHP Binary: '.PHP_BINARY);
        self::log('Bypassing OS-Check: '.(defined('bypassCheck') ? (bypassCheck == true ? 'true' : 'false') : 'Unknown'));
        self::log('Composer Lock: '.(file_exists('composer.lock') == true ? 'true' : 'false'));
        self::log('Vendor Folder: '.(file_exists(VENDOR_PATH) == true ? 'true' : 'false'));
        if ($request !== null) {
            self::log('Request Endpoint: '.$request->getUrl());
        }
        if ($exception !== null) {
            self::log('Exception: '.$exception);
        }
        self::log('============END DUMP============');
    }

    /**
     * Helper function to check if the current OS is Windows.
     *
     * @return bool Returns true if running Windows.
     */
    public static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Helper function to check if the current OS is Mac.
     *
     * @return bool Returns true if running Windows.
     */
    public static function isMac(): bool
    {
        return strtoupper(PHP_OS) === 'DARWIN';
    }

    /**
     * Logs message to a output file.
     *
     * @param string $message message to be logged to file.
     * @param string $file    file to output to.
     */
    public static function logOutput(
        $message,
        $file = 'output.txt',
    ) {
        file_put_contents($file, $message.PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Checks for a file existance, if it doesn't exist throw a dump and exit the script.
     *
     * @param $path   string Path to the file.
     * @param $reason string Reason the file is needed.
     */
    public static function existsOrError(
        $path,
        $reason,
    ) {
        if (!file_exists($path)) {
            self::log('The following file, `'.$path.'` is required and not found by the script for the following reason: '.$reason);
            self::log('Please make sure you follow the setup guide correctly.');
            self::dump();
            exit(1);
        }
    }

    /**
     * Checks to see if characters are at the start of the string.
     *
     * @param string $haystack The string to for the needle.
     * @param string $needle   The string to search for at the start of haystack.
     *
     * @return bool Returns true if needle is at start of haystack.
     */
    public static function startsWith(
        $haystack,
        $needle,
    ) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Prompts for user input. (Note: Holds the current thread!).
     *
     * @param string $prompt The prompt for the input.
     *
     * @return string The collected input.
     */
    public static function promptInput(
        $prompt = '>',
    ): string {
        echo "$prompt ";

        return stream_get_line(STDIN, 1024, PHP_EOL);
    }

    /**
     * Saves the stream's current state to prevent creating phantom streams.
     *
     * @param string     $broadcastId   Broadcast ID of the stream.
     * @param string     $streamUrl     Stream URL of the stream.
     * @param string     $streamKey     Stream Key of the stream.
     * @param int        $lastCommentTs Recent Max ID of comments
     * @param int        $lastLikeTs    Recent Max ID of likes.
     * @param string|int $lastQuestion  Last Question displayed.
     * @param int        $startTime     Epoch Time at which the stream started.
     * @param bool       $obs           True if the user is using obs.
     * @param string     $obsObject
     */
    public static function saveRecovery(
        string $broadcastId,
        string $streamUrl,
        string $streamKey,
        int $lastCommentTs,
        int $lastLikeTs,
        $lastQuestion,
        int $startTime,
        bool $obs,
        string $obsObject,
    ) {
        file_put_contents('backup.json', json_encode([
            'broadcastId'   => $broadcastId,
            'streamUrl'     => $streamUrl,
            'streamKey'     => $streamKey,
            'lastCommentTs' => $lastCommentTs,
            'lastLikeTs'    => $lastLikeTs,
            'lastQuestion'  => $lastQuestion,
            'startTime'     => $startTime,
            'obs'           => $obs,
            'obsObject'     => $obsObject,
        ]));
    }

    /**
     * Gets the json decoded recovery data.
     *
     * @return array Json-Decoded Recovery Data.
     */
    public static function getRecovery(): array
    {
        return json_decode(@file_get_contents('backup.json'), true);
    }

    /**
     * Checks if the recovery file is present.
     *
     * @return bool True if recovery file is present.
     */
    public static function isRecovery(): bool
    {
        clearstatcache();
        if (!STREAM_RECOVERY) {
            return false;
        }

        return (self::isWindows() || self::isMac()) && file_exists('backup.json');
    }

    /**
     * Deletes the recovery data if present.
     */
    public static function deleteRecovery()
    {
        @unlink('backup.json');
    }

    /**
     * Kills a process with.
     *
     * @param $pid
     */
    public static function killPid(
        $pid,
    ) {
        exec((self::isWindows() ? 'taskkill /F /PID' : 'kill -9')." $pid");
    }

    /**
     * Runs our login flow to authenticate the user as well as resolve all two-factor/challenge items.
     *
     * @param string     $username       Username of the target account.
     * @param string     $password       Password of the target account.
     * @param bool       $debug          Debug
     * @param bool       $truncatedDebug Truncated Debug
     * @param mixed|null $storagePath
     *
     * @return ExtendedInstagram Authenticated Session.
     */
    public static function loginFlow(
        $username,
        $password,
        $debug = false,
        $truncatedDebug = false,
        $storagePath = null,
    ): ExtendedInstagram {
        $ig = new ExtendedInstagram($debug, $truncatedDebug, $storagePath);

        try {
            $loginResponse = $ig->login($username, $password);

            if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
                self::log('Login Flow: Two-Factor Authentication Required! Please provide your verification code from your texts/other means.');
                $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
                $verificationMethod = 1;

                if ($loginResponse->getTwoFactorInfo()->getTotpTwoFactorOn() === true) {
                    $verificationMethod = 3;
                }

                self::log("Login Flow: We've detected that you're using ".($verificationMethod === 3 ? 'authenticator app' : 'text message').' verification. If you are actually using '.($verificationMethod === 3 ? 'text message' : 'authenticator app')." verification, please type 'yes', otherwise press enter.");
                $choice = self::promptInput();

                if ($choice === 'yes') {
                    $verificationMethod = ($verificationMethod === 3 ? 1 : 3);
                }

                do {
                    $verificationCode = intval(self::promptInput('Type your verification code>'));
                } while ((strlen($verificationCode) != 6) || !is_int($verificationCode));

                self::log('Login Flow: Logging in with verification token...');
                $ig->finishTwoFactorLogin($username, $password, $twoFactorIdentifier, $verificationCode, $verificationMethod);
            }
        } catch (Exception $e) {
            if ($e instanceof InstagramAPI\Exception\Checkpoint\ChallengeRequiredException) {
                self::log('Suspicious Login: Instagram needs user verification. Proceeding with the verification...');
                $iterations = 0;
                $webForm = false;
                $challenge = $e->getResponse()->getChallenge();
                if (!is_array($challenge)) {
                    $checkApiPath = substr($challenge->getApiPath(), 1);
                } else {
                    $checkApiPath = substr($challenge['api_path'], 1);
                }
                while (true) {
                    try {
                        if (++$iterations >= Request\Checkpoint::MAX_CHALLENGE_ITERATIONS) {
                            throw new InstagramAPI\Exception\Checkpoint\ChallengeIterationsLimitException();
                        }
                        switch (true) {
                            case $e instanceof InstagramAPI\Exception\Checkpoint\ChallengeRequiredException:
                                if ($webForm) {
                                    $ig->checkpoint->getWebFormCheckpoint($e->getResponse()->getChallenge()->getUrl());
                                } else {
                                    if ($iterations > 5) {
                                        $webForm = true;
                                    }
                                    // Send a challenge request
                                    $ig->checkpoint->sendChallenge($checkApiPath);
                                }
                                break;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\EscalationInformationalException:
                                $ig->checkpoint->sendAcceptEscalationInformational($checkApiPath);
                                break;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\SelectVerifyMethodException:
                                // If condition can be replaced by other logic. This will take always the phone number
                                // if it set, otherwise the email.
                                if ($e->getResponse()->getStepData()->getPhoneNumber() !== null) {
                                    $method = 0;
                                } else {
                                    $method = 1;
                                }
                                // requestVerificationCode() will request a verification code to your EMAIL or
                                // PHONE NUMBER. If you choose method 0, the code will be sent to your PHONE NUMBER.
                                // IF you choose method 1, the code will be sent to your EMAIL.
                                self::log(sprintf("\e[96m[✓] \e[0mVerification code sent via %s.", ($method === 0) ? 'SMS' : 'EMAIL'));
                                $ig->checkpoint->requestVerificationCode($checkApiPath, $method);
                                break;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\VerifyCodeException:
                                // The "STDIN" lets you paste the code via terminal for testing.
                                // You should replace this line with the logic you want.
                                // The verification code will be sent by Instagram via SMS.
                                echo '[>] Insert verification code: ';
                                $code = trim(fgets(STDIN));
                                // `sendVerificationCode()` will send the received verification code from the previous step.
                                // If the checkpoint was bypassed, you will be able to do any other request normally.
                                $challenge = $ig->checkpoint->sendVerificationCode($checkApiPath, $code);

                                if ($challenge->getLoggedInUser() !== null) {
                                    // If code was successfully verified, update login state and send login flow.
                                    $ig->finishCheckpoint($challenge);
                                    // Break switch and while loop.
                                    break 2;
                                } elseif ($challenge->getAction() === 'close') {
                                    break 2;
                                }
                                break;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\SubmitPhoneException:
                                echo '[>] Phone number verification required. Insert phone number: ';
                                $phone = trim(fgets(STDIN));
                                // Set the phone number for verification.
                                $ig->checkpoint->sendVerificationPhone($checkApiPath, $phone);
                                break;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\SubmitEmailException:
                                echo '[>] Email verification required. Insert email : ';
                                $email = trim(fgets(STDIN));
                                // Set the email for verification.
                                $ig->checkpoint->sendVerificationEmail($checkApiPath, $email);
                                break;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\DeltaLoginReviewException:
                                $ig->checkpoint->requestVerificationCode($checkApiPath, 0);
                                break 2;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\RecaptchaChallengeException:
                                // $sitekey = $e->getResponse()->getSitekey();
                                echo "\e[91m[x]\e[0m Catpcha required. Exiting...";
                                exit;
                                $googleResponse = trim(fgets(STDIN));
                                $ig->checkpoint->sendCaptchaResponse($e->getResponse()->getChallengeUrl(), $googleResponse);
                                break;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\EscalationChallengeInformationException:
                                $ig->checkpoint->sendAcceptEscalationInformational($e->getResponse()->getChallengeUrl());
                                break 2;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\SubmitPhoneNumberFormException:
                                echo '[>] Phone number verification required. Insert phone number: ';
                                $phone = trim(fgets(STDIN));
                                $ig->checkpoint->sendWebFormPhoneNumber($e->getResponse()->getChallengeUrl(), $phone);
                                break;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\SelectVerificationMethodFormException:
                                echo '[>] Insert verification method. 0 = SMS, 1 = EMAIL: ';
                                $verificationMethod = intval(trim(fgets(STDIN)));
                                $ig->checkpoint->selectVerificationMethodForm($e->getResponse()->getChallengeUrl(), $verificationMethod);
                                break;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\VerifySMSCodeFormForSMSCaptchaException:
                                echo '[>] Insert verification code: ';
                                $smsCode = trim(fgets(STDIN));
                                $ig->checkpoint->sendWebFormSmsCode($e->getResponse()->getChallengeUrl(), $smsCode);
                                break 2;
                            case $e instanceof InstagramAPI\Exception\Checkpoint\UFACBlockingFormException:
                                echo "\e[91m[x]\e[0m Account on moderation";
                                exit;
                                break 2;
                            default:
                                throw new InstagramAPI\Exception\Checkpoint\UnknownChallengeStepException();
                        }
                    } catch (InstagramAPI\Exception\Checkpoint\ChallengeIterationsLimitException $ex) {
                        echo "\e[91m[x]\e[0m Account likely to be blocked.";
                        exit;
                    } catch (Exception $ex) {
                        $e = $ex;
                    }
                }
            }
        }

        return $ig;
    }

    /**
     * Logs a message in console but it actually uses new lines.
     *
     * @param string $message    message to be logged.
     * @param string $outputFile
     */
    public static function log(
        $message,
        $outputFile = '',
    ) {
        echo $message."\n";
        if ($outputFile !== '') {
            self::logOutput($message, $outputFile);
        }
    }
}

class ExtendedInstagram extends Instagram
{
    public function changeUser(
        $username,
        $password,
    ) {
        $this->_setUser('regular', $username, $password);
    }

    public function updateLoginState(
        $userId,
    ) {
        $this->isMaybeLoggedIn = true;
        $this->account_id = $userId;
        $this->settings->set('account_id', $this->account_id);
        $this->settings->set('last_login', time());
    }

    public function sendLoginFlow()
    {
        $this->_sendLoginFlow(true);
    }

    public function challengePublic(
        $response,
        $username,
        $password,
    ) {
        Utils::log('Suspicious Login: Please select your verification option by typing "sms" or "email" respectively. Otherwise press enter to abort.');
        $choice = Utils::promptInput();
        if ($choice === 'sms') {
            $verification_method = 0;
        } elseif ($choice === 'email') {
            $verification_method = 1;
        } else {
            Utils::log('Suspicious Login: Aborting!');
            exit(1);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $checkApiPath = trim(substr($response->getChallenge()->getApiPath(), 1));
        $customResponse = $this->request($checkApiPath)
            ->setNeedsAuth(false)
            ->addPost('choice', $verification_method)
            ->addPost('guid', $this->uuid)
            ->addPost('device_id', $this->device_id)
            ->addPost('_csrftoken', $this->client->getToken())
            ->getDecodedResponse();

        try {
            if ($customResponse['status'] === 'ok' && isset($customResponse['action']) && $customResponse['action'] === 'close') {
                Utils::log('Suspicious Login: Account challenge unsuccessful!');
                exit(1);
            }

            Utils::log('Suspicious Login: Please enter the code you received via '.($verification_method ? 'email' : 'sms').'...');
            $cCode = Utils::promptInput();
            $this->changeUser($username, $password);
            $response = $this->request($checkApiPath)
                ->setNeedsAuth(false)
                ->addPost('security_code', $cCode)
                ->addPost('guid', $this->uuid)
                ->addPost('device_id', $this->device_id)
                ->addPost('_csrftoken', $this->client->getToken())
                ->getDecodedResponse();
            if (!isset($response['logged_in_user']) || !isset($response['logged_in_user']['pk'])) {
                Utils::log('Suspicious Login: Checkpoint likely failed, re-run script.');
                exit(1);
            }
            $this->updateLoginState((string) $response['logged_in_user']['pk']);
            $this->sendLoginFlow();
            Utils::log('Suspicious Login: Attempted to bypass checkpoint, good luck!');
        } catch (Exception $ex) {
            Utils::log('Suspicious Login: Account Challenge Failed :(.');
            Utils::dump($ex->getMessage());
            exit(1);
        }
    }

    /**
     * This adds support for Instagram-API's private code subscription.
     *
     * @see https://github.com/mgp25/Instagram-API/issues/2655
     *
     * @param mixed $e
     * @param mixed $response
     * @param mixed $ig
     * @param mixed $username
     * @param mixed $password
     */
    public function challengePrivate(
        $e,
        $response,
        $ig,
        $username,
        $password,
    ) {
        $iterations = 0;
        $challenge = $response->getChallenge();
        if (!is_array($challenge)) {
            $checkApiPath = substr($challenge->getApiPath(), 1);
        } else {
            $checkApiPath = substr($challenge['api_path'], 1);
        }
        while (true) {
            try {
                if (++$iterations >= Request\Checkpoint::MAX_CHALLENGE_ITERATIONS) {
                    throw new InstagramAPI\Exception\Checkpoint\ChallengeIterationsLimitException();
                }
                switch (true) {
                    case $e instanceof InstagramAPI\Exception\Checkpoint\ChallengeRequiredException:
                        // Send a challenge request
                        $ig->checkpoint->sendChallenge($checkApiPath);
                        break;
                    case $e instanceof InstagramAPI\Exception\Checkpoint\EscalationInformationalException:
                        $ig->checkpoint->sendAcceptEscalationInformational($checkApiPath);
                        break;
                    case $e instanceof InstagramAPI\Exception\Checkpoint\SelectVerifyMethodException:
                        // If condition can be replaced by other logic. This will take always the phone number
                        // if it set, otherwise the email.
                        if ($e->getResponse()->getStepData()->getPhoneNumber() !== null) {
                            $method = 0;
                        } else {
                            $method = 1;
                        }
                        // requestVerificationCode() will request a verification code to your EMAIL or
                        // PHONE NUMBER. If you choose method 0, the code will be sent to your PHONE NUMBER.
                        // IF you choose method 1, the code will be sent to your EMAIL.
                        $ig->checkpoint->requestVerificationCode($checkApiPath, $method);
                        break;
                    case $e instanceof InstagramAPI\Exception\Checkpoint\VerifyCodeException:
                        // The "STDIN" lets you paste the code via terminal for testing.
                        // You should replace this line with the logic you want.
                        // The verification code will be sent by Instagram via SMS.
                        $code = trim(fgets(STDIN));
                        // `sendVerificationCode()` will send the received verification code from the previous step.
                        // If the checkpoint was bypassed, you will be able to do any other request normally.
                        $challenge = $ig->checkpoint->sendVerificationCode($checkApiPath, $code);
                        if ($challenge->getLoggedInUser() !== null) {
                            // If code was successfully verified, update login state and send login flow.
                            $ig->finishCheckpoint($challenge);
                            // Break switch and while loop.
                            break 2;
                        } elseif ($challenge->getAction() === 'close') {
                            break 2;
                        }
                        break;
                    case $e instanceof InstagramAPI\Exception\Checkpoint\SubmitPhoneException:
                        $phone = trim(fgets(STDIN));
                        // Set the phone number for verification.
                        $ig->checkpoint->sendVerificationPhone($checkApiPath, $phone);
                        break;
                    case $e instanceof InstagramAPI\Exception\Checkpoint\SubmitEmailException:
                        $email = trim(fgets(STDIN));
                        // Set the email for verification.
                        $ig->checkpoint->sendVerificationEmail($checkApiPath, $email);
                        break;
                    case $e instanceof InstagramAPI\Exception\Checkpoint\DeltaLoginReviewException:
                        $ig->checkpoint->requestVerificationCode($checkApiPath, 0);
                        break 2;
                    default:
                        throw new InstagramAPI\Exception\Checkpoint\UnknownChallengeStepException();
                }
            } catch (InstagramAPI\Exception\Checkpoint\ChallengeIterationsLimitException $ex) {
                if ($ig->isMaybeLoggedIn) {
                    if (($e->getResponse()->getStepData() === null) && ($e->getResponse()->getMessage() === 'challenge_required')) {
                        // At this point Instagram will show us a notice telling that the account has been compromised and requires
                        // to update the password in order to continue.
                        $response = $ig->checkpoint->getCompromisedPage($e->getResponse()->getChallenge()->getUrl());
                        if ($response['entry_data']['Challenge']['challengeType'] === 'EscalationChallengeInformationalForm') {
                            $ig->checkpoint->sendAcceptEscalationInformational($e->getResponse()->getChallenge()->getUrl());
                        } else {
                            $newPassword = trim(fgets(STDIN));
                            $ig->account->changePassword($password, $newPassword);
                        }
                        $ig->login($username, $newPassword);
                        break;
                    } else {
                        echo 'Iteration limit reached! Exiting...';
                        exit;
                    }
                } else {
                    throw new InstagramAPI\Exception\InstagramException('Account likely to be blocked.');
                }
            }
        }
    }
}
