<?php

namespace InstagramAPI\Request;

use InstagramAPI\Constants;
use InstagramAPI\Exception\InternalException;
use InstagramAPI\Exception\SettingsException;
use InstagramAPI\Request\Metadata\Internal as InternalMetadata;
use InstagramAPI\Response;
use InstagramAPI\Signatures;
use InstagramAPI\Utils;

/**
 * Account-related functions, such as profile editing and security.
 *
 * @param string     $username    Username.
 * @param string     $password    The password that is going to be set for the account.
 * @param string     $signupCode  The signup code.
 * @param string     $email       The email user for registration.
 * @param string     $date        The date of birth. Format: YYYY-MM-DD.
 * @param string     $firstName   First name.
 * @param string     $waterfallId UUIDv4.
 * @param string     $tosVersion  ToS version.
 * @param array|null $sndata      SN Data.
 *
 * @throws \InstagramAPI\Exception\InstagramException
 *
 * @return Response\AccountCreateResponse
 */
class Account extends RequestCollection
{
    public function create(
        $username,
        $password,
        $signupCode,
        $email,
        $date,
        $firstName,
        $waterfallId,
        $tosVersion = 'row',
        $sndata = null
    ) {
        if (strlen($password) < 6) {
            throw new \InstagramAPI\Exception\InstagramException('Passwords must be at least 6 characters.');
        } elseif (in_array($password, Constants::BLACKLISTED_PASSWORDS, true)) {
            throw new \InstagramAPI\Exception\InstagramException('This is a common password. Try something that\'s harder to guess.');
        }

        $date = explode('-', $date);

        $request = $this->ig->request('accounts/create/')
            ->setNeedsAuth(false)
            ->addPost('tos_version', $tosVersion)
            ->addPost('allow_contacts_sync', 'true')
            ->addPost('phone_id', $this->ig->phone_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('username', $username)
            ->addPost('first_name', $firstName)
            ->addPost('adid', $this->ig->advertising_id)
            ->addPost('guid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('email', $email)
            ->addPost('day', $date[2])
            ->addPost('month', $date[1])
            ->addPost('year', $date[0])
            ->addPost('enc_password', Utils::encryptPassword($password, $this->ig->settings->get('public_key_id'), $this->ig->settings->get('public_key')))
            ->addPost('force_sign_up_code', $signupCode)
            ->addPost('waterfall_id', $waterfallId)
            ->addPost('qs_stamp', '')
            ->addPost('one_tap_opt_in', 'true');

        if ($this->ig->getIsAndroid()) {
            $request->addPost('suggestedUsername', '')
                ->addPost('is_secondary_account_creation', false)
                ->addPost('jazoest', Utils::generateJazoest($this->ig->phone_id))
                ->addPost('do_not_auto_login_if_credentials_match', 'true');
            if ($sndata !== null) {
                $request->addPost('sn_nonce', $sndata['sn_nonce'])
                        ->addPost('sn_result', $sndata['sn_result']);
            } else {
                $request->addPost('sn_nonce', base64_encode($username.'|'.time().'|'.random_bytes(24)))
                        ->addPost('sn_result', sprintf('GOOGLE_PLAY_UNAVAILABLE: %s', array_rand(['SERVICE_INVALID', 'UNKNOWN', 'SERVICE_DISABLED', 'NETWORK_ERROR', 'INTERNAL_ERROR', 'CANCELED', 'INTERRUPTED', 'API_UNAVAILABLE'])));
            }
        } else {
            $request->addPost('do_not_auto_login_if_credentials_match', '0')
                ->addPost('force_create_account', '0')
                ->addPost('ck_error', 'NSURLErrorDomain: -1202')
                ->addPost('ck_environment', 'production')
                ->addPost('ck_environment', 'iCloud.com.burbn.instagram');
        }

        return $request->getResponse(new Response\AccountCreateResponse());
    }

    /**
     * Create an account with validated phone number.
     *
     * @param string     $smsCode     The received SMS code.
     * @param string     $username    Username.
     * @param string     $password    The password that is going to be set for the account.
     * @param string     $phone       Phone with country code. For example: '+34123456789'.
     * @param string     $date        The date of birth. Format: YYYY-MM-DD.
     * @param string     $firstName   First name.
     * @param string     $waterfallId UUIDv4.
     * @param string     $tosVersion  ToS version.
     * @param array|null $sndata      SN Data.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AccountCreateResponse
     */
    public function createValidated(
        $smsCode,
        $username,
        $password,
        $phone,
        $date,
        $firstName,
        $waterfallId,
        $tosVersion = 'row',
        $sndata = null
    ) {
        if (strlen($password) < 6) {
            throw new \InstagramAPI\Exception\InstagramException('Passwords must be at least 6 characters.');
        } elseif (in_array($password, Constants::BLACKLISTED_PASSWORDS, true)) {
            throw new \InstagramAPI\Exception\InstagramException('This is a common password. Try something that\'s harder to guess.');
        }

        $date = explode('-', $date);

        $request = $this->ig->request('accounts/create_validated/')
            ->setNeedsAuth(false)
            ->addPost('tos_version', $tosVersion)
            ->addPost('allow_contacts_sync', 'true')
            ->addPost('phone_id', $this->ig->phone_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('username', $username)
            ->addPost('first_name', $firstName)
            ->addPost('adid', $this->ig->advertising_id)
            ->addPost('guid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('phone_number', $phone)
            ->addPost('day', $date[2])
            ->addPost('month', $date[1])
            ->addPost('year', $date[0])
            ->addPost('waterfall_id', $waterfallId)
            ->addPost('enc_password', Utils::encryptPassword($password, $this->ig->settings->get('public_key_id'), $this->ig->settings->get('public_key')))
            ->addPost('verification_code', $smsCode)
            ->addPost('qs_stamp', '')
            ->addPost('has_sms_consent', 'true')
            ->addPost('one_tap_opt_in', 'true');

        if ($this->ig->getIsAndroid()) {
            $request->addPost('suggestedUsername', '')
                ->addPost('is_secondary_account_creation', false)
                ->addPost('jazoest', Utils::generateJazoest($this->ig->phone_id))
                ->addPost('do_not_auto_login_if_credentials_match', 'true')
                ->addPost('force_sign_up_code', '');
            if ($sndata !== null) {
                $request->addPost('sn_nonce', $sndata['sn_nonce'])
                        ->addPost('sn_result', $sndata['sn_result']);
            } else {
                $request->addPost('sn_nonce', base64_encode($username.'|'.time().'|'.random_bytes(24)))
                        ->addPost('sn_result', sprintf('GOOGLE_PLAY_UNAVAILABLE: %s', array_rand(['SERVICE_INVALID', 'UNKNOWN', 'SERVICE_DISABLED', 'NETWORK_ERROR', 'INTERNAL_ERROR', 'CANCELED', 'INTERRUPTED', 'API_UNAVAILABLE'])));
            }
        } else {
            $request->addPost('do_not_auto_login_if_credentials_match', '0')
                ->addPost('force_create_account', '0')
                ->addPost('ck_error', 'NSURLErrorDomain: -1202')
                ->addPost('ck_environment', 'production')
                ->addPost('ck_environment', 'iCloud.com.burbn.instagram');
        }

        return $request->getResponse(new Response\AccountCreateResponse());
    }

    /**
     * Create secundary account.
     *
     * @param string $username  Username.
     * @param string $password  The password that is going to be set for the account.
     * @param string $firstName First name.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AccountCreateResponse
     */
    public function createSecundary(
        $username,
        $password,
        $firstName = ''
    ) {
        if (strlen($password) < 6) {
            throw new \InstagramAPI\Exception\InstagramException('Passwords must be at least 6 characters.');
        } elseif (in_array($password, Constants::BLACKLISTED_PASSWORDS, true)) {
            throw new \InstagramAPI\Exception\InstagramException('This is a common password. Try something that\'s harder to guess.');
        }

        return $this->ig->request('multiple_accounts/create_secondary_account/')
            ->addPost('suggestedUsername', '')
            ->addPost('should_copy_consent_and_birthday_from_main', 'true')
            ->addPost('main_user_authorization_token', $this->ig->settings->get('authorization_header'))
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('enc_password', Utils::encryptPassword($password, $this->ig->settings->get('public_key_id'), $this->ig->settings->get('public_key')))
            ->addPost('username', $username)
            ->addPost('first_name', $firstName)
            ->addPost('adid', $this->ig->advertising_id)
            ->addPost('guid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('main_user_id', $this->ig->account_id)
            ->addPost('force_sign_up_code', '')
            ->addPost('waterfall_id', Signatures::generateUUID())
            ->addPost('should_cal_link_to_main', 'false')
            ->addPost('one_tap_opt_in', 'true')
            ->addPost('should_link_to_main', 'false')
            ->getResponse(new Response\AccountCreateResponse());
    }

    /**
     * Start account registration process (Bloks).
     *
     * @param string $contactpoint Contactpoint: email or phone.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function startAccountRegistration(
        $contactpoint
    ) {
        $mode = 'phone';
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $contactpoint, $matches)) {
            $mode = 'email';
        }

        $response = $this->ig->processLoginClientDataAndRedirect();
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

        $mainBloks = $this->ig->bloks->parseResponse($response->asArray(), '(bk.action.core.TakeLast');
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'should_trigger_override_login_2fa_action')) {
                $paramBlok = $mainBlok;

                $parsed = $this->ig->bloks->parseBlok($paramBlok, 'bk.action.map.Make');
                $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'should_trigger_override_login_2fa_action'), 0, -2);

                foreach ($offsets as $offset) {
                    if (isset($parsed[$offset])) {
                        $parsed = $parsed[$offset];
                    } else {
                        break;
                    }
                }

                $serverMap = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            }
        }

        $registrationFlowId = Signatures::generateUUID();
        $headersFlowId = Signatures::generateUUID();
        $eventRequestId = Signatures::generateUUID();

        $this->ig->request('bloks/apps/com.bloks.www.bloks.caa.reg.async.ntf_start_experiment_exposure.async/')
            ->setSignedPost(false)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                    'lois_settings' => [
                        'lois_token'    => '',
                        'lara_override' => '',
                    ],
                ],
                'server_params' => [
                    'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
                    'layered_homepage_experiment_group'             => $serverMap['layered_homepage_experiment_group'] ?? null,
                    'device_id'                                     => $serverMap['device_id'] ?? null,
                    'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
                    'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['instance_id'],
                    'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
                    'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                    'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['marker_id']),
                    'family_device_id'                              => null, // $this->phone_id,
                    'offline_experiment_group'                      => $serverMap['offline_experiment_group'] ?? null,
                    'INTERNAL_INFRA_THEME'                          => $serverMap['INTERNAL_INFRA_THEME'] ?? 'harm_f',
                    'access_flow_version'                           => $serverMap['access_flow_version'] ?? 'LEGACY_FLOW',
                    'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        // $regAndFlowInfo = $this->ig->bloks->getRegAndInfoFlow($response->asArray(), 'reg_info');
        // $regInfo = json_decode($regAndFlowInfo['reg_info'], true);
        $regInfo = json_decode('{"registration_flow_id":null,"first_name":null,"last_name":null,"full_name":null,"contactpoint":null,"ar_contactpoint":null,"contactpoint_type":null,"is_using_unified_cp":false,"unified_cp_screen_variant":null,"is_cp_auto_confirmed":false,"is_cp_auto_confirmable":false,"confirmation_code":null,"birthday":null,"did_use_age":null,"gender":null,"use_custom_gender":false,"custom_gender":null,"encrypted_password":null,"username":null,"username_prefill":null,"fb_conf_source":null,"device_id":null,"ig4a_qe_device_id":null,"ig_nta_test_group":null,"family_device_id":null,"nta_eligibility_reason":null,"youth_consent_decision_time":null,"username_screen_experience":"control","user_id":null,"safetynet_token":null,"safetynet_response":null,"machine_id":null,"profile_photo":null,"profile_photo_id":null,"profile_photo_upload_id":null,"avatar":null,"email_oauth_token_no_contact_perm":null,"email_oauth_token":null,"email_oauth_tokens":[],"should_skip_two_step_conf":null,"openid_tokens_for_testing":null,"encrypted_msisdn":null,"encrypted_msisdn_for_safetynet":null,"cached_headers_safetynet_info":null,"should_skip_headers_safetynet":null,"headers_last_infra_flow_id":null,"headers_last_infra_flow_id_safetynet":null,"headers_flow_id":null,"was_headers_prefill_available":false,"sso_enabled":null,"existing_accounts":null,"used_ig_birthday":null,"sync_info":null,"create_new_to_app_account":null,"skip_session_info":null,"ck_error":null,"ck_id":null,"ck_nonce":null,"should_save_password":null,"horizon_synced_username":null,"fb_access_token":null,"horizon_synced_profile_pic":null,"is_identity_synced":false,"is_msplit_reg":null,"user_id_of_msplit_creator":null,"dma_data_combination_consent_given":null,"xapp_accounts":null,"fb_device_id":null,"fb_machine_id":null,"ig_device_id":null,"ig_machine_id":null,"should_skip_nta_upsell":null,"big_blue_token":null,"skip_sync_step_nta":null,"caa_reg_flow_source":null,"ig_authorization_token":null,"full_sheet_flow":false,"crypted_user_id":null,"is_caa_perf_enabled":true,"is_preform":true,"ignore_suma_check":false,"ignore_existing_login":false,"ignore_existing_login_from_suma":false,"ignore_existing_login_after_errors":false,"suggested_first_name":null,"suggested_last_name":null,"suggested_full_name":null,"replace_id_sync_variant":null,"is_redirect_from_nta_replace_id_sync_variant":false,"frl_authorization_token":null,"post_form_errors":null,"skip_step_without_errors":null,"existing_account_exact_match_checked":false,"existing_account_fuzzy_match_checked":false,"email_oauth_exists":false,"confirmation_code_send_error":null,"is_too_young":false,"source_account_type":null,"whatsapp_installed_on_client":null,"confirmation_medium":null,"source_credentials_type":null,"source_cuid":null,"source_account_reg_info":null,"soap_creation_source":null,"source_account_type_to_reg_info":null,"should_skip_youth_tos":false,"is_youth_regulation_flow_complete":false,"is_on_cold_start":false,"email_prefilled":false,"cp_confirmed_by_auto_conf":false,"auto_conf_info":null,"in_sowa_experiment":false,"eligible_to_flash_call_in_ig4a":false,"youth_regulation_config":null,"conf_allow_back_nav_after_change_cp":null,"conf_bouncing_cliff_screen_type":null,"conf_show_bouncing_cliff":null,"is_msplit_neutral_choice":false,"msg_previous_cp":null,"ntp_import_source_info":null,"flash_call_permissions_status":null,"attestation_result":null,"request_data_and_challenge_nonce_string":null,"confirmed_cp_and_code":null,"notification_callback_id":null,"reg_suma_state":null,"reduced_tos_test_group":"control","should_show_spi_before_conf":true,"google_oauth_account":null,"is_reg_request_from_ig_suma":false,"is_igios_spc_reg":false,"device_emails":[],"is_toa_reg":false,"is_threads_public":false,"spc_import_flow":false,"caa_play_integrity_attestation_result":null,"flash_call_provider":null,"name_prefill_variant":"control","spc_birthday_input":false,"failed_birthday_year_count":null,"user_presented_medium_source":null,"user_opted_out_of_ntp":null}', true);
        $regInfo['registration_flow_id'] = $registrationFlowId;
        $regInfo['reg_suma_state'] = 0;

        $clientParams = [
            'device_id'                     => $this->ig->device_id,
            'msg_previous_cp'               => '',
            'switch_cp_first_time_loading'  => 1,
            'accounts_list'                 => [],
            'confirmed_cp_and_code'         => (object) [],
            'family_device_id'              => $this->ig->phone_id,
            'fb_ig_device_id'               => [],
            'lois_settings'                 => [
                'lois_token'    => '',
                'lara_override' => '',
            ],
            'switch_cp_have_seen_suma'      => 0,
        ];

        if ($mode === 'phone') {
            $endpoint = 'bloks/apps/com.bloks.www.bloks.caa.reg.async.contactpoint_phone.async/';
            $clientParams['flash_call_permissions_status'] = [
                'CALL_PHONE'        => 'DENIED',
                'READ_CALL_LOG'     => 'DENIED',
                'READ_PHONE_STATE'  => 'DENIED',
            ];
            $clientParams['phone'] = sprintf('+%s', $contactpoint);
            $clientParams['was_headers_prefill_available'] = 0;
            $clientParams['login_upsell_phone_list'] = [];
            $clientParams['whatsapp_installed_on_client'] = 0;
            $clientParams['was_headers_prefill_used'] = 0;
            $clientParams['headers_infra_flow_id'] = '';
            $clientParams['build_type'] = 'release';
            $clientParams['encrypted_msisdn'] = '';
        } else {
            $endpoint = 'bloks/apps/com.bloks.www.bloks.caa.reg.async.contactpoint_email.async/';
            $clientParams['email_prefilled'] = 0;
            $clientParams['email'] = $contactpoint;
            $clientParams['is_from_device_emails'] = 1;
        }

        $response = $this->ig->request($endpoint)
            ->setSignedPost(false)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'                               => $clientParams,
                'server_params'                                     => [
                    'event_request_id'                              => $eventRequestId,
                    'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
                    'text_input_id'                                 => ($mode === 'phone') ? 167691366000078 : 8667500700035, // improve
                    'layered_homepage_experiment_group'             => null,
                    'device_id'                                     => $this->ig->device_id,
                    'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
                    'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['instance_id'],
                    'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
                    'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                    'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['marker_id']),
                    'reg_info'                                      => json_encode($regInfo),
                    'family_device_id'                              => $this->ig->phone_id,
                    'offline_experiment_group'                      => null,
                    'cp_funnel'                                     => 0,
                    'INTERNAL_INFRA_THEME'                          => 'harm_f',
                    'cp_source'                                     => 0,
                    'access_flow_version'                           => 'F2_FLOW',
                    'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                    'current_step'                                  => 0,
                    'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->ig->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        if ($mode === 'phone') {
            $regInfo['contactpoint'] = sprintf('+%s', $contactpoint);
            $regInfo['contactpoint_type'] = 'phone';
            $regInfo['flash_call_permissions_status'] = [
                'CALL_PHONE'        => 'DENIED',
                'READ_CALL_LOG'     => 'DENIED',
                'READ_PHONE_STATE'  => 'DENIED',
            ];
            $regInfo['was_headers_prefill_available'] = false;
        } else {
            $regInfo['contactpoint'] = sprintf($contactpoint);
            $regInfo['contactpoint_type'] = 'email';
            $regInfo['email_prefilled'] = false;
        }
        $regInfo['device_id'] = $this->ig->device_id;
        $regInfo['family_device_id'] = $this->ig->phone_id;
        $regInfo['whatsapp_installed_on_client'] = false;
        $regInfo['headers_flow_id'] = $headersFlowId;

        $fallbackCall = 0;
        if (str_contains($response->asJson(), 'flash_call_educational_acreen')) {
            $fallbackCall = 1;
            $response = $this->ig->request('bloks/apps/com.bloks.www.bloks.caa.reg.confirmation.flash_call_educational_acreen.async/')
                ->setSignedPost(false)
                ->setNeedsAuth(false)
                ->addPost('params', json_encode((object) [
                    'client_input_params'   => [
                        'permissions_status_after_requesting'   => [
                            'CALL_PHONE'        => 'DENIED',
                            'READ_CALL_LOG'     => 'DENIED',
                            'READ_PHONE_STATE'  => 'DENIED',
                        ],
                        'lois_settings' => [
                            'lois_token'    => '',
                            'lara_override' => '',
                        ],
                    ],
                    'server_params' => [
                        'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
                        'layered_homepage_experiment_group'             => null,
                        'request_type'                                  => 'REROUTE_AFTER_PERMISSIONS_REQUEST',
                        'device_id'                                     => $this->ig->device_id,
                        'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
                        'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['instance_id'],
                        'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
                        'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                        'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['marker_id']),
                        'reg_info'                                      => json_encode($regInfo),
                        'offline_experiment_group'                      => $serverMap['offline_experiment_group'] ?? null,
                        'INTERNAL_INFRA_THEME'                          => 'harm_f,default,default,harm_f',
                        'access_flow_version'                           => 'F2_FLOW',
                        'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                        'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->ig->uuid,
                        'current_step'                                  => 2,
                    ],
                ]))
                ->addPost('bk_client_context', json_encode((object) [
                    'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                    'styles_id'     => 'instagram',
                ]))
                ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
                ->addPost('_uuid', $this->ig->uuid)
                // ->addPost('_csrftoken', $this->ig->client->getToken())
                ->getResponse(new Response\GenericResponse());
        }

        $response = $this->ig->request('bloks/apps/com.bloks.www.bloks.caa.reg.confirmation/')
            ->setSignedPost(false)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                    'lois_settings' => [
                        'lois_token'    => '',
                        'lara_override' => '',
                    ],
                ],
                'server_params' => [
                    'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
                    'is_fallback_from_flash_call'                   => $fallbackCall,
                    'device_id'                                     => $this->ig->device_id,
                    'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
                    'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
                    'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                    'family_device_id'                              => $this->ig->phone_id,
                    'reg_info'                                      => json_encode($regInfo),
                    'INTERNAL_INFRA_THEME'                          => 'harm_f,default,default,harm_f',
                    'INTERNAL_INFRA_screen_id'                      => 'CAA_REG_CONTACT_POINT_PHONE',
                    'access_flow_version'                           => 'F2_FLOW',
                    'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->ig->uuid,
                    'current_step'                                  => 2,
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        return ['response' => $response, 'latency_data'  => $internalLatencyData, 'reg_info' => $regInfo, 'event_request_id' => $eventRequestId, 'server_map' => $serverMap];
    }

    /**
     * Finish account registration process (Bloks).
     *
     * @param array  $startRegistrationData Data from start registration function.
     * @param string $code                  Received code.
     * @param string $fullName              Full name.
     * @param string $username              Username.
     * @param string $password              Password.
     * @param string $birthday              Birthday. Format dd-mm-yyyy
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function finishAccountRegistration(
        $startRegistrationData,
        $code,
        $fullName,
        $username,
        $password,
        $birthday
    ) {
        $serverMap = $startRegistrationData['server_map'];
        $internalLatencyData = $startRegistrationData['latency_data'];
        $eventRequestId = $startRegistrationData['event_request_id'];
        $regInfo = $startRegistrationData['reg_info'];
        $regInfo['caa_reg_flow_source'] = 'cacheable_aymh_screen';

        $serverParams = [
            'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
            'layered_homepage_experiment_group'             => $serverMap['layered_homepage_experiment_group'] ?? null,
            'device_id'                                     => $this->ig->device_id,
            'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
            'wa_timer_id'                                   => 'wa_retriever',
            'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['instance_id'],
            'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
            'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
            'sms_retriever_started_prior_step'              => 0,
            'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['marker_id']),
            'reg_info'                                      => json_encode($regInfo),
            'family_device_id'                              => $this->ig->phone_id,
            'offline_experiment_group'                      => $serverMap['offline_experiment_group'] ?? null,
            'INTERNAL_INFRA_THEME'                          => 'harm_f,default,default,harm_f',
            'access_flow_version'                           => 'F2_FLOW',
            'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
            'current_step'                                  => 3,
            'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->ig->uuid,
        ];

        if ($regInfo['contactpoint_type'] === 'phone') {
            $serverParams['confirmation_medium'] = 'sms';
        } else {
            $serverParams['text_input_id'] = 8821848400124;
            $serverParams['INTERNAL_INFRA_THEME'] = 'harm_f,harm_f';
        }

        $response = $this->ig->request('bloks/apps/com.bloks.www.bloks.caa.reg.confirmation.async/')
            ->setSignedPost(false)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                    'confirmed_cp_and_code' => json_encode((object) []),
                    'code'                  => $code,
                    'fb_ig_device_id'       => [],
                    'device_id'             => $this->ig->device_id,
                    'lois_settings'         => [
                        'lois_token'    => '',
                        'lara_override' => '',
                    ],
                ],
                'server_params' => $serverParams,
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        if (!str_contains($response->asJson(), 'confirmation_success')) {
            throw new \InstagramAPI\Exception\InstagramException('SMS code not valid or expired.');
        }

        if ($regInfo['contactpoint_type'] === 'phone') {
            $regInfo['confirmation_medium'] = 'sms';
            $regInfo['confirmation_code'] = $code;
        } else {
            $re = '/confirmation_code\\\\\\\\\\\\":\\\\\\\\\\\\"(\w+)\\\\\\\\\\\\/m';
            preg_match_all($re, $response->asJson(), $matches, PREG_SET_ORDER, 0);
            if ($matches) {
                $regInfo['confirmation_code'] = $matches[0][1];
            }
        }

        $regInfo['confirmed_cp_and_code'] = [];
        $ts = (string) (time() - random_int(3, 5));
        $nonce = base64_encode($username.'|'.$ts.'|'.random_bytes(24));
        $snResponse = sprintf('VERIFICATION_PENDING: request time is %s', $ts);
        $encryptedPassword = Utils::encryptPassword($password, '', '', true);

        $response = $this->ig->request('bloks/apps/com.bloks.www.bloks.caa.reg.password.async/')
            ->setSignedPost(false)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                    'safetynet_response'                    => $snResponse,
                    'email_oauth_token_map'                 => json_encode((object) []),
                    'caa_play_integrity_attestation_result' => '',
                    'fb_ig_device_id'                       => [],
                    'safetynet_token'                       => $nonce,
                    'encrypted_msisdn_for_safetynet'        => '',
                    'lois_settings'                         => [
                        'lois_token'    => '',
                        'lara_override' => '',
                    ],
                    'whatsapp_installed_on_client'          => 0,
                    'machine_id'                            => $this->ig->settings->get('mid'),
                    'headers_last_infra_flow_id_safetynet'  => '',
                    'system_permissions_status'             => [
                        'READ_CONTACTS'         => 'DENIED',
                        'GET_ACCOUNTS'          => 'DENIED',
                        'READ_PHONE_STATE'      => 'DENIED',
                        'READ_PHONE_NUMBERS'    => 'DENIED',
                    ],
                    'encrypted_password'                    => $encryptedPassword,
                ],
                'server_params' => [
                    'event_request_id'                              => $eventRequestId,
                    'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
                    'layered_homepage_experiment_group'             => $serverMap['layered_homepage_experiment_group'] ?? null,
                    'device_id'                                     => $this->ig->device_id,
                    'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
                    'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['instance_id'],
                    'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
                    'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                    'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['marker_id']),
                    'reg_info'                                      => json_encode($regInfo),
                    'family_device_id'                              => $this->ig->phone_id,
                    'offline_experiment_group'                      => $serverMap['offline_experiment_group'] ?? null,
                    'INTERNAL_INFRA_THEME'                          => 'harm_f,default,default,harm_f',
                    'access_flow_version'                           => 'F2_FLOW',
                    'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                    'current_step'                                  => 5, // this is okay from 3 to 5
                    'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->ig->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        if (!str_contains($response->asJson(), 'CAA_REG_BIRTHDAY')) {
            throw new \InstagramAPI\Exception\InstagramException('Something went wrong during password step.');
        }

        $regInfo['safetynet_response'] = $snResponse;
        $regInfo['safetynet_token'] = $nonce;
        $regInfo['encrypted_password'] = $encryptedPassword;
        $regInfo['did_use_age'] = false;
        $regInfo['should_save_password'] = false;

        $response = $this->ig->request('bloks/apps/com.bloks.www.bloks.caa.reg.birthday.async/')
            ->setSignedPost(false)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                    'should_skip_youth_tos'                 => 0,
                    'is_youth_regulation_flow_complete'     => 0,
                    'client_timezone'                       => $this->ig->getTimezoneName(),
                    'birthday_or_current_date_string'       => $birthday,
                    'birthday_timestamp'                    => \DateTime::createFromFormat('m-d-Y', $birthday)->getTimestamp(),
                    'lois_settings'                         => [
                        'lois_token'    => '',
                        'lara_override' => '',
                    ],
                ],
                'server_params' => [
                    'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
                    'layered_homepage_experiment_group'             => $serverMap['layered_homepage_experiment_group'] ?? null,
                    'device_id'                                     => $this->ig->device_id,
                    'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
                    'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['instance_id'],
                    'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
                    'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                    'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['marker_id']),
                    'reg_info'                                      => json_encode($regInfo),
                    'family_device_id'                              => $this->ig->phone_id,
                    'offline_experiment_group'                      => $serverMap['offline_experiment_group'] ?? null,
                    'INTERNAL_INFRA_THEME'                          => 'harm_f,default,default,harm_f',
                    'access_flow_version'                           => 'F2_FLOW',
                    'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                    'current_step'                                  => 7, // this is okay from 5 to 7
                    'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->ig->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        if (!str_contains($response->asJson(), 'CAA_REG_NAME_IG_AND_SOAP')) {
            throw new \InstagramAPI\Exception\InstagramException('Something went wrong during birthday step.');
        }

        $regInfo['birthday'] = $birthday;

        $response = $this->ig->request('bloks/apps/com.bloks.www.bloks.caa.reg.name_ig_and_soap.async/')
            ->setSignedPost(false)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                    'name'              => $fullName,
                    'accounts_list'     => [],
                    'lois_settings'     => [
                        'lois_token'    => '',
                        'lara_override' => '',
                    ],
                ],
                'server_params' => [
                    'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
                    'layered_homepage_experiment_group'             => $serverMap['layered_homepage_experiment_group'] ?? null,
                    'device_id'                                     => $this->ig->device_id,
                    'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
                    'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['instance_id'],
                    'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
                    'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                    'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['marker_id']),
                    'reg_info'                                      => json_encode($regInfo),
                    'family_device_id'                              => $this->ig->phone_id,
                    'offline_experiment_group'                      => $serverMap['offline_experiment_group'] ?? null,
                    'INTERNAL_INFRA_THEME'                          => 'harm_f,default,default,harm_f',
                    'access_flow_version'                           => 'F2_FLOW',
                    'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                    'current_step'                                  => 8,
                    'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->ig->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        if (!str_contains($response->asJson(), 'CAA_REG_USERNAME')) {
            throw new \InstagramAPI\Exception\InstagramException('Something went wrong during name step.');
        }

        $responseArr = $response->asArray();
        $mainBloks = $this->ig->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'text_input_id')) {
                $parsed = $this->ig->bloks->parseBlok($mainBlok, 'bk.action.map.Make');
                $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'text_input_id'), 0, -2);

                foreach ($offsets as $offset) {
                    if (isset($parsed[$offset])) {
                        $parsed = $parsed[$offset];
                    } else {
                        break;
                    }
                }

                $textInputMap = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            }
        }

        $regInfo['full_name'] = $fullName;
        $response = $this->ig->request('bloks/apps/com.bloks.www.bloks.caa.reg.username.async/')
            ->setSignedPost(false)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                    'validation_text'              => $username,
                    'family_device_id'             => $this->ig->phone_id,
                    'device_id'                    => $this->ig->device_id,
                    'lois_settings'                => [
                        'lois_token'    => '',
                        'lara_override' => '',
                    ],
                    'qe_device_id'                 => $this->ig->uuid,
                ],
                'server_params' => [
                    'event_request_id'                              => $eventRequestId,
                    'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
                    'text_input_id'                                 => ($regInfo['contactpoint_type'] === 'phone') ? 169595097900031 : 9113415500031, // improve
                    'layered_homepage_experiment_group'             => $serverMap['layered_homepage_experiment_group'] ?? null,
                    'device_id'                                     => $this->ig->device_id,
                    'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
                    'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['instance_id'],
                    'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
                    'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                    'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['marker_id']),
                    'reg_info'                                      => json_encode($regInfo),
                    'family_device_id'                              => $this->ig->phone_id,
                    'offline_experiment_group'                      => null,
                    'INTERNAL_INFRA_THEME'                          => 'harm_f,default,default,harm_f',
                    'suggestions_container_id'                      => ($regInfo['contactpoint_type'] === 'phone') ? 169595097900030 : 9113415500030,
                    'action'                                        => 1,
                    'screen_id'                                     => ($regInfo['contactpoint_type'] === 'phone') ? 169595097900017 : 9113415500017,
                    'access_flow_version'                           => 'F2_FLOW',
                    'input_id'                                      => ($regInfo['contactpoint_type'] === 'phone') ? 169595097900032 : 9113415500032,
                    'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                    'current_step'                                  => 9,
                    'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->ig->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        if (!str_contains($response->asJson(), 'CAA_REG_CONTACTPOINT_EMAIL_OAUTH_TOKEN')) {
            throw new \InstagramAPI\Exception\InstagramException('Something went wrong during username step.');
        }

        $regInfo['username'] = $username;
        $regInfo['username_prefill'] = $username;

        $response = $this->ig->request('bloks/apps/com.bloks.www.bloks.caa.reg.create.account.async/')
            ->setSignedPost(false)
            ->setNeedsAuth(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                    'ck_error'                     => '',
                    'device_id'                    => $this->ig->device_id,
                    'waterfall_id'                 => $serverMap['waterfall_id'] ?? null,
                    'failed_birthday_year_count'   => '',
                    'headers_last_infra_flow_id'   => '',
                    'machine_id'                   => $this->ig->settings->get('mid'),
                    'should_ignore_existing_login' => 0,
                    'reached_from_tos_screen'      => 1,
                    'ck_nonce'                     => '',
                    'lois_settings'                => [
                        'lois_token'    => '',
                        'lara_override' => '',
                    ],
                    'ck_id'                             => '',
                    'no_contact_perm_email_oauth_token' => '',
                    'encrypted_msisdn'                  => '',
                ],
                'server_params' => [
                    'event_request_id'                              => $eventRequestId,
                    'is_from_logged_out'                            => intval($serverMap['is_from_logged_out'] ?? 0),
                    'layered_homepage_experiment_group'             => $serverMap['layered_homepage_experiment_group'] ?? null,
                    'device_id'                                     => $this->ig->device_id,
                    'waterfall_id'                                  => $serverMap['waterfall_id'] ?? null,
                    'INTERNAL__latency_qpl_instance_id'             => $internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['instance_id'],
                    'flow_info'                                     => json_encode(['flow_name'  =>  'new_to_family_ig_default', 'flow_type' => 'ntf']),
                    'is_platform_login'                             => intval($serverMap['is_platform_login'] ?? 0),
                    'INTERNAL__latency_qpl_marker_id'               => intval($internalLatencyData['com.bloks.www.bloks.caa.reg.aymh_create_account_button.async']['marker_id']),
                    'reg_info'                                      => json_encode($regInfo),
                    'family_device_id'                              => $this->ig->phone_id,
                    'offline_experiment_group'                      => null,
                    'INTERNAL_INFRA_THEME'                          => 'harm_f,default,default,harm_f',
                    'access_flow_version'                           => 'F2_FLOW',
                    'is_from_logged_in_switcher'                    => intval($serverMap['is_from_logged_in_switcher'] ?? 0),
                    'current_step'                                  => 10,
                    'qe_device_id'                                  => $serverMap['qe_device_id'] ?? $this->ig->uuid,
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        if (!str_contains($response->asJson(), 'registration_response')) {
            throw new \InstagramAPI\Exception\InstagramException('Something went wrong during account registration step.');
        }

        $response = $this->ig->processCreateResponse($response);

        return $response;
    }

    /**
     * Check if phone number is valid.
     *
     * @param string $phone Phone with country code. For example: '+34123456789'.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function checkPhoneNumber(
        $phone
    ) {
        return $this->ig->request('accounts/check_phone_number/')
            ->setNeedsAuth(false)
            ->addPost('prefill_shown', 'False')
            ->addPost('login_nonce_map', '{}')
            ->addPost('phone_number', $phone)
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('guid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Request registration SMS code.
     *
     * @param string $phone       Phone with country code. For example: '+34123456789'.
     * @param string $waterfallId UUIDv4.
     * @param string $username    Username.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SendSignupSmsCodeResponse
     */
    public function requestRegistrationSms(
        $phone,
        $waterfallId,
        $username
    ) {
        $this->ig->setUserWithoutPassword($username);

        return $this->ig->request('accounts/send_signup_sms_code/')
            ->setNeedsAuth(false)
            ->addPost('phone_number', $phone)
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('device_id', $this->ig->device_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('android_build_type', 'release')
            ->addPost('guid', $this->ig->uuid)
            ->addPost('waterfall_id', $waterfallId)
            ->getResponse(new Response\SendSignupSmsCodeResponse());
    }

    /**
     * Validate signup sms code.
     *
     * @param string $smsCode     The received SMS code.
     * @param string $phone       Phone with country code. For example: '+34123456789'.
     * @param string $waterfallId UUIDv4.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function validateSignupSmsCode(
        $smsCode,
        $phone,
        $waterfallId
    ) {
        return $this->ig->request('accounts/validate_signup_sms_code/')
            ->setNeedsAuth(false)
            ->addPost('verification_code', $smsCode)
            ->addPost('phone_number', $phone)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('guid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('waterfall_id', $waterfallId)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Send email verification code.
     *
     * @param string $email       Email.
     * @param string $waterfallId UUIDv4.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SendVerifyEmailResponse
     */
    public function sendEmailVerificationCode(
        $email,
        $waterfallId
    ) {
        return $this->ig->request('accounts/send_verify_email/')
            ->setNeedsAuth(false)
            ->addPost('email', $email)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('guid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('waterfall_id', $waterfallId)
            ->addPost('auto_confirm_only', 'false')
            ->getResponse(new Response\SendVerifyEmailResponse());
    }

    /**
     * Check confirmation code.
     *
     * @param string $code        The received code.
     * @param string $email       Email.
     * @param string $waterfallId UUIDv4.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CheckConfirmationCodeResponse
     */
    public function checkConfirmationCode(
        $code,
        $email,
        $waterfallId
    ) {
        return $this->ig->request('accounts/check_confirmation_code/')
            ->setNeedsAuth(false)
            ->addPost('code', $code)
            ->addPost('email', $email)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('guid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('waterfall_id', $waterfallId)
            ->getResponse(new Response\CheckConfirmationCodeResponse());
    }

    /**
     * Get login activity and suspicious login attempts.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\LoginActivityResponse
     */
    public function getLoginActivity()
    {
        return $this->ig->request('session/login_activity/')
            ->addParam('device_id', $this->ig->device_id)
            ->getResponse(new Response\LoginActivityResponse());
    }

    /**
     * Get login activity and suspicious login attempts (Bloks).
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getUnrecognizedLoginsBloks()
    {
        $response = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.security.login_activities.unrecognized_logins/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'server_params' => [
                    'INTERNAL_INFRA_THEME'      => 'harm_f,default,default,harm_f',
                    'INTERNAL_INFRA_screen_id'  => $this->ig->bloksInfo['INTERNAL_INFRA_screen_id'] ?? '',
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $arrayResponse = $response->asArray();

        try {
            return $arrayResponse['layout']['bloks_payload']['data'][0]['data']['initial'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get login activity.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getLoginActivityBloks()
    {
        $response = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.security.login_activities/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'client_input_params' => [
                    'profile_identifier'    => $this->ig->settings->get('fbid_v2'),
                    'account_type'          => 1,
                ],
                'server_params' => [
                    'is_device_management'              => 1,
                    'requested_screen_component_type'   => 2,
                    'INTERNAL_INFRA_THEME'              => 'harm_f,default,default,harm_f',
                    'INTERNAL_INFRA_screen_id'          => 'login_activities',
                    'ig_auth_proof_json'                => $this->ig->settings->get('authorization_header'),
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $arrayResponse = $response->asArray();

        $loginActivity = [];

        try {
            foreach ($arrayResponse['layout']['bloks_payload']['data'] as $data) {
                if (isset($data['data']['key']) && str_contains($data['data']['key'], 'FX_LOGIN_ACTIVITIES_SESSIONS')) {
                    $loginActivity[] = $data['data']['initial'];
                }
            }
        } catch (\Exception $e) {
            return $loginActivity;
        }

        return $loginActivity;
    }

    /**
     * Logout session.
     *
     * @param $sessionId Session ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function logoutSession(
        $sessionId
    ) {
        return $this->ig->request('session/login_activity/logout_session/')
            ->setSignedPost(false)
            ->addPost('session_id', $sessionId)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Logout session (Bloks).
     *
     * @param string $sessionId SessionID.
     * @param int    $accountId Account ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function logoutSessionBloks(
        $sessionId,
        $accountId
    ) {
        return $this->ig->request('bloks/apps/com.bloks.www.fx.settings.security.login_activities.unrecognized_logins.logout/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'client_params' => [
                    'session_id'        => $sessionId,
                    'account_type'      => 1,
                    'account_id'        => $accountId,
                    'family_device_id'  => $this->ig->phone_id,
                ],
                'server_params' => [
                    'requested_screen_component_type'   => null,
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_marker_id'   => $this->ig->bloksInfo['INTERNAL__latency_qpl_marker_id'] ?? '',
                    'INTERNAL__latency_qpl_instance_id' => $this->ig->bloksInfo['INTERNAL__latency_qpl_instance_id'] ?? '',
                    'INTERNAL_INFRA_THEME'              => 'harm_f,default,default,harm_f',
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Approve (Confirm it was you) a suspicious login.
     *
     * @param string $loginId        Login ID.
     * @param string $loginTimestamp Login timestamp.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     *
     * @see getLoginActivity() for obtaining login IDs, login timestamps and suspicious logins
     */
    public function approveSuspiciousLogin(
        $loginId,
        $loginTimestamp
    ) {
        return $this->ig->request('session/login_activity/avow_login/')
        ->setSignedPost(false)
        ->addPost('login_timestamp', $loginTimestamp)
        ->addPost('login_id', $loginId)
        // ->addPost('_csrftoken', $this->ig->client->getToken())
        ->addPost('_uuid', $this->ig->uuid)
        ->getResponse(new Response\GenericResponse());
    }

    /**
     * Approve (Confirm it was you) a suspicious login (Bloks).
     *
     * @param string $sessionId SessionID.
     * @param int    $accountId Account ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function approveSuspiciousLoginBloks(
        $sessionId,
        $accountId
    ) {
        return $this->ig->request('bloks/apps/com.bloks.www.fx.settings.security.avow_login/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'client_params' => [
                    'session_id'        => $sessionId,
                    'account_type'      => 1,
                    'account_id'        => $accountId,
                    'family_device_id'  => $this->ig->phone_id,
                ],
                'server_params' => [
                    'requested_screen_component_type'   => null,
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_marker_id'   => $this->ig->bloksInfo['INTERNAL__latency_qpl_marker_id'] ?? '',
                    'INTERNAL__latency_qpl_instance_id' => $this->ig->bloksInfo['INTERNAL__latency_qpl_instance_id'] ?? '',
                    'INTERNAL_INFRA_THEME'              => 'harm_f,default,default,harm_f',
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get details about child and main IG accounts.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function getAccountFamily()
    {
        return $this->ig->request('multiple_accounts/get_account_family/')
            ->addHeader('X-Tigon-Is-Retry', 'False')
            ->getResponse(new Response\MultipleAccountFamilyResponse());
    }

    /**
     * Get unseen facebook notifications.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UnseenFacebookNotificationsResponse
     */
    public function getUnseenFacebookNotifications()
    {
        return $this->ig->request('family_navigation/get_unseen_fb_notification_info/')
            ->getResponse(new Response\UnseenFacebookNotificationsResponse());
    }

    /**
     * Get details about the currently logged in account.
     *
     * Also try People::getSelfInfo() instead, for some different information.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     *
     * @see People::getSelfInfo()
     */
    public function getCurrentUser()
    {
        return $this->ig->request('accounts/current_user/')
            ->addParam('edit', true)
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Edit your gender.
     *
     * WARNING: Remember to also call `editProfile()` *after* using this
     * function, so that you act like the real app!
     *
     * @param string $gender this can be male, female, empty or null for 'prefer not to say' or anything else for custom
     *
     * @return Response\UserInfoResponse
     */
    public function setGender(
        $gender = ''
    ) {
        switch (strtolower($gender)) {
            case 'male':$gender_id = 1;
                break;
            case 'female':$gender_id = 2;
                break;
            case null:
            case '':$gender_id = 3;
                break;
            default:$gender_id = 4;
        }

        return $this->ig->request('accounts/set_gender/')
            ->setSignedPost(false)
            ->addPost('gender', $gender_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('custom_gender', $gender_id === 4 ? $gender : '')
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Edit your birthday.
     *
     * WARNING: Remember to also call `editProfile()` *after* using this
     * function, so that you act like the real app!
     *
     * @param string $day   Day of birth.
     * @param string $month Month of birth.
     * @param string $year  Year of birth.
     *
     * @return Response\UserInfoResponse
     */
    public function setBirthday(
        $day,
        $month,
        $year
    ) {
        return $this->ig->request('accounts/set_birthday/')
            ->setSignedPost(false)
            ->addPost('day', $day)
            ->addPost('month', $month)
            ->addPost('year', $year)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Edit your birthday (bloks).
     *
     * @param string $day   Day of birth.
     * @param string $month Month of birth.
     * @param string $year  Year of birth.
     *
     * @return Response\UserInfoResponse
     */
    public function setBirthdayBloks(
        $day,
        $month,
        $year
    ) {
        $this->ig->request('bloks/apps/com.bloks.www.fxcal.xplat.settings.edit.birthday.confirmation.async/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [],
                'server_params'         => [
                    'from_other_entrypoint'             => 0,
                    'INTERNAL__latency_qpl_marker_id'   => $this->ig->bloksInfo['INTERNAL__latency_qpl_marker_id'] ?? '',
                    'confirmation_dialog_title'         => 'Confirm changes to your birthday',
                    'INTERNAL_INFRA_THEME'              => 'harm_f,default,default,harm_f',
                    'requested_screen_component_type'   => null,
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_instance_id' => $this->ig->bloksInfo['INTERNAL__latency_qpl_instance_id'] ?? '',
                    'dialog_type'                       => 'edit_birthday',
                    'timestamp'                         => strtotime(sprintf('%s-%s-%s', $day, $month, $year)),
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
                'ttrc_join_id'  => Signatures::generateUUID(),
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $response = $this->ig->request('bloks/apps/com.bloks.www.fxcal.xplat.settings.edit.birthday.async/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                    'timestamp'                         => strtotime(sprintf('%s-%s-%s', $day, $month, $year)),
                ],
                'server_params' => [
                    'from_other_entrypoint'             => 0,
                    'INTERNAL__latency_qpl_marker_id'   => $this->ig->bloksInfo['INTERNAL__latency_qpl_marker_id'] ?? '',
                    'INTERNAL_INFRA_THEME'              => 'harm_f,default,default,harm_f',
                    'requested_screen_component_type'   => null,
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_instance_id' => $this->ig->bloksInfo['INTERNAL__latency_qpl_instance_id'] ?? '',
                    'dialog_type'                       => 'edit_birthday',
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
                'ttrc_join_id'  => Signatures::generateUUID(),
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $this->ig->request('bloks/apps/com.bloks.www.fxcal.xplat.settings.birthday.populate.history.async/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'client_input_params'   => [
                ],
                'server_params' => [
                    'requested_screen_component_type'   => null,
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_marker_id'   => $this->ig->bloksInfo['INTERNAL__latency_qpl_marker_id'] ?? '',
                    'INTERNAL__latency_qpl_instance_id' => $this->ig->bloksInfo['INTERNAL__latency_qpl_instance_id'] ?? '',
                    'INTERNAL_INFRA_THEME'              => 'harm_f,default,default,harm_f',
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
                'ttrc_join_id'  => Signatures::generateUUID(),
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        return $response;
    }

    /**
     * Get personal info from Settings (Bloks).
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return string[]
     */
    public function getPersonalInfo()
    {
        $response = $this->ig->request('bloks/apps/com.bloks.www.fxcal.settings.navigation/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'server_params' => [
                    'should_show_done_button'   => 0,
                    'is_deeplink'               => 0,
                    'INTERNAL_INFRA_THEME'      => 'harm_f',
                    'entrypoint'                => 'app_settings',
                    'node_id'                   => 'personal_info',
                    'INTERNAL_INFRA_screen_id'  => 'personal_info',
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $re = '/Contact\sinfo,\s(\w+@\w+\.\w+)|Birthday,\s(\w+\s\d+,\s\d+)/m';
        preg_match_all($re, $response->asJson(), $matches, PREG_SET_ORDER, 0);

        $info = [];
        foreach ($matches as $match) {
            if (str_contains($match[0], 'Contact info')) {
                $info['contact'] = $match[1];
            } else {
                $timestamp = strtotime($match[2]);
                $birthday = date('d/m/Y', $timestamp);
                $info['birthday'] = $birthday;
            }
        }

        return $info;
    }

    /**
     * Update profile name.
     *
     * It can only by updated two times within 14 days.
     *
     * @param string $name Profile name.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function setProfileName(
        $name
    ) {
        return $this->ig->request('accounts/update_profile_name/')
            ->setSignedPost(false)
            ->addPost('first_name', $name)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Update username to a new one.
     *
     * @param string $newUsername New username.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function setUsername(
        $newUsername
    ) {
        return $this->ig->request('accounts/update_profile_username/')
            ->setSignedPost(false)
            ->addPost('username', $newUsername)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Update profile name via Account Center (Bloks).
     *
     * It can only by updated two times within 14 days.
     *
     * @param string $name Profile name.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function setProfileNameBloks(
        $name
    ) {
        $response = $this->ig->request('bloks/apps/com.bloks.www.fxim.settings.name.change.async/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'client_input_params' => [
                    'full_name'         => $name,
                    'family_device_id'  => $this->ig->phone_id,
                ],
                'server_params' => [
                    'should_validate_full_name_with_space'  => 0,
                    'identity_ids'                          => $this->ig->settings->get('fbid_v2'),
                    'INTERNAL_INFRA_THEME'                  => 'harm_f',
                    'should_dismiss_screen_after-save'      => 0,
                    'is_meta_verified_profile_editing_flow' => 0,
                    'machine_id'                            => null,
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
                'ttrc_join_id'  => Signatures::generateUUID(),
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        return $response;
    }

    /**
     * Edit your biography.
     *
     * You are able to add `@mentions` and `#hashtags` to your biography, but
     * be aware that Instagram disallows certain web URLs and shorteners.
     *
     * Also keep in mind that anyone can read your biography (even if your
     * account is private).
     *
     * WARNING: Remember to also call `editProfile()` *after* using this
     * function, so that you act like the real app!
     *
     * @param string $biography Biography text. Use "" for nothing.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     *
     * @see Account::editProfile() should be called after this function!
     */
    public function setBiography(
        $biography
    ) {
        if (!is_string($biography) || mb_strlen($biography, 'utf8') > 150) {
            throw new \InvalidArgumentException('Please provide a 0 to 150 character string as biography.');
        }

        return $this->ig->request('accounts/set_biography/')
            ->addPost('raw_text', $biography)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('device_id', $this->ig->device_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Changes your account's profile picture.
     *
     * @param string $photoFilename The photo filename.
     * @param bool   $shareToFeed   Share the photo to feed.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function changeProfilePicture(
        $photoFilename,
        $shareToFeed = false
    ) {
        $photo = new \InstagramAPI\Media\Photo\InstagramPhoto($photoFilename, ['jpgOutput' => true, 'targetFeed' => Constants::PROFILE_PIC]);
        $internalMetadata = new InternalMetadata(Utils::generateUploadId(true));
        $internalMetadata->setPhotoDetails(Constants::PROFILE_PIC, $photo->getFile());
        $uploadResponse = $this->ig->internal->uploadPhotoData(Constants::PROFILE_PIC, $internalMetadata);

        $request = $this->ig->request('accounts/change_profile_picture/')
            ->setSignedPost(false)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('use_fbuploader', 'true')
            ->addPost('remove_birthday_selfie', 'False')
            ->addPost('upload_id', $internalMetadata->getUploadId());

        if ($shareToFeed) {
            $request->addPost('share_to_feed', 'true');
        }

        return $request->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Remove your account's profile picture.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function removeProfilePicture()
    {
        return $this->ig->request('accounts/remove_profile_picture/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Edit your profile.
     *
     * Warning: You must provide ALL parameters to this function. The values
     * which you provide will overwrite all current values on your profile.
     * You can use getCurrentUser() to see your current values first.
     *
     * @param string      $url         Website URL. Use "" for nothing.
     * @param string      $phone       Phone number. Use "" for nothing.
     * @param string      $name        Full name. Use "" for nothing.
     * @param string      $biography   Biography text. Use "" for nothing.
     * @param string      $email       Email. Required!
     * @param string|null $newUsername (optional) Rename your account to a new username,
     *                                 which you've already verified with checkUsername().
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     *
     * @see Account::getCurrentUser() to get your current account details.
     * @see Account::checkUsername() to verify your new username first.
     */
    public function editProfile(
        $url,
        $phone,
        $name,
        $biography,
        $email,
        $newUsername = null
    ) {
        if ($email === null || $email === '') {
            throw new \InvalidArgumentException('No email provided.');
        }
        // We must mark the profile for editing before doing the main request.
        $userResponse = $this->ig->request('accounts/current_user/')
            ->addParam('edit', true)
            ->getResponse(new Response\UserInfoResponse());

        // Get the current user's name from the response.
        $currentUser = $userResponse->getUser();
        if (!$currentUser || !is_string($currentUser->getUsername())) {
            throw new InternalException('Unable to find current account username while preparing profile edit.');
        }
        $oldUsername = $currentUser->getUsername();

        // Determine the desired username value.
        $username = is_string($newUsername) && strlen($newUsername) > 0
                  ? $newUsername
                  : $oldUsername; // Keep current name.

        return $this->ig->request('accounts/edit_profile/')
            ->addPost('primary_profile_link_type', '0')
            ->addPost('external_url', $url)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('phone_number', $phone)
            ->addPost('username', $username)
            ->addPost('show_fb_link_on_profile', 'false')
            ->addPost('first_name', $name)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('biography', $biography)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('email', $email)
            ->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Get anonymous profile picture.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AnonymousProfilePictureResponse
     */
    public function getAnonymousProfilePicture()
    {
        return $this->ig->request('accounts/anonymous_profile_picture/')
                        ->getResponse(new Response\AnonymousProfilePictureResponse());
    }

    /**
     * Sets your account to public.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function setPublic()
    {
        $request = $this->ig->request('accounts/set_public/')
            ->addPost('_uuid', $this->ig->uuid);
        // ->addPost('_csrftoken', $this->ig->client->getToken());

        if ($this->ig->getIsAndroid()) {
            $request->addPost('_uid', $this->ig->account_id);
        }

        return $request->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Sets your account to private.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UserInfoResponse
     */
    public function setPrivate()
    {
        $request = $this->ig->request('accounts/set_private/')
            ->addPost('_uuid', $this->ig->uuid);
        // ->addPost('_csrftoken', $this->ig->client->getToken());

        if ($this->ig->getIsAndroid()) {
            $request->addPost('_uid', $this->ig->account_id);
        }

        return $request->getResponse(new Response\UserInfoResponse());
    }

    /**
     * Switches your account to business profile.
     *
     * In order to switch your account to Business profile you MUST
     * call Account::setBusinessInfo().
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SwitchBusinessProfileResponse
     *
     * @see Account::setBusinessInfo() sets required data to become a business profile.
     */
    public function switchToBusinessProfile()
    {
        return $this->ig->request('business_conversion/get_business_convert_social_context/')
            ->getResponse(new Response\SwitchBusinessProfileResponse());
    }

    /**
     * Switches your account to personal profile.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SwitchPersonalProfileResponse
     */
    public function switchToPersonalProfile()
    {
        return $this->ig->request('accounts/convert_to_personal/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\SwitchPersonalProfileResponse());
    }

    /**
     * Sets contact information for business profile.
     *
     * @param string $phoneNumber Phone number with country code. Format: +34123456789.
     * @param string $email       Email.
     * @param string $categoryId  TODO: Info.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CreateBusinessInfoResponse
     */
    public function setBusinessInfo(
        $phoneNumber,
        $email,
        $categoryId
    ) {
        return $this->ig->request('accounts/create_business_info/')
            ->addPost('set_public', 'true')
            ->addPost('entry_point', 'setting')
            ->addPost('public_phone_contact', json_encode([
                'public_phone_number'       => $phoneNumber,
                'business_contact_method'   => 'CALL',
            ]))
            ->addPost('public_email', $email)
            ->addPost('category_id', $categoryId)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\CreateBusinessInfoResponse());
    }

    /**
     * Check if an Instagram username is available (not already registered).
     *
     * Use this before trying to rename your Instagram account,
     * to be sure that the new username is available.
     *
     * @param string $username Instagram username to check.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CheckUsernameResponse
     *
     * @see Account::editProfile() to rename your account.
     */
    public function checkUsername(
        $username
    ) {
        $this->ig->setUserWithoutPassword($username);

        return $this->ig->request('users/check_username/')
            ->setNeedsAuth(false)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('username', $username)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('is_group_creation', false)
            ->getResponse(new Response\CheckUsernameResponse());
    }

    /**
     * Check if an email is available (not already registered).
     *
     * @param string $email       Email to check.
     * @param string $waterfallId UUIDv4.
     * @param string $username    Username.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CheckEmailResponse
     */
    public function checkEmail(
        $email,
        $waterfallId,
        $username
    ) {
        $this->ig->setUserWithoutPassword($username);

        $request = $this->ig->request('users/check_email/')
            ->setNeedsAuth(false)
            ->addPost('email', $email);

        if ($this->ig->getIsAndroid()) {
            $request->addPost('android_device_id', $this->ig->device_id)
                ->addPost('login_nonce_map', '{}')
                ->addPost('login_nonces', '[]')
                ->addPost('qe_id', $this->ig->uuid)
                ->addPost('waterfall_id', $waterfallId);
        } else {
            $request->addPost('qe_id', $this->ig->device_id);
        }

        return $request->getResponse(new Response\CheckEmailResponse());
    }

    /**
     * Get signup config.
     *
     * @param string $username Username.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getSignupConfig(
        $username
    ) {
        $this->ig->setUserWithoutPassword($username);

        return $this->ig->request('consent/get_signup_config/')
            ->setNeedsAuth(false)
            ->addParam('guid', $this->ig->uuid)
            ->addParam('main_account_selected', 'false')
            ->getResponse(new Response\CheckEmailResponse());
    }

    /**
     * Get username suggestions.
     *
     * @param string $email         Email to check.
     * @param string $waterfallId   UUIDv4.
     * @param string $usernameQuery Username query for username suggestions.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\UsernameSuggestionsResponse
     */
    public function getUsernameSuggestions(
        $email,
        $waterfallId,
        $usernameQuery = ''
    ) {
        return $this->ig->request('accounts/username_suggestions/')
            ->setNeedsAuth(false)
            ->addPost('phone_id', $this->ig->phone_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('guid', $this->ig->uuid)
            ->addPost('name', $usernameQuery)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('email', $email)
            ->addPost('waterfall_id', $waterfallId)
            ->getResponse(new Response\UsernameSuggestionsResponse());
    }

    /**
     * Get account spam filter status.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CommentFilterResponse
     */
    public function getCommentFilter()
    {
        return $this->ig->request('accounts/get_comment_filter/')
            ->getResponse(new Response\CommentFilterResponse());
    }

    /**
     * Set account spam filter status (on/off).
     *
     * @param int $config_value Whether spam filter is on (0 or 1).
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CommentFilterSetResponse
     */
    public function setCommentFilter(
        $config_value
    ) {
        return $this->ig->request('accounts/set_comment_filter/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('config_value', $config_value)
            ->getResponse(new Response\CommentFilterSetResponse());
    }

    /**
     * Get whether the comment category filter is disabled.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CommentCategoryFilterResponse
     */
    public function getCommentCategoryFilterDisabled()
    {
        return $this->ig->request('accounts/get_comment_category_filter_disabled/')
            ->getResponse(new Response\CommentCategoryFilterResponse());
    }

    /**
     * Get account spam filter keywords.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CommentFilterKeywordsResponse
     */
    public function getCommentFilterKeywords()
    {
        return $this->ig->request('accounts/get_comment_filter_keywords/')
            ->getResponse(new Response\CommentFilterKeywordsResponse());
    }

    /**
     * Set account spam filter keywords.
     *
     * @param string $keywords List of blocked words, separated by comma.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CommentFilterSetResponse
     */
    public function setCommentFilterKeywords(
        $keywords
    ) {
        return $this->ig->request('accounts/set_comment_filter_keywords/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('keywords', $keywords)
            ->getResponse(new Response\CommentFilterSetResponse());
    }

    /**
     * Change your account's password.
     *
     * @param string $oldPassword Old password.
     * @param string $newPassword New password.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\ChangePasswordResponse
     */
    public function changePassword(
        $oldPassword,
        $newPassword
    ) {
        return $this->ig->request('accounts/change_password/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('enc_old_password', Utils::encryptPassword($oldPassword, $this->ig->settings->get('public_key_id'), $this->ig->settings->get('public_key')))
            ->addPost('enc_new_password1', Utils::encryptPassword($newPassword, $this->ig->settings->get('public_key_id'), $this->ig->settings->get('public_key')))
            ->addPost('enc_new_password2', Utils::encryptPassword($newPassword, $this->ig->settings->get('public_key_id'), $this->ig->settings->get('public_key')))
            ->getResponse(new Response\ChangePasswordResponse());
    }

    /**
     * Get account security info and backup codes.
     *
     * WARNING: STORE AND KEEP BACKUP CODES IN A SAFE PLACE. THEY ARE EXTREMELY
     *          IMPORTANT! YOU WILL GET THE CODES IN THE RESPONSE. THE BACKUP
     *          CODES LET YOU REGAIN CONTROL OF YOUR ACCOUNT IF YOU LOSE THE
     *          PHONE NUMBER! WITHOUT THE CODES, YOU RISK LOSING YOUR ACCOUNT!
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AccountSecurityInfoResponse
     *
     * @see Account::enableTwoFactorSMS()
     */
    public function getSecurityInfo()
    {
        return $this->ig->request('accounts/account_security_info/')
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\AccountSecurityInfoResponse());
    }

    /**
     * Get account security info and backup codes.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AccountSecurityInfoResponse
     *
     * @see Account::getSecurityInfo()
     */
    public function regenBackupCodes()
    {
        return $this->ig->request('accounts/regen_backup_codes/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('device_id', $this->ig->device_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\AccountSecurityInfoResponse());
    }

    /**
     * Request that Instagram enables two factor SMS authentication.
     *
     * The SMS will have a verification code for enabling two factor SMS
     * authentication. You must then give that code to enableTwoFactorSMS().
     *
     * @param string $phoneNumber Phone number with country code. Format: +34123456789.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SendTwoFactorEnableSMSResponse
     *
     * @see Account::enableTwoFactorSMS()
     */
    public function sendTwoFactorEnableSMS(
        $phoneNumber
    ) {
        $cleanNumber = '+'.preg_replace('/[^0-9]/', '', $phoneNumber);

        return $this->ig->request('accounts/send_two_factor_enable_sms/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('phone_number', $cleanNumber)
            ->getResponse(new Response\SendTwoFactorEnableSMSResponse());
    }

    /**
     * Enable Two Factor authentication.
     *
     * WARNING: STORE AND KEEP BACKUP CODES IN A SAFE PLACE. THEY ARE EXTREMELY
     *          IMPORTANT! YOU WILL GET THE CODES IN THE RESPONSE. THE BACKUP
     *          CODES LET YOU REGAIN CONTROL OF YOUR ACCOUNT IF YOU LOSE THE
     *          PHONE NUMBER! WITHOUT THE CODES, YOU RISK LOSING YOUR ACCOUNT!
     *
     * @param string $phoneNumber      Phone number with country code. Format: +34123456789.
     * @param string $verificationCode The code sent to your phone via `Account::sendTwoFactorEnableSMS()`.
     * @param bool   $trustDevice      If you want to trust the used Device ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\AccountSecurityInfoResponse
     *
     * @see Account::sendTwoFactorEnableSMS()
     * @see Account::getSecurityInfo()
     */
    public function enableTwoFactorSMS(
        $phoneNumber,
        $verificationCode,
        $trustDevice = false
    ) {
        $cleanNumber = '+'.preg_replace('/[^0-9]/', '', $phoneNumber);

        $this->ig->request('accounts/enable_sms_two_factor/')
            ->addPost('trust_this_device', ($trustDevice) ? '1' : '0')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('phone_number', $cleanNumber)
            ->addPost('verification_code', $verificationCode)
            ->getResponse(new Response\EnableTwoFactorSMSResponse());

        return $this->getSecurityInfo();
    }

    /**
     * Disable Two Factor authentication.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\DisableTwoFactorSMSResponse
     */
    public function disableTwoFactorSMS()
    {
        return $this->ig->request('accounts/disable_sms_two_factor/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\DisableTwoFactorSMSResponse());
    }

    /**
     * Save presence status to the storage.
     *
     * @param bool $disabled
     */
    protected function _savePresenceStatus(
        $disabled
    ) {
        try {
            $this->ig->settings->set('presence_disabled', $disabled ? '1' : '0');
        } catch (SettingsException $e) {
            // Ignore storage errors.
        }
    }

    /**
     * Get presence status.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\PresenceStatusResponse
     */
    public function getPresenceStatus()
    {
        return $this->ig->request('accounts/get_presence_disabled/')
            ->setSignedGet(true)
            ->getResponse(new Response\PresenceStatusResponse());
    }

    /**
     * Enable presence.
     *
     * Allow accounts you follow and anyone you message to see when you were
     * last active on Instagram apps.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function enablePresence()
    {
        /** @var Response\GenericResponse $result */
        $result = $this->ig->request('accounts/set_presence_disabled/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('disabled', '0')
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $this->_savePresenceStatus(false);

        return $result;
    }

    /**
     * Disable presence.
     *
     * You won't be able to see the activity status of other accounts.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function disablePresence()
    {
        /** @var Response\GenericResponse $result */
        $result = $this->ig->request('accounts/set_presence_disabled/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('disabled', '1')
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $this->_savePresenceStatus(true);

        return $result;
    }

    /**
     * Tell Instagram to send you a message to verify your email address.
     *
     * @param string $email If the email is already set, it is not required.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SendConfirmEmailResponse
     */
    public function sendConfirmEmail(
        $email = null
    ) {
        $request = $this->ig->request('accounts/send_confirm_email/')
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('send_source', 'personal_information')
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('guid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('_uuid', $this->ig->uuid);
        // ->addPost('_csrftoken', $this->ig->client->getToken())

        if ($email !== null) {
            $request->addPost('email', $email);
        }

        return $request->getResponse(new Response\SendConfirmEmailResponse());
    }

    /**
     * Verify email code.
     *
     * @param string $email The email.
     * @param string $code  The received code.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function verifyEmailCode(
        $email,
        $code
    ) {
        $request = $this->ig->request('accounts/verify_email_code/')
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('send_source', 'personal_information')
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('guid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('code', $code)
            ->addPost('email', $email);

        return $request->getResponse(new Response\GenericResponse());
    }

    /**
     * Confirm verification email.
     *
     * @param string $confirmationLink Confirmation link.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\ConfirmEmailResponse
     */
    public function confirmEmail(
        $confirmationLink
    ) {
        $re = '/^https:\/\/(www\.)?instagram.com\/accounts\/confirm_email\/(\w+)\/(\w+)/m';
        preg_match_all($re, $confirmationLink, $matches, PREG_OFFSET_CAPTURE, 0);

        if (empty($matches)) {
            throw new \InstagramAPI\Exception\InstagramException('Not valid link provided.');
        }

        return $this->ig->request("accounts/confirm_email/{$matches[2][0][0]}/{$matches[3][0][0]}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->getResponse(new Response\ConfirmEmailResponse());
    }

    /**
     * Tell Instagram to send you an SMS code to verify your phone number.
     *
     * @param string $phoneNumber Phone number with country code. Format: +34123456789.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SendSMSCodeResponse
     */
    public function sendSMSCode(
        $phoneNumber
    ) {
        $cleanNumber = '+'.preg_replace('/[^0-9]/', '', $phoneNumber);

        return $this->ig->request('accounts/send_sms_code/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('phone_number', $cleanNumber)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\SendSMSCodeResponse());
    }

    /**
     * Submit the SMS code you received to verify your phone number.
     *
     * @param string $phoneNumber      Phone number with country code. Format: +34123456789.
     * @param string $verificationCode The code sent to your phone via `Account::sendSMSCode()`.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\VerifySMSCodeResponse
     *
     * @see Account::sendSMSCode()
     */
    public function verifySMSCode(
        $phoneNumber,
        $verificationCode
    ) {
        $cleanNumber = '+'.preg_replace('/[^0-9]/', '', $phoneNumber);

        return $this->ig->request('accounts/verify_sms_code/')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('phone_number', $cleanNumber)
            ->addPost('verification_code', $verificationCode)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\VerifySMSCodeResponse());
    }

    /**
     * Generate TOTP Code.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\TotpCodeResponse
     */
    public function getTOTPCode()
    {
        return $this->ig->request('accounts/generate_two_factor_totp_key/')
            ->setSignedPost(false)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->getResponse(new Response\TotpCodeResponse());
    }

    /**
     * Enable TOTP Two factor authentication.
     *
     * @param string $code OTP code (6-digit code).
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function enableTOTPAuthentication(
        $code
    ) {
        return $this->ig->request('accounts/enable_totp_two_factor/')
            ->addPost('verification_code', $code)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->device_id)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Set contact point prefill.
     *
     * @param string $usage    Either "prefill" or "auto_confirmation".
     * @param bool   $prelogin Pre-login state.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function setContactPointPrefill(
        $usage,
        $prelogin = true
    ) {
        $request = $this->ig->request('accounts/contact_point_prefill/')
            ->addPost('usage', $usage);

        if ($prelogin === true) {
            $request->setNeedsAuth(false)
                    ->addPost('phone_id', $this->ig->phone_id);
        } else {
            $request->addPost('_uid', $this->ig->account_id)
                    ->addPost('device_id', $this->ig->device_id)
                    ->addPost('_uuid', $this->ig->uuid);
        }

        return $request->getResponse(new Response\GenericResponse());
    }

    /**
     * Get name prefill.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getNamePrefill()
    {
        $request = $this->ig->request('accounts/get_name_prefill/')
            ->setNeedsAuth(false)
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('device_id', $this->ig->device_id)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     *  Get prefill candidates.
     *
     * DEPRECATED
     *
     * @param array $clientContactPoints phone (read from SIM) or/and email (read from google account manager).
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\PrefillCandidatesResponse
     */
    public function getPrefillCandidates(
        $clientContactPoints = null
    ) {
        $request = $this->ig->request('accounts/get_prefill_candidates/')
            ->setNeedsAuth(false)
            ->addPost('android_device_id', $this->ig->device_id)
            ->addPost('device_id', $this->ig->uuid)
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('usages', '["account_recovery_omnibox"]');

        if ($clientContactPoints !== null) {
            $contactPoints = [];
            if (isset($clientContactPoints['email'])) {
                $contactPoints[] = [
                    'type'      => 'email',
                    'value'     => $clientContactPoints['email'],
                    'source'    => 'android_account_manager',
                ];
            }
            if (isset($clientContactPoints['phone'])) {
                $contactPoints[] = [
                    'type'      => 'phone',
                    'value'     => $clientContactPoints['phone'],
                    'source'    => 'sim',
                ];
            }
            if (!empty($contactPoints)) {
                $request->addPost('client_contact_points', json_encode($contactPoints));
            }
        }

        return $request->getResponse(new Response\PrefillCandidatesResponse());
    }

    /**
     * Get account badge notifications for the "Switch account" menu.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\BadgeNotificationsResponse
     */
    public function getBadgeNotifications()
    {
        return $this->ig->request('notifications/badge/')
            ->setSignedPost(false)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_ids', $this->ig->account_id)
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('device_id', $this->ig->uuid)
            ->getResponse(new Response\BadgeNotificationsResponse());
    }

    /**
     * Get Facebook ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\FacebookIdResponse
     */
    public function getFacebookId()
    {
        return $this->ig->request('fb/get_connected_fbid/')
            ->setSignedPost(false)
            ->setIsSilentFail(true)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\FacebookIdResponse());
    }

    /**
     * Get linked accounts status.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\LinkageStatusResponse
     */
    public function getLinkageStatus()
    {
        return $this->ig->request('linked_accounts/get_linkage_status/')
            ->getResponse(new Response\LinkageStatusResponse());
    }

    /**
     * Get cross posting destination (Cross posting to Facebook).
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\CrossPostingDestinationStatusResponse
     */
    public function getCrossPostingDestinationStatus()
    {
        return $this->ig->request('ig_fb_xposting/account_linking/user_xposting_destination/')
            ->addParam('signed_body', Signatures::generateSignature('').'.{}')
            ->getResponse(new Response\CrossPostingDestinationStatusResponse());
    }

    /**
     * TODO.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function getProcessContactPointSignals()
    {
        return $this->ig->request('accounts/process_contact_point_signals/')
            ->addPost('google_tokens', '[]')
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('device_id', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Send Google token users.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function sendGoogleTokenUsers()
    {
        return $this->ig->request('accounts/google_token_users/')
            ->setNeedsAuth(false)
            ->addPost('google_tokens', '[]')
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Send recovery flow via email.
     *
     * @param string $query Username or email.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\SendRecoveryFlowResponse
     */
    public function sendRecoveryFlowEmail(
        $query
    ) {
        return $this->ig->request('accounts/send_recovery_flow_email/')
            ->addPost('guid', $this->ig->uuid)
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('adid', $this->ig->advertising_id)
            ->addPost('query', $query)
            ->addPost('device_id', $this->ig->device_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\SendRecoveryFlowResponse());
    }

    /**
     * Send recovery flow via phone.
     *
     * @param string $query             Username or email.
     * @param bool   $whatsAppInstalled Wether WhatsApp is installed or not.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\LookupPhoneResponse
     */
    public function lookupPhone(
        $query,
        $whatsAppInstalled = false
    ) {
        return $this->ig->request('users/lookup_phone/')
            ->addPost('supports_sms_code', 'true')
            ->addPost('use_whatsapp', $whatsAppInstalled)
            ->addPost('guid', $this->ig->uuid)
            ->addPost('phone_id', $this->ig->phone_id)
            ->addPost('adid', $this->ig->advertising_id)
            ->addPost('query', $query)
            ->addPost('device_id', $this->ig->device_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\LookupPhoneResponse());
    }

    /**
     * Get accounts multi login.
     *
     * Returns logged in data of accounts.
     *
     * @param string $macLoginNonce Mac login nonce.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\MultiAccountsResponse
     */
    public function getAccountsMultiLogin(
        $macLoginNonce
    ) {
        return $this->ig->request('multiple_accounts/multi_account_login/')
            ->setNeedsAuth(false)
            ->setSignedPost(false)
            ->addPost('mac_login_nonce', $macLoginNonce)
            ->addPost('logged_in_user_ids', $this->ig->account_id)
            ->addPost('device_id', $this->ig->device_id)
            ->addPost('guid', $this->ig->uuid)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\MultiAccountsResponse());
    }

    /**
     * Set if user can tag you in media posts.
     *
     * @param bool $set true for enabling and false for disabling.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function updateTagSettingsAction(
        $set
    ) {
        return $this->ig->request('bloks/apps/com.instagram.bullying.privacy.tags_options.update_tag_setting_action/')
            ->setSignedPost(false)
            ->addPost('tag_setting', $set ? 'on' : 'off')
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('nest_data_manifest', 'true')
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Set if user can mention you in media posts.
     *
     * @param bool $set true for enabling and false for disabling.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return Response\GenericResponse
     */
    public function updateMentionSettingsAction(
        $set
    ) {
        return $this->ig->request('bloks/apps/com.instagram.bullying.privacy.mentions_options.update_mention_settting_action/')
            ->setSignedPost(false)
            ->addPost('tag_setting', $set ? 'on' : 'off')
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('nest_data_manifest', 'true')
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get synced Facebook pages IDs.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return string[]
     */
    public function getSyncedFacebookPagesIds()
    {
        $response = $this->ig->request('bloks/apps/com.bloks.www.fxcal.settings.post.account/')
            ->setSignedPost(false)
            ->addPost('params', json_encode((object) [
                'server_params' => [
                    'should_show_done_button'   => 0,
                    'account_id'                => $this->ig->account_id,
                    'newly_linked'              => 0,
                    'entrypoint'                => 1,
                ],
            ]))
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
                'ttrc_join_id'  => Signatures::generateUUID(),
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $re = '/\[{"\\\\u3401":{"#":true,"\$":"(\w+?\s?\w+?\s?\w+?\s?\w+?\S?\w+?\s?\w+?\s?\w+?)","&":"(Button|Selected\sButton)","\\\\u0087":".\(bk\.action\.array\.Make,.\\\\"&\\\\",.\(bk\.action\.core\.Match,.\(bk\.action\.core\.Match,.\(bk\.action\.bloks\.GetVariable2,.\\\\"\d+\\\\"\),.\(bk\.action\.array\.Make,.\(bk\.action.core\.Pattern,.\\\\"(\d+)/m';
        preg_match_all($re, $response->asJson(), $matches, PREG_SET_ORDER, 0);

        $pages = [];
        foreach ($matches as $match) {
            $pages[$match[3]] = explode(' Facebook', $match[1])[0];
        }

        return $pages;
    }

    /**
     * Get active crossposting account (Business accounts. IG xpost to FB).
     * This only retrieves linked FB pages.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return array
     */
    public function getActiveCrosspostingAccount()
    {
        $response = $this->ig->internal->sendGraph(
            '25065272511213483404914864494',
            [
                'input' => [
                    'key'               => '1L1D',
                    'caller_context'    => [
                        'function_credential'   => 'function_credential',
                        'caller'                => 'ReelViewerFragment',
                    ],
                ],
            ],
            'IGOneLinkMiddlewarePageQuery',
            'xfb_one_link_monoschema',
            false,
            'pando'
        );

        $arr = $response->asArray();
        if (isset($arr['data'])) {
            $data = $arr['data'];
            foreach ($data as $k => $v) {
                if ($k === '1$xfb_one_link_monoschema(input:$input)') {
                    $info = $data[$k];
                    if (isset($info['page_info'])) {
                        $pageInfo = $info['page_info']['bpl_page'];

                        return [
                            $pageInfo['name']   => [
                                'id'        => $pageInfo['id'],
                                'entity'    => 'PAGE',
                            ],
                        ];
                    }
                }
            }
        } else {
            return [];
        }
    }

    /**
     * Get active crossposting account (IG xpost to FB).
     * This only retrieves linked FB user.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return array
     */
    public function getActiveCrosspostingAccountUser()
    {
        $response = $this->ig->internal->sendGraph(
            '11674382495679744485820947859',
            [
                'caller_name'   => 'fx_product_foundation_client_FXOnline_client_cache',
            ],
            'FxIgLinkageCacheQuery',
            'xe_client_cache_accounts',
            false,
            'pando'
        );

        $arr = $response->asArray();
        if (isset($arr['data'])) {
            $data = $arr['data'];
            foreach ($data as $k => $v) {
                if ($k === '1$xe_client_cache_accounts(caller_name:$caller_name)') {
                    $info = $data[$k];
                    foreach ($info as $user) {
                        if ($user['platform'] === 'FACEBOOK') {
                            return [
                                $user['username']   => [
                                    'id'        => $user['id'],
                                    'entity'    => 'USER',
                                ],
                            ];
                        }
                    }
                }
            }
        } else {
            return [];
        }
    }

    /**
     * Get account center linked accounts.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return array
     */
    public function getAccountCenterLinkedAccounts()
    {
        $response = $this->ig->request('bloks/apps/com.bloks.www.fxcal.settings.post/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'server_params'         => [
                    'requested_screen_component_type'   => 2,
                    'should_show_done_button'           => 0,
                    'INTERNAL_INFRA_THEME'              => 'harm_f',
                    'INTERNAL_INFRA_screen_id'          => $this->ig->bloksInfo['INTERNAL_INFRA_screen_id'] ?? '',
                    'entrypoint'                        => 'app_settings',
                ],
            ]))
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->ig->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $dataBlock = null;
        // $secondBlock = null;
        $accountIdentifiers = [];
        $results = [];
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'account_identifier')) {
                $parsed = $this->ig->bloks->parseBlok($mainBlok, 'bk.action.map.Make');
                $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'account_identifier'), 0, -2);

                foreach ($offsets as $offset) {
                    if (isset($parsed[$offset])) {
                        $parsed = $parsed[$offset];
                    } else {
                        break;
                    }
                }

                $map = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
                $this->ig->bloksInfo = array_merge($this->ig->bloksInfo, $map);

                if (!isset($this->ig->bloksInfo['account_identifier']) || empty($this->ig->bloksInfo['account_identifier'])) {
                    return [];
                }

                try {
                    $response = $this->ig->request('bloks/apps/com.bloks.www.fxcal.settings.post.account/')
                    ->setSignedPost(false)
                    ->addPost('params', json_encode([
                        'server_params'         => [
                            'requested_screen_component_type'   => 2,
                            'account_identifier'                => $this->ig->bloksInfo['account_identifier'],
                            'INTERNAL_INFRA_screen_id'          => $this->ig->bloksInfo['INTERNAL_INFRA_screen_id'],
                        ],
                    ]))
                    ->addPost('_uuid', $this->ig->uuid)
                    ->addPost('bk_client_context', json_encode([
                        'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                        'styles_id'     => 'instagram',
                    ]))
                    ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
                    ->getResponse(new Response\GenericResponse());
                } catch (\Exception $e) {
                    continue;
                }

                $re = '/radio_button_selected_id:\d+","mode":".","initial":"(\d+)"/m';
                preg_match_all($re, $response->asJson(), $active, PREG_SET_ORDER, 0);
                $default = null;
                if (!empty($active)) {
                    $default = $active[0][1];
                }

                $re = '/(\w+\s?\w+),\sFacebook.*f32.Eq,\s\\\\\"(\d+)\\\\/U';
                preg_match_all($re, $response->asJson(), $matches, PREG_SET_ORDER, 0);

                if (!empty($matches)) {
                    foreach ($matches as $match) {
                        $results[$match[1]] = ['id' => $match[2]];
                        if ($match[2] === $default) {
                            $results[$match[1]]['default'] = true;
                        } else {
                            $results[$match[1]]['default'] = false;
                        }
                        preg_match_all(sprintf('/%d\),\s\\\\"(\w+)/m', $match[2]), $response->asJson(), $submatches, PREG_SET_ORDER, 0);
                        if (!empty($submatches)) {
                            $results[$match[1]]['entity'] = $submatches[0][1] === 'EntPage' ? 'PAGE' : 'USER';
                        }
                    }
                } else {
                    $re = '/"([^"]*, Facebook[^"]*)"/m';
                    preg_match_all($re, $response->asJson(), $matches, PREG_SET_ORDER, 0);

                    $re = '/\\\\"story\\\\", \(bk\.action\.i64\.Const, (\d+)\), (\d),/m';
                    preg_match_all($re, $response->asJson(), $data, PREG_SET_ORDER, 0);

                    foreach ($matches as $key=>$value) {
                        $results[$value[1]] = [
                            'id'        => $data[$key][1],
                            'entity'    => $data[$key][2] === '1' ? 'USER' : 'PAGE',
                        ];
                    }
                }

                return $results;
            }
            /*
            if (str_contains($mainBlok, 'INTERNAL_INFRA_screen_id')) {
                $secondBlock = $mainBlok;
            }
            */
        }

        /*
        if ($secondBlock !== null) {
            $parsed = $this->ig->bloks->parseBlok($secondBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'INTERNAL_INFRA_screen_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->ig->bloksInfo = array_merge($map, $this->ig->bloksInfo);
        }
        */
        return $results;
    }

    /**
     * Set default sharing target.
     *
     * @param int $sourceAccountId Account ID.
     * @param int targetIdentityId  Page ID.
     * @param mixed $targetIdentityId
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function setDefaultSharingTarget(
        $sourceAccountId,
        $targetIdentityId
    ) {
        return $this->ig->request('bloks/apps/com.bloks.www.fxcal.xplat.settings.post.target.async/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'         => [
                    'family_device_id'  => $this->ig->phone_id,
                ],
                'server_params'         => [
                    'target_identity_type'              => 'EntPage',
                    'INTERNAL_INFRA_THEME'              => 'harm_f',
                    'source_account_id'                 => $sourceAccountId,  // Account ID
                    'target_identity_id'                => $targetIdentityId, // PAGE ID
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
     * Add email contact point.
     *
     * @param string $email Email.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function addEmailContactPoint(
        $email
    ) {
        $response = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.contact_point/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'server_params'         => [
                    'entrypoint'                        => 'app_settings',
                    'should_show_done_button'           => 0,
                    'INTERNAL_INFRA_screen_id'          => 'CONTACT_POINT_SETTINGS',
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->ig->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $dataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'INTERNAL_INFRA_screen_id')) {
                $dataBlock = $mainBlok;
            }
        }
        if ($dataBlock !== null) {
            $parsed = $this->ig->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'INTERNAL_INFRA_screen_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->ig->bloksInfo = array_merge($map, $this->ig->bloksInfo);
        }

        if (isset($this->ig->bloksInfo['normalized_contact_point'])) {
            $this->ig->settings->set('phone_number', $this->ig->bloksInfo['normalized_contact_point']);
        }

        $request = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.contact_point.select_type/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'server_params'         => [
                    'contact_point_event_type'          => 'add',
                    'contact_point_source'              => 'fx_settings',
                    'INTERNAL_INFRA_screen_id'          => $this->ig->bloksInfo['INTERNAL_INFRA_screen_id'] ?? '',
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->ig->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $dataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'INTERNAL_INFRA_screen_id')) {
                $dataBlock = $mainBlok;
            }
        }
        if ($dataBlock !== null) {
            $parsed = $this->ig->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'INTERNAL_INFRA_screen_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->ig->bloksInfo = array_merge($map, $this->ig->bloksInfo);
        }
        $request = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.contact_point.add/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'server_params'         => [
                    'contact_point_type'                => 'email',
                    'contact_point_source'              => 'fx_settings',
                    'INTERNAL_INFRA_screen_id'          => $this->ig->bloksInfo['INTERNAL_INFRA_screen_id'] ?? '',
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->ig->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $dataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'INTERNAL__latency_qpl_marker_id') && str_contains($mainBlok, 'INTERNAL__latency_qpl_instance_id')) {
                $dataBlock = $mainBlok;
            }
        }
        if ($dataBlock !== null) {
            $parsed = $this->ig->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'INTERNAL__latency_qpl_marker_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->ig->bloksInfo = array_merge($map, $this->ig->bloksInfo);
        }

        $authBlob = $this->getLinkingAuthBlob()->getJsonSerializedBlob();

        $request = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.contact_point.add.async/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'   => [
                    'country'                           => null,
                    'family_device_id'                  => $this->ig->phone_id,
                    'ig_account_encrypted_auth_proof'   => $authBlob,
                    'selected_accounts'                 => [$this->ig->settings->get('fbid_v2')],
                    'contact_point'                     => $email,
                ],
                'server_params'         => [
                    'contact_point_event_type'          => 'add',
                    'contact_point_type'                => 'email',
                    'contact_point_source'              => 'fx_settings',
                    'INTERNAL__latency_qpl_marker_id'   => $this->ig->bloksInfo['INTERNAL__latency_qpl_marker_id'] ?? '',
                    'INTERNAL__latency_qpl_instance_id' => $this->ig->bloksInfo['INTERNAL__latency_qpl_instance_id'] ?? '',
                    'INTERNAL_INFRA_THEME'              => 'harm_f',
                    'machine_id'                        => null,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->ig->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $dataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'INTERNAL_INFRA_screen_id')) {
                $dataBlock = $mainBlok;
            }
        }
        if ($dataBlock !== null) {
            $parsed = $this->ig->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'INTERNAL_INFRA_screen_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->ig->bloksInfo = array_merge($map, $this->ig->bloksInfo);
        }

        $request = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.contact_point.verify/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'server_params'         => [
                    'contact_point_event_type'          => 'add',
                    'contact_point_type'                => 'email',
                    'contact_point_source'              => 'fx_settings',
                    'normalized_contact_point'          => $email,
                    'selected_accounts'                 => $this->ig->settings->get('fbid_v2'),
                    'INTERNAL_INFRA_screen_id'          => $this->ig->bloksInfo['INTERNAL_INFRA_screen_id'] ?? '',
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->ig->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $dataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'INTERNAL__latency_qpl_marker_id') && str_contains($mainBlok, 'INTERNAL__latency_qpl_instance_id')) {
                $dataBlock = $mainBlok;
            }
        }
        if ($dataBlock !== null) {
            $parsed = $this->ig->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'INTERNAL__latency_qpl_marker_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->ig->bloksInfo = array_merge($map, $this->ig->bloksInfo);
        }

        return $authBlob;
    }

    /**
     * Verify email contact point.
     *
     * @param string $email    Email.
     * @param string $code     Code.
     * @param string $authBlob Auth blob.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function verifyEmailContactPoint(
        $email,
        $code,
        $authBlob
    ) {
        $request = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.contact_point.verify.async/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'   => [
                    'pin_code'                          => $code,
                    'family_device_id'                  => $this->ig->phone_id,
                    'selected_accounts'                 => [$this->ig->settings->get('fbid_v2')],
                    'contact_point'                     => $email,
                ],
                'server_params'         => [
                    'contact_point_event_type'          => 'add',
                    'contact_point_type'                => 'email',
                    'contact_point_source'              => 'fx_settings',
                    'ig_account_encrypted_auth_proof'   => $authBlob,
                    'INTERNAL__latency_qpl_marker_id'   => $this->ig->bloksInfo['INTERNAL__latency_qpl_marker_id'] ?? '',
                    'INTERNAL__latency_qpl_instance_id' => $this->ig->bloksInfo['INTERNAL__latency_qpl_instance_id'] ?? '',
                    'INTERNAL_INFRA_THEME'              => 'harm_f',
                    'machine_id'                        => null,
                    'normalized_contact_point'          => $email,
                    'selected_accounts'                 => $this->ig->settings->get('fbid_v2'),
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
     * Delete phone contact point.
     *
     * @param string     $phone       Phone number.
     * @param mixed|null $phoneNumber
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function deletePhoneContactPoint(
        $phoneNumber = null
    ) {
        if ($phoneNumber === null && !empty($this->ig->settings->get('phone_number'))) {
            $phoneNumber = $this->ig->settings->get('phone_number');
        } else {
            $response = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.contact_point/')
                ->setSignedPost(false)
                ->addPost('params', json_encode([
                    'server_params'         => [
                        'entrypoint'                        => 'app_settings',
                        'should_show_done_button'           => 0,
                        'INTERNAL_INFRA_screen_id'          => 'CONTACT_POINT_SETTINGS',
                    ],
                ]))
                ->addPost('bk_client_context', json_encode([
                    'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                    'styles_id'     => 'instagram',
                ]))
                ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
                ->getResponse(new Response\GenericResponse());

            $re = '/(\+\d+)/m';
            preg_match($re, $response->asJson(), $matches, PREG_OFFSET_CAPTURE, 0);

            if (!empty($matches)) {
                $this->ig->settings->set('phone_number', $matches[0][0]);
                $phoneNumber = $matches[0][0];
            }
        }
        $authBlob = $this->getLinkingAuthBlob()->getJsonSerializedBlob();
        $request = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.contact_point.delete.async/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'   => [
                    'family_device_id'                  => $this->ig->phone_id,
                    'ig_account_encrypted_auth_proof'   => $authBlob,
                ],
                'server_params'         => [
                    'contact_point_type'                => 'phone_number',
                    'contact_point_source'              => 'fx_settings',
                    'ig_account_encrypted_auth_proof'   => $authBlob,
                    'INTERNAL__latency_qpl_marker_id'   => $this->ig->bloksInfo['INTERNAL__latency_qpl_marker_id'] ?? '',
                    'INTERNAL__latency_qpl_instance_id' => $this->ig->bloksInfo['INTERNAL__latency_qpl_instance_id'] ?? '',
                    'INTERNAL_INFRA_THEME'              => 'harm_f',
                    'machine_id'                        => null,
                    'normalized_contact_point'          => $phoneNumber,
                    'selected_accounts'                 => $this->ig->settings->get('fbid_v2'),
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
     * Get linking auth blob.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return LinkingAuthBlobResponse
     */
    public function getLinkingAuthBlob()
    {
        return $this->ig->request('fxcal/get_native_linking_auth_blob/')
            ->setSignedPost(false)
            ->addPost('_uuid', $this->ig->uuid)
            ->getResponse(new Response\LinkingAuthBlobResponse());
    }

    /**
     * Get security emails.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function getSecurityEmails()
    {
        return $this->ig->request('bloks/apps/com.instagram.account_security.screens.email_sent_list/')
            ->setSignedPost(false)
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get help options.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function getHelpOptions()
    {
        return $this->ig->request('bloks/apps/com.instagram.portable.settings.help/')
            ->setSignedPost(false)
            ->addPost('bk_client_context', json_encode((object) [
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Create bug ID.
     *
     * @param string $description
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function createBugId(
        $description
    ) {
        return $this->ig->internal->sendGraph(
            '293839176013550553288378882913',
            [
                'input' => [
                    'attachment_file_names' => json_encode([
                        'screenshot_0.png'                      => 1,
                        'mobileconfigs.txt'                     => 1,
                        'appearance_theme_state.json'           => 1,
                        'fan_club_data.json'                    => 1,
                        'reels_viewer_last_seen_media.json'     => 1,
                        'feed_requests.txt'                     => 1,
                        'main_feed_last_seen_medias.json'       => 1,
                        'realtime_client.json'                  => 1,
                        'direct_mutation_manager.txt'           => 1,
                        'direct_interop_gating_status.json'     => 1,
                        'direct_notification_traces.json'       => 1,
                        'logcat.txt'                            => 1,
                        'stacktrace.txt'                        => 1,
                        'fb_liger_reporting.txt'                => 1,
                        'RecentPendingMedia.json'               => 1,
                        'IngestionLogcat.txt'                   => 1,
                        'RecentUploadAttemptErrors.json'        => 1,
                    ]),
                    'build_num'                     => $this->ig->getVersionCode(),
                    'category_id'                   => '493186350727442',
                    'claim'                         => $this->ig->client->wwwClaim,
                    'description'                   => $description,
                    'endpoint'                      => 'com.instagram.portable.settings.help:com.instagram.portable.settings.help',
                    'files'                         => [],
                    'has_complete_logs_consent'     => true,
                    'misc_info'                     => json_encode([
                        'IG_UserId'                             => $this->ig->account_id,
                        'IG_Username'                           => $this->ig->username,
                        'fbns_token'                            => $this->ig->settings->get('fbns_token'),
                        'transport_type_account'                => 'ig_advanced_crypto_transport',
                        'user_interop_status'                   => 'DIRECT_INTEROP_GATING_STATUS_ELIGIBLE_AND_UPGRADED',
                        'direct_last_viewed_thread_is_interop'  => 'false',
                        'device_id'                             => $this->ig->device_id,
                        'Git_Hash'                              => 'MASTER',
                        'Branch'                                => 'master',
                        'OS_Version'                            => $this->ig->device->getAndroidVersion(),
                        'Manufacturer'                          => $this->ig->device->getManufacturer(),
                        'Model'                                 => $this->ig->device->getModel(),
                        'Locale'                                => 'English (United States)',
                        'Build_Type'                            => 'RELEASE',
                        'source'                                => 'bloks',
                        'client_server_join_key'                => 'dKNAQNZLZNBppesuPeKV',
                        'invalid_attachment_file_names'         => [
                            'media_publisher.txt'       => 1,
                            'ad_delivery_logging.json'  => 1,
                            'mobile_network_stack.txt'  => 1,
                        ],
                        'last_played_video_ids'                 => [],
                        'ar_engine_supported'                   => 'true',
                        'available_disk_space_bytes'            => strval(mt_rand(6089027072, 17489027072)),
                        'black_box_trace_id'                    => 'BdA1RkollNA',
                        'promotion_id'                          => '1490312904699955',
                        'deviceVolume'                          => number_format(1 / mt_rand(1, 10), 1),
                        'device_silent_mode'                    => 'NORMAL',
                        'm_target_sdk_version'                  => 34,
                    ]),
                    'nav_chain'                     => $this->ig->getNavChain(),
                    'source'                        => 'bloks',
                ],
            ],
            'IGBugReportSubmitMutation',
            null,
            false,
            'minimal',
            'api/v1/wwwgraphql/ig/query/'
        );
    }

    /**
     * Select Two Factor Method.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function selectTwoFactorMethod()
    {
        return $this->ig->request('bloks/apps/com.bloks.www.fx.settings.security.two_factor.select_method/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'   => [
                    'ig_auth_proof_json'                => $this->ig->settings->get('authorization_header'),
                    'machine_id'                        => $this->ig->settings->get('mid'),
                ],
                'server_params'         => [
                    'should_show_done_button'           => 0,
                    'account_type'                      => 1,
                    'account_id'                        => $this->ig->settings->get('fbid_v2'),
                    'INTERNAL_INFRA_screen_id'          => '0',
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Generate two factor key.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function twoFactorTotpGenerateKey()
    {
        $response = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.security.two_factor.totp.generate_key/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'   => [
                    'device_id'                         => $this->ig->device_id,
                    'machine_id'                        => $this->ig->settings->get('mid'),
                    'family_device_id'                  => $this->ig->phone_id,
                ],
                'server_params'         => [
                    'ig_auth_proof_json'                => $this->ig->settings->get('authorization_header'),
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_marker_id'   => 0,
                    'INTERNAL__latency_qpl_instance_id' => 0,
                    'account_id'                        => $this->ig->settings->get('fbid_v2'),
                    'account_type'                      => 1,
                    'INTERNAL_INFRA_THEME'              => 'harm_f',
                    'requested_screen_component_type'   => null,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->ig->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $dataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'key_text')) {
                $dataBlock = $mainBlok;
            }
        }
        if ($dataBlock !== null) {
            $parsed = $this->ig->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'key_text'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->ig->bloksInfo = array_merge($map, $this->ig->bloksInfo);
        }

        $response = $this->ig->request('bloks/apps/com.bloks.www.fx.settings.security.two_factor.totp.key/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'   => [
                    'machine_id'                        => $this->ig->settings->get('mid'),
                ],
                'server_params'         => [
                    'account_id'                        => $this->ig->settings->get('fbid_v2'),
                    'ig_auth_proof_json'                => $this->ig->settings->get('authorization_header'),
                    'key_text'                          => $this->ig->bloksInfo['key_text'],
                    'INTERNAL_INFRA_screen_id'          => $this->ig->bloksInfo['INTERNAL_INFRA_screen_id'] ?? '',
                    'key_id'                            => $this->ig->bloksInfo['key_id'],
                    'qr_code_uri'                       => stripslashes(stripslashes($this->ig->bloksInfo['qr_code_uri'])),
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        $responseArr = $response->asArray();
        $mainBloks = $this->ig->bloks->parseResponse($responseArr, '(bk.action.core.TakeLast');
        $dataBlock = null;
        foreach ($mainBloks as $mainBlok) {
            if (str_contains($mainBlok, 'totp_key_id')) {
                $dataBlock = $mainBlok;
            }
        }
        if ($dataBlock !== null) {
            $parsed = $this->ig->bloks->parseBlok($dataBlock, 'bk.action.map.Make');
            $offsets = array_slice($this->ig->bloks->findOffsets($parsed, 'totp_key_id'), 0, -2);

            foreach ($offsets as $offset) {
                if (isset($parsed[$offset])) {
                    $parsed = $parsed[$offset];
                } else {
                    break;
                }
            }

            $map = $this->ig->bloks->map_arrays($parsed[0], $parsed[1]);
            $this->ig->bloksInfo = array_merge($map, $this->ig->bloksInfo);
        }

        return $response;
    }

    /**
     * Generate two factor key.
     *
     * @param mixed $totpCode
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return GenericResponse
     */
    public function twoFactorTotpEnable(
        $totpCode
    ) {
        $this->ig->request('bloks/apps/com.bloks.www.fx.settings.security.two_factor.totp.code/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'   => [
                    'machine_id'                        => $this->ig->settings->get('mid'),
                ],
                'server_params'         => [
                    'ig_auth_proof_json'                 => $this->ig->settings->get('authorization_header'),
                    'account_id'                         => $this->ig->settings->get('fbid_v2'),
                    'totp_key_id'                        => $this->ig->bloksInfo['totp_key_id'],
                    'INTERNAL_INFRA_screen_id'           => $this->ig->bloksInfo['INTERNAL_INFRA_screen_id'] ?? '',
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());

        return $this->ig->request('bloks/apps/com.bloks.www.fx.settings.security.two_factor.totp.enable/')
            ->setSignedPost(false)
            ->addPost('params', json_encode([
                'client_input_params'   => [
                    'device_id'                         => $this->ig->device_id,
                    'machine_id'                        => $this->ig->settings->get('mid'),
                    'family_device_id'                  => $this->ig->phone_id,
                    'verification_code'                 => $totpCode,
                ],
                'server_params'         => [
                    'ig_auth_proof_json'                => $this->ig->settings->get('authorization_header'),
                    'machine_id'                        => null,
                    'INTERNAL__latency_qpl_marker_id'   => 0,
                    'INTERNAL__latency_qpl_instance_id' => 0,
                    'account_id'                        => $this->ig->settings->get('fbid_v2'),
                    'account_type'                      => 1,
                ],
            ]))
            ->addPost('bk_client_context', json_encode([
                'bloks_version' => Constants::BLOCK_VERSIONING_ID,
                'styles_id'     => 'instagram',
            ]))
            ->addPost('bloks_versioning_id', Constants::BLOCK_VERSIONING_ID)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }
}
