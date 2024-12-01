<?php

namespace InstagramAPI;

/**
 * Instagram's Private API.
 *
 * TERMS OF USE:
 * - This code is in no way affiliated with, authorized, maintained, sponsored
 *   or endorsed by Instagram or any of its affiliates or subsidiaries. This is
 *   an independent and unofficial API. Use at your own risk.
 * - We do NOT support or tolerate anyone who wants to use this API to send spam
 *   or commit other online crimes.
 * - You will NOT use this API for marketing or other abusive purposes (spam,
 *   botting, harassment, massive bulk messaging...).
 *
 * @author mgp25: Founder, Reversing, Project Leader (https://github.com/mgp25)
 * @author SteveJobzniak (https://github.com/SteveJobzniak)
 */
class Instagram implements ExperimentsInterface
{
    /**
     * Experiments refresh interval in sec.
     *
     * @var int
     */
    public const EXPERIMENTS_REFRESH = 7200;

    /**
     * Currently active Instagram username.
     *
     * @var string
     */
    public $username;

    /**
     * Currently active Instagram password.
     *
     * @var string
     */
    public $password;

    /**
     * Currently active Facebook access token.
     *
     * @var string
     */
    public $fb_access_token;

    /**
     * The Android device for the currently active user.
     *
     * @var Devices\DeviceInterface
     */
    public $device;

    /**
     * Toggles API query/response debug output.
     *
     * @var bool
     */
    public $debug;

    /**
     * Monolog logger.
     *
     * @var LoggerInterface
     */
    public $logger;

    /**
     * Toggles truncating long responses when debugging.
     *
     * @var bool
     */
    public $truncatedDebug;

    /**
     * For internal use by Instagram-API developers!
     *
     * Toggles the throwing of exceptions whenever Instagram-API's "Response"
     * classes lack fields that were provided by the server. Useful for
     * discovering that our library classes need updating.
     *
     * This is only settable via this public property and is NOT meant for
     * end-users of this library. It is for contributing developers!
     *
     * @var bool
     */
    public $apiDeveloperDebug = false;

    /**
     * Global flag for users who want to run the library incorrectly online.
     *
     * YOU ENABLE THIS AT YOUR OWN RISK! WE GIVE _ZERO_ SUPPORT FOR THIS MODE!
     * EMBEDDING THE LIBRARY DIRECTLY IN A WEBPAGE (WITHOUT ANY INTERMEDIARY
     * PROTECTIVE LAYER) CAN CAUSE ALL KINDS OF DAMAGE AND DATA CORRUPTION!
     *
     * YOU HAVE BEEN WARNED. ANY DATA CORRUPTION YOU CAUSE IS YOUR OWN PROBLEM!
     *
     * The right way to run the library online is described in `webwarning.htm`.
     *
     * @var bool
     *
     * @see Instagram::__construct()
     */
    public static $allowDangerousWebUsageAtMyOwnRisk = false;

    /**
     * Global flag for users who want to run the library incorrectly.
     *
     * YOU ENABLE THIS AT YOUR OWN RISK! WE GIVE _ZERO_ SUPPORT FOR THIS MODE!
     * THIS WILL SKIP ANY PRE AND POST LOGIN FLOW!
     *
     * THIS SHOULD BE ONLY USED FOR RESEARCHING AND EXPERIMENTAL PURPOSES.
     *
     * YOU HAVE BEEN WARNED. ANY DATA CORRUPTION YOU CAUSE IS YOUR OWN PROBLEM!
     *
     * @var bool
     */
    public static $skipLoginFlowAtMyOwnRisk = false;

    /**
     * Global flag for users who want to run the library incorrectly.
     *
     * YOU ENABLE THIS AT YOUR OWN RISK! WE GIVE _ZERO_ SUPPORT FOR THIS MODE!
     * THIS WILL SKIP ANY PRE AND POST LOGIN FLOW!
     *
     * THIS SHOULD BE ONLY USED FOR RESEARCHING AND EXPERIMENTAL PURPOSES.
     *
     * YOU HAVE BEEN WARNED. ANY DATA CORRUPTION YOU CAUSE IS YOUR OWN PROBLEM!
     *
     * @var bool
     */
    public static $skipAccountValidation = false;

    /**
     * Global flag for users who want to run the library incorrectly.
     *
     * YOU ENABLE THIS AT YOUR OWN RISK! WE GIVE _ZERO_ SUPPORT FOR THIS MODE!
     * THIS WILL SKIP ANY PRE AND POST LOGIN FLOW!
     *
     * THIS SHOULD BE ONLY USED FOR RESEARCHING AND EXPERIMENTAL PURPOSES.
     *
     * YOU HAVE BEEN WARNED. ANY DATA CORRUPTION YOU CAUSE IS YOUR OWN PROBLEM!
     *
     * @var bool
     */
    public static $runLoginRegisterPush = false;

    /**
     * Global flag for users who want to manage login exceptions on their own.
     *
     * YOU ENABLE THIS AT YOUR OWN RISK! WE GIVE _ZERO_ SUPPORT FOR THIS MODE!
     *
     * @var bool
     *
     * @see Instagram::__construct()
     */
    public static $manuallyManageLoginException = false;

    /**
     * Global flag for users who want to run deprecated functions.
     *
     * YOU ENABLE THIS AT YOUR OWN RISK! WE GIVE _ZERO_ SUPPORT FOR THIS MODE!
     *
     * @var bool
     *
     * @see Instagram::__construct()
     */
    public static $overrideDeprecatedThrower = false;

    /**
     * Global flag for users who want to enable cURL debug.
     *
     * @var bool
     *
     * @see Instagram::__construct()
     */
    public static $curlDebug = false;

    /**
     * Retry on NetworkExpcetion.
     *
     * @var bool
     */
    public static $retryOnNetworkException = false;

    /**
     * Retry on NetworkExpcetion sleep.
     *
     * @var int
     */
    public static $retryOnNetworkExceptionSleep = 5;

    /**
     * Callbacks on ProxyHandler.
     *
     * @var array[]
     */
    public static $onProxyHandler = [];

    /**
     * Send async.
     *
     * @var bool
     */
    public static $sendAsync = false;

    /**
     * Disable login bloks.
     *
     * @var bool
     */
    public static $disableLoginBloks = false;

    /**
     * Override GoodDevices check.
     *
     * @var bool
     */
    public static $overrideGoodDevicesCheck = false;

    /**
     * Use bloks login.
     *
     * @var bool
     */
    public static $useBloksLogin = true;

    /**
     * Request promises.
     *
     * @var \GuzzleHttp\Promise\Promise
     */
    public $promises = [];

    /**
     * UUID.
     *
     * @var string
     */
    public $uuid;

    /**
     * Google Play Advertising ID.
     *
     * The advertising ID is a unique ID for advertising, provided by Google
     * Play services for use in Google Play apps. Used by Instagram.
     *
     * @var string
     *
     * @see https://support.google.com/googleplay/android-developer/answer/6048248?hl=en
     */
    public $advertising_id;

    /**
     * Device ID.
     *
     * @var string
     */
    public $device_id;

    /**
     * Phone ID.
     *
     * @var string
     */
    public $phone_id;

    /**
     * Numerical UserPK ID of the active user account.
     *
     * @var string
     */
    public $account_id;

    /**
     * Our current guess about the session status.
     *
     * This contains our current GUESS about whether the current user is still
     * logged in. There is NO GUARANTEE that we're still logged in. For example,
     * the server may have invalidated our current session due to the account
     * password being changed AFTER our last login happened (which kills all
     * existing sessions for that account), or the session may have expired
     * naturally due to not being used for a long time, and so on...
     *
     * NOTE TO USERS: The only way to know for sure if you're logged in is to
     * try a request. If it throws a `LoginRequiredException`, then you're not
     * logged in anymore. The `login()` function will always ensure that your
     * current session is valid. But AFTER that, anything can happen... It's up
     * to Instagram, and we have NO control over what they do with your session!
     *
     * @var bool
     */
    public $isMaybeLoggedIn = false;

    /**
     * Raw API communication/networking class.
     *
     * @var Client
     */
    public $client;

    /**
     * Bloks class.
     *
     * @var Bloks
     */
    public $bloks;

    /**
     * The account settings storage.
     *
     * @var Settings\StorageHandler|null
     */
    public $settings;

    /**
     * The current application session ID.
     *
     * This is a temporary ID which changes in the official app every time the
     * user closes and re-opens the Instagram application or switches account.
     *
     * @var string
     */
    public $session_id;

    /**
     * A list of experiments enabled on per-account basis.
     *
     * @var array
     */
    public $experiments;

    /**
     * Custom Device string.
     *
     * @var string|null
     */
    public $customDeviceString;

    /**
     * Custom filter for selecting devices.
     *
     * @var callable|null
     */
    public $deviceFilter;

    /**
     * Custom Device string.
     *
     * @var string|null
     */
    public $customDeviceId;

    /**
     * Version Code.
     *
     * @var string
     */
    public $versionCode;

    /**
     * Login attempt counter.
     *
     * @var int
     */
    public $loginAttemptCount = 1;

    /**
     * Custom pigeon session id.
     *
     * @var string
     */
    public $customPigeonSessionId;

    /**
     * The radio type used for requests.
     *
     * @var array
     */
    public $radio_type = 'wifi-none';

    /**
     * Timezone offset.
     *
     * @var int
     */
    public $timezoneOffset;

    /**
     * Timezone name.
     *
     * @var string
     */
    public $timezoneName;

    /**
     * The platform used for requests.
     *
     * @var string
     */
    public $platform;

    /**
     * Connection speed.
     *
     * @var string
     */
    public $connectionSpeed = '-1kbps';

    /**
     * EU user.
     *
     * @var bool
     */
    public $isEUUser = true;

    /**
     * Battery level.
     *
     * @var int
     */
    public $batteryLevel = 100;

    /**
     * Sound enabled.
     *
     * @var bool
     */
    public $soundEnabled = false;

    /**
     * Camera enabled.
     *
     * @var bool
     */
    public $cameraEnabled = false;

    /**
     * Device charging.
     *
     * @var bool
     */
    public $isDeviceCharging = true;

    /**
     * Locale.
     *
     * @var string
     */
    public $locale = '';

    /**
     * Accept language.
     *
     * @var string
     */
    public $acceptLanguage = '';

    /**
     * Accept language.
     *
     * @var string|null
     */
    public $appStartupCountry;

    /**
     * Event batch collection.
     *
     * @var array
     */
    public $eventBatch = [
        [], // less common
        [], // android strings and other events will fit here
        [], // Most of the events will go here
    ];

    /**
     * Batch index.
     *
     * @var int
     */
    public $batchIndex = 0;

    /**
     * Navigation sequence.
     *
     * @var int
     */
    public $navigationSequence = 0;

    /**
     * Web user agent.
     *
     * @var string|null
     */
    public $webUserAgent;

    /**
     * Logging events compression mode.
     *
     * 0 - Compressed. Event as file
     * 1 - Uncompressed. Multi batch
     * 2 - Compressed. Multi batch/single batch
     *
     * @var int
     */
    public $eventsCompressedMode = 2;

    /**
     * iOS Model.
     *
     * @var string|null
     */
    public $iosModel;

    /**
     * Dark mode enabled.
     *
     * @var bool
     */
    public $darkModeEnabled = false;

    /**
     * Low data enabled.
     *
     * @var bool
     */
    public $lowDataModeEnabled = false;

    /**
     * iOS DPI.
     *
     * @var string|null
     */
    public $iosDpi;

    /**
     * Navigation chain.
     *
     * @var string
     */
    public $navChain = '';

    /**
     * Navigation chain step.
     *
     * @var int
     */
    public $navChainStep = 1;

    /**
     * Previous navigation chain class.
     *
     * @var string
     */
    public $prevNavChainClass = '';

    /**
     * Disable auto retries in media upload.
     *
     * USE IT UNDER YOUR OWN RISK.
     *
     * @var bool
     */
    public $disableAutoRetriesMediaUpload = false;

    /**
     * Login Waterfall ID.
     *
     * @var string
     */
    public $loginWaterfallId = '';

    /**
     * Carrier.
     *
     * @var string
     */
    public $carrier = 'Android';

    /**
     * Enable resolution check.
     *
     * @var bool
     */
    public $enableResolutionCheck = false;

    /**
     * Bypass call list.
     *
     * @var string[]
     */
    public $bypassCalls = [];

    /**
     * Custom resolver.
     *
     * @var callable
     */
    public $customResolver;

    /**
     * Number of retries to be made when retry
     * on network failure is enabled.
     *
     * @var int
     */
    public $retriesOnNetworkFailure = 3;

    /**
     * Gyroscope enabled.
     *
     * @var bool
     */
    public $gyroscopeEnabled = true;

    /**
     * Background enabled.
     *
     * @var bool
     */
    public $background = false;

    /**
     * Given consent.
     *
     * @var bool
     */
    public $givenConsent = true;

    /**
     * Device init state enabled.
     *
     * @var bool
     */
    public $devicecInitState = false;

    /**
     * Is sessionless.
     *
     * @var bool
     */
    public $isSessionless = false;

    /**
     * CDN RMD.
     *
     * @var bool
     */
    public $cdn_rmd = false;

    /**
     * Is login flow.
     *
     * @var bool
     */
    public $isLoginFlow = false;

    /**
     * Bloks info.
     *
     * @var array
     */
    public $bloksInfo = [];

    /**
     * TimelineFeed object from Login flow.
     *
     * @var TimelineFeedResponse|null
     */
    public $initTimelineFeed;

    /**
     * ReelsTrayFeed object from Login flow.
     *
     * @var ReelsTrayFeedResponse|null
     */
    public $initTrayFeed;

    /**
     * Middle forward proxy.
     *
     * @var string|null
     */
    public $middleForwardProxy;

    /**
     * Middle proxy key.
     *
     * @var string
     */
    public $middleProxyKey = 'D3f4ult#k3y';

    /** @var Request\Account Collection of Account related functions. */
    public $account;
    /** @var Request\Business Collection of Business related functions. */
    public $business;
    /** @var Request\Checkpoint Collection of Checkpoint related functions. */
    public $checkpoint;
    /** @var Request\Collection Collection of Collections related functions. */
    public $collection;
    /** @var Request\Creative Collection of Creative related functions. */
    public $creative;
    /** @var Request\Direct Collection of Direct related functions. */
    public $direct;
    /** @var Request\Discover Collection of Discover related functions. */
    public $discover;
    /** @var Request\Event Collection of Event related functions. */
    public $event;
    /** @var Request\Hashtag Collection of Hashtag related functions. */
    public $hashtag;
    /** @var Request\Highlight Collection of Highlight related functions. */
    public $highlight;
    /** @var Request\TV Collection of Instagram TV functions. */
    public $tv;
    /** @var Request\Internal Collection of Internal (non-public) functions. */
    public $internal;
    /** @var Request\Live Collection of Live related functions. */
    public $live;
    /** @var Request\Location Collection of Location related functions. */
    public $location;
    /** @var Request\Media Collection of Media related functions. */
    public $media;
    /** @var Request\Music Collection of Music related functions. */
    public $music;
    /** @var Request\People Collection of People related functions. */
    public $people;
    /** @var Request\Push Collection of Push related functions. */
    public $push;
    /** @var Request\Reel Collection of Reel related functions. */
    public $reel;
    /** @var Request\Shopping Collection of Shopping related functions. */
    public $shopping;
    /** @var Request\Story Collection of Story related functions. */
    public $story;
    /** @var Request\Timeline Collection of Timeline related functions. */
    public $timeline;
    /** @var Request\Usertag Collection of Usertag related functions. */
    public $usertag;
    /** @var Request\Web Collection of Web related functions. */
    public $web;

    /**
     * Constructor.
     *
     * @param bool            $debug          Show API queries and responses.
     * @param bool            $truncatedDebug Truncate long responses in debug.
     * @param array           $storageConfig  Configuration for the desired
     *                                        user settings storage backend.
     * @param bool            $platform       The platform to be emulated. 'android' or 'ios'.
     * @param LoggerInterface $logger         Custom logger interface.
     *
     * @throws Exception\InstagramException
     */
    public function __construct(
        $debug = false,
        $truncatedDebug = false,
        array $storageConfig = [],
        $platform = 'android',
        $logger = null
    ) {
        if ($platform !== 'android' && $platform !== 'ios') {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid platform.', $platform));
        } else {
            $this->platform = $platform;
        }

        // Disable incorrect web usage by default. People should never embed
        // this application emulator library directly in a webpage, or they
        // might cause all kinds of damage and data corruption. They should
        // use an intermediary layer such as a database or a permanent process!
        // NOTE: People can disable this safety via the flag at their own risk.
        if (!self::$allowDangerousWebUsageAtMyOwnRisk && (!defined('PHP_SAPI') || PHP_SAPI !== 'cli')) {
            // IMPORTANT: We do NOT throw any exception here for users who are
            // running the library via a webpage. Many webservers are configured
            // to hide all PHP errors, and would just give the user a totally
            // blank webpage with "Error 500" if we throw here, which would just
            // confuse the newbies even more. Instead, we output a HTML warning
            // message for people who try to run the library on a webpage.
            echo file_get_contents(__DIR__.'/../webwarning.htm');
            echo '<p>If you truly want to enable <em>incorrect</em> website usage by directly embedding this application emulator library in your page, then you can do that <strong>AT YOUR OWN RISK</strong> by setting the following flag <em>before</em> you create the <code>Instagram()</code> object:</p>'.PHP_EOL;
            echo '<p><code>\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;</code></p>'.PHP_EOL;
            exit(0); // Exit without error to avoid triggering Error 500.
        }

        // Prevent people from running this library on ancient PHP versions, and
        // verify that people have the most critically important PHP extensions.
        // NOTE: All of these are marked as requirements in composer.json, but
        // some people install the library at home and then move it somewhere
        // else without the requirements, and then blame us for their errors.
        if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50600) {
            throw new Exception\InternalException(
                'You must have PHP 5.6 or higher to use the Instagram API library.'
            );
        }
        static $extensions = ['curl', 'mbstring', 'gd', 'exif', 'zlib'];
        foreach ($extensions as $ext) {
            if (!@extension_loaded($ext)) {
                throw new Exception\InternalException(sprintf(
                    'You must have the "%s" PHP extension to use the Instagram API library.',
                    $ext
                ));
            }
        }

        // Debugging options.
        $this->debug = $debug;
        $this->truncatedDebug = $truncatedDebug;
        $this->logger = $logger;

        // Load all function collections.
        $this->account = new Request\Account($this);
        $this->business = new Request\Business($this);
        $this->checkpoint = new Request\Checkpoint($this);
        $this->collection = new Request\Collection($this);
        $this->creative = new Request\Creative($this);
        $this->direct = new Request\Direct($this);
        $this->discover = new Request\Discover($this);
        $this->event = new Request\Event($this);
        $this->hashtag = new Request\Hashtag($this);
        $this->highlight = new Request\Highlight($this);
        $this->tv = new Request\TV($this);
        $this->internal = new Request\Internal($this);
        $this->live = new Request\Live($this);
        $this->location = new Request\Location($this);
        $this->media = new Request\Media($this);
        $this->music = new Request\Music($this);
        $this->people = new Request\People($this);
        $this->push = new Request\Push($this);
        $this->reel = new Request\Reel($this);
        $this->shopping = new Request\Shopping($this);
        $this->story = new Request\Story($this);
        $this->timeline = new Request\Timeline($this);
        $this->usertag = new Request\Usertag($this);
        $this->web = new Request\Web($this);

