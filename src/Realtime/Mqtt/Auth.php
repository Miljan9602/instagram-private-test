<?php

namespace InstagramAPI\Realtime\Mqtt;

use Fbns\Auth as AuthInterface;
use InstagramAPI\Instagram;

class Auth implements AuthInterface
{
    public const AUTH_TYPE = 'cookie_auth';

    /**
     * @var Instagram
     */
    protected $_instagram;

    /**
     * Constructor.
     *
     * @param Instagram $instagram
     */
    public function __construct(
        Instagram $instagram
    ) {
        $this->_instagram = $instagram;
    }

    /** {@inheritdoc} */
    public function getClientId(): string
    {
        return substr($this->getDeviceId(), 0, 20);
    }

    /** {@inheritdoc} */
    public function getClientType(): string
    {
        return self::AUTH_TYPE;
    }

    /** {@inheritdoc} */
    public function getUserId(): string
    {
        return $this->_instagram->account_id;
    }

    /** {@inheritdoc} */
    public function getPassword(): string
    {
        $cookie = $this->_instagram->client->getCookie('sessionid', 'i.instagram.com');
        if ($cookie !== null) {
            return sprintf('%s=%s', $cookie->getName(), $cookie->getValue());
        }

        throw new \RuntimeException('No session cookie was found.');
    }

    /** {@inheritdoc} */
    public function getDeviceId(): string
    {
        return $this->_instagram->uuid;
    }

    /** {@inheritdoc} */
    public function getDeviceSecret(): string
    {
        return '';
    }

    /** {@inheritdoc} */
    public function __toString()
    {
        return '';
    }
}
