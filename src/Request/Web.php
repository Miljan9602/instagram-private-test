<?php

namespace InstagramAPI\Request;

use InstagramAPI\Response;
use InstagramAPI\Utils;

/**
 * Functions related to Instagram Web.
 */
class Web extends RequestCollection
{
    /**
     * Pre login.
     *
     * Used to get csrftoken.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return string
     */
    public function sendPreLogin()
    {
        if (extension_loaded('sodium') === false) {
            throw new \InstagramAPI\Exception\InternalException('You must have the sodium PHP extension to use web login.');
        }

        return $this->ig->request('https://www.instagram.com/accounts/login/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->setAddDefaultHeaders(false)
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->getRawResponse();
    }

    /**
     * Web login.
     *
     * @param string $username
     * @param string $password
     * @param string $csrftoken
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return string
     */
    public function login(
        $username,
        $password
    ) {
        if (extension_loaded('sodium') === false) {
            throw new \InstagramAPI\Exception\InternalException('You must have the sodium PHP extension to use web login.');
        }

        return $this->ig->request('https://www.instagram.com/api/v1/web/accounts/login/ajax/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->setAddDefaultHeaders(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('username', $username)
            ->addPost('optIntoOneTap', 'false')
            ->addPost('enc_password', Utils::encryptPasswordForBrowser($password))
            ->addPost('query_params', json_encode([], JSON_FORCE_OBJECT))
            ->addPost('trustedDeviceRecords', json_encode([], JSON_FORCE_OBJECT))
            ->getRawResponse();
    }

    /**
     * Send signup SMS.
     *
     * @param string $phone     The phone number.
     * @param string $mid       Mid value (obtained from cookie).
     * @param string $csrftoken
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return string
     */
    public function sendSignupSms(
        $phone,
        $mid
    ) {
        return $this->ig->request('https://www.instagram.com/accounts/send_signup_sms_code_ajax/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->setAddDefaultHeaders(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('client_id', $mid)
            ->addPost('phone_number', $phone)
            ->addPost('phone_id', '')
            ->addPost('big_blue_token', '')
            ->getRawResponse();
    }

    /**
     * Send email verification code.
     *
     * @param string $email     The email.
     * @param string $mid       Mid value (obtained from cookie).
     * @param string $csrftoken
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return string
     */
    public function sendEmailVerificationCode(
        $email,
        $mid
    ) {
        return $this->ig->request('accounts/send_verify_email/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->setAddDefaultHeaders(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('device_id', $mid)
            ->addPost('email', $email)
            ->getRawResponse();
    }

    /**
     * Check email verification code.
     *
     * @param string $email The email.
     * @param string $code  The verification code.
     * @param string $mid   Mid value (obtained from cookie).
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return string
     */
    public function checkEmailVerificationCode(
        $email,
        $code,
        $mid
    ) {
        return $this->ig->request('accounts/check_confirmation_code/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->setAddDefaultHeaders(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('code', $code)
            ->addPost('device_id', $mid)
            ->addPost('email', $email)
            ->getRawResponse();
    }

    /**
     * Web registration.
     *
     * @param string $username     The account username.
     * @param string $password     The account password.
     * @param string $name         The name of the account.
     * @param string $phoneOrEmail The phone number or email.
     * @param string $day          Day of birth.
     * @param string $month        Month of birth.
     * @param string $year         Year of bith.
     * @param string $mid          Mid value (obtained from cookie).
     * @param bool   $attempt      Wether it is an attempt or not.
     * @param string $tos          Terms of Service.
     * @param string $smsCode      The SMS code.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return string
     */
    public function createAccount(
        $username,
        $password,
        $name,
        $phoneOrEmail,
        $day,
        $month,
        $year,
        $mid,
        $attempt,
        $smsCode = null,
        $tos = 'row'
    ) {
        if (extension_loaded('sodium') === false) {
            throw new \InstagramAPI\Exception\InternalException('You must have the sodium PHP extension to use web login.');
        }

        if ($attempt) {
            $endpoint = '/accounts/web_create_ajax/attempt/';
        } else {
            $endpoint = '/accounts/web_create_ajax/';
        }

        $request = $this->ig->request('https://www.instagram.com'.$endpoint)
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->setAddDefaultHeaders(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('enc_password', Utils::encryptPasswordForBrowser($password))
            ->addPost('username', $username)
            ->addPost('first_name', $name)
            ->addPost('month', $month)
            ->addPost('day', $day)
            ->addPost('year', $year)
            ->addPost('client_id', $mid)
            ->addPost('seamless_login_enabled', 1)
            ->addPost('tos_version', $tos);

        if (strpos($phoneOrEmail, '@') !== false) {
            $request->addPost('email', $phoneOrEmail);
        } else {
            $request->addPost('phone_number', $phoneOrEmail);
        }

        if ($attempt === false && (strpos($phoneOrEmail, '@') !== false)) {
            $request->addPost('code', $smsCode);
        } else {
            $request->addPost('sms_code', $smsCode);
        }

        return $request->getRawResponse();
    }

    /**
     * Gets account information.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AccountAccessToolResponse
     */
    public function getAccountData()
    {
        $response = $this->ig->request('https://instagram.com/accounts/access_tool/')
            ->setAddDefaultHeaders(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->getRawResponse();

        return new Response\AccountAccessToolResponse(json_decode($response, true));
    }

    /**
     * Like a media using Web Session.
     *
     * @param string $mediaId
     * @param string $csrftoken
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function like(
        $mediaId
    ) {
        return $this->ig->request("https://instagram.com/web/likes/{$mediaId}/like/")
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('', '')
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Follow a user using Web Session.
     *
     * @param string $userId
     * @param string $csrftoken
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\WebFollowResponse
     */
    public function follow(
        $userId
    ) {
        return $this->ig->request("https://instagram.com/web/friendships/{$userId}/follow/")
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('', '')
            ->getResponse(new Response\WebFollowResponse());
    }

    /**
     * Unfollow a user using Web Session.
     *
     * @param string $userId
     * @param string $csrftoken
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\WebFollowResponse
     */
    public function unfollow(
        $userId
    ) {
        return $this->ig->request("https://instagram.com/web/friendships/{$userId}/unfollow/")
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('', '')
            ->getResponse(new Response\WebFollowResponse());
    }

    /**
     * Report media using web session.
     *
     * @param string $mediaId
     * @param string $reason    The reason of the report. '1' is Spam, '4' is pornography.
     * @param string $csrftoken
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function reportMedia(
        $mediaId,
        $reason
    ) {
        return $this->ig->request("https://instagram.com/media/{$mediaId}/flag/")
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('reason', $reason)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get username profile info.
     *
     * @param string $username
     * @param string $csrftoken
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\WebUserInfoResponse
     */
    public function getUserInfo(
        $username
    ) {
        return $this->ig->request('https://i.instagram.com/api/v1/users/web_profile_info/')
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->setIsSilentFail(true)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addParam('username', $username)
            ->getResponse(new Response\WebUserInfoResponse());
    }

    /**
     * Top search.
     *
     * @param string $query
     * @param mixed  $context
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\WebUserInfoResponse
     */
    public function getTopSearch(
        $query,
        $context = 'blended'
    ) {
        return $this->ig->request('https://i.instagram.com/api/v1/users/web_profile_info/')
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->setIsSilentFail(true)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addParam('context', $context)
            ->addParam('query', $query)
            ->addParam('include_reel', 'true')
            ->getRawResponse();
    }

    /**
     * Get media info.
     *
     * @param string $mediaId The media ID in Instagram's internal format (ie "3482384834_43294").
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\MediaInfoResponse
     */
    public function getMediaInfo(
        $mediaId
    ) {
        return $this->ig->request("https://www.instagram.com/api/v1/media/$mediaId/info/")
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->setIsSilentFail(true)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->getResponse(new Response\MediaInfoResponse());
    }

    /**
     * Get media info.
     *
     * @param string $mediaId The media ID in Instagram's web format.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getMediaInfoQuery(
        $mediaId
    ) {
        $response = $this->ig->request('https://instagram.com/graphql/query')
            ->setAddDefaultHeaders(false)
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->setIsSilentFail(true)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Fb-Friendly-Name', 'PolarisPostActionLoadPostQueryQuery')
            ->addHeader('X-Bloks-Version-Id', '09d4437a3b9f5707ed0adf4614de5f4d546124576e17ba49716eb9823d803aea')
            ->addPost('fb_api_caller_class', 'RelayModern')
            ->addPost('fb_api_req_friendly_name', 'PolarisPostActionLoadPostQueryQuery')
            ->addPost('variables', json_encode(['shortcode'  => $mediaId, 'fetch_tagged_user_count' => null, 'hoisted_comment_id' => null, 'hoisted_reply_id' => null]))
            ->addPost('server_timestamps', 'true')
            ->addPost('doc_id', '8845758582119845')
            ->getResponse(new Response\GenericResponse());

        $arr = $response->asArray();
        if (isset($arr['data'])) {
            $data = $arr['data'];
            foreach ($data as $k => $v) {
                if (is_array($data[$k])) {
                    return new Response\MediaInfoResponse($data[$k]);
                }
            }
        }

        return $response;
    }

    /**
     * Gets information about password changes.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AccountAccessToolResponse
     */
    public function getPasswordChanges()
    {
        $response = $this->ig->request('https://instagram.com/accounts/access_tool/password_changes')
            ->setAddDefaultHeaders(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->getRawResponse();

        return new Response\AccountAccessToolResponse(json_decode($response, true));
    }

    /**
     * Make GraphQL request.
     *
     * @param string $queryHash Query hash.
     * @param array  $variables Variables.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\WebUserInfoResponse
     */
    public function sendGraphqlQuery(
        $queryHash,
        array $variables
    ) {
        return $this->ig->request('https://www.instagram.com/graphql/query/')
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->setIsSilentFail(true)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addParam('query_hash', $queryHash)
            ->addParam('variables', json_encode($variables))
            ->getRawResponse();
    }

    /**
     * Update date of birth.
     *
     * @param string $day   Day.
     * @param string $month Month.
     * @param string $year  Year.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function updateDateOfBirth(
        $day,
        $month,
        $year
    ) {
        return $this->ig->request('https://instagram.com/web/consent/update_dob/')
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('day', $day)
            ->addPost('month', $month)
            ->addPost('year', $year)
            ->addPost('current_screen_key', 'dob')
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Edit profile.
     *
     * @param string $firstName   First name.
     * @param string $email       Email.
     * @param string $phoneNumber Phone number.
     * @param string $biography   Biography.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function editProfile(
        $firstName = null,
        $email = null,
        $phoneNumber = null,
        $biography = null
    ) {
        $response = $this->ig->request('https://instagram.com/api/v1/accounts/edit/web_form_data/')
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->getResponse(new Response\WebFormDataResponse());

        return $this->ig->request('https://instagram.com/api/v1/web/accounts/edit/')
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('Referer', 'https://www.instagram.com')
            ->addHeader('Origin', 'https://www.instagram.com')
            ->addHeader('X-Ig-App-Id', '936619743392459')
            ->addHeader('X-Instagram-Ajax', '1013884189')
            ->addPost('first_name', ($firstName !== null) ? $firstName : $response->getFormData()->getFirstName())
            ->addPost('email', ($email !== null) ? $email : $response->getFormData()->getEmail())
            ->addPost('username', $response->getFormData()->getUsername())
            ->addPost('phone_number', ($phoneNumber !== null) ? $phoneNumber : $response->getFormData()->getPhoneNumber())
            ->addPost('biography', ($biography !== null) ? $biography : $response->getFormData()->getBiography())
            ->addPost('external_url', '')
            ->addPost('chaining_enabled', 'on')
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get a specific user's story feed with broadcast details GraphQL Query.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     *
     * @see Story::getUserReelMediaFeed()
     */
    public function getUserStoryFeedQuery(
        $userId
    ) {
        return $this->ig->request('https://instagram.com/graphql/query')
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->addHeader('User-Agent', $this->ig->getWebUserAgent())
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('X-Fb-Friendly-Name', 'PolarisStoriesV3ReelPageStandaloneDirectQuery')
            ->addHeader('X-Bloks-Version-Id', '022709c2f0737e27f95f3ca8fe06539bad6f7c77f58c2b18a303961c2aea2eb5')
            ->addPost('fb_api_caller_class', 'RelayModern')
            ->addPost('fb_api_req_friendly_name', 'PolarisStoriesV3ReelPageStandaloneDirectQuery')
            ->addPost('variables', json_encode(['reel_ids_arr'  => [$userId]]))
            ->addPost('server_timestamps', 'true')
            ->addPost('doc_id', '8118053404899604')
            ->getResponse(new Response\GenericResponse());
    }
}