        // Configure the settings storage and network client.
        $self = $this;
        $this->settings = Settings\Factory::createHandler(
            $storageConfig,
            [
                // This saves all user session cookies "in bulk" at script exit
                // or when switching to a different user, so that it only needs
                // to write cookies to storage a few times per user session:
                'onCloseUser' => function ($storage) use ($self) {
                    if ($self->client instanceof Client) {
                        $self->client->saveCookieJar();
                    }
                },
            ]
        );
        $this->client = new Client($this);
        $this->bloks = new Bloks();
        $this->experiments = [];
    }

    /**
     * Controls the SSL verification behavior of the Client.
     *
     * @see http://docs.guzzlephp.org/en/latest/request-options.html#verify
     *
     * @param bool|string $state TRUE to verify using PHP's default CA bundle,
     *                           FALSE to disable SSL verification (this is
     *                           insecure!), String to verify using this path to
     *                           a custom CA bundle file.
     */
    public function setVerifySSL(
        $state
    ) {
        $this->client->setVerifySSL($state);
    }

    /**
     * Gets the current SSL verification behavior of the Client.
     *
     * @return bool|string
     */
    public function getVerifySSL()
    {
        return $this->client->getVerifySSL();
    }

    /**
     * Set the proxy to use for requests.
     *
     * @see http://docs.guzzlephp.org/en/latest/request-options.html#proxy
     *
     * @param string|array|null $value String or Array specifying a proxy in
     *                                 Guzzle format, or NULL to disable
     *                                 proxying.
     */
    public function setProxy(
        $value
    ) {
        $this->client->setProxy($value);
    }

    /**
     * Gets the current proxy used for requests.
     *
     * @return string|array|null
     */
    public function getProxy()
    {
        return $this->client->getProxy();
    }

    /**
     * Set the proxy to use for middle proxy.
     *
     * @param string|null $value String.
     */
    public function setMiddleForwardProxy(
        $value
    ) {
        $this->middleForwardProxy = $value;
    }

    /**
     * Gets the current key used for middle proxy.
     *
     * @return string
     */
    public function getMiddleProxyKey()
    {
        return $this->middleProxyKey;
    }

    /**
     * Set the key to use for middle proxy.
     *
     * @param string $value String.
     */
    public function setMiddleProxyKey(
        $value
    ) {
        $this->middleProxyKey = $value;
    }

    /**
     * Gets the current proxy used for middle proxy.
     *
     * @return string|null
     */
    public function getMiddleForwardProxy()
    {
        return $this->middleForwardProxy;
    }

    /**
     * Set custom resolver.
     *
     * @param callable $value.
     */
    public function setCustomResolver(
        $value
    ) {
        $this->customResolver = $value;
    }

    /**
     * Set number of retries to make on
     * network failure.
     *
     * @param int $value.
     */
    public function setRetriesOnNetworkFailure(
        $value
    ) {
        $this->retriesOnNetworkFailure = $value;
    }

    /**
     * Set the host to resolve.
     *
     * @see https://curl.haxx.se/libcurl/c/CURLOPT_RESOLVE.html
     *
     * @param string|null $value String specifying the host used for resolving.
     */
    public function setResolveHost(
        $value
    ) {
        $this->client->setResolveHost($value);
    }

    /**
     * Gets the current resolving host.
     *
     * @return string|null
     */
    public function getResolveHost()
    {
        return $this->client->getResolveHost();
    }

    /**
     * Set a custom device string.
     *
     * If the provided device string is not valid, a device from
     * the good devices list will be chosen randomly.
     *
     * @param string|null $value Device string.
     */
    public function setDeviceString(
        $value
    ) {
        $this->customDeviceString = $value;
    }

    /**
     * Set a custom list if device string.
     *
     * A random deviece string will be picked from the provided list.
     * If the provided device string is not valid, a device from
     * the good devices list will be chosen randomly.
     *
     * @param string[]|null $value Device string.
     */
    public function setCustomDevices(
        $value
    ) {
        if (is_array($value)) {
            $deviceString = $value[array_rand($value)];
            $this->customDeviceString = is_string($deviceString) ? $deviceString : null;
        }
    }

    /**
     * Set a custom device ID.
     *
     * @param string|null $value Device string.
     */
    public function setCustomDeviceId(
        $value
    ) {
        $this->customDeviceId = $value;
    }

    /**
     * Set version code.
     *
     * If the provided version code is not valid, the default version code
     * will be chosen.
     *
     * @param string $value
     * @param bool   $random A random version code will be chosen if set to true.
     */
    public function setVersionCode(
        $value,
        $random = false
    ) {
        if ($random === true) {
            $versionCode = Constants::VERSION_CODE[array_rand(Constants::VERSION_CODE)];
        } else {
            $versionCode = (!in_array($value, Constants::VERSION_CODE)) ? Constants::VERSION_CODE[array_rand(Constants::VERSION_CODE)] : $value;
        }
        $this->versionCode = $versionCode;
    }

    /**
     * Get version code.
     *
     * @return string Version Code.
     */
    public function getVersionCode()
    {
        return $this->versionCode;
    }

    /**
     * Sets the network interface override to use.
     *
     * Only works if Guzzle is using the cURL backend. But that's
     * almost always the case, on most PHP installations.
     *
     * @see http://php.net/curl_setopt CURLOPT_INTERFACE
     *
     * @param string|null $value Interface name, IP address or hostname, or NULL
     *                           to disable override and let Guzzle use any
     *                           interface.
     */
    public function setOutputInterface(
        $value
    ) {
        $this->client->setOutputInterface($value);
    }

    /**
     * Gets the current network interface override used for requests.
     *
     * @return string|null
     */
    public function getOutputInterface()
    {
        return $this->client->getOutputInterface();
    }

    /**
     * Set custom pigeon session ID.
     *
     * WARNING: Do NOT use this function unless you know
     * what you are doing.
     *
     * @param string $value custom pigeon session id.
     */
    public function setCustomPigeonSessionId(
        $value
    ) {
        $this->customPigeonSessionId = $value;
    }

    /**
     * Return custom pigeon session ID.
     */
    public function getCustomPigeonSessionId()
    {
        return $this->customPigeonSessionId;
    }

    /**
     * Set the radio type used for requests.
     *
     * @param string $value String specifying the radio type.
     */
    public function setRadioType(
        $value
    ) {
        if ($value !== 'wifi-none' && $value !== 'mobile-lte') {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid radio type.', $value));
        }

        $this->radio_type = $value;
    }

    /**
     * Get the radio type used for requests.
     *
     * @return string
     */
    public function getRadioType()
    {
        return $this->radio_type;
    }

    /**
     * Get the connection type based on radio type.
     *
     * @param string $platform Platform.
     *
     * @return string
     */
    public function getConnectionType(
        $platform = 'ig'
    ) {
        if ($this->getRadioType() === 'wifi-none') {
            return Constants::X_IG_Connection_Type; // Default
        } else {
            return ($platform === 'ig') ? 'MOBILE(LTE)' : 'MOBILE.LTE';
        }
    }

    /**
     * Set the timezone offset.
     *
     * @param int $value Timezone offset.
     */
    public function setTimezoneOffset(
        $value
    ) {
        $this->timezoneOffset = $value;
    }

    /**
     * Get timezone offset.
     *
     * @return string
     */
    public function getTimezoneOffset()
    {
        if ($this->getTimezoneName() !== null) {
            try {
                $datetime = new \DateTime('now', new \DateTimeZone($this->getTimezoneName()));

                return $datetime->getOffset();
            } catch (\Exception $e) {
                return null;
            }
        } else {
            return $this->timezoneOffset;
        }
    }

    /**
     * Set the timezone name.
     *
     * @param string $value Timezone name.
     */
    public function setTimezoneName(
        $value
    ) {
        $this->timezoneName = $value;
    }

    /**
     * Get timezone name.
     *
     * @return string
     */
    public function getTimezoneName()
    {
        return $this->timezoneName;
    }

    /**
     * Set locale.
     *
     * @param string|string[] $value
     */
    public function setLocale(
        $value
    ) {
        if (!is_array($value)) {
            $value = [$value];
        }

        $matches = preg_grep('/^[a-z]{2}_[A-Z]{2}$/', $value);

        if (!empty($matches)) {
            $this->locale = implode(', ', $matches);
        } else {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid locale.', $value));
        }
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        if ($this->locale === '') {
            return Constants::USER_AGENT_LOCALE;
        } else {
            return $this->locale;
        }
    }

    /**
     * Set accept Language.
     *
     * @param string|string[] $value
     */
    public function setAcceptLanguage(
        $value
    ) {
        if (!is_array($value)) {
            $value = [$value];
        }

        $matches = preg_grep('/^[a-z]{2}-[A-Z]{2}$/', $value);

        if (!empty($matches)) {
            $this->acceptLanguage = implode(', ', $matches);
        } else {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid accept language value.', $value));
        }
    }

    /**
     * Get Accept Language.
     *
     * @return string
     */
    public function getAcceptLanguage()
    {
        if ($this->acceptLanguage === '') {
            return Constants::ACCEPT_LANGUAGE;
        } else {
            return $this->acceptLanguage;
        }
    }

    /**
     * Set app startup country.
     *
     * @param string|null
     * @param mixed $value
     */
    public function setAppStartupCountry(
        $value
    ) {
        if (preg_match_all('/^[A-Z]{2}$/m', $value, $matches)) {
            $this->appStartupCountry = $matches[0][0];
        } else {
            throw new \InvalidArgumentException('Not a valid app startup country value.');
        }
    }

    /**
     * Get app startup country.
     *
     * @return string
     */
    public function getAppStartupCountry()
    {
        return $this->appStartupCountry;
    }

    /**
     * Get the platform used for requests.
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Check if running on Android platform.
     *
     * @return string
     */
    public function getIsAndroid()
    {
        return $this->platform === 'android';
    }

    /**
     * Check if using an android session.
     *
     * @return bool
     */
    public function getIsAndroidSession()
    {
        if (strpos($this->settings->get('device_id'), 'android') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the connection speed.
     *
     * @return string
     */
    public function getConnectionSpeed()
    {
        return $this->connectionSpeed;
    }

    /**
     * Set the connection speed.
     *
     * @param string $value Connection Speed. Format: '53kbps'.
     */
    public function setConnectionSpeed(
        $value
    ) {
        $this->connectionSpeed = $value;
    }

    /**
     * Get if user is in EU.
     *
     * @return bool
     */
    public function getIsEUUser()
    {
        return $this->isEUUser;
    }

    /**
     * Set if user is from EU.
     *
     * @param bool $value. 'true' or 'false'
     */
    public function setIsEUUser(
        $value
    ) {
        $this->isEUUser = $value;
    }

    /**
     * Get battery level.
     *
     * @return int
     */
    public function getBatteryLevel()
    {
        return $this->batteryLevel;
    }

    /**
     * Set battery level.
     *
     * @param int $value.
     */
    public function setBatteryLevel(
        $value
    ) {
        if ($value < 1 && $value > 100) {
            throw new \InvalidArgumentException(sprintf('"%d" is not a valid battery level.', $value));
        }

        $this->batteryLevel = $value;
    }

    /**
     * Get sound enabled.
     *
     * @return int
     */
    public function getSoundEnabled()
    {
        return intval($this->soundEnabled);
    }

    /**
     * Set sound enabled.
     *
     * @param bool $value.
     */
    public function setSoundEnabled(
        $value
    ) {
        $this->soundEnabled = $value;
    }

    /**
     * Get camera enabled.
     *
     * @return int
     */
    public function getCameraEnabled()
    {
        return intval($this->cameraEnabled);
    }

    /**
     * Set camera enabled.
     *
     * @param bool $value.
     */
    public function setCameraEnabled(
        $value
    ) {
        $this->cameraEnabled = $value;
    }

    /**
     * Get if device is charging.
     *
     * @return string
     */
    public function getIsDeviceCharging()
    {
        return intval($this->isDeviceCharging);
    }

    /**
     * Set battery level.
     *
     * @param bool $value.
     */
    public function setIsDeviceCharging(
        $value
    ) {
        $this->isDeviceCharging = $value;
    }

    /**
     * Set Web User Agent.
     *
     * @param string $value.
     */
    public function setWebUserAgent(
        $value
    ) {
        $this->webUserAgent = $value;
    }

    /**
     * Get Web User Agent.
     *
     * @return string
     */
    public function getWebUserAgent()
    {
        return ($this->webUserAgent === null) ? Constants::WEB_USER_AGENT : $this->webUserAgent;
    }

    /**
     * Get if device is VP9 compatible.
     *
     * @return bool
     */
    public function getIsVP9Compatible()
    {
        return $this->device->getIsVP9Compatible();
    }

    /**
     * Get logging events compressed mode.
     *
     * @return int
     */
    public function getEventsCompressedMode()
    {
        return $this->eventsCompressedMode;
    }

    /**
     * Set iOS Model.
     *
     * @param string $device iOS device model.
     */
    public function setIosModel(
        $device
    ) {
        Utils::checkIsValidiDevice($device);
        $this->iosModel = $device;
    }

    /**
     * Get iOS Model.
     *
     * @return string
     */
    public function getIosModel()
    {
        return $this->iosModel;
    }

    /**
     * Set iOS DPI.
     *
     * @param string $value.
     */
    public function setIosDpi(
        $value
    ) {
        $this->iosDpi = $value;
    }

    /**
     * Get iOS DPI.
     *
     * @return string
     */
    public function getIosDpi()
    {
        return $this->iosDpi;
    }

    /**
     * Get is dark mode enabled.
     *
     * @return bool
     */
    public function getIsDarkModeEnabled()
    {
        return $this->darkModeEnabled;
    }

    /**
     * Set is dark mode enabled.
     *
     * @param bool $value
     */
    public function setIsDarkModeEnabled(
        $value
    ) {
        $this->darkModeEnabled = $value;
    }

    /**
     * Get low data mode enabled.
     *
     * @return bool
     */
    public function getIsLowDataModeEnabled()
    {
        return $this->lowDataModeEnabled;
    }

    /**
     * Set low data mode enabled.
     *
     * @param bool $value
     */
    public function setIsLowDataModeEnabled(
        $value
    ) {
        $this->lowDataModeEnabled = $value;
    }

    /**
     * Get navigation chain.
     *
     * @return string
     */
    public function getNavChain()
    {
        return $this->navChain;
    }

    /**
     * Set navigation chain.
     *
     * @param mixed $value
     */
    public function setNavChain(
        $value
    ) {
        if ($value === '') {
            $this->navChain = '';
        } else {
            $this->navChain .= $value;
        }
    }

    /**
     * Get navigation chain step.
     *
     * @return int
     */
    public function getNavChainStep()
    {
        return $this->navChainStep;
    }

    /**
     * Set navigation chain step.
     *
     * @param mixed $value
     */
    public function setNavChainStep(
        $value
    ) {
        $this->navChainStep = $value;
    }

    /**
     * Increment navigation chain step.
     */
    public function incrementNavChainStep()
    {
        $this->navChainStep++;
    }

    /**
     * Decrement navigation chain step.
     */
    public function decrementNavChainStep()
    {
        $this->navChainStep--;
    }

    /**
     * Get previous navigation chain class.
     *
     * @return string
     */
    public function getPrevNavChainClass()
    {
        return $this->prevNavChainClass;
    }

    /**
     * Set next previous navigation chain class.
     *
     * @param string $value
     */
    public function setPrevNavChainClass(
        $value
    ) {
        $this->prevNavChainClass = $value;
    }

    /**
     * Disable auto retries media upload.
     *
     * @param bool $value
     */
    public function disableAutoRetriesMediaUpload(
        $value
    ) {
        $this->disableAutoRetriesMediaUpload = $value;
    }

    /**
     * Get auto disable retries media upload.
     *
     * @return bool
     */
    public function getIsDisabledAutoRetriesMediaUpload()
    {
        return $this->disableAutoRetriesMediaUpload;
    }

    /**
     * Getcarrier.
     *
     * @return string
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * Set carrier.
     *
     * @param string $value
     */
    public function setCarrier(
        $value
    ) {
        $this->carrier = $value;
    }

    /**
     * Set gyroscope enabled.
     *
     * @param bool $value
     */
    public function setGyroscopeEnabled(
        $value
    ) {
        $this->gyroscopeEnabled = boolval($value);
    }

    /**
     * Get gyroscope enabled.
     */
    public function getGyroscopeEnabled()
    {
        return $this->gyroscopeEnabled;
    }

    /**
     * Set background state.
     *
     * @param bool $value
     */
    public function setBackgroundState(
        $value
    ) {
        $this->background = boolval($value);
    }

    /**
     * Get background state.
     */
    public function getBackgroundState()
    {
        return $this->background ? 'true' : 'false';
    }

    /**
     * Set device init state.
     *
     * @param bool $value
     */
    public function setDeviceInitState(
        $value
    ) {
        $this->devicecInitState = boolval($value);
    }

    /**
     * Get device init state.
     */
    public function getDeviceInitState()
    {
        return $this->devicecInitState;
    }

    /**
     * Set given consent.
     *
     * @param bool $value
     */
    public function setGivenConsent(
        $value
    ) {
        $this->givenConsent = boolval($value);
    }

    /**
     * Get given consent.
     */
    public function getGivenConsent()
    {
        return $this->givenConsent;
    }

    /**
     * Enable resolution check.
     *
     * @param string $value
     */
    public function enableResolutionCheck(
        $value
    ) {
        $this->enableResolutionCheck = $value;
    }

    /**
     * Bypasses calls.
     *
     * It skips specified api endpoints. Some IPs refuses certain endpoint
     * causing a logged_out error. By identifying the endpoint causing the logged_out,
     * you can use this function to skip them.
     *
     * @param string|string[] $endpoints Endpoints.
     */
    public function bypassCalls(
        $endpoints
    ) {
        if (!is_array($endpoints)) {
            $endpoints = [$endpoints];
        }

        $this->bypassCalls = $endpoints;
    }

    /**
     * Set user Guzzle Options.
     *
     * @param array
     * @param mixed $options
     */
    public function setUserGuzzleOptions(
        $options
    ) {
        $this->client = new Client($this, $options);
    }

    /**
     * Login to Instagram or automatically resume and refresh previous session.
     *
     * Sets the active account for the class instance. You can call this
     * multiple times to switch between multiple Instagram accounts.
     *
     * WARNING: You MUST run this function EVERY time your script runs! It
     * handles automatic session resume and relogin and app session state
     * refresh and other absolutely *vital* things that are important if you
     * don't want to be banned from Instagram!
     *
     * WARNING: This function MAY return a CHALLENGE telling you that the
     * account needs two-factor login before letting you log in! Read the
     * two-factor login example to see how to handle that.
     *
     * @param string      $username           Your Instagram username.
     *                                        You can also use your email or phone,
     *                                        but take in mind that they won't work
     *                                        when you have two factor auth enabled.
     * @param string      $password           Your Instagram password.
     * @param int         $appRefreshInterval How frequently `login()` should act
     *                                        like an Instagram app that's been
     *                                        closed and reopened and needs to
     *                                        "refresh its state", by asking for
     *                                        extended account state details.
     *                                        Default: After `1800` seconds, meaning
     *                                        `30` minutes after the last
     *                                        state-refreshing `login()` call.
     *                                        This CANNOT be longer than `6` hours.
     *                                        Read `_sendLoginFlow()`! The shorter
     *                                        your delay is the BETTER. You may even
     *                                        want to set it to an even LOWER value
     *                                        than the default 30 minutes!
     * @param string|null $deletionToken      Deletion token. Stop account deletion.
     * @param bool        $loggedOut          If account was forced to log out.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse|null A login response if a
     *                                                   full (re-)login
     *                                                   happens, otherwise
     *                                                   `NULL` if an existing
     *                                                   session is resumed.
     */
    public function login(
        $username,
        $password,
        $appRefreshInterval = 1800,
        $deletionToken = null,
        $loggedOut = false
    ) {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('You must provide a username and password to login().');
        }

        return $this->_login($username, $password, false, $appRefreshInterval, $deletionToken, $loggedOut);
    }

    /**
     * Login to Instagram with Facebook or automatically resume and refresh previous session.
     *
     * Sets the active account for the class instance. You can call this
     * multiple times to switch between multiple Instagram accounts.
     *
     * WARNING: You MUST run this function EVERY time your script runs! It
     * handles automatic session resume and relogin and app session state
     * refresh and other absolutely *vital* things that are important if you
     * don't want to be banned from Instagram!
     *
     * WARNING: This function MAY return a CHALLENGE telling you that the
     * account needs two-factor login before letting you log in! Read the
     * two-factor login example to see how to handle that.
     *
     * @param string $username           Your Instagram username.
     * @param string $fbAccessToken      Your Facebook access token.
     * @param int    $appRefreshInterval How frequently `loginWithFacebook()` should act
     *                                   like an Instagram app that's been
     *                                   closed and reopened and needs to
     *                                   "refresh its state", by asking for
     *                                   extended account state details.
     *                                   Default: After `1800` seconds, meaning
     *                                   `30` minutes after the last
     *                                   state-refreshing `login()` call.
     *                                   This CANNOT be longer than `6` hours.
     *                                   Read `_sendLoginFlow()`! The shorter
     *                                   your delay is the BETTER. You may even
     *                                   want to set it to an even LOWER value
     *                                   than the default 30 minutes!
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse|null A login response if a
     *                                                   full (re-)login
     *                                                   happens, otherwise
     *                                                   `NULL` if an existing
     *                                                   session is resumed.
     */
    public function loginWithFacebook(
        $username,
        $fbAccessToken,
        $appRefreshInterval = 1800
    ) {
        if (empty($username) || empty($fbAccessToken)) {
            throw new \InvalidArgumentException('You must provide a Facebook access token to loginWithFacebook().');
        }

        return $this->_loginWithFacebook($username, $fbAccessToken, false, $appRefreshInterval);
    }

    /**
     * Login to Instagram with email link.
     *
     * Sets the active account for the class instance. You can call this
     * multiple times to switch between multiple Instagram accounts.
     *
     * @param string $username           Your Instagram username.
     * @param string $link               Login link.
     * @param int    $appRefreshInterval How frequently `loginWithFacebook()` should act
     *                                   like an Instagram app that's been
     *                                   closed and reopened and needs to
     *                                   "refresh its state", by asking for
     *                                   extended account state details.
     *                                   Default: After `1800` seconds, meaning
     *                                   `30` minutes after the last
     *                                   state-refreshing `login()` call.
     *                                   This CANNOT be longer than `6` hours.
     *                                   Read `_sendLoginFlow()`! The shorter
     *                                   your delay is the BETTER. You may even
     *                                   want to set it to an even LOWER value
     *                                   than the default 30 minutes!
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse|null A login response if a
     *                                                   full (re-)login
     *                                                   happens, otherwise
     *                                                   `NULL` if an existing
     *                                                   session is resumed.
     */
    public function loginWithEmailLink(
        $username,
        $link,
        $appRefreshInterval = 1800
    ) {
        if (empty($username) || empty($link)) {
            throw new \InvalidArgumentException('You must provide a link to loginWithEmailLink().');
        }

        return $this->_loginWithEmailLink($username, $link, false, $appRefreshInterval);
    }

    /**
     * Internal login handler.
     *
     * @param string      $username
     * @param string      $password
     * @param bool        $forceLogin         Force login to Instagram instead of
     *                                        resuming previous session. Used
     *                                        internally to do a new, full relogin
     *                                        when we detect an expired/invalid
     *                                        previous session.
     * @param int         $appRefreshInterval
     * @param string|null $deletionToken      Deletion token. Stop account deletion.
     * @param bool        $loggedOut          If account was forced to log out.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse|null
     *
     * @see Instagram::login() The public login handler with a full description.
     */
    protected function _login(
        $username,
        $password,
        $forceLogin = false,
        $appRefreshInterval = 1800,
        $deletionToken = null,
        $loggedOut = false
    ) {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('You must provide a username and password to _login().');
        }

        // Switch the currently active user/pass if the details are different.
        if ($this->username !== $username || $this->password !== $password) {
            $this->_setUser('regular', $username, $password);

            if ($this->settings->get('pending_events') !== null) {
                $this->eventBatch = json_decode($this->settings->get('pending_events'), true);
                $this->settings->set('pending_events', '');
            }
        }

        $waterfallId = Signatures::generateUUID();
        $this->loginWaterfallId = $waterfallId;
        $startTime = round(microtime(true) * 1000);

        if ($loggedOut === false) {
            $this->event->sendInstagramInstallWithReferrer($this->loginWaterfallId, 0);
            $this->event->sendInstagramInstallWithReferrer($this->loginWaterfallId, 1);
            $this->event->sendFlowSteps('landing', 'step_view_loaded', $waterfallId, $startTime);
            $this->event->sendFlowSteps('landing', 'landing_created', $waterfallId, $startTime);
            $this->event->sendPhoneId($waterfallId, $startTime, 'request');
        }

        // Perform a full relogin if necessary.
        if (!$this->isMaybeLoggedIn || $forceLogin) {
            if ($this->loginAttemptCount === 1 && !self::$skipLoginFlowAtMyOwnRisk && !$loggedOut) {
                $this->_sendPreLoginFlow();
            }

            if ($loggedOut === false) {
                // THIS IS NOT USED ANYMORE IN BLOKS LOGIN
                if (self::$useBloksLogin === false) {
                    $mobileConfigResponse = $this->internal->getMobileConfig(true)->getHttpResponse();
                    $this->settings->set('public_key', $mobileConfigResponse->getHeaderLine('ig-set-password-encryption-pub-key'));
                    $this->settings->set('public_key_id', $mobileConfigResponse->getHeaderLine('ig-set-password-encryption-key-id'));
                }

                $this->event->sendStringImpressions(['2131231876' => 1, '2131231882' => 1, '2131886885' => 2, '2131887195' => 1, '2131887196' => 1, '2131888193' => 4, '2131888472' => 1, '2131890367' => 1, '2131891325' => 1, '2131892179' => 1, '2131892669' => 1, '2131892673' => 1, '2131893765' => 1, '2131893766' => 1, '2131893767' => 1, '2131893768' => 1, '2131893769' => 1, '2131893770' => 1, '2131893771' => 1, '2131893772' => 1, '2131893773' => 1, '2131893774' => 1, '2131893775' => 1, '2131893776' => 1, '2131893777' => 1, '2131893778' => 1, '2131893779' => 1, '2131893780' => 1, '2131893781' => 1, '2131893782' => 1, '2131893783' => 1, '2131893784' => 1, '2131893785' => 1, '2131893788' => 1, '2131893789' => 1, '2131893790' => 1, '2131893791' => 2, '2131893792' => 1, '2131893793' => 1, '2131893806' => 1, '2131893898' => 1, '2131894010' => 1, '2131894018' => 1, '2131896911' => 1, '2131898165' => 1]);
                $this->event->sendFlowSteps('login', 'log_in_username_focus', $waterfallId, $startTime);
                $this->event->sendFlowSteps('login', 'log_in_password_focus', $waterfallId, $startTime);
                $this->event->sendFlowSteps('login', 'log_in_attempt', $waterfallId, $startTime);
                $this->event->sendFlowSteps('login', 'sim_card_state', $waterfallId, $startTime);
                $this->event->sendStringImpressions(['17039371' => 2, '17039886' => 2, '17040255' => 1, '17040256' => 1, '17040257' => 1, '17040645' => 1, '2131232017' => 1, '2131232109' => 1, '2131232164' => 1, '2131232213' => 1, '2131232214' => 1, '2131232227' => 1, '2131232228' => 1, '2131232373' => 1, '2131232374' => 1, '2131232482' => 1, '2131232484' => 1, '2131232609' => 1, '2131232621' => 1, '2131886419' => 1, '2131886885' => 4, '2131887050' => 1, '2131887411' => 1, '2131888159' => 1, '2131890407' => 1, '2131890652' => 1, '2131891238' => 1, '2131891283' => 1, '2131892179' => 1, '2131892675' => 3, '2131892749' => 1, '2131892925' => 3, '2131893604' => 1, '2131894671' => 1, '2131894713' => 2, '2131895369' => 1, '2131895744' => 1, '2131896364' => 1, '2131898082' => 1, '2131898155' => 1]);
                $this->event->sendStringImpressions(['2131231967' => 1, '2131232008' => 1, '2131232152' => 1, '2131232466' => 1, '2131232682' => 1, '2131886420' => 1, '2131886709' => 1, '2131887421' => 1, '2131888159' => 1, '2131889830' => 1, '2131890107' => 5, '2131890302' => 1, '2131890652' => 1, '2131890810' => 4, '2131890813' => 4, '2131892646' => 1, '2131892913' => 1, '2131893117' => 3, '2131893560' => 1, '2131893562' => 1, '2131893668' => 1, '2131893810' => 1, '2131893811' => 1, '2131894468' => 1, '2131896103' => 1, '2131896230' => 1, '2131896432' => 2, '2131896577' => 1, '2131897080' => 3, '2131897172' => 3, '2131897229' => 1, '2131897678' => 2]);

                $this->event->sendAttributionSdkDebug([
                    'event_name'    => 'report_events',
                    'event_types'   => '[LOGIN]',
                ]);
                $this->event->sendAttributionSdkDebug([
                    'event_name'    => 'report_events_compliant',
                    'event_types'   => '[LOGIN]',
                ]);

                $this->event->sendFxSsoLibrary('auth_token_write_start', null, 'log_in');
                $this->event->sendFxSsoLibrary('auth_token_write_failure', 'provider_not_found', 'log_in');
                $this->event->sendFxSsoLibrary('auth_token_write_start', null, 'log_in');
                $this->event->sendFxSsoLibrary('auth_token_write_failure', 'provider_not_found', 'log_in');
            }

            if (PHP_SAPI === 'cli' && $this->getMiddleForwardProxy() === null && self::$runLoginRegisterPush) {
                $loop = \React\EventLoop\Factory::create();
                $loop->addPeriodicTimer(0.5, function () use ($loop) {
                    if ($this->settings->get('fbns_token') !== null) {
                        $loop->stop();
                    }
                });
                // $logger = new \Monolog\Logger('push');
                // $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::INFO));
                $push = new Push($loop, $this, null, false);
                $push->start();
                $loop->run();
            }

            if (self::$useBloksLogin) {
                // $this->loginAttemptCount = 1;
                // $response = $this->getHomeTemplate();
                $response = $this->processLoginClientDataAndRedirect();
                $re = '/(\d+),\s(\d+),\s\\\\"(com[a-zA-Z0-9\._]+)\\\\"/m';
                preg_match_all($re, $response->asJson(), $matches, PREG_SET_ORDER, 0);

                $internalLatencyData = [];
                foreach ($matches as $entry) {
                    $markerId = intval($entry[1]);
                    $latencyId = intval($entry[2]);
                    $controller = $entry[3];

                    $internalLatencyData[$controller] = [
                        'marker_id'   => $markerId,
                        'instance_id' => $latencyId,
                    ];
                }

                $responseArr = $response->asArray();
                $mainBloks = $this->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
                $firstDataBlok = null;
                $firstDataBlokBack = null;
                $secondDataBlok = null;
                $thirdDataBlok = null;
                $fourthDataBlock = null;
                foreach ($mainBloks as $mainBlok) {
                    if (str_contains($mainBlok, 'INTERNAL__latency_qpl_instance_id') && str_contains($mainBlok, 'INTERNAL__latency_qpl_marker_id')) {
                        $firstDataBlok = $mainBlok;
                    }
                    if (str_contains($mainBlok, 'ar_event_source') && str_contains($mainBlok, 'event_step')) {
                        $firstDataBlokBack = $mainBlok;
                    }
                    if (str_contains($mainBlok, 'typeahead_id') && str_contains($mainBlok, 'text_input_id') && str_contains($mainBlok, 'text_component_id')) {
                        $secondDataBlok = $mainBlok;
                    }
                    if (str_contains($mainBlok, 'INTERNAL_INFRA_screen_id')) {
                        $thirdDataBlok = $mainBlok;
                    }
                    if (str_contains($mainBlok, 'context_data')) {
                        $fourthDataBlock = $mainBlok;
                    }
                    if ($firstDataBlok !== null && $secondDataBlok !== null && $loggedOut === false) {
                        break;
                    } elseif ($firstDataBlok !== null && $secondDataBlok !== null && $thirdDataBlok !== null) {
                        break;
                    }
                }

                $mainBloks = $this->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
                foreach ($mainBloks as $mainBlok) {
                    if (str_contains($mainBlok, 'should_trigger_override_login_2fa_action')) {
                        $paramBlok = $mainBlok;

                        $parsed = $this->bloks->parseBlok($paramBlok, 'bk.action.map.Make');
                        $offsets = array_slice($this->bloks->findOffsets($parsed, 'should_trigger_override_login_2fa_action'), 0, -2);

                        foreach ($offsets as $offset) {
                            if (isset($parsed[$offset])) {
                                $parsed = $parsed[$offset];
                            } else {
                                break;
                            }
                        }

                        $serverMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
                    }
                    if (str_contains($mainBlok, 'should_show_nested_nta_from_aymh')) {
                        $paramBlok = $mainBlok;

                        $parsed = $this->bloks->parseBlok($paramBlok, 'bk.action.map.Make');
                        $offsets = array_slice($this->bloks->findOffsets($parsed, 'should_show_nested_nta_from_aymh'), 0, -2);

                        foreach ($offsets as $offset) {
                            if (isset($parsed[$offset])) {
                                $parsed = $parsed[$offset];
                            } else {
                                break;
                            }
                        }

                        $clientMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
                    }
                }

                /*
                if ($firstDataBlok === null) {
                    $firstDataBlok = $firstDataBlokBack;
                    $this->bloksInfo['INTERNAL__latency_qpl_instance_id'] = [0,0];
                    $this->bloksInfo['INTERNAL__latency_qpl_marker_id'] = [0,0];
                    $this->bloksInfo['INTERNAL_INFRA_THEME'] = 'HARMONIZATION_F';
                } else {
                    $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
                    $offsets = array_slice($this->bloks->findOffsets($parsed, 'INTERNAL__latency_qpl_instance_id'), 0, -2);

                    foreach ($offsets as $offset) {
                        if (isset($parsed[$offset])) {
                            $parsed = $parsed[$offset];
                        } else {
                            break;
                        }
                    }

                    $firstMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
                    $this->bloksInfo = array_merge($firstMap, $this->bloksInfo);
                }
                */

                /*
                if ($firstDataBlok === null) {
                    $this->isMaybeLoggedIn = false;
                    $this->settings->set('mid', '');
                    $this->settings->set('rur', '');
                    $this->settings->set('www_claim', '');
                    $this->settings->set('account_id', '');
                    $this->settings->set('authorization_header', 'Bearer IGT:2:'); // Header won't be added into request until a new authorization is obtained.
                    $this->account_id = null;

                    throw new \InstagramAPI\Exception\AccountStateException('Try login again.');
                }
                */

                if ($secondDataBlok !== null) {
                    $parsed = $this->bloks->parseBlok($secondDataBlok, 'bk.action.map.Make');
                    $offsets = array_slice($this->bloks->findOffsets($parsed, 'text_input_id'), 0, -2);

                    foreach ($offsets as $offset) {
                        if (isset($parsed[$offset])) {
                            $parsed = $parsed[$offset];
                        } else {
                            break;
                        }
                    }

                    $secondMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
                    $this->bloksInfo = array_merge($secondMap, $this->bloksInfo);
                }

                if ($thirdDataBlok !== null) {
                    $parsed = $this->bloks->parseBlok($thirdDataBlok, 'bk.action.map.Make');
                    $offsets = array_slice($this->bloks->findOffsets($parsed, 'INTERNAL_INFRA_screen_id'), 0, -2);

                    foreach ($offsets as $offset) {
                        if (isset($parsed[$offset])) {
                            $parsed = $parsed[$offset];
                        } else {
                            break;
                        }
                    }

                    $thirdMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
                    $this->bloksInfo = array_merge($thirdMap, $this->bloksInfo);
                }

                if ($fourthDataBlock !== null) {
                    $parsed = $this->bloks->parseBlok($fourthDataBlock, 'bk.action.map.Make');
                    $offsets = array_slice($this->bloks->findOffsets($parsed, 'context_data'), 0, -2);

                    foreach ($offsets as $offset) {
                        if (isset($parsed[$offset])) {
                            $parsed = $parsed[$offset];
                        } else {
                            break;
                        }
                    }

                    $fourthMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
                    $this->bloksInfo = array_merge($fourthMap, $this->bloksInfo);
                }

                $this->loginOauthTokenFetch($mainBloks, $internalLatencyData);

                if ($loggedOut === false) {
                    $response = $this->sendLoginTextInputTypeAhead($username, $mainBloks, $internalLatencyData);
                } else {
                    $response = $this->getLoginPasswordEntry();
                }

                if ($loggedOut === false) {
                    $accountList = [];
                } else {
                    $accountList = [
                        [
                            'uid'               => $this->account_id,
                            'credential_type'   => 'none',
                            'token'             => '',
                        ],
                    ];
                }

                $this->event->sendNavigation('button', 'com.bloks.www.caa.login.login_homepage', 'com.bloks.www.caa.login.login_homepage');
                $this->event->sendPasswordEncryptionAttempt();

                if ($this->apiDeveloperDebug) {
                    $usedClientParams = [
                        'should_show_nested_nta_from_aymh',
                        'device_id',
                        'sim_phones',
                        'login_attempt_count',
                        'secure_family_device_id',
                        'machine_id',
                        'accounts_list',
                        'auth_secure_device_id',
                        'has_whatsapp_installed',
                        'password',
                        'sso_token_map_json_string',
                        'family_device_id',
                        'fb_ig_device_id',
                        'device_emails',
                        'try_num',
                        'lois_settings',
                        'event_flow',
                        'event_step',
                        'headers_infra_flow_id',
                        'openid_tokens',
                        'client_known_key_hash',
                        'contact_point',
                        'encrypted_msisdn',
                    ];

                    Utils::checkArrayKeyDifferences(array_keys($clientMap), $usedClientParams);
                }

                $response = $this->request('bloks/apps/com.bloks.www.bloks.caa.login.async.send_login_request/')
                    ->setNeedsAuth(false)
                    ->setSignedPost(false)
                    ->addHeader('X-Ig-Attest-Params', '{"attestation":[{"version":2,"type":"keystore","errors":[-1004],"challenge_nonce":"","signed_nonce":"","key_hash":""}]}')
                    ->addPost('params', json_encode([
                        'client_input_params'           => [
                            'should_show_nested_nta_from_aymh'  => 0,
                            'device_id'                         => $this->device_id,
                            'sim_phones'                        => [],
                            'login_attempt_count'               => $this->loginAttemptCount,
                            'secure_family_device_id'           => '',
                            'machine_id'                        => $this->settings->get('mid'),
                            'accounts_list'                     => $accountList,
                            'auth_secure_device_id'             => '',
                            'has_whatsapp_installed'            => 0,
                            'password'                          => Utils::encryptPassword($password, '', '', true), // Encrypt password with default key and type 1.
                            'sso_token_map_json_string'         => '',
                            'family_device_id'                  => $this->phone_id,
                            'fb_ig_device_id'                   => [],
                            'device_emails'                     => [],
                            'try_num'                           => $this->loginAttemptCount,
                            'lois_settings'                     => [
                                'lara_override' => '',
                                'lois_token'    => '',
                            ],
                            'event_flow'                    => ($loggedOut === false) ? 'login_manual' : 'aymh',
                            'event_step'                    => 'home_page',
                            'headers_infra_flow_id'         => '',
                            'openid_tokens'                 => (object) [],
                            'client_known_key_hash'         => '',
                            'contact_point'                 => $username,
                            'encrypted_msisdn'              => '',
                        ],
                        'server_params'         => [
                            'should_trigger_override_login_2fa_action'      => intval($serverMap['should_trigger_override_login_2fa_action'] ?? 0),
                            'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? $loggedOut),
                            'should_trigger_override_login_success_action'  => intval($serverMap['should_trigger_override_login_success_action'] ?? 0),
                            'login_credential_type'                         => $serverMap['login_credential_type'] ?? 'none',
                            'server_login_source'                           => $serverMap['server_login_source'] ?? 'login',
                            'waterfall_id'                                  => $serverMap['waterfall_id'] ?? $waterfallId, // $firstMap['waterfall_id'],
                            'login_source'                                  => $serverMap['login_source'] ?? 'Login',
                            'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                            'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.login.async.send_login_request']['marker_id']),
                            'offline_experiment_group'                      => $serverMap['offline_experiment_group'] ?? 'caa_iteration_v3_perf_ig_4',
                            'is_from_landing_page'                          => intval($serverMap['is_from_landing_page'] ?? 0),
                            'password_text_input_id'                        => $serverMap['password_text_input_id'] ?? '',
                            'is_from_empty_password'                        => intval($serverMap['is_from_empty_password'] ?? 0),
                            'is_from_msplit_fallback'                       => intval($serverMap['is_from_msplit_fallback'] ?? 0),
                            'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->uuid,
                            'ar_event_source'                               => $serverMap['ar_event_source'] ?? 'login_home_page',
                            'username_text_input_id'                        => $serverMap['username_text_input_id'] ?? '',
                            'layered_homepage_experiment_group'             => $serverMap['layered_homepage_experiment_group'] ?? null,
                            'device_id'                                     => $serverMap['device_id'] ?? $this->device_id,
                            'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.login.async.send_login_request']['instance_id'],
                            'reg_flow_source'                               => $serverMap['reg_flow_source'] ?? 'login_home_native_integration_point', // cacheable_aymh_screen
                            'is_caa_perf_enabled'                           => intval($serverMap['is_caa_perf_enabled'] ?? 0),
                            'credential_type'                               => $serverMap['credential_type'] ?? 'password',
                            'is_from_password_entry_page'                   => intval($serverMap['is_from_password_entry_page'] ?? 0),
                            'caller'                                        => $serverMap['caller'] ?? 'gslr',
                            'family_device_id'                              => null, // $this->phone_id,
                            // 'INTERNAL_INFRA_THEME'                          => $serverMap['INTERNAL_INFRA_THEME'] ?? 'harm_f',
                            'is_from_assistive_id'                          => intval($serverMap['is_from_assistive_id'] ?? 0),
                            'access_flow_version'                           => $serverMap['access_flow_version'] ?? 'F2_FLOW',
                            'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                        ],
                    ]))
                    ->addPost('bk_client_context', json_encode([
                        'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                        'styles_id'     => 'instagram',
                    ]))
                    ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
                    ->getResponse(new Response\LoginResponse());

                $mainBloks = $this->bloks->parseResponse($response->asArray(), '(bk.action.caa.HandleLoginResponse');

                $firstDataBlok = null;
                foreach ($mainBloks as $mainBlok) {
                    if (str_contains($mainBlok, 'logged_in_user')) {
                        $firstDataBlok = $mainBlok;
                        break;
                    }
                }

                if ($firstDataBlok !== null) {
                    $loginResponseWithHeaders = $this->bloks->parseBlok($firstDataBlok, 'bk.action.caa.HandleLoginResponse');
                } else {
                    // $loginResponseWithHeaders = $this->bloks->parseBlok(json_encode($response->asArray()['layout']['bloks_payload']['tree']), 'bk.action.caa.HandleLoginResponse');
                    $loginResponseWithHeaders = $this->bloks->parseBlok(json_encode($response->asArray()['layout']['bloks_payload']), 'bk.action.caa.HandleLoginResponse');
                }

                $errorMap = [];
                if (is_array($loginResponseWithHeaders)) {
                    $errorMap = $this->_parseLoginErrors($loginResponseWithHeaders, $response);

                    $re = '/(com\.bloks\.www\.two_factor_login\.\w+)/m';
                    preg_match_all($re, $response->asJson(), $firstMatch, PREG_SET_ORDER, 0);
                    $re = '/(?!.*\bcode_entry_help\b)(com\.bloks\.www(\.ap)?\.two_step_verification\.\w+)/m';
                    preg_match_all($re, $response->asJson(), $secondMatch, PREG_SET_ORDER, 0);
                    if ($firstMatch || $secondMatch) {
                        $endpoint = empty($firstMatch) ? $secondMatch[0][1] : $firstMatch[0][1];
                        $responseArr = $response->asArray();
                        $mainBloks = $this->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');

                        foreach ($mainBloks as $mainBlok) {
                            if (str_contains($mainBlok, 'two_step_verification_context') && str_contains($mainBlok, 'flow_source')) {
                                $firstDataBlok = $mainBlok;
                                break;
                            }
                        }

                        if ($firstDataBlok !== null) {
                            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
                            $offsets = array_slice($this->bloks->findOffsets($parsed, 'two_step_verification_context'), 0, -2);
                        } else {
                            foreach ($mainBloks as $mainBlok) {
                                if (str_contains($mainBlok, 'context_data') || (str_contains($mainBlok, 'generic_code_entry') || str_contains($mainBlok, 'method_picker'))) {
                                    $firstDataBlok = $mainBlok;
                                    break;
                                }
                            }
                            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
                            $offsets = array_slice($this->bloks->findOffsets($parsed, 'context_data'), 0, -2);
                        }

                        foreach ($offsets as $offset) {
                            if (isset($parsed[$offset])) {
                                $parsed = $parsed[$offset];
                            } else {
                                break;
                            }
                        }

                        $twoFactorMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
                        $this->bloksInfo = array_merge($this->bloksInfo, $twoFactorMap);

                        // 2FA Bloks
                        $response = $this->getTwoFactorBloksScreen($endpoint);

                        $responseArr = $response->asArray();
                        $mainBloks = $this->bloks->parseResponse($responseArr, 'bk.action.map.Make');

                        if (isset($this->bloksInfo['INTERNAL_INFRA_screen_id']) && ($this->bloksInfo['INTERNAL_INFRA_screen_id'] === 'generic_code_entry' || isset($this->bloksInfo['context_data']) || $this->bloksInfo['INTERNAL_INFRA_screen_id'] === 'method_picker')) {
                            foreach ($mainBloks as $mainBlok) {
                                if (str_contains($mainBlok, 'context_data')) {
                                    $firstDataBlok = $mainBlok;
                                    break;
                                }
                            }

                            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
                            $offsets = array_slice($this->bloks->findOffsets($parsed, 'context_data'), 0, -2);
                        } else {
                            foreach ($mainBloks as $mainBlok) {
                                if (str_contains($mainBlok, 'flow_source') && str_contains($mainBlok, 'two_step_verification_context')) {
                                    $firstDataBlok = $mainBlok;
                                    break;
                                }
                            }

                            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
                            $offsets = array_slice($this->bloks->findOffsets($parsed, 'two_step_verification_context'), 0, -2);
                        }

                        foreach ($offsets as $offset) {
                            if (isset($parsed[$offset])) {
                                $parsed = $parsed[$offset];
                            } else {
                                break;
                            }
                        }

                        $twoFactorMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
                        $this->bloksInfo = array_merge($this->bloksInfo, $twoFactorMap);

                        $twoFactorResponse = [
                            'two_factor_context'    => $twoFactorMap['two_step_verification_context'] ?? $twoFactorMap['context_data'],
                            'two_factor_required'   => true,
                            'is_bloks'              => true,
                            'is_generic'            => isset($twoFactorMap['context_data']) && str_contains($response->asJson(), 'generic_code_entry'),
                            'verification_picker'   => str_contains($response->asJson(), 'com.bloks.www.ap.two_step_verification.challenge_picker'),
                        ];

                        if ($endpoint === 'com.bloks.www.two_step_verification.entrypoint' || $endpoint === 'com.bloks.www.ap.two_step_verification.entrypoint_async') {
                            if (isset($this->bloksInfo['challenge'])) {
                                $challenge = $this->bloksInfo['challenge'];
                            } else {
                                if (str_contains($response->asJson(), 'Check your notifications')) {
                                    $challenge = 'notification';
                                } else {
                                    $challenge = 'unknown';
                                }
                            }
                            $twoFactorResponse['two_factor_challenge'] = $challenge;

                            return new Response\LoginResponse($twoFactorResponse);
                        } else {
                            switch ($endpoint) {
                                case 'com.bloks.www.two_factor_login.enter_totp_code':
                                case 'com.bloks.www.two_step_verification.enter_totp_code':
                                    $twoFactorResponse['two_factor_challenge'] = 'totp';

                                    return new Response\LoginResponse($twoFactorResponse);
                                    break;
                                case 'com.bloks.www.two_factor_login.enter_backup_code':
                                case 'com.bloks.www.two_step_verification.enter_backup_code':
                                    $twoFactorResponse['two_factor_challenge'] = 'backup_codes';

                                    return new Response\LoginResponse($twoFactorResponse);
                                    break;
                                case 'com.bloks.www.two_factor_login.enter_sms_code':
                                case 'com.bloks.www.two_step_verification.enter_sms_code':
                                    $twoFactorResponse['two_factor_challenge'] = 'sms';
                                    $twoFactorResponse['masked_cp'] = $this->bloksInfo['masked_cp'];

                                    return new Response\LoginResponse($twoFactorResponse);
                                    break;
                                case 'com.bloks.www.two_factor_login.enter_email_code':
                                case 'com.bloks.www.two_step_verification.enter_email_code':
                                    $twoFactorResponse['two_factor_challenge'] = 'email';

                                    return new Response\LoginResponse($twoFactorResponse);
                                    break;
                                case 'com.bloks.www.two_factor_login.enter_whatsapp_code':
                                case 'com.bloks.www.two_step_verification.enter_whatsapp_code':
                                    $twoFactorResponse['two_factor_challenge'] = 'whatsapp';

                                    return new Response\LoginResponse($twoFactorResponse);
                                    break;
                                case 'com.bloks.www.ap.two_step_verification.code_entry':
                                    $twoFactorResponse['two_factor_challenge'] = 'generic_code_entry';

                                    return new Response\LoginResponse($twoFactorResponse);
                                    break;
                                default:
                                    throw new Exception\InstagramException('Two factor method not implemented yet.');
                            }
                        }
                    }
                }
                $twoFactorResponse = $this->_throwLoginException($response, $errorMap);
                if ($twoFactorResponse !== null) {
                    return $twoFactorResponse;
                }
                $loginResponse = $this->_processSuccesfulLoginResponse($loginResponseWithHeaders, $appRefreshInterval, false);
            } else {
                try {
                    $request = $this->request('accounts/login/')
                        ->setNeedsAuth(false)
                        ->addPost('jazoest', Utils::generateJazoest($this->phone_id))
                        ->addPost('device_id', $this->device_id)
                        ->addPost('username', $this->username)
                        ->addPost('enc_password', Utils::encryptPassword($password, $this->settings->get('public_key_id'), $this->settings->get('public_key')))
                        // ->addPost('_csrftoken', $this->client->getToken())
                        ->addPost('phone_id', $this->phone_id)
                        ->addPost('adid', $this->advertising_id)
                        ->addPost('login_attempt_count', $this->loginAttemptCount);

                    if ($deletionToken !== null) {
                        $request->addPost('stop_deletion_token', $deletionToken);
                    }

                    if ($this->getPlatform() === 'android') {
                        $request->addPost('country_codes', json_encode(
                            [
                                [
                                    'country_code' => Utils::getCountryCode(explode('_', $this->getLocale())[1]),
                                    'source'       => [
                                        'default',
                                    ],
                                ],
                            ]
                        ))
                            ->addPost('guid', $this->uuid)
                            ->addPost('google_tokens', '[]');
                    } elseif ($this->getPlatform() === 'ios') {
                        $request->addPost('reg_login', '0');
                    }
                    $loginResponse = $request->getResponse(new Response\LoginResponse());
                    if ($loginResponse->getLoggedInUser()->getIsBusiness() !== null) {
                        $this->settings->set('business_account', $loginResponse->getLoggedInUser()->getIsBusiness());
                    }
                } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                    // Login failed because checkpoint is required.
                    // Return server response to tell user they to bypass checkpoint.
                    throw $e;
                } catch (Exception\InstagramException $e) {
                    if ($e->hasResponse() && $e->getResponse()->isTwoFactorRequired()) {
                        // Login failed because two-factor login is required.
                        // Return server response to tell user they need 2-factor.
                        return $e->getResponse();
                    } elseif ($e->hasResponse() && ($e->getResponse()->getInvalidCredentials() === true)) {
                        $this->loginAttemptCount++;

                        throw $e;
                    } else {
                        if ($e->getResponse() === null) {
                            throw new Exception\NetworkException($e);
                        }

                        // Login failed for some other reason... Re-throw error.
                        throw $e;
                    }
                }

                if ($loginResponse->getLoggedInUser()->getUsername() === 'Instagram User') {
                    throw new Exception\AccountDisabledException('Account has been suspended.');
                }
            }

            /*
            try {
                $this->account->getAccountsMultiLogin($response->getMacLoginNonce());
            } catch (\InstagramAPI\Exception\InstagramException $e) {
                //pass
            }
            */

            $this->event->sendFlowSteps('login', 'log_in', $waterfallId, $startTime);
            $this->event->pushNotificationSettings();
            $this->event->enableNotificationSettings([
                'ig_product_announcements', 'ig_friends_on_instagram', 'ig_likes', 'ig_other', 'ig_photos_of_you', 'ig_private_user_follow_request', 'ig_igtv_video_updates', 'ig_mentions_in_bio', 'ig_direct', 'uploads', 'ig_reminders', 'ig_direct_requests', 'ig_igtv_recommended_videos', 'ig_new_followers', 'ig_likes_and_comments_on_photos_of_you', 'ig_first_posts_and_stories', 'ig_comment_likes', 'ig_live_videos', 'ig_comments', 'ig_shopping_drops', 'ig_view_counts', 'ig_direct_video_chat', 'ig_posting_status',
            ]);
            $this->event->sendAttributionSdkDebug([
                'event_name'    => 'get_compliance_action_success',
                'message'       => 'REPORT',
                'event_types'   => '[LOGIN]',
            ]);

            $this->event->sendNavigationTabImpression(1);
            $this->event->sendScreenshotDetector();
            $this->event->sendNavigationTabImpression(0);
            $this->loginAttemptCount = 0;
            $this->_updateLoginState($loginResponse);

            $this->_sendLoginFlow(true, $appRefreshInterval);

            // Full (re-)login successfully completed. Return server response.
            return $loginResponse;
        }

        // Attempt to resume an existing session, or full re-login if necessary.
        // NOTE: The "return" here gives a LoginResponse in case of re-login.
        return $this->_sendLoginFlow(false, $appRefreshInterval);
    }

    /**
     * Process login client data and redirect.
     *
     * @param bool $isLoggedOut If states comes from logged_out.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function processLoginClientDataAndRedirect(
        $isLoggedOut = false
    ) {
        if ($isLoggedOut) {
            $accountList = [
                [
                    'uid'               => $this->account_id,
                    'credential_type'   => 'none',
                    'token'             => '',
                ],
            ];
        } else {
            $accountList = [];
        }

        return $this->request('bloks/apps/com.bloks.www.bloks.caa.login.process_client_data_and_redirect/')
        ->setNeedsAuth(false)
        ->setSignedPost(false)
        ->addPost('params', json_encode([
            'is_from_logged_out'            => $isLoggedOut,
            'logged_out_user'               => '',
            'qpl_join_id'                   => Signatures::generateUUID(),
            'family_device_id'              => $this->phone_id,
            'device_id'                     => $this->device_id,
            'offline_experiment_group'      => $this->settings->get('offline_experiment'),
            'waterfall_id'                  => $this->loginWaterfallId,
            'show_internal_settings'        => false,
            'last_auto_login_time'          => 0,
            'disable_auto_login'            => false,
            'qe_device_id'                  => $this->uuid,
            'is_from_logged_in_switcher'    => false,
            'switcher_logged_in_uid'        => '',
            'account_list'                  => $accountList,
            'blocked_uid'                   => [],
            'launched_url'                  => '',
            'INTERNAL_INFRA_THEME'          => 'HARMONIZATION_F',
        ]))
        ->addPost('bk_client_context', json_encode([
            'bloks_version' => Constants::BLOCK_VERSIONING_ID,
            'styles_id'     => 'instagram',
        ]))
        ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
        ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get home template.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getHomeTemplate()
    {
        return $this->request('bloks/apps/com.bloks.www.caa.login.home_template/')
        ->setNeedsAuth(false)
        ->setSignedPost(false)
        ->addPost('server_params', json_encode([
            'qe_device_id_server'      => $this->uuid,
            'family_device_id_server'  => '',
            'device_id_server'         => $this->device_id,
        ]))
        ->addPost('bk_client_context', json_encode([
            'bloks_version' => Constants::BLOCK_VERSIONING_ID,
            'styles_id'     => 'instagram',
        ]))
        ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
        ->getResponse(new Response\GenericResponse());
    }

    /**
     * Login no click form controller.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function loginNoClickFormController()
    {
        return $this->request('bloks/apps/com.bloks.www.bloks.caa.login.form.no.click.async.controller/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'lois_settings'                     => [
                        'lara_override' => '',
                        'lois_token'    => '',
                    ],
                    'no_visit_count'    => 1,
                ],
                'server_params'         => [
                    'is_from_logged_out'                            => 0,
                    'layered_homepage_experiment_group'             => null,
                    'device_id'                                     => $this->device_id,
                    'waterfall_id'                                  => null,
                    'machine_id'                                    => null,
                    'INTERNAL__latency_qpl_instance_id'             => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
                    'is_platform_login'                             => 0,
                    'INTERNAL__latency_qpl_marker_id'               => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
                    'family_device_id'                              => null,
                    'offline_experiment_group'                      => null,
                    'INTERNAL_INFRA_THEME'                          => 'harm_f',
                    'access_flow_version'                           => 'F2_FLOW',
                    'is_from_logged_in_switcher'                    => 0,
                    'qe_device_id'                                  => $this->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\LoginResponse());
    }

    /**
     * Login OAuth token fetch.
     *
     * @param array $mainBloks
     * @param array $internalLatencyData
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function loginOauthTokenFetch(
        $mainBloks,
        $internalLatencyData
    ) {
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'com.bloks.www.caa.login.oauth.token.fetch.async')) {
                $paramBlok = $mainBlok;

                $parsed = $this->bloks->parseBlok($paramBlok, 'bk.action.map.Make');
                $offsets = array_slice($this->bloks->findOffsets($parsed, 'layered_homepage_experiment_group'), 0, -2);

                foreach ($offsets as $offset) {
                    if (isset($parsed[$offset])) {
                        $parsed = $parsed[$offset];
                    } else {
                        break;
                    }
                }

                $serverMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            }
        }

        return $this->request('bloks/apps/com.bloks.www.caa.login.oauth.token.fetch.async/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'username_input'                    => '',
                    'lois_settings'                     => [
                        'lara_override' => '',
                        'lois_token'    => '',
                    ],
                ],
                'server_params'         => [
                    'is_from_logged_out'                            => 0,
                    'layered_homepage_experiment_group'             => null,
                    'device_id'                                     => $serverMap['device_id'] ?? $this->device_id,
                    'waterfall_id'                                  => $serverMap['waterfall_id'] ?? $this->loginWaterfallId,
                    'INTERNAL__latency_qpl_instance_id'             => intval($internalLatencyData['com.bloks.www.caa.login.oauth.token.fetch.async']['instance_id'] ?? 0),
                    'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                    'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.caa.login.oauth.token.fetch.async']['marker_id'] ?? 0),
                    'family_device_id'                              => $this->phone_id,
                    'offline_experiment_group'                      => $serverMap['offline_experiment_group'] ?? 'caa_iteration_v3_perf_ig_4',
                    // 'INTERNAL_INFRA_THEME'                          => $serverMap['INTERNAL_INFRA_THEME'] ?? $this->bloksInfo['INTERNAL_INFRA_THEME'],
                    'access_flow_version'                           => $serverMap['access_flow_version'] ?? 'F2_FLOW',
                    'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                    'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\LoginResponse());
    }

    /**
     * Send login text input typy ahead.
     *
     * @param bool  $username            Username.
     * @param array $mainBloks
     * @param array $internalLatencyData
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function sendLoginTextInputTypeAhead(
        $username,
        $mainBloks,
        $internalLatencyData
    ) {
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'com.bloks.www.caa.login.cp_text_input_type_ahead')) {
                $paramBlok = $mainBlok;

                $parsed = $this->bloks->parseBlok($paramBlok, 'bk.action.map.Make');
                $offsets = array_slice($this->bloks->findOffsets($parsed, 'text_component_id'), 0, -2);

                foreach ($offsets as $offset) {
                    if (isset($parsed[$offset])) {
                        $parsed = $parsed[$offset];
                    } else {
                        break;
                    }
                }

                $serverMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            }
        }

        return $this->request('bloks/apps/com.bloks.www.caa.login.cp_text_input_type_ahead/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'lois_settings'     => [
                        'lara_override' => '',
                        'lois_token'    => '',
                    ],
                    'account_centers'   => [
                        /* [
                            'profiles'  => [
                                'id'    => [
                                    'is_derived'            => 0,
                                    'credentials'           => [],
                                    'account_center_id'     => '',
                                    'profile_picture_url'   => '',
                                    'notification_count'    => 0,
                                    'token'                 => '',
                                    'last_access_time'      => 0,
                                    'has_smartlock'         => 0,
                                    'credential_type'       => '',
                                    'password'              => '',
                                    'user_id'               => '',
                                    'name'                  => '',
                                    'username'              => '',
                                    'account_source'        => '',
                                ],
                            ],
                            'id'        => '',

                        ], */
                    ],
                    'query'             => $username,
                ],
                'server_params'         => [
                    'is_from_logged_out'                => 0,
                    'text_input_id'                     => intval($serverMap['text_input_id'][1] ?? $this->bloksInfo['text_input_id'][1]),
                    'typeahead_id'                      => intval($serverMap['typeahead_id'][1] ?? $this->bloksInfo['typeahead_id'][1]),
                    'layered_homepage_experiment_group' => null,
                    'device_id'                         => $serverMap['device_id'] ?? $this->device_id,
                    'waterfall_id'                      => $serverMap['waterfall_id'] ?? $this->loginWaterfallId,
                    'INTERNAL__latency_qpl_instance_id' => intval($internalLatencyData['com.bloks.www.caa.login.cp_text_input_type_ahead']['instance_id'] ?? 0),
                    'is_platform_login'                 => intval($serverMap['is_platform_login'] ?? 0),
                    'text_component_id'                 => intval($serverMap['text_component_id'][1] ?? $this->bloksInfo['text_component_id'][1]),
                    'INTERNAL__latency_qpl_marker_id'   => intval($internalLatencyData['com.bloks.www.caa.login.cp_text_input_type_ahead']['marker_id'] ?? 0),
                    'family_device_id'                  => $this->phone_id,
                    'offline_experiment_group'          => $serverMap['offline_experiment_group'] ?? 'caa_iteration_v3_perf_ig_4',
                    // 'INTERNAL_INFRA_THEME'              => $serverMap['INTERNAL_INFRA_THEME'] ?? $this->bloksInfo['INTERNAL_INFRA_THEME'],
                    // 'fdid'                            => isset($this->bloksInfo['fdid']) ? $this->bloksInfo['fdid'] : $this->phone_id,
                    'screen_id'                         => intval($serverMap['screen_id'][1] ?? 0),
                    'access_flow_version'               => $serverMap['access_flow_version'] ?? 'F2_FLOW',
                    'is_from_logged_in_switcher'        => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                    'qe_device_id'                      => $serverMap['qe_device_id'] ?? $this->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Send login text input typy ahead.
     *
     * @param string $endpoint Endpoint.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getTwoFactorBloksScreen(
        $endpoint
    ) {
        if (isset($this->bloksInfo['INTERNAL_INFRA_screen_id']) && $this->bloksInfo['INTERNAL_INFRA_screen_id'] === 'generic_code_entry' || isset($this->bloksInfo['context_data']) || $this->bloksInfo['INTERNAL_INFRA_screen_id'] === 'method_picker') {
            $serverParams = [
                'context_data' => $this->bloksInfo['context_data'],
            ];

            if (isset($this->bloksInfo['INTERNAL_INFRA_screen_id'])) {
                $serverParams['INTERNAL_INFRA_screen_id'] = $this->bloksInfo['INTERNAL_INFRA_screen_id'];
            }
        } else {
            $serverParams = [
                'two_step_verification_context' => $this->bloksInfo['two_step_verification_context'],
                // 'INTERNAL_INFRA_THEME'          => $this->bloksInfo['INTERNAL_INFRA_THEME'],
                'flow_source'                   => $this->bloksInfo['flow_source'] ?? 'two_factor_login',
            ];

            if (isset($this->bloksInfo['INTERNAL_INFRA_screen_id'])) {
                $serverParams['INTERNAL_INFRA_screen_id'] = $this->bloksInfo['INTERNAL_INFRA_screen_id'];
            }
        }

        $serverParams['family_device_id'] = $this->phone_id;
        $serverParams['device_id'] = $this->device_id;

        return $this->request("bloks/apps/{$endpoint}/")
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'   => [
                    'device_id'             => $this->device_id,
                    'is_whatsapp_installed' => 0,
                    'machine_id'            => $this->settings->get('mid'),
                ],
                'server_params'         => $serverParams,
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get login password entry.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getLoginPasswordEntry()
    {
        return $this->request('bloks/apps/com.bloks.www.caa.login.aymh_password_entry/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'user_id'   => $this->account_id,
                ],
                'server_params'         => [
                    // 'offline_experiment_group'          => $this->settings->get('offline_experiment'),
                    // 'INTERNAL_INFRA_THEME'              => $this->bloksInfo['INTERNAL_INFRA_THEME'],
                    'device_id'                         => $this->device_id,
                    'is_platform_login'                 => 0,
                    'qe_device_id'                      => $this->uuid,
                    'family_device_id'                  => $this->phone_id,
                    'INTERNAL_INFRA_screen_id'          => isset($this->bloksInfo['INTERNAL_INFRA_screen_id']) ? $this->bloksInfo['INTERNAL_INFRA_screen_id'][1] : '',
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Request login/reset password link.
     *
     * @param string $username Username.
     * @param string $method   Method. 'phone' or 'email'.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getForgotPasswordLink(
        $username,
        $method = 'phone'
    ) {
        $this->_setUser('regular', $username, 'nopass');
        $response = $this->processLoginClientDataAndRedirect();
        $responseArr = $response->asArray();
        $mainBloks = $this->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $firstDataBlok = null;
        $firstDataBlokBack = null;
        $secondDataBlok = null;
        $thirdDataBlok = null;
        $fourthDataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'INTERNAL__latency_qpl_instance_id') && str_contains($mainBlok, 'INTERNAL__latency_qpl_marker_id')) {
                $firstDataBlok = $mainBlok;
            }
            if (str_contains($mainBlok, 'ar_event_source') && str_contains($mainBlok, 'event_step')) {
                $firstDataBlokBack = $mainBlok;
            }
            if (str_contains($mainBlok, 'typeahead_id') && str_contains($mainBlok, 'text_input_id') && str_contains($mainBlok, 'text_component_id')) {
                $secondDataBlok = $mainBlok;
            }
            if (str_contains($mainBlok, 'INTERNAL_INFRA_screen_id')) {
                $thirdDataBlok = $mainBlok;
            }
            if (str_contains($mainBlok, 'context_data')) {
                $fourthDataBlock = $mainBlok;
            }
            if ($firstDataBlok !== null && $secondDataBlok !== null) {
                break;
            } elseif ($firstDataBlok !== null && $secondDataBlok !== null && $thirdDataBlok !== null) {
                break;
            }
        }

        if ($firstDataBlok === null) {
            $firstDataBlok = $firstDataBlokBack;
            $this->bloksInfo['INTERNAL__latency_qpl_instance_id'] = [0, 0];
            $this->bloksInfo['INTERNAL__latency_qpl_marker_id'] = [0, 0];
            $this->bloksInfo['INTERNAL_INFRA_THEME'] = 'HARMONIZATION_F';
        } else {
            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'INTERNAL__latency_qpl_instance_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $firstMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($firstMap, $this->bloksInfo);
        }

        $parsed = $this->bloks->parseBlok($secondDataBlok, 'bk.action.map.Make');
        $offsets = array_slice($this->bloks->findOffsets($parsed, 'text_component_id'), 0, -2);

        foreach ($offsets as $offset) {
            if (isset($parsed[$offset])) {
                $parsed = $parsed[$offset];
            } else {
                break;
            }
        }

        $secondMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
        $this->bloksInfo = array_merge($secondMap, $this->bloksInfo);

        if ($thirdDataBlok !== null) {
            $parsed = $this->bloks->parseBlok($thirdDataBlok, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'INTERNAL_INFRA_screen_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $thirdMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($thirdMap, $this->bloksInfo);
        }

        if ($fourthDataBlock !== null) {
            $parsed = $this->bloks->parseBlok($fourthDataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'context_data'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $fourthMap = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($fourthMap, $this->bloksInfo);
        }

        $waterfallId = Signatures::generateUUID();
        $response = $this->request('bloks/apps/com.bloks.www.caa.ar.search.prefill.async/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'username_input'                    => '',
                    'device_id'                         => $this->device_id,
                    'lois_settings'                     => [
                        'lara_override' => '',
                        'lois_token'    => '',
                    ],
                    'qe_device_id'      => $this->uuid,
                ],
                'server_params'         => [
                    'is_from_logged_out'                => 0,
                    'layered_homepage_experiment_group' => null,
                    'device_id'                         => $this->device_id,
                    'waterfall_id'                      => $waterfallId,
                    'event_source'                      => 'login_home_page',
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_instance_id' => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
                    'should_push_screen'                => 1,
                    'is_platform_login'                 => 0,
                    'back_nav_action'                   => 'BACK',
                    'INTERNAL__latency_qpl_marker_id'   => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
                    'cds_screen_animation_type'         => 'default',
                    'family_device_id'                  => $this->phone_id,
                    'offline_experiment_group'          => 'caa_iteration_v3_perf_ig_4',
                    // 'INTERNAL_INFRA_THEME'              => 'harm_f',
                    'access_flow_version'               => 'F2_FLOW',
                    'is_from_logged_in_switcher'        => 0,
                    'current_step'                      => 'LOGIN',
                    'qe_device_id'                      => $this->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $firstDataBlok = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'context_data')) {
                $firstDataBlok = $mainBlok;
            }
        }

        if ($firstDataBlok !== null) {
            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'context_data'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($this->bloksInfo, $map);
        }

        $response = $this->request('bloks/apps/com.bloks.www.caa.ar.search/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'device_id'                         => $this->device_id,
                    'family_device_id'                  => $this->phone_id,
                    'lois_settings'                     => [
                        'lara_override' => '',
                        'lois_token'    => '',
                    ],
                    'waterfall_id'      => $waterfallId,
                    'qe_device_id'      => $this->uuid,
                ],
                'server_params'         => [
                    'is_from_logged_out'                => 0,
                    'context_data'                      => $this->bloksInfo['context_data'],
                    'back_nav_action'                   => 'BACK',
                    'family_device_id'                  => $this->phone_id,
                    'device_id'                         => $this->device_id,
                    'offline_experiment_group'          => 'caa_iteration_v3_perf_ig_4',
                    'waterfall_id'                      => $waterfallId,
                    'INTERNAL_INFRA_screen_id'          => 'CAA_ACCOUNT_RECOVERY_SEARCH',
                    'access_flow_version'               => 'F2_FLOW',
                    'qe_device_id'                      => $this->uuid,
                    'is_platform_login'                 => 0,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $firstDataBlok = null;
        $secondDataBlok = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'INTERNAL__latency_qpl_marker_id')) {
                $firstDataBlok = $mainBlok;
            }
            if (str_contains($mainBlok, 'context_data') && str_contains($mainBlok, 'access_flow_version')) {
                $secondDataBlok = $mainBlok;
            }
        }

        $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
        $offsets = array_slice($this->bloks->findOffsets($parsed, 'INTERNAL__latency_qpl_marker_id'), 0, -2);

        foreach ($offsets as $offset) {
            if (isset($parsed[$offset])) {
                $parsed = $parsed[$offset];
            } else {
                break;
            }
        }

        $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
        $this->bloksInfo = array_merge($map, $this->bloksInfo);

        $parsed = $this->bloks->parseBlok($secondDataBlok, 'bk.action.map.Make');
        $offsets = array_slice($this->bloks->findOffsets($parsed, 'context_data'), 0, -2);

        foreach ($offsets as $offset) {
            if (isset($parsed[$offset])) {
                $parsed = $parsed[$offset];
            } else {
                break;
            }
        }

        $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
        $this->bloksInfo = array_merge($map, $this->bloksInfo);

        $response = $this->request('bloks/apps/com.bloks.www.caa.ar.search.async/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'text_input_id'                 => '',
                    'flash_call_permissions_status' => [
                        'READ_PHONE_STATE'  => 'GRANTED',
                        'READ_CALL_LOG'     => 'GRANTED',
                        'CALL_PHONE'        => 'GRANTED',
                    ],
                    'was_headers_prefill_available'     => 0,
                    'sfdid'                             => $this->bloksInfo['sfdid'] ?? '',
                    'fetched_email_token_list'          => (object) [],
                    'search_query'                      => $username,
                    'android_build_type'                => 'release',
                    'accounts_list'                     => (object) [],
                    'ig_android_qe_device_id'           => $this->uuid,
                    'ig_oauth_token'                    => [],
                    'is_whatsapp_installed'             => 0,
                    'lois_settings'                     => [
                        'lara_override' => '',
                        'lois_token'    => '',
                    ],
                    'was_headers_prefill_used'      => 0,
                    'headers_infra_flow_id'         => '',
                    'fetched_email_list'            => [],
                    'sso_accounts_auth_data'        => [],
                    'encrypted_msisdn'              => '',
                ],
                'server_params'         => [
                    'event_request_id'                  => $this->bloksInfo['event_request_id'] ?? '',
                    'is_from_logged_out'                => 0,
                    'layered_homepage_experiment_group' => null,
                    'device_id'                         => $this->device_id,
                    'waterfall_id'                      => $waterfallId,
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_instance_id' => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
                    'is_platform_login'                 => 0,
                    'context_data'                      => $this->bloksInfo['context_data'],
                    'INTERNAL__latency_qpl_marker_id'   => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
                    'family_device_id'                  => $this->phone_id,
                    'offline_experiment_group'          => 'caa_iteration_v3_perf_ig_4',
                    // 'INTERNAL_INFRA_THEME'              => 'harm_f', // $this->bloksInfo['INTERNAL_INFRA_THEME'],
                    'access_flow_version'               => 'F2_FLOW',
                    'is_from_logged_in_switcher'        => 0,
                    'qe_device_id'                      => $this->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $firstDataBlok = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'context_data')) {
                $firstDataBlok = $mainBlok;
            }
        }

        if ($firstDataBlok !== null) {
            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'context_data'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($this->bloksInfo, $map);
        }

        $secondDataBlok = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'cuid')) {
                $secondDataBlok = $mainBlok;
            }
        }

        if ($secondDataBlok !== null) {
            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'cuid'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($map, $this->bloksInfo);
        }

        $secondDataBlok = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'lookup_query')) {
                $secondDataBlok = $mainBlok;
            }
        }

        if ($secondDataBlok !== null) {
            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'lookup_query'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($map, $this->bloksInfo);

            $keysArray = $this->bloksInfo['account'][0];
            $valuesArray = $this->bloksInfo['account'][1];

            $this->bloksInfo = array_merge($this->bloksInfo, $this->bloks->recursiveArrayMerge($keysArray, $valuesArray));
        }

        /*
        if (!isset($this->bloksInfo['cuid'])) {
            throw new \InstagramAPI\Exception\InstagramException('Something went wrong, try again later.');
        }
        */

        $re = sprintf('/"%s\\\\",\s\\\\"(\d+)\\\\"/m', $username);
        preg_match_all($re, $response->asJson(), $matches, PREG_SET_ORDER, 0);

        $serverParams = [
            'event_request_id'                  => $this->bloksInfo['event_request_id'],
            'layered_homepage_experiment_group' => null,
            'device_id'                         => $this->device_id,
            'waterfall_id'                      => $this->bloksInfo['waterfall_id'],
            'machine_id'                        => $this->settings->get('mid'),
            'INTERNAL__latency_qpl_instance_id' => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
            'is_platform_login'                 => 0,
            'context_data'                      => $this->bloksInfo['context_data'],
            'auth_method'                       => $method,
            'INTERNAL__latency_qpl_marker_id'   => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
            'family_device_id'                  => $this->phone_id,
            'offline_experiment_group'          => 'caa_iteration_v3_perf_ig_4',
            'is_feta_account'                   => 0,
            // 'INTERNAL_INFRA_THEME'              => 'harm_f,default,harm_f',
            'is_auth_method_rejected'           => 1,
            'access_flow_version'               => 'F2_FLOW',
            'is_from_logged_in_switcher'        => 0,
            'qe_device_id'                      => $this->uuid,
        ];

        /*
        if (isset($this->bloksInfo['cuid'])) {
            $serverParams['account'] = [
                'foa_sso_data'                  => null,
                'lara_auth_method'              => 'push_to_session',
                'cuid'                          => $this->bloksInfo['cuid'],
                'is_vetted_device'              => 0,
                'name'                          => $this->bloksInfo['name'],
                'lookup_type'                   => 'username',
                'contactpoints'                 => [
                    'data'  => [
                        [
                            'type'      => 'email',
                            'display'   => json_decode('"' . array_unique($this->bloks->findValueWithSubstringRecursive($this->bloksInfo, '****'), SORT_REGULAR)[0][0] . '"'),
                            'id'        => $this->bloksInfo['lookup_query'],
                        ]
                    ]
                ],
                'profile_pic_url'               => stripslashes(stripslashes($this->bloksInfo['profile_pic_url'])),
                'lookup_query'                  => $this->bloksInfo['lookup_query'],
            ];
        }
            */

        $response = $this->request('bloks/apps/com.bloks.www.caa.ar.auth_method.async/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'lois_settings'                     => [
                        'lara_override' => '',
                        'lois_token'    => '',
                    ],
                    'android_build_type'            => 'release',
                ],
                'server_params'         => $serverParams,
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $firstDataBlok = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'context_data') && str_contains($mainBlok, $method)) {
                $firstDataBlok = $mainBlok;
            }
        }

        if ($firstDataBlok !== null) {
            $parsed = $this->bloks->parseBlok($firstDataBlok, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'context_data'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($this->bloksInfo, $map);
        }

        if (!$matches) {
            $re = sprintf('/"%s\\\\",\s\\\\"(\d+)\\\\"/m', $username);
            preg_match_all($re, $response->asJson(), $matches, PREG_SET_ORDER, 0);

            if (!$matches) {
                throw new Exception\InstagramException('Something went wrong, try again later.');
            }
        }
        $this->bloksInfo['cuid'] = $matches[0][1];

        return $this->request('bloks/apps/com.bloks.www.caa.ar.auth_option_selection.async/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'emails'                                => [],
                    'selected_xapp_contactpoint_index'      => null,
                    'auth_option'                           => $method,
                    'lois_settings'                         => [
                        'lara_override' => '',
                        'lois_token'    => '',
                    ],
                    'machine_id'                            => $this->settings->get('mid'),
                    'tokens'                                => [],
                    'selected_phone_number_index'           => -1,
                    'android_build_type'                    => 'release',
                ],
                'server_params'         => [
                    'event_request_id'                  => $this->bloksInfo['event_request_id'],
                    'is_from_logged_out'                => 0,
                    'is_device_vetted'                  => 0,
                    'layered_homepage_experiment_group' => null,
                    'cuid'                              => $this->bloksInfo['cuid'],
                    'device_id'                         => $this->device_id,
                    'waterfall_id'                      => $this->bloksInfo['waterfall_id'],
                    'is_oauth_eligible'                 => 0,
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_instance_id' => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
                    'is_multiple_account_flow'          => 0,
                    'is_platform_login'                 => 0,
                    'context_data'                      => $this->bloksInfo['context_data'],
                    'INTERNAL__latency_qpl_marker_id'   => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
                    'family_device_id'                  => $this->phone_id,
                    // 'INTERNAL_INFRA_THEME'              => 'harm_f,default,default,harm_f,default,harm_f',
                    'auth_options'                      => ['push_to_session', 'email', 'password'],
                    'access_flow_version'               => 'F2_FLOW',
                    'is_from_logged_in_switcher'        => 0,
                    'qe_device_id'                      => $this->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Internal Facebook login handler.
     *
     * @param string $username           Your Instagram username.
     * @param string $fbAccessToken      Facebook access token.
     * @param bool   $forceLogin         Force login to Instagram instead of
     *                                   resuming previous session. Used
     *                                   internally to do a new, full relogin
     *                                   when we detect an expired/invalid
     *                                   previous session.
     * @param int    $appRefreshInterval
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse|null
     *
     * @see Instagram::loginWithFacebook() The public Facebook login handler with a full description.
     */
    protected function _loginWithFacebook(
        $username,
        $fbAccessToken,
        $forceLogin = false,
        $appRefreshInterval = 1800
    ) {
        if (empty($fbAccessToken)) {
            throw new \InvalidArgumentException('You must provide an fb_access_token to _loginWithFacebook().');
        }
        // Switch the currently active access token if it is different.
        if ($this->fb_access_token !== $fbAccessToken) {
            $this->_setUser('facebook', $username, $fbAccessToken);
        }
        if (!$this->isMaybeLoggedIn || $forceLogin) {
            if ($this->loginAttemptCount === 0 && !self::$skipLoginFlowAtMyOwnRisk) {
                $this->_sendPreLoginFlow();
            }

            try {
                $response = $this->request('fb/facebook_signup/')
                     ->setNeedsAuth(false)
                     ->addPost('dryrun', 'false')
                     ->addPost('phone_id', $this->phone_id)
                     ->addPost('adid', $this->advertising_id)
                     ->addPost('device_id', $this->device_id)
                     ->addPost('waterfall_id', Signatures::generateUUID())
                     ->addPost('fb_access_token', $this->fb_access_token)
                     ->getResponse(new Response\LoginResponse());
            } catch (Exception\InstagramException $e) {
                if ($e->hasResponse() && $e->getResponse()->isTwoFactorRequired()) {
                    // Login failed because two-factor login is required.
                    // Return server response to tell user they need 2-factor.
                    return $e->getResponse();
                } elseif ($e->hasResponse() && ($e->getResponse()->getInvalidCredentials() === true)) {
                    $this->loginAttemptCount++;
                } else {
                    if ($e->getResponse() === null) {
                        throw new Exception\NetworkException($e);
                    }

                    // Login failed for some other reason... Re-throw error.
                    throw $e;
                }
            }
            $this->_updateLoginState($response);

            $this->_sendLoginFlow(true, $appRefreshInterval);

            // Full (re-)login successfully completed. Return server response.
            return $response;
        }

        // Attempt to resume an existing session, or full re-login if necessary.
        // NOTE: The "return" here gives a LoginResponse in case of re-login.
        return $this->_sendLoginFlow(false, $appRefreshInterval);
    }

    /**
     * Internal Email link login handler.
     *
     * @param string $username           Your Instagram username.
     * @param string $link               Email login link.
     * @param bool   $forceLogin         Force login to Instagram instead of
     *                                   resuming previous session. Used
     *                                   internally to do a new, full relogin
     *                                   when we detect an expired/invalid
     *                                   previous session.
     * @param int    $appRefreshInterval
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse|null
     *
     * @see Instagram::loginWithEmailLink() The public email with link login handler with a full description.
     */
    protected function _loginWithEmailLink(
        $username,
        $link,
        $forceLogin = false,
        $appRefreshInterval = 1800
    ) {
        // Switch the currently active user/pass if the details are different.
        if ($this->username !== $username) {
            $this->_setUser('regular', $username, 'NOPASSWORD');

            if ($this->settings->get('pending_events') !== null) {
                $this->eventBatch = json_decode($this->settings->get('pending_events'), true);
                $this->settings->set('pending_events', '');
            }
        }

        if (!$this->isMaybeLoggedIn || $forceLogin) {
            if ($this->loginAttemptCount === 1 && !self::$skipLoginFlowAtMyOwnRisk) {
                $this->_sendPreLoginFlow();
            }

            try {
                $str = explode('?', $link);
                if (!isset($str[1])) {
                    throw new Exception\InvalidLoginLinkException();
                }
                parse_str($str[1], $params);
                if (!isset($params['uid']) && !isset($params['token'])) {
                    throw new Exception\InvalidLoginLinkException();
                }

                $request = $this->request('accounts/one_click_login/')
                    ->setNeedsAuth(false)
                    ->addPost('source', 'email')
                    // ->addPost('_csrftoken', $this->client->getToken())
                    ->addPost('uid', $params['uid'])
                    ->addPost('adid', $this->advertising_id)
                    ->addPost('guid', $this->uuid)
                    ->addPost('device_id', $this->device_id)
                    ->addPost('token', $params['token'])
                    ->addPost('auto_send', '0');

                $response = $request->getResponse(new Response\LoginResponse());
                $this->settings->set('business_account', $response->getLoggedInUser()->getIsBusiness());
            } catch (Exception\InstagramException $e) {
                if ($e->hasResponse() && $e->getResponse()->isTwoFactorRequired()) {
                    // Login failed because two-factor login is required.
                    // Return server response to tell user they need 2-factor.
                    return $e->getResponse();
                } elseif ($e->hasResponse() && ($e->getResponse()->getInvalidCredentials() === true)) {
                    $this->loginAttemptCount++;
                } else {
                    if ($e->getResponse() === null) {
                        throw new Exception\NetworkException($e);
                    }

                    // Login failed for some other reason... Re-throw error.
                    throw $e;
                }
            }

            $this->_updateLoginState($response);

            $this->_sendLoginFlow(true, $appRefreshInterval);

            // Full (re-)login successfully completed. Return server response.
            return $response;
        }

        // Attempt to resume an existing session, or full re-login if necessary.
        // NOTE: The "return" here gives a LoginResponse in case of re-login.
        return $this->_sendLoginFlow(false, $appRefreshInterval);
    }

    /**
     * Finish a two-factor authenticated login.
     *
     * This function finishes a two-factor challenge that was provided by the
     * regular `login()` function. If you successfully answer their challenge,
     * you will be logged in after this function call.
     *
     * @param string      $username            Your Instagram username used for login.
     *                                         Email and phone aren't allowed here.
     * @param string      $password            Your Instagram password.
     * @param string      $twoFactorIdentifier Two factor identifier, obtained in
     *                                         login() response. Format: `123456`.
     * @param string      $verificationCode    Verification code you have received
     *                                         via SMS.
     * @param string      $verificationMethod  The verification method for 2FA. 1 is SMS,
     *                                         2 is backup codes, 3 is TOTP, 4 is notification,
     *                                         6 is whatsapp.
     * @param int         $appRefreshInterval  See `login()` for description of this
     *                                         parameter.
     * @param string|null $usernameHandler     Instagram username sent in the login response.
     *                                         Email and phone aren't allowed here.
     * @param bool        $trustDevice         If you want to trust the used Device ID.
     * @param string      $pollingNonce        Trusted polling nonce.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse
     */
    public function finishTwoFactorLogin(
        $username,
        $password,
        $twoFactorIdentifier,
        $verificationCode,
        $verificationMethod = 1,
        $appRefreshInterval = 1800,
        $usernameHandler = null,
        $trustDevice = true,
        $pollingNonce = null
    ) {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('You must provide a username and password to finishTwoFactorLogin().');
        }
        if ((empty($verificationCode) && ($verificationMethod !== 4)) || empty($twoFactorIdentifier)) {
            throw new \InvalidArgumentException('You must provide a verification code and two-factor identifier to finishTwoFactorLogin().');
        }
        if (!in_array($verificationMethod, [1, 2, 3, 4, 6], true)) {
            throw new \InvalidArgumentException('You must provide a valid verification method value.');
        }

        // Switch the currently active user/pass if the details are different.
        // NOTE: The username and password AREN'T actually necessary for THIS
        // endpoint, but this extra step helps people who statelessly embed the
        // library directly into a webpage, so they can `finishTwoFactorLogin()`
        // on their second page load without having to begin any new `login()`
        // call (since they did that in their previous webpage's library calls).
        if ($this->username !== $username || $this->password !== $password) {
            $this->_setUser('regular', $username, $password);
        }

        $username = ($usernameHandler !== null) ? $usernameHandler : $username;

        // Remove all whitespace from the verification code.
        $verificationCode = preg_replace('/\s+/', '', $verificationCode);

        $request = $this->request('accounts/two_factor_login/')
            ->setNeedsAuth(false)
            ->addPost('verification_code', $verificationCode)
            ->addPost('phone_id', $this->phone_id)
            // ->addPost('_csrftoken', $this->client->getToken())
            ->addPost('two_factor_identifier', $twoFactorIdentifier)
            ->addPost('username', $username)
            ->addPost('trust_this_device', ($trustDevice) ? '1' : '0')
            ->addPost('guid', $this->uuid)
            ->addPost('device_id', $this->device_id)
            ->addPost('waterfall_id', $this->loginWaterfallId)
            // 1 - SMS, 2 - Backup codes, 3 - TOTP, 4 - Notification approval, 6 - whatsapp
            ->addPost('verification_method', $verificationMethod);

        if ($pollingNonce !== null) {
            $request->addPost('trusted_notification_polling_nonces', json_encode([$pollingNonce]));
        }

        $response = $request->getResponse(new Response\LoginResponse());

        $this->_updateLoginState($response);
        $this->_sendLoginFlow(true, $appRefreshInterval);

        return $response;
    }

    /**
     * Request a new security code SMS for a Two Factor login account.
     *
     * NOTE: You should first attempt to `login()` which will automatically send
     * you a two factor SMS. This function is just for asking for a new SMS if
     * the old code has expired.
     *
     * NOTE: Instagram can only send you a new code every 60 seconds.
     *
     * @param string      $username            Your Instagram username.
     * @param string      $password            Your Instagram password.
     * @param string      $twoFactorIdentifier Two factor identifier, obtained in
     *                                         `login()` response.
     * @param string|null $usernameHandler     Instagram username sent in the login response.
     *                                         Email and phone aren't allowed here.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\TwoFactorLoginSMSResponse
     */
    public function sendTwoFactorLoginSMS(
        $username,
        $password,
        $twoFactorIdentifier,
        $usernameHandler = null
    ) {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('You must provide a username and password to sendTwoFactorLoginSMS().');
        }
        if (empty($twoFactorIdentifier)) {
            throw new \InvalidArgumentException('You must provide a two-factor identifier to sendTwoFactorLoginSMS().');
        }

        // Switch the currently active user/pass if the details are different.
        // NOTE: The password IS NOT actually necessary for THIS
        // endpoint, but this extra step helps people who statelessly embed the
        // library directly into a webpage, so they can `sendTwoFactorLoginSMS()`
        // on their second page load without having to begin any new `login()`
        // call (since they did that in their previous webpage's library calls).
        if ($this->username !== $username || $this->password !== $password) {
            $this->_setUser('regular', $username, $password);
        }

        $username = ($usernameHandler !== null) ? $usernameHandler : $username;

        return $this->request('accounts/send_two_factor_login_sms/')
            ->setNeedsAuth(false)
            ->addPost('two_factor_identifier', $twoFactorIdentifier)
            ->addPost('username', $username)
            ->addPost('device_id', $this->device_id)
            ->addPost('guid', $this->uuid)
            // ->addPost('_csrftoken', $this->client->getToken())
            ->getResponse(new Response\TwoFactorLoginSMSResponse());
    }

    /**
     * Request a new security code via WhatsApp for a Two Factor login account.
     *
     * @param string      $username            Your Instagram username.
     * @param string      $password            Your Instagram password.
     * @param string      $twoFactorIdentifier Two factor identifier, obtained in
     *                                         `login()` response.
     * @param string|null $usernameHandler     Instagram username sent in the login response.
     *                                         Email and phone aren't allowed here.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\TwoFactorLoginSMSResponse
     */
    public function sendTwoFactorLoginWhatsapp(
        $username,
        $password,
        $twoFactorIdentifier,
        $usernameHandler = null
    ) {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('You must provide a username and password to sendTwoFactorLoginSMS().');
        }
        if (empty($twoFactorIdentifier)) {
            throw new \InvalidArgumentException('You must provide a two-factor identifier to sendTwoFactorLoginSMS().');
        }

        // Switch the currently active user/pass if the details are different.
        // NOTE: The password IS NOT actually necessary for THIS
        // endpoint, but this extra step helps people who statelessly embed the
        // library directly into a webpage, so they can `sendTwoFactorLoginSMS()`
        // on their second page load without having to begin any new `login()`
        // call (since they did that in their previous webpage's library calls).
        if ($this->username !== $username || $this->password !== $password) {
            $this->_setUser('regular', $username, $password);
        }

        $username = ($usernameHandler !== null) ? $usernameHandler : $username;

        return $this->request('two_factor/send_two_factor_login_whatsapp/')
            ->setNeedsAuth(false)
            ->addPost('two_factor_identifier', $twoFactorIdentifier)
            ->addPost('username', $username)
            ->addPost('device_id', $this->device_id)
            ->addPost('guid', $this->uuid)
            // ->addPost('_csrftoken', $this->client->getToken())
            ->getResponse(new Response\TwoFactorLoginSMSResponse());
    }

    /**
     * Check trusted notification status for 2FA login.
     *
     * This checks wether a device has approved the login via
     * notification.
     *
     * @param string $username            Your Instagram username.
     * @param string $twoFactorIdentifier Two factor identifier, obtained in
     *                                    `login()` response.
     * @param string $pollingNonce        Trusted polling nonce.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\TwoFactorNotificationStatusResponse
     */
    public function checkTrustedNotificationStatus(
        $username,
        $twoFactorIdentifier,
        $pollingNonce
    ) {
        if (empty($username)) {
            throw new \InvalidArgumentException('You must provide a username.');
        }
        if (empty($twoFactorIdentifier)) {
            throw new \InvalidArgumentException('You must provide a two-factor identifier.');
        }

        return $this->request('two_factor/check_trusted_notification_status/')
        ->setNeedsAuth(false)
        ->addPost('two_factor_identifier', $twoFactorIdentifier)
        ->addPost('username', $username)
        ->addPost('device_id', $this->device_id)
        ->addPost('trusted_notification_polling_nonces', json_encode([$pollingNonce]))
        // ->addPost('_csrftoken', $this->client->getToken())
        ->getResponse(new Response\TwoFactorNotificationStatusResponse());
    }

    /**
     * Finish a two-factor authenticated login (Bloks version).
     *
     * This function finishes a two-factor challenge that was provided by the
     * regular `login()` function. If you successfully answer their challenge,
     * you will be logged in after this function call.
     *
     * @param string $username         Your Instagram username used for login.
     *                                 Email and phone aren't allowed here.
     * @param string $password         Your Instagram password.
     * @param string $challenge        2FA challenge type.
     * @param string $verificationCode Verification code.
     * @param bool   $trustDevice      If you want to trust the used Device ID.
     * @param mixed  $context
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse
     */
    public function finishTwoFactorVerification(
        $username,
        $password,
        $context,
        $challenge,
        $verificationCode,
        $trustDevice = true
    ) {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('You must provide a username and password to finishTwoFactorVerification().');
        }
        if (empty($verificationCode) || empty($context)) {
            throw new \InvalidArgumentException('You must provide a verification code and two-factor identifier to finishTwoFactorVerification().');
        }
        if (!in_array($challenge, ['totp', 'backup', 'backup_codes', 'sms', 'email', 'whatsapp', 'notification'], true)) {
            throw new \InvalidArgumentException('You must provide a valid 2FA challenge type.');
        }

        // Switch the currently active user/pass if the details are different.
        // NOTE: The username and password AREN'T actually necessary for THIS
        // endpoint, but this extra step helps people who statelessly embed the
        // library directly into a webpage, so they can `finishTwoFactorVerification()`
        // on their second page load without having to begin any new `login()`
        // call (since they did that in their previous webpage's library calls).
        if ($this->username !== $username || $this->password !== $password) {
            $this->_setUser('regular', $username, $password);
        }

        // Remove all whitespace from the verification code.
        $verificationCode = preg_replace('/\s+/', '', $verificationCode);

        $response = $this->request('bloks/apps/com.bloks.www.two_step_verification.verify_code.async/')
            ->setNeedsAuth(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'auth_secure_device_id'         => '',
                    'machine_id'                    => $this->settings->get('mid'),
                    'code'                          => $verificationCode,
                    'should_trust_device'           => intval($trustDevice),
                    'family_device_id'              => $this->phone_id,
                    'device_id'                     => $this->device_id,
                ],
                'server_params'         => [
                    'challenge'                                     => $challenge,
                    'INTERNAL__latency_qpl_marker_id'               => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
                    'INTERNAL__latency_qpl_instance_id'             => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
                    'two_step_verification_context'                 => $context, // $this->bloksInfo['two_step_verification_context'],
                    'flow_source'                                   => 'two_factor_login', // $this->bloksInfo['flow_source'],
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $mainBloks = $this->bloks->parseResponse($response->asArray(), '(bk.action.caa.HandleLoginResponse');

        $firstDataBlok = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'logged_in_user')) {
                $firstDataBlok = $mainBlok;
                break;
            }
        }

        if ($firstDataBlok !== null) {
            $loginResponseWithHeaders = $this->bloks->parseBlok($firstDataBlok, 'bk.action.caa.HandleLoginResponse');
        } else {
            $loginResponseWithHeaders = $this->bloks->parseBlok(json_encode($response->asArray()['layout']['bloks_payload']['tree']), 'bk.action.caa.HandleLoginResponse');
        }

        if (is_array($loginResponseWithHeaders)) {
            if (str_contains($response->asJson(), 'BLOKS_TWO_STEP_VERIFICATION_ENTER_CODE:error_message:')) {
                throw new Exception\Invalid2FACodeException('Invalid 2FA code');
            }
            $errorMap = $this->_parseLoginErrors($loginResponseWithHeaders, $response);
            $this->_throwLoginException($response, $errorMap);
        }
        $response = $this->_processSuccesfulLoginResponse($loginResponseWithHeaders, 1800);
        // $this->_updateLoginState($response);
        // $this->_sendLoginFlow(true, $appRefreshInterval);

        return $response;
    }

    /**
     * Finish a two-factor generic authenticated login (Bloks version).
     *
     * This function finishes a two-factor challenge that was provided by the
     * regular `login()` function. If you successfully answer their challenge,
     * you will be logged in after this function call.
     *
     * @param string $username         Your Instagram username used for login.
     *                                 Email and phone aren't allowed here.
     * @param string $password         Your Instagram password.
     * @param string $challenge        2FA challenge type.
     * @param string $verificationCode Verification code.
     * @param bool   $trustDevice      If you want to trust the used Device ID.
     * @param mixed  $context
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse
     */
    public function finishTwoFactorGenericVerification(
        $username,
        $password,
        $context,
        $verificationCode
    ) {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('You must provide a username and password to finishTwoFactorVerification().');
        }
        if (empty($verificationCode) || empty($context)) {
            throw new \InvalidArgumentException('You must provide a verification code and two-factor identifier to finishTwoFactorVerification().');
        }

        // Switch the currently active user/pass if the details are different.
        // NOTE: The username and password AREN'T actually necessary for THIS
        // endpoint, but this extra step helps people who statelessly embed the
        // library directly into a webpage, so they can `finishTwoFactorVerification()`
        // on their second page load without having to begin any new `login()`
        // call (since they did that in their previous webpage's library calls).
        if ($this->username !== $username || $this->password !== $password) {
            $this->_setUser('regular', $username, $password);
        }

        // Remove all whitespace from the verification code.
        $verificationCode = preg_replace('/\s+/', '', $verificationCode);

        $response = $this->request('bloks/apps/com.bloks.www.ap.two_step_verification.code_entry_async/')
            ->setNeedsAuth(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'code'                          => $verificationCode,
                ],
                'server_params'         => [
                    'INTERNAL__latency_qpl_marker_id'               => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
                    'INTERNAL__latency_qpl_instance_id'             => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
                    'context_data'                                  => $context, // $this->bloksInfo['context_data'],
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $mainBloks = $this->bloks->parseResponse($response->asArray(), '(bk.action.caa.HandleLoginResponse');

        $firstDataBlok = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'logged_in_user')) {
                $firstDataBlok = $mainBlok;
                break;
            }
        }

        if ($firstDataBlok !== null) {
            $loginResponseWithHeaders = $this->bloks->parseBlok($firstDataBlok, 'bk.action.caa.HandleLoginResponse');
        } else {
            $loginResponseWithHeaders = $this->bloks->parseBlok(json_encode($response->asArray()['layout']['bloks_payload']['tree']), 'bk.action.caa.HandleLoginResponse');
        }

        if (is_array($loginResponseWithHeaders)) {
            if (str_contains($response->asJson(), 'try a new one')) {
                throw new Exception\Invalid2FACodeException('Invalid 2FA code');
            }
            if (str_contains($response->asJson(), 'Post login failed')) {
                throw new Exception\InstagramException('Post login failed. Retry again.');
            }
            $errorMap = $this->_parseLoginErrors($loginResponseWithHeaders, $response);
            $this->_throwLoginException($response, $errorMap);
        }
        $response = $this->_processSuccesfulLoginResponse($loginResponseWithHeaders, 1800);
        // $this->_updateLoginState($response);
        // $this->_sendLoginFlow(true, $appRefreshInterval);

        return $response;
    }

    /**
     * Get available 2FA methods.
     *
     * @param string $context 2FA context.
     * @param mixed  $method
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return string[]
     */
    public function getAvailableTwoFactorMethods(
        $context,
        $method = false
    ) {
        $endpoint = ($method === false) ? 'bloks/apps/com.bloks.www.two_step_verification.method_picker/' : 'bloks/apps/com.bloks.www.ap.two_step_verification.challenge_picker/';

        if ($method === false) {
            $serverParams = [
                'INTERNAL_INFRA_screen_id'                      => isset($this->bloksInfo['INTERNAL_INFRA_screen_id']) ? intval($this->bloksInfo['INTERNAL_INFRA_screen_id'][1]) : 'e650di:116',
                'two_step_verification_context'                 => $context, // $this->bloksInfo['two_step_verification_context'],
                'flow_source'                                   => 'two_factor_login', // $this->bloksInfo['flow_source'],
            ];
        } else {
            $serverParams = [
                'INTERNAL_INFRA_screen_id'                      => 'method_picker', // isset($this->bloksInfo['INTERNAL_INFRA_screen_id']) ? intval($this->bloksInfo['INTERNAL_INFRA_screen_id'][1]) : 'e650di:116',
                'context_data'                                  => $context, // $this->bloksInfo['two_step_verification_context'],
                // 'flow_source'                                 => 'two_factor_login', //$this->bloksInfo['flow_source'],
            ];
        }

        $response = $this->request($endpoint)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'is_whatsapp_installed'         => 0,
                ],
                'server_params'         => $serverParams,
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->bloks->parseResponse($responseArr, 'bk.action.map.Make');
        $dataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'INTERNAL__latency_qpl_marker_id') && str_contains($mainBlok, 'INTERNAL__latency_qpl_instance_id')) {
                $dataBlock = $mainBlok;
            }
        }
        if ($dataBlock !== null) {
            $parsed = $this->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'INTERNAL__latency_qpl_marker_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($map, $this->bloksInfo);
        }

        $dataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'two_step_verification_context')) {
                $dataBlock = $mainBlok;
            }
        }
        if ($dataBlock !== null) {
            $parsed = $this->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->bloks->findOffsets($parsed, 'two_step_verification_context'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->bloksInfo = array_merge($map, $this->bloksInfo);
        }

        $responseJ = $response->asJson();
        $methods = [];

        $matches = [];
        $count = preg_match_all('/send a code to/m', $responseJ, $matches);

        if (str_contains($responseJ, 'sms') || $count === 1 || $count === 2) {
            $methods[] = 'sms';
        }
        if (str_contains($responseJ, 'backup_codes')) {
            $methods[] = 'backup_codes';
        }
        if (str_contains($responseJ, 'email')) {
            $methods[] = 'email';
        }
        if (str_contains($responseJ, 'totp')) {
            $methods[] = 'totp';
        }
        if (str_contains($responseJ, 'whatsapp') || $count === 2) {
            $methods[] = 'whatsapp';
        }
        if (str_contains($responseJ, 'approve_from_another_device')) {
            $methods[] = 'notification';
        }

        return $methods;
    }

    /**
     * 2FA method picker.
     *
     * @param string $context           2FA context.
     * @param string $method            2FA challenge type.
     * @param bool   $verifiationMethod Login response.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function selectTwoFactorMethod(
        $context,
        $method,
        $verifiationMethod = false
    ) {
        if (!in_array($method, ['totp', 'backup_codes', 'sms', 'email', 'whatsapp', 'notification'], true)) {
            throw new \InvalidArgumentException('You must provide a valid 2FA method type.');
        }

        if ($verifiationMethod) {
            $endpoint = 'bloks/apps/com.bloks.www.bloks.ap.two_step_verification.challenge_picker.async/';
            switch ($method) {
                case 'sms':
                    $method = 'SMS';
                    break;
                case 'email':
                    $method = 'EMAIL';
                    break;
                case 'notification':
                    $method = 'AFAD';
                    break;
            }
            $clientParams = [
                'selected_challenge'    => $method,
            ];
        } else {
            $endpoint = 'bloks/apps/com.bloks.www.two_step_verification.method_picker.navigation.async/';
            $clientParams = [
                'selected_method'    => $method,
            ];
        }

        $response = $this->request($endpoint)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode([
                'client_input_params'           => $clientParams,
                'server_params'                 => [
                    'INTERNAL__latency_qpl_marker_id'               => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
                    'INTERNAL__latency_qpl_instance_id'             => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
                    'two_step_verification_context'                 => $this->bloksInfo['two_step_verification_context'] ?? $context, // $this->bloksInfo['two_step_verification_context'],
                    'flow_source'                                   => 'two_factor_login', // $this->bloksInfo['flow_source'],
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        switch ($method) {
            case 'totp':
                $endpoint = 'bloks/apps/com.bloks.www.two_factor_login.enter_totp_code/';
                break;
            case 'backup_codes':
                $endpoint = 'bloks/apps/com.bloks.www.two_factor_login.enter_backup_code/';
                break;
            case 'sms':
                $endpoint = 'bloks/apps/com.bloks.www.two_step_verification.enter_sms_code/';
                break;
            case 'email':
                $endpoint = 'bloks/apps/com.bloks.www.two_step_verification.enter_email_code/';
                break;
            case 'whatsapp':
                $endpoint = 'bloks/apps/com.bloks.www.two_step_verification.enter_whatsapp_code/';
                break;
        }

        $response = $this->request($endpoint)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode([
                'server_params'         => [
                    'INTERNAL_INFRA_screen_id'                      => isset($this->bloksInfo['INTERNAL_INFRA_screen_id']) ? intval($this->bloksInfo['INTERNAL_INFRA_screen_id'][1]) : 'e8o7m7:2',
                    'two_step_verification_context'                 => $context, // $this->bloksInfo['two_step_verification_context'],
                    'flow_source'                                   => 'two_factor_login', // $this->bloksInfo['flow_source'],
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        if ($method === 'sms' || $method === 'whatsapp') {
            $responseArr = $response->asArray();
            $mainBloks = $this->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
            $dataBlock = null;
            foreach ($mainBloks as $mainBlok) {
                if (str_contains($mainBlok, 'masked_cp')) {
                    $dataBlock = $mainBlok;
                }
            }
            if ($dataBlock !== null) {
                $parsed = $this->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
                $offsets = array_slice($this->bloks->findOffsets($parsed, 'masked_cp'), 0, -2);

                foreach ($offsets as $offset) {
                    if (isset($parsed[$offset])) {
                        $parsed = $parsed[$offset];
                    } else {
                        break;
                    }
                }

                $map = $this->bloks->map_arrays($parsed[0], $parsed[1]);
                $this->bloksInfo = array_merge($map, $this->bloksInfo);
            }
        }

        if ($method === 'sms' || $method === 'whatsapp') {
            $this->requestTwoFactorCode($context, $method);
        }

        return $response;
    }

    /**
     * Check trusted notification status (Bloks).
     *
     * @param string $context 2FA context.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function checkTrustedNotificationBloksStatus(
        $context
    ) {
        $response = $this->request('bloks/apps/com.bloks.www.two_step_verification.has_been_allowed.async/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'           => [
                    'auth_secure_device_id'     => '',
                    'device_id'                 => $this->device_id,
                    'family_device_id'          => $this->phone_id,
                    'machine_id'                => $this->settings->get('mid'),
                ],
                'server_params'         => [
                    'machine_id'                                    => null,
                    'INTERNAL__latency_qpl_marker_id'               => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
                    'INTERNAL__latency_qpl_instance_id'             => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
                    'device_id'                                     => null,
                    'two_step_verification_context'                 => $this->bloksInfo['two_step_verification_context'] ?? $context, // $this->bloksInfo['two_step_verification_context'],
                    'flow_source'                                   => 'two_factor_login', // 'login_challenges', //$this->bloksInfo['flow_source'],
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $mainBloks = $this->bloks->parseResponse($response->asArray(), '(bk.action.caa.HandleLoginResponse');

        $firstDataBlok = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'logged_in_user')) {
                $firstDataBlok = $mainBlok;
                break;
            }
        }

        if ($firstDataBlok !== null) {
            $loginResponseWithHeaders = $this->bloks->parseBlok($firstDataBlok, 'bk.action.caa.HandleLoginResponse');
        } else {
            $loginResponseWithHeaders = $this->bloks->parseBlok(json_encode($response->asArray()['layout']['bloks_payload']['tree']), 'bk.action.caa.HandleLoginResponse');
        }

        if (is_array($loginResponseWithHeaders)) {
            return false;
        }

        $loginResponse = $this->_processSuccesfulLoginResponse($loginResponseWithHeaders, 1800);

        return $loginResponse;
    }

    /**
     * 2FA Bloks code request.
     *
     * @param string $context   2FA context.
     * @param string $challenge 2FA challenge type.
     * @param mixed  $method
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function requestTwoFactorCode(
        $context,
        $method
    ) {
        if (!in_array($method, ['totp', 'backup_codes', 'sms', 'email', 'whatsapp', 'notification'], true)) {
            throw new \InvalidArgumentException('You must provide a valid 2FA method type.');
        }

        $serverParams = [
            'challenge'                                     => $method,
            'INTERNAL__latency_qpl_marker_id'               => isset($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && is_array($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) && count($this->bloksInfo['INTERNAL__latency_qpl_marker_id']) > 1 ? intval($this->bloksInfo['INTERNAL__latency_qpl_marker_id'][1]) : 0,
            'INTERNAL__latency_qpl_instance_id'             => isset($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? (is_array($this->bloksInfo['INTERNAL__latency_qpl_instance_id']) ? intval($this->bloksInfo['INTERNAL__latency_qpl_instance_id'][1]) : 1) : 1,
            'two_step_verification_context'                 => $context, // $this->bloksInfo['two_step_verification_context'],
            'flow_source'                                   => 'two_factor_login', // $this->bloksInfo['flow_source'],
        ];

        if ($method === 'sms' || $method === 'whatsapp') {
            $serverParams['masked_cp'] = $this->bloksInfo['masked_cp'];
        }

        $response = $this->request('bloks/apps/com.bloks.www.two_step_verification.send_code.async/')
            ->setNeedsAuth(false)
            ->addPost('params', json_encode([
                'server_params'         => $serverParams,
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        return $response;
    }

    /**
     * Finish checkpoint.
     *
     * If code verification went successful, we proceed to update state and
     * send login flow.
     *
     * @param Response\LoginResponse $verifyCodeResponse
     *
     * @throws Exception\InstagramException
     */
    public function finishCheckpoint(
        $verifyCodeResponse
    ) {
        $this->_updateLoginState($verifyCodeResponse);
        $this->_sendLoginFlow(true, 1800);
    }

    /**
     * Request information about available password recovery methods for an account.
     *
     * This will tell you things such as whether SMS or EMAIL-based recovery is
     * available for the given account name.
     *
     * `WARNING:` You can call this function without having called `login()`,
     * but be aware that a user database entry will be created for every
     * username you try to look up. This is ONLY meant for recovering your OWN
     * accounts.
     *
     * @param string $username Your Instagram username.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\UsersLookupResponse
     */
    public function userLookup(
        $username
    ) {
        // Set active user (without pwd), and create database entry if new user.
        $this->setUserWithoutPassword($username);
        $waterfallId = Signatures::generateUUID();

        return $this->request('users/lookup/')
            ->setNeedsAuth(false)
            ->addPost('country_codes', json_encode(
                [
                    [
                        'country_code' => Utils::getCountryCode(explode('_', $this->getLocale())[1]),
                        'source'       => [
                            'default',
                        ],
                    ],
                ]
            ))
            ->addPost('q', $username)
            ->addPost('directly_sign_in', 'true')
            ->addPost('username', $username)
            ->addPost('device_id', $this->device_id)
            ->addPost('android_build_type', 'release')
            ->addPost('guid', $this->uuid)
            ->addPost('waterfall_id', $waterfallId)
            ->addPost('directly_sign_in', 'true')
            // ->addPost('_csrftoken', $this->client->getToken())
            ->getResponse(new Response\UsersLookupResponse());
    }

    /**
     * Request a recovery EMAIL to get back into your account.
     *
     * `WARNING:` You can call this function without having called `login()`,
     * but be aware that a user database entry will be created for every
     * username you try to look up. This is ONLY meant for recovering your OWN
     * accounts.
     *
     * @param string $username Your Instagram username.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\RecoveryResponse
     */
    public function sendRecoveryEmail(
        $username
    ) {
        // Verify that they can use the recovery email option.
        $userLookup = $this->userLookup($username);
        if (!$userLookup->getCanEmailReset()) {
            throw new Exception\InternalException('Email recovery is not available, since your account lacks a verified email address.');
        }

        return $this->request('accounts/send_recovery_flow_email/')
            ->setNeedsAuth(false)
            ->addPost('query', $username)
            ->addPost('adid', $this->advertising_id)
            ->addPost('device_id', $this->device_id)
            ->addPost('guid', $this->uuid)
            // ->addPost('_csrftoken', $this->client->getToken())
            ->getResponse(new Response\RecoveryResponse());
    }

    /**
     * Request a recovery SMS to get back into your account.
     *
     * `WARNING:` You can call this function without having called `login()`,
     * but be aware that a user database entry will be created for every
     * username you try to look up. This is ONLY meant for recovering your OWN
     * accounts.
     *
     * @param string $username Your Instagram username.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\RecoveryResponse
     */
    public function sendRecoverySMS(
        $username
    ) {
        // Verify that they can use the recovery SMS option.
        $userLookup = $this->userLookup($username);
        if (!$userLookup->getHasValidPhone() || !$userLookup->getCanSmsReset()) {
            throw new Exception\InternalException('SMS recovery is not available, since your account lacks a verified phone number.');
        }

        return $this->request('users/lookup_phone/')
            ->setNeedsAuth(false)
            ->addPost('query', $username)
            // ->addPost('_csrftoken', $this->client->getToken())
            ->getResponse(new Response\RecoveryResponse());
    }

    /**
     * Set the active account for the class instance.
     *
     * We can call this multiple times to switch between multiple accounts.
     *
     * @param string $loginType 'regular' or 'facebook'.
     * @param string $username  Your Instagram username.
     * @param string $password  Your Instagram password.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     */
    protected function _setUser(
        $loginType,
        $username,
        $password
    ) {
        if ((empty($username) || empty($password)) && $loginType === 'regular') {
            throw new \InvalidArgumentException('You must provide a username and password to _setUser().');
        }

        // Load all settings from the storage and mark as current user.
        $this->settings->setActiveUser($username);

        // Generate the user's device instance, which will be created from the
        // user's last-used device IF they've got a valid, good one stored.
        // But if they've got a BAD/none, this will create a brand-new device.

        $autoFallback = self::$overrideGoodDevicesCheck ? false : true;
        if ($this->settings->get('devicestring') !== null) {
            $savedDeviceString = $this->settings->get('devicestring');
        } elseif ($this->customDeviceString !== null) {
            $savedDeviceString = $this->customDeviceString;
        } else {
            $savedDeviceString = null;
            $autoFallback = true;
        }

        if (empty($this->settings->get('version_code'))) {
            $this->setVersionCode('', true);
            $this->settings->set('version_code', $this->getVersionCode());
        } else {
            if (!in_array($this->settings->get('version_code'), Constants::VERSION_CODE)) {
                $this->setVersionCode('', true);
                $this->settings->set('version_code', $this->getVersionCode());
            }
            $this->setVersionCode($this->settings->get('version_code'));
        }

        $this->device = new Devices\Device(
            Constants::IG_VERSION,
            $this->getVersionCode(),
            $this->getLocale(),
            $this->getAcceptLanguage(),
            $savedDeviceString,
            $autoFallback,
            $this->getPlatform(),
            $this->getIosModel(),
            $this->getIosDpi(),
            $this->enableResolutionCheck,
            $this->deviceFilter
        );

        // Get active device string so that we can compare it to any saved one.
        $deviceString = $this->device->getDeviceString();

        // Generate a brand-new device fingerprint if the device wasn't reused
        // from settings, OR if any of the stored fingerprints are missing.
        // NOTE: The regeneration when our device model changes is to avoid
        // dangerously reusing the "previous phone's" unique hardware IDs.
        // WARNING TO CONTRIBUTORS: Only add new parameter-checks here if they
        // are CRITICALLY important to the particular device. We don't want to
        // frivolously force the users to generate new device IDs constantly.
        $resetCookieJar = false;
        if ($deviceString !== $savedDeviceString // Brand new device, or missing
            || empty($this->settings->get('uuid')) // one of the critically...
            // || $this->settings->get('phone_id') === null // ...important device... Empty string values could be valid.
            || empty($this->settings->get('device_id'))) { // ...parameters.
            // Erase all previously stored device-specific settings and cookies.
            $this->settings->eraseDeviceSettings();

            // Save the chosen device string to settings.
            if ($this->getPlatform() === 'ios') {
                $deviceString = 'ios';
            }

            $this->settings->set('devicestring', $deviceString);

            // Generate hardware fingerprints for the new device.
            if ($this->customDeviceId !== null) {
                $this->settings->set('device_id', $this->customDeviceId);
            } else {
                $this->settings->set('device_id', Signatures::generateDeviceId($this->getPlatform()));
            }

            if ($this->getIsAndroid()) {
                $this->settings->set('version_code', $this->getVersionCode());
                $result = Signatures::generateSpecialUUID();
                $phoneId = $result['phone_id'];
                $this->settings->set('offline_experiment', $result['offline_experiment']);
                $this->settings->set('phone_id', $phoneId);
            } else {
                $this->settings->set('phone_id', $this->settings->get('device_id'));
            }
            $this->settings->set('uuid', Signatures::generateUUID(true, true));

            if ($loginType === 'facebook') {
                $this->settings->set('fb_access_token', $password);
            }

            // Erase any stored account ID, to ensure that we detect ourselves
            // as logged-out. This will force a new relogin from the new device.
            $this->settings->set('account_id', '');

            // We'll also need to throw out all previous cookies.
            $resetCookieJar = true;
        }

        // Generate other missing values. These are for less critical parameters
        // that don't need to trigger a complete device reset like above. For
        // example, this is good for new parameters that Instagram introduces
        // over time, since those can be added one-by-one over time here without
        // needing to wipe/reset the whole device.
        if (empty($this->settings->get('advertising_id'))) {
            $this->settings->set('advertising_id', Signatures::generateUUID());
        }
        if (empty($this->settings->get('session_id'))) {
            $this->settings->set('session_id', Signatures::generateUUID());
        }
        if (empty($this->settings->get('offline_experiment'))) {
            $result = Signatures::generateSpecialUUID($this->settings->get('phone_id'));
            $this->settings->set('offline_experiment', $result['offline_experiment']);
        }

        // Store various important parameters for easy access.
        $this->username = $username;
        $this->password = $password;
        $this->uuid = $this->settings->get('uuid');
        $this->advertising_id = $this->settings->get('advertising_id');
        $this->device_id = $this->settings->get('device_id');
        $this->phone_id = $this->settings->get('phone_id');

        $this->session_id = $this->settings->get('session_id');
        if ($loginType === 'facebook') {
            $this->fb_access_token = $this->settings->get('fb_access_token');
        }
        $this->experiments = $this->settings->getExperiments();

        // Load the previous session details if we're possibly logged in.
        if ($this->settings->get('authorization_header') !== null) {
            $authorizationData = json_decode(base64_decode(explode(':', $this->settings->get('authorization_header'))[2]), true);
        }
        if (!isset($authorizationData['sessionid'])) {
            if (!$resetCookieJar && $this->settings->isMaybeLoggedIn()) {
                $this->isMaybeLoggedIn = true;
                $this->account_id = $this->settings->get('account_id');
            } else {
                $this->isMaybeLoggedIn = false;
                $this->account_id = null;
            }
        } else {
            $this->isMaybeLoggedIn = true;
            if (!isset($authorizationData['ds_user_id'])) {
                $this->account_id = $this->settings->get('account_id');
            } else {
                if ($this->settings->get('account_id') === null) {
                    $this->settings->set('account_id', $authorizationData['ds_user_id']);
                }
                $this->account_id = $authorizationData['ds_user_id'];
            }
        }
        $this->loginAttemptCount = 1;

        // Configures Client for current user AND updates isMaybeLoggedIn state
        // if it fails to load the expected cookies from the user's jar.
        // Must be done last here, so that isMaybeLoggedIn is properly updated!
        // NOTE: If we generated a new device we start a new cookie jar.
        $this->client->updateFromCurrentSettings($resetCookieJar);
    }

    /**
     * Set the active account for the class instance, without knowing password.
     *
     * This internal function is used by all unauthenticated pre-login functions
     * whenever they need to perform unauthenticated requests, such as looking
     * up a user's account recovery options.
     *
     * `WARNING:` A user database entry will be created for every username you
     * set as the active user, exactly like the normal `_setUser()` function.
     * This is necessary so that we generate a user-device and data storage for
     * each given username, which gives us necessary data such as a "device ID"
     * for the new user's virtual device, to use in various API-call parameters.
     *
     * `WARNING:` This function CANNOT be used for performing logins, since
     * Instagram will validate the password and will reject the missing
     * password. It is ONLY meant to be used for *RECOVERY* PRE-LOGIN calls that
     * need device parameters when the user DOESN'T KNOW their password yet.
     *
     * @param string $username Your Instagram username.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     */
    public function setUserWithoutPassword(
        $username
    ) {
        if (empty($username) || !is_string($username)) {
            throw new \InvalidArgumentException('You must provide a username.');
        }

        // Switch the currently active user/pass if the username is different.
        // NOTE: Creates a user database (device) for the user if they're new!
        // NOTE: Because we don't know their password, we'll mark the user as
        // having "NOPASSWORD" as pwd. The user will fix that when/if they call
        // `login()` with the ACTUAL password, which will tell us what it is.
        // We CANNOT use an empty string since `_setUser()` will not allow that!
        // NOTE: If the user tries to look up themselves WHILE they are logged
        // in, we'll correctly NOT call `_setUser()` since they're already set.
        if ($this->username !== $username) {
            $this->_setUser('regular', $username, 'NOPASSWORD');
        }
    }

    /**
     * Updates the internal state after a successful login.
     *
     * @param Response\LoginResponse $response The login response.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     */
    protected function _updateLoginState(
        Response\LoginResponse $response
    ) {
        if (self::$skipAccountValidation === false) {
            // This check is just protection against accidental bugs. It makes sure
            // that we always call this function with a *successful* login response!
            if (!$response instanceof Response\LoginResponse
                || !$response->isOk() || empty($response->getLoggedInUser()->getPk())) {
                throw new \InvalidArgumentException('Invalid login response provided to _updateLoginState().');
            }

            $this->isMaybeLoggedIn = true;
            $this->account_id = $response->getLoggedInUser()->getPk();
            $this->settings->set('account_id', $this->account_id);
            $this->settings->set('last_login', time());
        }
    }

    /**
     * Sends pre-login flow. This is required to emulate real device behavior.
     *
     * @throws Exception\InstagramException
     */
    protected function _sendPreLoginFlow()
    {
        // Reset zero rating rewrite rules.
        $this->client->zeroRating()->reset();
        // Calling this non-token API will put a csrftoken in our cookie
        // jar. We must do this before any functions that require a token.

        $this->settings->set('nav_started', 'false');
        if ($this->getIsAndroid()) {
            // Start emulating batch requests with Pidgeon Raw Client Time.
            $this->client->startEmulatingBatch();

            $this->event->sendInstagramDeviceIds($this->loginWaterfallId);
            $this->event->sendApkTestingExposure();
            $this->event->sendApkSignatureV2();
            $this->event->sendEmergencyPushInitialVersion();

            try {
                try {
                    $this->internal->fetchZeroRatingToken('token_expired', false);
                    $this->internal->createAndroidKeystore();
                    // $this->account->setContactPointPrefill('prefill');
                    /*
                    $this->internal->sendGraph('455411352809009551099714876', [
                        'input' => [
                            'app_scoped_id'     => $this->uuid,
                            'appid'             => Constants::FACEBOOK_ANALYTICS_APPLICATION_ID,
                            'family_device_id'  => $this->phone_id,
                        ]
                    ], 'FamilyDeviceIDAppScopedDeviceIDSyncMutation', false); */
                } catch (\Exception $e) {
                    // pass. Checkpoint wont happen on this step and if server is congested an HTML response will be returned. Exception EmptyResponseException.
                }

                // $this->event->sendZeroCarrierSignal();
                // $this->internal->bootstrapMsisdnHeader();
                // $this->internal->readMsisdnHeader('default');

                /* QE SYNC DISABLED
                try {
                    $this->internal->syncDeviceFeatures(true);
                } catch (\Exception $e) {
                    // pass
                }
                */

                /*
                //THIS WAS USED IN PRELOGIN FOR OBTAINING DEVICE EXPERIMENTS AND PUBLIC KEY TO ENCRYPT PASSWORDS
                //SEEMS IT IS NOT BEING USED ANYMORE WITH BLOKS LOGIN
                */
                if (self::$useBloksLogin === false) {
                    $mobileConfigResponse = $this->internal->getMobileConfig(true)->getHttpResponse();
                    $this->settings->set('public_key', $mobileConfigResponse->getHeaderLine('ig-set-password-encryption-pub-key'));
                    $this->settings->set('public_key_id', $mobileConfigResponse->getHeaderLine('ig-set-password-encryption-key-id'));
                }

                // $this->internal->bootstrapMsisdnHeader();
                /*
                try {
                    //$this->internal->logAttribution();
                    $this->internal->sendGraph('455411352809009551099714876', [
                        'input' => [
                            'app_scoped_id'     => $this->uuid,
                            'appid'             => Constants::FACEBOOK_ANALYTICS_APPLICATION_ID,
                            'family_device_id'  => $this->phone_id,
                        ]
                    ], 'FamilyDeviceIDAppScopedDeviceIDSyncMutation', false);
                    $this->people->getNonExpiredFriendRequests();
                } catch (\InstagramAPI\Exception\InstagramException $e) {
                    // pass
                }
                */
            } finally {
                // Stops emulating batch requests.
                $this->client->stopEmulatingBatch();
            }

            // Start emulating batch requests with Pidgeon Raw Client Time.
            $this->client->startEmulatingBatch();
        } else {
            // IOS. PROBABLY USING BLOKS LOGIN INSTEAD OF THE FOLLOWING.
            $mobileConfigResponse = $this->internal->getMobileConfig(true)->getHttpResponse();
            $this->settings->set('public_key', $mobileConfigResponse->getHeaderLine('ig-set-password-encryption-pub-key'));
            $this->settings->set('public_key_id', $mobileConfigResponse->getHeaderLine('ig-set-password-encryption-key-id'));
        }

        try {
            // $this->internal->readMsisdnHeader('default', true);
            /*
            try {
                $this->account->setContactPointPrefill('prefill');
            } catch (\Exception $e) {
                //pass
            }
            */

            if ($this->getPlatform() === 'ios') {
                $this->account->getNamePrefill();
            }
            // WAS USED BEFORE BLOKS LOGIN
            if (self::$useBloksLogin === false) {
                $this->internal->getMobileConfig(true);
            }

            /* QE SYNC DISABLED
            try {
                $this->internal->syncDeviceFeatures(true, true);
            } catch (\Exception $e) {
                //pass
            }
            */
        } finally {
            // Stops emulating batch requests.
            $this->client->stopEmulatingBatch();
        }
    }

    /**
     * Registers available Push channels during the login flow.
     */
    protected function _registerPushChannels()
    {
        // Forcibly remove the stored token value if >24 hours old.
        // This prevents us from constantly re-registering the user's
        // "useless" token if they have stopped using the Push features.
        try {
            $lastFbnsToken = (int) $this->settings->get('last_fbns_token');
        } catch (\Exception $e) {
            $lastFbnsToken = null;
        }
        if (!$lastFbnsToken || $lastFbnsToken < strtotime('-24 hours')) {
            try {
                $this->settings->set('fbns_token', '');
            } catch (\Exception $e) {
                // Ignore storage errors.
            }

            return;
        }

        // Read our token from the storage.
        try {
            $fbnsToken = $this->settings->get('fbns_token');
        } catch (\Exception $e) {
            $fbnsToken = null;
        }
        if ($fbnsToken === null) {
            return;
        }

        // Register our last token since we had a fresh (age <24 hours) one,
        // or clear our stored token if we fail to register it again.
        try {
            $this->push->register('mqtt', $fbnsToken);
        } catch (\Exception $e) {
            try {
                $this->settings->set('fbns_token', '');
            } catch (\Exception $e) {
                // Ignore storage errors.
            }
        }
    }

    /**
     * Sends login flow. This is required to emulate real device behavior.
     *
     * @param bool $justLoggedIn       Whether we have just performed a full
     *                                 relogin (rather than doing a resume).
     * @param int  $appRefreshInterval See `login()` for description of this
     *                                 parameter.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\InstagramException
     *
     * @return Response\LoginResponse|null A login response if a
     *                                                   full (re-)login is
     *                                                   needed during the login
     *                                                   flow attempt, otherwise
     *                                                   `NULL`.
     */
    protected function _sendLoginFlow(
        $justLoggedIn,
        $appRefreshInterval = 21600
    ) {
        if (!is_int($appRefreshInterval) || $appRefreshInterval < 0) {
            throw new \InvalidArgumentException("Instagram's app state refresh interval must be a positive integer.");
        }
        if ($appRefreshInterval > 21600) {
            throw new \InvalidArgumentException("Instagram's app state refresh interval is NOT allowed to be higher than 6 hours, and the lower the better!");
        }

        if (self::$skipLoginFlowAtMyOwnRisk) {
            return null;
        }

        $this->isLoginFlow = true;

        // SUPER IMPORTANT:
        //
        // STOP trying to ask us to remove this code section!
        //
        // EVERY time the user presses their device's home button to leave the
        // app and then comes back to the app, Instagram does ALL of these things
        // to refresh its internal app state. We MUST emulate that perfectly,
        // otherwise Instagram will silently detect you as a "fake" client
        // after a while!
        //
        // You can configure the login's $appRefreshInterval in the function
        // parameter above, but you should keep it VERY frequent (definitely
        // NEVER longer than 6 hours), so that Instagram sees you as a real
        // client that keeps quitting and opening their app like a REAL user!
        //
        // Otherwise they WILL detect you as a bot and silently BLOCK features
        // or even ban you.
        //
        // You have been warned.
        if ($justLoggedIn) {
            // Reset zero rating rewrite rules.
            try {
                $this->client->zeroRating()->reset();
                $this->event->sendCellularDataOpt();
                $this->event->legacyFbTokenOnIgAccessControl('token_access', 'ig_login_util', 'LoginUtil');
                $this->event->legacyFbTokenOnIgAccessControl('token_access', 'ig_login_util', 'LoginUtil');
                $this->event->legacyFbTokenOnIgAccessControl('token_access', 'ig_login_util', 'LoginUtil');
                $this->event->sendDarkModeOpt();
            } catch (\Exception $e) {
                // pass
            }
            // Perform the "user has just done a full login" API flow.

            // Batch request 1
            $this->client->startEmulatingBatch();
            $feed = null;

            try {
                $this->internal->fetchZeroRatingToken('token_expired', false, false);
            } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                throw $e;
            } catch (\Exception $e) {
                // pass
            }

            try {
                $this->internal->getLoomFetchConfig();

                if ($this->settings->get('business_account')) {
                    $this->business->getMonetizationProductsEligibilityData();
                    $this->business->getMonetizationProductsGating();
                }

                $response = $this->account->getAccountFamily();
                $this->request($response->getCurrentAccount()->getProfilePicUrl())->getRawResponse();
                if (self::$useBloksLogin === true) {
                    $response = $this->internal->getBloksSaveCredentialsScreen();
                    sleep(mt_rand(1, 3));
                }
                // $this->internal->sendGraph('4703444349433374284764063878', ['is_pando' => true], 'AREffectConsentStateQuery', 'viewer', false, 'pando');

                $this->event->sendZeroCarrierSignal();
                $this->internal->getMobileConfig(true);
            } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                throw $e;
            } catch (\Exception $e) {
                // pass
            } finally {
                // Stops emulating batch requests.
                $this->client->stopEmulatingBatch();

                try {
                    $this->internal->getMobileConfig(false);
                    $this->event->sendNavigation('button', 'com.bloks.www.caa.login.login_homepage', 'com.bloks.www.caa.login.save-credentials');

                    /*
                    $this->internal->sendGraph('8463128007441046037090177764',
                        [
                            'configType' => 'viper'
                        ],
                        'IgmConfigSyncQuery', 'xig_twoMeasurement_platform_config', false, 'pando');
                    */
                    $this->_registerPushChannels();
                    $this->internal->sendGraph('11424838746690953787234584958', [], 'FxIgFetaInfoQuery', 'fx_pf_feta_info', false, 'pando');
                    $this->internal->sendGraph('11674382495679744485820947859', [
                        'caller_name'   => 'fx_product_foundation_client_FXOnline_client_cache',
                    ], 'FxIgLinkageCacheQuery', 'xe_client_cache_accounts', false, 'pando');
                    $this->internal->getNavBarCameraDestination();
                } catch (\Exception $e) {
                    // pass
                }
            }

            // Batch request 2
            $this->client->startEmulatingBatch();

            try {
                $this->internal->getAsyncNdxIgSteps('NDX_IG4A_MA_FEATURE');
                $this->event->sendNdxAction('ig_server_eligibility_check');
                $this->event->sendNdxAction('ig4a_ndx_request');
                $this->event->sendNdxAction('contact_importer');
                $this->event->sendNdxAction('multiple_account');
                $this->event->sendNdxAction('phone_number_acquisition');
                $this->event->sendNdxAction('email_acquisition');
                $this->event->sendNdxAction('location_service');

                $this->event->sendDevicePermissions('InstagramDevicePermissionLocationPublicAPI');

                $this->people->getLimitedInteractionsReminder();

                $this->settings->set('salt_ids', '220140399,332020310');
                $this->people->getSharePrefill();

                $requestId = Signatures::generateUUID();
                $this->event->sendInstagramFeedRequestSent($requestId, 'cold_start_fetch');
                $this->setNavChain('');
                $this->settings->set('salt_ids', '220140399,332020310,974466465,974460658');
                $feed = $this->timeline->getTimelineFeed(null, [
                    'reason'        => Constants::REASONS[0],
                    'request_id'    => $requestId,
                ]);
                $this->initTimelineFeed = $feed;
                $this->event->sendNavigation('cold_start', 'login', 'feed_timeline');
                $this->event->sendInstagramFeedRequestSent($requestId, 'cold_start_fetch', true);
                $items = $feed->getFeedItems();
                $items = array_slice($items, 0, 2);

                foreach ($items as $item) {
                    if ($item->getMediaOrAd() !== null) {
                        switch ($item->getMediaOrAd()->getMediaType()) {
                            case 1:
                                $this->event->sendOrganicMediaImpression($item->getMediaOrAd(), 'feed_timeline');
                                break;
                            case 2:
                                $this->event->sendOrganicViewedImpression($item->getMediaOrAd(), 'feed_timeline');
                                // Not playing the video.
                                break;
                            case 8:
                                $carouselItem = $item->getMediaOrAd()->getCarouselMedia()[0]; // First item of the carousel.
                                if ($carouselItem->getMediaType() === 1) {
                                    $this->event->sendOrganicMediaImpression(
                                        $item->getMediaOrAd(),
                                        'feed_timeline',
                                        [
                                            'feed_request_id'   => null,
                                        ]
                                    );
                                } else {
                                    $this->event->sendOrganicViewedImpression(
                                        $item->getMediaOrAd(),
                                        'feed_timeline',
                                        null,
                                        null,
                                        null,
                                        [
                                            'feed_request_id'   => null,
                                        ]
                                    );
                                }
                                break;
                        }
                    }
                    $previewComments = ($item->getMediaOrAd() === null) ? [] : $item->getMediaOrAd()->getPreviewComments();

                    if ($previewComments !== null) {
                        foreach ($previewComments as $comment) {
                            $this->event->sendCommentImpression($item->getMediaOrAd(), $comment->getUserId(), $comment->getPk(), $comment->getCommentLikeCount(), 'feed_timeline');
                        }
                    }
                }
            } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                throw $e;
            } catch (Exception\LoginRequiredException $e) {
                throw $e;
            } catch (\Exception $e) {
                // pass
            }

            self::$sendAsync = true;

            try {
                // $this->internal->sendGraph('47034443410017494685272535358', [], 'AREffectConsentStateQuery', true);

                $requestId = Signatures::generateUUID();
                $traySessionId = Signatures::generateUUID();
                $this->event->sendStoriesRequest($traySessionId, $requestId, 'cold_start');

                $trayFeed = $this->story->getReelsTrayFeed('cold_start', $requestId, $traySessionId);
                $this->initTrayFeed = $trayFeed;

                $this->internal->sendGraph('33052919472135518510885263591', [], 'BasicAdsOptInQuery', 'xfb_user_basic_ads_preferences', false, 'pando');
                $this->internal->sendGraph('35850666251457231147855668495', [], 'AFSOptInQuery', 'AFSStatusGraphQLWrapper', false, 'pando');

                $this->internal->getAsyncNdxIgSteps('NDX_IG_IMMERSIVE');
            } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                throw $e;
            } catch (\Exception $e) {
                // pass
            } finally {
                // Stops emulating batch requests
                $this->client->stopEmulatingBatch();

                try {
                    $this->account->getBadgeNotifications();

                    $this->internal->sendGraph('205278892814757334779864170428', [
                        'languages'     => ['nolang'],
                        'service_ids'   => ['MUTED_WORDS'],
                    ], 'IGContentFilterDictionaryLookupQuery', 'ig_content_filter_dictionary_lookup_query', false, 'pando');
                } catch (\Exception $e) {
                    // pass
                }
            }

            try {
                try {
                    $this->event->sendNavigation('cold_start', 'login', 'feed_timeline');
                    $this->settings->set('nav_started', 'true');
                } catch (\Exception $e) {
                    // pass
                }

                // $this->internal->cdnRmd();
            } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                throw $e;
            } catch (\Exception $e) {
                // pass
            }

            // Batch request 3
            $this->client->startEmulatingBatch();

            $this->settings->set('salt_ids', '');

            try {
                if ($feed !== null) {
                    $feedTimelineItems = $feed->getFeedItems();
                    if (count($feedTimelineItems) > 0 && $feedTimelineItems[0]->getEndOfFeedDemarcator() !== null) {
                        $this->timeline->getUserFeed($this->account_id);
                        $this->people->getInfoById($this->account_id, null, null, true); // Prefetch
                        $this->highlight->getUserFeed($this->account_id);
                        $this->people->getCreatorInfo($this->account_id);
                    }
                }
                // $this->internal->logResurrectAttribution();
                // $this->internal->getDeviceCapabilitiesDecisions();
                // $this->people->getBootstrapUsers();

                self::$sendAsync = false;
                $this->internal->getQPFetch(['LOGIN_INTERSTITIAL']);
                self::$sendAsync = true;

                $this->settings->set('salt_ids', '');
                $this->media->getBlockedMedia();
                $this->internal->sendGraph('25336029839814386604447461985', [
                    'params' => [
                        'params'                => '{"params":"{\"server_params\":{\"extras_json\":\"{\\\"is_account_linked\\\":true,\\\"newly_linked_accounts\\\":false}\",\"crosspost_upsell_variant\":\"bottomsheet_close_friends_story_feed\",\"should_dismiss\":false,\"crosspost_upsell_entrypoint\":\"IG_STORY_COMPOSER_CLOSE_FRIENDS_STORY_BUTTON\"}}"}',
                        'infra_params'          => ['device_id' => $this->device_id],
                        'bloks_versioning_id'   => Constants::BLOCK_VERSIONING_ID,
                        'app_id'                => 'com.bloks.www.cxp.xposting_upsells.native_shell',
                    ],
                    'bk_context'    => [
                        'is_flipper_enabled'            => false,
                        'theme_params'                  => [],
                        'debug_tooling_metadata_token'  => null,
                    ],
                ], 'IGBloksAppRootQuery', 'bloks_app', false, 'pando', false, true);

                $this->story->getInjectedStories([$this->account_id], $traySessionId);
                // $this->internal->sendGraph('279018452917733073575656047369', [], 'FetchAttributionEventComplianceAction', 'fetch_attribution_event_compliance_action', true, 'pando');
                // $this->reel->discover();
                $this->people->getInfoById($this->account_id);
            } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                throw $e;
            } catch (\Exception $e) {
                // pass
            } finally {
                // Stops emulating batch requests
                $this->client->stopEmulatingBatch();

                try {
                    $this->creative->sendSupportedCapabilities();
                } catch (\Exception $e) {
                    // pass
                }
            }

            /*
            try {
                $this->account->getProcessContactPointSignals();
            } catch (\Exception $e) {
                // pass
            }
            */

            // Batch request 4
            $this->client->startEmulatingBatch();

            try {
                // $this->timeline->getTimelineFeed(); TODO
                $this->internal->sendGraph(
                    '97942539015262622076776956304',
                    [
                        'usecase'           => 'IG_ADS_PREFETCH',
                        'test_id'           => '59705010009496',
                        'purpose'           => 'product::ads_personalization',
                        'version'           => '0.0.5',
                        'client_msg_type'   => 'INFER',
                    ],
                    'OnDeviceFLFeatures',
                    'on_device_fl_features',
                    false,
                    'pando',
                    false,
                    true
                );

                $rand = mt_rand(6000000000000000, 6099999999999999) / 10000000000000000;
                $formatRand = rtrim(sprintf('%.16f', $rand), '0');

                if (substr($formatRand, -1) == '.') {
                    $formatRand .= '0';
                }

                $this->internal->sendGraph(
                    '387719987211424210844178051540',
                    [
                        'use_case_version'      => '0.0.5',
                        'use_case'              => 'IG_ADS_PREFETCH',
                        'flow'                  => 'PREDICT',
                        'examples'              => [
                            [
                                'timestamp' => time(),
                                'features'  => [
                                    [
                                        'value' => '10',
                                        'id'    => '3614',
                                    ],
                                    [
                                        'value' => $formatRand,
                                        'id'    => -1,
                                    ],
                                    [
                                        'value' => '40532000',
                                        'id'    => '2620',
                                    ],
                                    [
                                        'value' => '-1',
                                        'id'    => '2474',
                                    ],
                                    [
                                        'value' => '10',
                                        'id'    => '100001',
                                    ],
                                    [
                                        'value' => '0',
                                        'id'    => '100002',
                                    ],
                                ],
                                'id'        => $traySessionId,
                                'context'   => $traySessionId,
                            ],
                        ],
                    ],
                    'DcpFeaturesUpload',
                    'xfb_post_dcp_features_upload',
                    false,
                    'pando',
                    false,
                    true
                );

                $this->internal->sendGraph(
                    '21631519914279241558813005594',
                    [
                        'service_names' => [
                            'CROSS_POSTING_SETTING',
                        ],
                        'custom_partner_params' => [
                            [
                                'value' => 'FB',
                                'key'   => 'CROSSPOSTING_DESTINATION_APP',
                            ],
                            [
                                'value' => '',
                                'key'   => 'CROSSPOSTING_SHARE_TO_SURFACE',
                            ],
                            [
                                'value' => 'true',
                                'key'   => 'OVERRIDE_USER_VALIDATION_WITH_CXP_ELIGIBILITY_RULE',
                            ],
                        ],
                        'client_caller_name'    => 'ig_android_service_cache_crossposting_setting',
                        'caller_name'           => 'fx_product_foui_Afion_client_FXOnline_client_cache',
                    ],
                    'FxIgConnectedServicesInfoQuery',
                    'fx_service_cache',
                    false,
                    'pando',
                    false,
                    true
                );

                /*
                $this->internal->sendGraph('18293997046226642457734318433', [
                    'is_pando' => true,
                    'input'    => [
                        'actor_id'              => $this->account_id,
                        'client_mutation_id'    => \InstagramAPI\Signatures::generateUUID(),
                        'events'                => [
                            'adid'                  => null,
                            'event_name'            => 'RESURRECTION',
                            'no_advertisement_id'   => false,
                        ],
                        'log_only'              => true,
                    ],
                ], 'ReportAttributionEventsMutation', 'report_attribution_events', false, 'pando');
                */
            } catch (\Exception $e) {
                // pass
            }

            self::$sendAsync = false;

            try {
                // $this->discover->getMixedMedia();
                $this->internal->writeSupportedCapabilities();
                $this->reel->getShareToFbConfig();
            } catch (\Exception $e) {
                // pass
            }

            self::$sendAsync = true;

            try {
                $this->internal->sendGraph('43230821013683556483393399494', [], 'IGFxLinkedAccountsQuery', 'fx_linked_accounts', false, 'pando');
                // $this->internal->sendGraph('171864746410373358862136873197', ['is_pando' => true, 'data' => (object) []], 'ListCallsQuery', 'list_ig_calls_paginated_query', false, 'pando');
                /*$this->internal->sendGraph('13513772661704761708109730075', [
                    'is_pando' => true,
                    'input'    => [
                        'caller_context'    => [
                            'caller'                => 'StartupManager',
                            'function_credential'   => 'function_credential'
                        ],
                        'key'   => '1L1D'
                    ],
                ], 'IGOneLinkMiddlewareWhatsAppBusinessQuery', 'xfb_one_link_monoschema', false, 'pando');*/
                $this->internal->sendGraph('14088097634272511800572157181', [
                    'client_states'    => [
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_AUDIENCE_CHANGE_FEED',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_MIGRATION_FEED_WAVE2',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_MIGRATION_STORIES_WAVE2',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_REEL_CCP_MIGRATION_FEED',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_REEL_CCP_MIGRATION_STORY',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_STORY_REEL_CCP_MIGRATION_FEED',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_FEED_REEL_CCP_MIGRATION_STORY',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_UNIFIED_STORIES_FEED',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_UNLINKED_USER_FEED',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'BOTTOMSHEET_XAR_REELS',
                            'sequence_number'       => 2,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'DIALOG_FEED',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'DIALOG_STORY',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'TOOLTIP_AUTOSHARE_FEED',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'TOOLTIP_CURRENTLY_SHARING_FEED',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'TOOLTIP_NUX_STORIES',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'TOOLTIP_PAGE_SHARE_FEED',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'TOOLTIP_SHORTCUT_DESTINATION_PICKER_NOT_SHARING_STORIES',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                        [
                            'last_impression_time'  => 0,
                            'variant'               => 'TOOLTIP_SHORTCUT_DESTINATION_PICKER_STORIES',
                            'sequence_number'       => 0,
                            'impression_count'      => 0,
                        ],
                    ],
                ], 'SyncCXPNoticeStateMutation', 'xcxp_sync_notice_state', false, 'pando', false, true);
                $this->internal->sendGraph('17657533919338591111083362666', [], 'HasAvatarQuery', 'viewer', false, 'pando');

                try {
                    $this->internal->storeClientPushPermissions();
                    $this->internal->getViewableStatuses(true);
                    $this->account->getPresenceStatus();
                    $this->direct->getHasInteropUpgraded();
                    // $this->internal->getNotificationsSettings();
                } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    // pass
                }
            } catch (\Exception $e) {
                // pass
            } finally {
                // Stops emulating batch requests
                $this->client->stopEmulatingBatch();

                try {
                    // $this->direct->getInbox(null, null, 0);
                } catch (\Exception $e) {
                    // pass
                }
            }

            try {
                $this->discover->getExploreFeed(null, Signatures::generateUUID(), null, true);
            } catch (\Exception $e) {
                // pass
            }

            // Batch request 5
            $this->client->startEmulatingBatch();

            try {
                try {
                    $this->direct->getPresences();
                    $this->direct->getInbox(null, null, 15, false, 'all', 'initial_snapshot');

                    // $this->internal->sendGraph('243882031010379133527862780970', [], 'FBToIGDefaultAudienceBottomSheetQuery', false, 'graphservice');
                    // $this->internal->sendGraph('338246149711919572858330660779', ['is_pando' => true], 'FBToIGDefaultAudienceSettingQuery', true, 'pando');
                } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    // pass
                }
            } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                throw $e;
            } catch (\Exception $e) {
                // pass
            } finally {
                // Stops emulating batch requests
                $this->client->stopEmulatingBatch();

                try {
                    $this->settings->set('salt_ids', '220140399,332020310,974466465,974460658');
                    $this->account->getBadgeNotifications();
                } catch (\Exception $e) {
                    // pass
                }
            }

            $this->client->startEmulatingBatch();

            // Batch request 5
            try {
                // $this->story->getReelsMediaFeed($this->account_id);
                /*
                try {
                    $this->internal->sendGraph('2360595178779351530479091981', ['is_pando' => true, 'fb_profile_image_size' => 200], 'FxIGMasterAccountQuery', 'fxcal_accounts', false, 'pando');
                }  catch (\Exception $e) {
                    // pass
                }
                */
                /*
                $this->internal->sendGraph('21564406653994218282552117012', [
                    'is_pando' => true,
                    'configs_request' => [
                        'crosspost_app_surface_list' => [
                            [
                                'cross_app_share_type'  => 'CROSSPOST',
                                'destination_app'       => 'FB',
                                'destination_surface'   => 'REELS',
                                'source_surface'        => 'REELS'
                            ]
                        ],
                        'source_app' => 'IG'
                    ]
                ], 'CrossPostingContentCompatibilityConfig', 'xcxp_unified_crossposting_configs_root', false, 'pando');
                */
                $this->internal->getNotes();

                /*
                try {
                    $this->internal->sendGraph('215817804115327440933115577895',
                    [
                        'is_pando'      => true,
                        'user_id'       => $this->account_id,
                        'query_params'  => [
                            'instruction_key_ids'   => ['4546360412114313'], // mobile config 57985
                            'refresh_only'          => true,
                        ],
                    ], 'IGAvatarStickersForKeysQuery', 'fetch__IGUser', false, 'pando');
                } catch (\Exception $e) {
                    // pass
                }
                */

                /*
                if ($this->getPlatform() === 'android') {
                    $this->internal->getArlinkDownloadInfo();
                }
                */
            } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                throw $e;
            } catch (\Exception $e) {
                // pass
            } finally {
                // Stops emulating batch requests
                $this->client->stopEmulatingBatch();

                try {
                    $this->settings->set('salt_ids', '');
                    $this->internal->getQPFetch(['FLOATING_BANNER', 'MEGAPHONE', 'TOOLTIP', 'INTERSTITIAL', 'BOTTOMSHEET']);
                    $this->people->getSharePrefill(true);
                } catch (\Exception $e) {
                    // pass
                }
            }
            self::$sendAsync = false;

        /*
        // Batch request 5
        $this->client->startEmulatingBatch();

        try {
            $this->internal->getQPCooldowns();
        } catch (\Exception $e) {
            // pass
        } finally {
            // Stops emulating batch requests
            $this->client->stopEmulatingBatch();
        }
        */

        /*
        try {
            $this->internal->getFacebookOTA();
        } catch (\Exception $e) {
        }
        */
        } else {
            $lastLoginTime = $this->settings->get('last_login');
            $isSessionExpired = $lastLoginTime === null || (time() - $lastLoginTime) > $appRefreshInterval;

            // Perform the "user has returned to their already-logged in app,
            // so refresh all feeds to check for news" API flow.
            if ($isSessionExpired) {
                // Batch Request 1
                $this->client->startEmulatingBatch();

                try {
                    // Act like a real logged in app client refreshing its news timeline.
                    // This also lets us detect if we're still logged in with a valid session.
                    try {
                        $trayFeed = $this->story->getReelsTrayFeed('cold_start');
                        $this->initTrayFeed = $trayFeed;
                    } catch (Exception\LoginRequiredException $e) {
                        if (!self::$manuallyManageLoginException) {
                            if (isset($e->getResponse()->asArray()['logout_reason'])) {
                                try {
                                    $this->performPostForceLogoutActions($e->getResponse()->asArray()['logout_reason'], 'feed/reels_tray/');
                                } catch (\Exception $e) {
                                    // pass
                                }

                                return $this->_login($this->username, $this->password, true, $appRefreshInterval);
                            } else {
                                // If our session cookies are expired, we were now told to login,
                                // so handle that by running a forced relogin in that case!
                                return $this->_login($this->username, $this->password, true, $appRefreshInterval);
                            }
                        } else {
                            throw $e;
                        }
                    } catch (Exception\EmptyResponseException|Exception\ThrottledException $e) {
                        // This can have EmptyResponse, and that's ok.
                    }
                    $feed = $this->timeline->getTimelineFeed(null, [
                        'is_pull_to_refresh' => $isSessionExpired ? null : mt_rand(1, 3) < 3,
                    ]);

                    $this->initTimelineFeed = $feed;
                    $items = $feed->getFeedItems();
                    if (is_array($items)) {
                        $items = array_slice($items, 0, 2);
                    } else {
                        $items = [];
                    }

                    foreach ($items as $item) {
                        if ($item->getMediaOrAd() !== null) {
                            switch ($item->getMediaOrAd()->getMediaType()) {
                                case 1:
                                    $this->event->sendOrganicMediaImpression($item->getMediaOrAd(), 'feed_timeline');
                                    break;
                                case 2:
                                    $this->event->sendOrganicViewedImpression($item->getMediaOrAd(), 'feed_timeline');
                                    // Not playing the video.
                                    break;
                                case 8:
                                    $carouselItem = $item->getMediaOrAd()->getCarouselMedia()[0]; // First item of the carousel.
                                    if ($carouselItem->getMediaType() === 1) {
                                        $this->event->sendOrganicMediaImpression(
                                            $item->getMediaOrAd(),
                                            'feed_timeline',
                                            [
                                                'feed_request_id'   => null,
                                            ]
                                        );
                                    } else {
                                        $this->event->sendOrganicViewedImpression(
                                            $item->getMediaOrAd(),
                                            'feed_timeline',
                                            null,
                                            null,
                                            null,
                                            [
                                                'feed_request_id'   => null,
                                            ]
                                        );
                                    }
                                    break;
                            }
                        }
                        $previewComments = ($item->getMediaOrAd() === null) ? [] : $item->getMediaOrAd()->getPreviewComments();

                        if ($previewComments !== null) {
                            foreach ($previewComments as $comment) {
                                $this->event->sendCommentImpression($item->getMediaOrAd(), $comment->getUserId(), $comment->getPk(), $comment->getCommentLikeCount(), 'feed_timeline');
                            }
                        }
                    }

                    try {
                        $this->people->getSharePrefill();
                        // $this->people->getRecentActivityInbox();
                    } catch (Exception\LoginRequiredException $e) {
                        throw $e;
                    } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                        throw $e;
                    } catch (\Exception $e) {
                        // pass
                    }
                } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                    throw $e;
                } catch (Exception\LoginRequiredException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    // pass
                } finally {
                    // Stops emulating batch requests.
                    $this->client->stopEmulatingBatch();
                }

                self::$sendAsync = true;
                // Batch Request 2
                $this->client->startEmulatingBatch();

                try {
                    // $this->people->getSharePrefill();
                    // $this->people->getRecentActivityInbox();
                    $this->people->getInfoById($this->account_id);
                    // $this->internal->getDeviceCapabilitiesDecisions();
                } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                    throw $e;
                } catch (Exception\LoginRequiredException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    // pass
                } finally {
                    // Stops emulating batch requests.
                    $this->client->stopEmulatingBatch();
                }

                // Batch Request 3
                $this->client->startEmulatingBatch();

                try {
                    $this->direct->getPresences();
                    $this->discover->getExploreFeed('', Signatures::generateUUID(), null, true, true);
                    $this->direct->getInbox();
                } catch (Exception\EmptyResponseException|Exception\ThrottledException $e) {
                    // This can have EmptyResponse, and that's ok.
                } catch (Exception\LoginRequiredException $e) {
                    throw $e;
                } finally {
                    // Stops emulating batch requests.
                    $this->client->stopEmulatingBatch();
                }

                $this->settings->set('last_login', time());

                // Generate and save a new application session ID.
                $this->session_id = Signatures::generateUUID();
                $this->settings->set('session_id', $this->session_id);

                // Do the rest of the "user is re-opening the app" API flow...
                // $this->people->getBootstrapUsers();

                // Start emulating batch requests with Pidgeon Raw Client Time.
                $this->client->startEmulatingBatch();

                try {
                    $this->internal->getQPFetch(['FLOATING_BANNER', 'MEGAPHONE', 'TOOLTIP', 'INTERSTITIAL', 'BOTTOMSHEET']);
                    // $this->direct->getRankedRecipients('reshare', true);
                    // $this->direct->getRankedRecipients('raven', true);
                } catch (Exception\Checkpoint\ChallengeRequiredException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    // pass
                } finally {
                    $this->_registerPushChannels();
                    // Stops emulating batch requests.
                    $this->client->stopEmulatingBatch();
                }
                self::$sendAsync = false;
            } else {
                try {
                    $trayFeed = $this->story->getReelsTrayFeed('cold_start');
                    $this->initTrayFeed = $trayFeed;
                } catch (Exception\LoginRequiredException $e) {
                    if (!self::$manuallyManageLoginException) {
                        if (isset($e->getResponse()->asArray()['logout_reason'])) {
                            try {
                                $this->performPostForceLogoutActions($e->getResponse()->asArray()['logout_reason'], 'feed/reels_tray/');
                            } catch (\Exception $e) {
                                // pass
                            }

                            return $this->_login($this->username, $this->password, true, $appRefreshInterval);
                        } else {
                            // If our session cookies are expired, we were now told to login,
                            // so handle that by running a forced relogin in that case!
                            return $this->_login($this->username, $this->password, true, $appRefreshInterval);
                        }
                    } else {
                        throw $e;
                    }
                } catch (Exception\EmptyResponseException|Exception\ThrottledException $e) {
                    // This can have EmptyResponse, and that's ok.
                }
            }

            // Users normally resume their sessions, meaning that their
            // experiments never get synced and updated. So sync periodically.
            $lastExperimentsTime = $this->settings->get('last_experiments');
            if ($lastExperimentsTime === null || (time() - intval($lastExperimentsTime)) > self::EXPERIMENTS_REFRESH) {
                // Start emulating batch requests with Pidgeon Raw Client Time.
                // $this->client->startEmulatingBatch();
                try {
                    $this->internal->getMobileConfig(true);
                    $this->internal->getMobileConfig(false);
                } catch (\Exception $e) {
                    // Ignore exception if 500 is received.
                }
            }

            // Update zero rating token when it has been expired.
            $expired = time() - (int) $this->settings->get('zr_expires');

            try {
                if ($expired > 0) {
                    $this->client->zeroRating()->reset();
                    $this->internal->fetchZeroRatingToken($expired > 7200 ? 'token_stale' : 'token_expired', false, false);
                    $this->event->sendZeroCarrierSignal();
                }
            } catch (Exception\InstagramException $e) {
                // pass
            }
        }

        $this->event->forceSendBatch();
        // We've now performed a login or resumed a session. Forcibly write our
        // cookies to the storage, to ensure that the storage doesn't miss them
        // in case something bad happens to PHP after this moment.
        $this->client->saveCookieJar();
        $this->isLoginFlow = false;

        return null;
    }

    /**
     * Perform post force logout actions.
     *
     * @param int    $logoutReason Logout reason.
     * @param string $path         Path.
     *
     * @throws Exception\InstagramException
     *
     * @return Response\GenericResponse
     *
     * @see Instagram::login()
     */
    public function performPostForceLogoutActions(
        $logoutReason,
        $path
    ) {
        return $this->request('accounts/perform_post_force_logout_actions/')
            ->setNeedsAuth(false)
            ->addPost('user_id', $this->account_id)
            ->addPost('_uid', $this->account_id)
            // ->addPost('_csrftoken', $this->client->getToken())
            ->addPost('guid', $this->uuid)
            ->addPost('device_id', $this->device_id)
            ->addPost('path', $path)
            ->addPost('_uuid', $this->uuid)
            ->addPost('logout_reason', $logoutReason)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Log out of Instagram.
     *
     * WARNING: Most people should NEVER call `logout()`! Our library emulates
     * the Instagram app for Android, where you are supposed to stay logged in
     * forever. By calling this function, you will tell Instagram that you are
     * logging out of the APP. But you SHOULDN'T do that! In almost 100% of all
     * cases you want to *stay logged in* so that `login()` resumes your session!
     *
     * @throws Exception\InstagramException
     *
     * @return Response\LogoutResponse
     *
     * @see Instagram::login()
     */
    public function logout()
    {
        $response = $this->request('accounts/logout/')
            ->setSignedPost(false)
            ->addPost('phone_id', $this->phone_id)
            // ->addPost('_csrftoken', $this->client->getToken())
            ->addPost('guid', $this->uuid)
            ->addPost('device_id', $this->device_id)
            ->addPost('_uuid', $this->uuid)
            ->getResponse(new Response\LogoutResponse());

        // We've now logged out. Forcibly write our cookies to the storage, to
        // ensure that the storage doesn't miss them in case something bad
        // happens to PHP after this moment.
        $this->client->saveCookieJar();

        return $response;
    }

    /**
     * Process successful login response (Bloks).
     *
     * @param string $loginResponseWithHeaders Login response in JSON format.
     * @param int    $appRefreshInterval       How frequently `login()` should act
     *                                         like an Instagram app that's been
     *                                         closed and reopened and needs to
     *                                         "refresh its state", by asking for
     *                                         extended account state details.
     *                                         Default: After `1800` seconds, meaning
     *                                         `30` minutes after the last
     *                                         state-refreshing `login()` call.
     *                                         This CANNOT be longer than `6` hours.
     *                                         Read `_sendLoginFlow()`! The shorter
     *                                         your delay is the BETTER. You may even
     *                                         want to set it to an even LOWER value
     *                                         than the default 30 minutes!
     * @param bool   $loginFlow                Perform login flow.
     *
     * @throws Exception\InstagramException
     * @throws Exception\AccountDisabledException
     *
     * @return Response\LoginResponse
     *
     * @see Instagram::login()
     */
    protected function _processSuccesfulLoginResponse(
        $loginResponseWithHeaders,
        $appRefreshInterval = 1800,
        $loginFlow = true
    ) {
        $loginResponseWithHeaders = str_replace('\"exact_profile_identified\":null}', '"exact_profile_identified":null}', $loginResponseWithHeaders);
        $loginResponseWithHeaders = json_decode($loginResponseWithHeaders, true);

        if (isset($loginResponseWithHeaders['assistive_id_type'])) {
            $msg = sprintf('Identification error. Account suspended or shadow banned. Maybe you meant username: %s?', $loginResponseWithHeaders['username']);
            $loginResponse = new Response\LoginResponse([
                'error_type'    => 'identification_error',
                'status'        => 'fail',
                'message'       => $msg,
            ]);
            $e = new Exception\IdentificationErrorException($msg);
            $e->setResponse($loginResponse);

            throw $e;
        }

        $re = '/"full_name":"(.*?)","/m';
        preg_match_all($re, $loginResponseWithHeaders['login_response'], $matches, PREG_SET_ORDER, 0);
        if ($matches) {
            $loginResponse = $matches[0][1];
            $loginResponse = str_replace('\"', '"', $loginResponse);
            $loginResponse = str_replace('"', '\"', $loginResponse);
            $re = '/("full_name":")(.*?)(",")/m';
            $loginResponseWithHeaders['login_response'] = preg_replace($re, '${1}'.$loginResponse.'${3}', $loginResponseWithHeaders['login_response']);
        }
        $re = '/"page_name":"(.*?)","/m';
        preg_match_all($re, $loginResponseWithHeaders['login_response'], $matches, PREG_SET_ORDER, 0);
        if ($matches) {
            $loginResponse = $matches[0][1];
            $loginResponse = str_replace('\"', '"', $loginResponse);
            $loginResponse = str_replace('"', '\"', $loginResponse);
            $re = '/("page_name":")(.*?)(",")/m';
            $loginResponseWithHeaders['login_response'] = preg_replace($re, '${1}'.$loginResponse.'${3}', $loginResponseWithHeaders['login_response']);
        }

        $loginResponse = json_decode($loginResponseWithHeaders['login_response'], true);
        if (!isset($loginResponse['status'])) {
            $loginResponse['status'] = 'ok';
        }
        $loginResponse = new Response\LoginResponse($loginResponse);
        $headersJson = $loginResponseWithHeaders['headers'];
        $headersJson = preg_replace('/"{"request_index/m', '{"request_index', $headersJson);
        $headersJson = preg_replace('/_server\\\\\/"}"/m', 'server\/"}', $headersJson);
        $headersJson = preg_replace('/"co\wp""/m', '\\"coop\\""', $headersJson);

        $headers = json_decode($headersJson, true);

        $this->settings->set('public_key', $headers['IG-Set-Password-Encryption-Pub-Key']);
        $this->settings->set('public_key_id', $headers['IG-Set-Password-Encryption-Key-Id']);
        $this->settings->set('authorization_header', $headers['IG-Set-Authorization']);

        if (isset($headers['ig-set-ig-u-rur']) && $headers['ig-set-ig-u-rur'] !== '') {
            $this->settings->set('rur', $headers['ig-set-ig-u-rur']);
        }

        if ($loginResponse->getLoggedInUser()->getUsername() === 'Instagram User') {
            throw new Exception\AccountDisabledException('Account has been suspended.');
        }
        if ($loginResponse->getLoggedInUser()->getIsBusiness() !== null) {
            $this->settings->set('business_account', $loginResponse->getLoggedInUser()->getIsBusiness());
        }
        $this->settings->set('fbid_v2', $loginResponse->getLoggedInUser()->getFbidV2());
        if ($loginResponse->getLoggedInUser()->getPhoneNumber() !== null) {
            $this->settings->set('phone_number', $loginResponse->getLoggedInUser()->getPhoneNumber());
        }

        $this->_updateLoginState($loginResponse);
        if ($loginFlow === true) {
            $this->_sendLoginFlow(true, $appRefreshInterval);
        }

        return $loginResponse;
    }

    /**
     * Parse login errors (Bloks).
     *
     * @param array         $loginResponseWithHeaders Login bloks array.
     * @param LoginResponse $response
     *
     * @throws Exception\InstagramException
     *
     * @return array
     *
     * @see Instagram::login()
     */
    protected function _parseLoginErrors(
        $loginResponseWithHeaders,
        $response = null
    ) {
        $offsets = array_slice($this->bloks->findOffsets($loginResponseWithHeaders, '\login_error_dialog_shown\\'), 0, -2);
        if (empty($offsets)) {
            $offsets = array_slice($this->bloks->findOffsets($loginResponseWithHeaders, '\exception_message\\'), 0, -2);
        }
        if (empty($offsets)) {
            $offsets = array_slice($this->bloks->findOffsets($loginResponseWithHeaders, '\account_recovery_lookup_client_rate_limited\\'), 0, -2);
        }

        $result = [];
        if ($response !== null && str_contains($response->asJson(), 'checkpoint')) {
            $result = ['event_category' => 'checkpoint'];
        }
        if ($response !== null && empty($result) && str_contains($response->asJson(), 'FIRST_PASSWORD_FAILURE')) {
            $result = ['event_category' => 'FIRST_PASSWORD_FAILURE'];
        }
        if ($response !== null && empty($result) && str_contains($response->asJson(), 'login_failure')) {
            $result = ['event_category' => 'login_failure'];
        }
        if ($response !== null && empty($result) && str_contains($response->asJson(), 'An unexpected error occurred. Please try logging in again.')) {
            $result = ['exception_message' => 'Login Error: An unexpected error occurred. Please try logging in again.'];
        }

        if (!empty($result)) {
            return $result;
        }

        if ($offsets) {
            foreach ($offsets as $offset) {
                if (isset($loginResponseWithHeaders[$offset])) {
                    $loginResponseWithHeaders = $loginResponseWithHeaders[$offset];
                } else {
                    break;
                }
            }

            $errorMap = $this->bloks->map_arrays($loginResponseWithHeaders[0], $loginResponseWithHeaders[1]);
            foreach ($errorMap as $key => $value) {
                if (!is_array($errorMap[$key]) && $value !== null) {
                    $errorMap[stripslashes($key)] = str_replace(['/', '\\'], '', $value);
                }
                unset($errorMap[$key]);
            }
        } else {
            $errorMap = [];
        }

        return $errorMap;
    }

    /**
     * Process Login Response (bloks).
     *
     * @param Response\LoginResponse $response Login response.
     *
     * @throws Exception\InstagramException
     * @throws Exception\AccountDeletionException
     * @throws Exception\InvalidUsernameException
     * @throws Exception\TooManyAttemptsException
     * @throws Exception\AccountDisabledException
     * @throws Exception\IncorrectPasswordException
     * @throws Exception\UnexpectedLoginErrorException
     * @throws Exception\Checkpoint\ChallengeRequiredException
     */
    public function processCreateResponse(
        $response
    ) {
        $registrationResponseWithHeaders = $this->bloks->parseBlok(json_encode($response->asArray()['layout']['bloks_payload']['tree']), 'bk.action.caa.HandleLoginResponse');

        if (is_array($registrationResponseWithHeaders)) {
            $errorMap = $this->_parseLoginErrors($registrationResponseWithHeaders, $response);
            $this->_throwLoginException($response, $errorMap);
        }

        $registrationResponseWithHeaders = json_decode($registrationResponseWithHeaders, true);
        $registrationResponse = json_decode($registrationResponseWithHeaders['registration_response'], true);
        if (!isset($registrationResponse['status'])) {
            $registrationResponse['status'] = 'ok';
        }
        $accountCreateResponse = new Response\AccountCreateResponse($registrationResponse);

        $headersJson = $registrationResponseWithHeaders['headers'];
        $headers = json_decode($headersJson, true);

        $this->settings->set('public_key', $headers['IG-Set-Password-Encryption-Pub-Key']);
        $this->settings->set('public_key_id', $headers['IG-Set-Password-Encryption-Key-Id']);
        $this->settings->set('authorization_header', $headers['IG-Set-Authorization']);

        if (isset($headers['ig-set-ig-u-rur']) && $headers['ig-set-ig-u-rur'] !== '') {
            $this->settings->set('rur', $headers['ig-set-ig-u-rur']);
        }

        if ($accountCreateResponse->getCreatedUser()->getUsername() === 'Instagram User') {
            throw new Exception\AccountDisabledException('Account has been suspended.');
        }
        $this->settings->set('business_account', false);
        $this->settings->set('fbid_v2', $accountCreateResponse->getCreatedUser()->getFbidV2());

        $this->isMaybeLoggedIn = true;
        $this->account_id = $accountCreateResponse->getCreatedUser()->getPk();
        $this->settings->set('account_id', $this->account_id);
        $this->settings->set('last_login', time());

        return $accountCreateResponse;
    }

    /**
     * Throw login exceptions (Bloks).
     *
     * @param Response\LoginResponse $response Login response.
     * @param array                  $errorMap Error map.
     *
     * @throws Exception\InstagramException
     * @throws Exception\AccountDeletionException
     * @throws Exception\InvalidUsernameException
     * @throws Exception\TooManyAttemptsException
     * @throws Exception\AccountDisabledException
     * @throws Exception\IncorrectPasswordException
     * @throws Exception\UnexpectedLoginErrorException
     * @throws Exception\Checkpoint\ChallengeRequiredException
     *
     * @see Instagram::login()
     */
    protected function _throwLoginException(
        $response,
        $errorMap
    ) {
        if (isset($errorMap['exception_message']) || isset($errorMap['event_category'])) {
            if (!isset($errorMap['exception_message'])) {
                $errorMap['exception_message'] = '';
            }
            if (str_contains($response->asJson(), 'The password you entered is incorrect. Please try again.')) {
                $errorMap['exception_message'] = 'The password you entered is incorrect. Please try again.';
            }

            switch ($errorMap['exception_message']) {
                case 'Login Error: An unexpected error occurred. Please try logging in again.':
                    // case "Unmapped IG Error: This IG Error was not mapped to an Error Code. To fix it, update the error tool under 'CAA' to map it to an Error Code.";
                    throw new Exception\UnexpectedLoginErrorException($errorMap['exception_message']);
                    break;
                case 'Incorrect Password: The password you entered is incorrect. Please try again.':
                case 'The password you entered is incorrect. Please try again.':
                    $this->loginAttemptCount++;

                    throw new Exception\IncorrectPasswordException($errorMap['exception_message']);
                    break;
                default:
                    if (isset($errorMap['event_category'])) {
                        if ($errorMap['event_category'] === 'checkpoint') {
                            $loginResponse = $this->bloks->parseBlok(json_encode($response->asArray()['layout']['bloks_payload']['tree']), 'bk.action.caa.PresentCheckpointsFlow');
                            $loginResponse = preg_replace('/challenge_context\\\\":\\\\[a-zA-Z0-9]/m', 'challenge_context\":\"\\', $loginResponse);
                            $loginResponse = preg_replace('/challenge_context":"\W/m', 'challenge_context":{', $loginResponse);
                            $loginResponse = json_decode(stripslashes($loginResponse), true);
                            if (isset($loginResponse['error'])) {
                                $loginResponse = $loginResponse['error']['error_data'];
                            }
                            $loginResponse = new Response\CheckpointResponse($loginResponse);

                            $e = new Exception\Checkpoint\ChallengeRequiredException();
                            $e->setResponse($loginResponse);

                            throw $e;
                        /*
                        $offsets = array_slice($this->bloks->findOffsets($loginResponseWithHeaders, '\error_user_msg\\'), 0, -2);

                        foreach ($offsets as $offset) {
                            if (isset($loginResponseWithHeaders[$offset])) {
                                $loginResponseWithHeaders = $loginResponseWithHeaders[$offset];
                            } else {
                                break;
                            }
                        }

                        $errorMap = $this->bloks->map_arrays($loginResponseWithHeaders[0], $loginResponseWithHeaders[1]);
                        foreach ($errorMap as $key => $value) {
                            if (!is_array($errorMap[$key])) {
                                $errorMap[stripslashes($key)] = stripslashes($value);
                            }
                            unset($errorMap[$key]);
                        }
                        */
                        } elseif ($errorMap['event_category'] === 'two_fac') {
                            $loginResponse = $this->bloks->parseBlok(json_encode($response->asArray()['layout']['bloks_payload']['tree']), 'bk.action.caa.PresentTwoFactorAuthFlow');
                            $loginResponse = json_decode(stripslashes($loginResponse), true);
                            $loginResponse = new Response\LoginResponse($loginResponse);

                            return $loginResponse;
                        } elseif ($errorMap['event_category'] === 'FIRST_PASSWORD_FAILURE') {
                            $this->loginAttemptCount++;
                            $msg = 'Invalid password or older password used.';
                            $loginResponse = new Response\LoginResponse([
                                'error_type'    => 'incorrect_password',
                                'status'        => 'fail',
                                'message'       => $msg,
                            ]);
                            $e = new Exception\IncorrectPasswordException($msg);
                            $e->setResponse($loginResponse);

                            throw $e;
                        } elseif ($errorMap['event_category'] === 'login_home_page_interaction') {
                            $msg = "You can't use Instagram because your account didn't follow our Community Guidelines. This decision can't be reversed either because we've already reviewed it, or because 180 days have passed since your account was disabled";
                            if (str_contains(json_encode($response->asArray()['layout']['bloks_payload']['tree']), $msg)) {
                                $loginResponse = new Response\LoginResponse([
                                    'error_type'    => 'inactive_user',
                                    'status'        => 'fail',
                                    'message'       => $msg,
                                ]);
                                $e = new Exception\AccountDisabledException($msg);
                                $e->setResponse($loginResponse);

                                throw $e;
                            }
                            $msg = 'Please wait a few minutes before you try again';
                            if (str_contains(json_encode($response->asArray()['layout']['bloks_payload']['tree']), $msg)) {
                                $loginResponse = new Response\LoginResponse([
                                    'error_type'    => 'too_many_attempts',
                                    'status'        => 'fail',
                                    'message'       => $msg,
                                ]);
                                $e = new Exception\TooManyAttemptsException($msg);
                                $e->setResponse($loginResponse);

                                throw $e;
                            }
                            $msg = "We can't find an account with ";
                            if (str_contains(json_encode($response->asArray()['layout']['bloks_payload']['tree']), $msg)) {
                                $loginResponse = new Response\LoginResponse([
                                    'error_type'    => 'invalid_username',
                                    'status'        => 'fail',
                                    'message'       => sprintf('%s%s', $msg, $this->username),
                                ]);
                                $e = new Exception\InvalidUsernameException(sprintf('%s%s', $msg, $this->username));
                                $e->setResponse($loginResponse);

                                throw $e;
                            }
                            $msg = 'An unexpected error occurred. Please try logging in again.';
                            if (str_contains(json_encode($response->asArray()['layout']['bloks_payload']['tree']), $msg)) {
                                $loginResponse = new Response\LoginResponse([
                                    'error_type'    => 'unexpected_login_error',
                                    'status'        => 'fail',
                                    'message'       => $msg,
                                ]);
                                $e = new Exception\UnexpectedLoginErrorException($msg);
                                $e->setResponse($loginResponse);

                                throw $e;
                            }
                            $msg = 'You requested to delete';
                            if (str_contains(json_encode($response->asArray()['layout']['bloks_payload']['tree']), $msg)) {
                                $loginResponse = new Response\LoginResponse([
                                    'error_type'    => 'account_deletion_requested',
                                    'status'        => 'fail',
                                    'message'       => sprintf('You requested to delete your account: %s', $this->username),
                                ]);
                                $e = new Exception\AccountDeletionException(sprintf('You requested to delete your account: %s', $this->username));
                                $e->setResponse($loginResponse);

                                throw $e;
                            }
                            $msg = 'You entered the wrong code too many times. Wait a few minutes and try again.';
                            if (str_contains(json_encode($response->asArray()['layout']['bloks_payload']['tree']), $msg)) {
                                $loginResponse = new Response\LoginResponse([
                                    'error_type'    => 'too_many_attempts_wrong_code',
                                    'status'        => 'fail',
                                    'message'       => $msg,
                                ]);
                                $e = new Exception\TooManyAttemptsException('You entered the wrong code too many times. Wait a few minutes and try again.');
                                $e->setResponse($loginResponse);

                                throw $e;
                            }
                            $msg = 'Sorry, there was a problem with your request.';
                            if (str_contains(json_encode($response->asArray()['layout']['bloks_payload']['tree']), $msg)) {
                                $loginResponse = new Response\LoginResponse([
                                    'error_type'    => 'server_or_ip_error',
                                    'status'        => 'fail',
                                    'message'       => $msg.'Retry again.',
                                ]);
                                $e = new Exception\InstagramException($msg);
                                $e->setResponse($loginResponse);

                                throw $e;
                            }
                            $msg = "The username you entered doesn't appear to belong to an account. Please check your username and try again.";
                            if (str_contains(json_encode($response->asArray()['layout']['bloks_payload']['tree']), $msg)) {
                                $loginResponse = new Response\LoginResponse([
                                    'error_type'    => 'invalid_username',
                                    'status'        => 'fail',
                                    'message'       => $msg,
                                ]);
                                $e = new Exception\InvalidUserException(sprintf('%s If the username exists, it is very likely the IP used is flagged.', $msg));
                                $e->setResponse($loginResponse);

                                throw $e;
                            }

                            throw new Exception\InstagramException($errorMap['event_category']);
                        } else {
                            throw new Exception\InstagramException($errorMap['event_category']);
                        }
                    } else {
                        throw new Exception\InstagramException($errorMap['exception_message']);
                    }
            }
        }
    }

    /**
     * Checks if a parameter is enabled in the given experiment.
     *
     * @param string $experiment
     * @param string $param
     * @param bool   $default
     * @param bool   $useDefault
     *
     * @return bool
     */
    public function isExperimentEnabled(
        $experiment,
        $param,
        $default = false,
        $useDefault = false
    ) {
        if ($useDefault === false) {
            return isset($this->experiments[$experiment][$param])
                ? in_array($this->experiments[$experiment][$param], ['enabled', 'true', '1'])
                : $default;
        } else {
            return $default;
        }
    }

    /**
     * Get a parameter value for the given experiment.
     *
     * @param string $experiment
     * @param string $param
     * @param mixed  $default
     * @param bool   $useDefault
     *
     * @return mixed
     */
    public function getExperimentParam(
        $experiment,
        $param,
        $default = null,
        $useDefault = false
    ) {
        if ($useDefault === false) {
            return $this->experiments[$experiment][$param]
                ?? $default;
        } else {
            return $default;
        }
    }

    /**
     * Create a custom API request.
     *
     * Used internally, but can also be used by end-users if they want
     * to create completely custom API queries without modifying this library.
     *
     * @param string $url
     *
     * @return Request
     */
    public function request(
        $url
    ) {
        return new Request($this, $url, $this->customResolver);
    }
}
