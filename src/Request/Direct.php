<?php

namespace InstagramAPI\Request;

use InstagramAPI\Constants;
use InstagramAPI\Exception\InstagramException;
use InstagramAPI\Exception\ThrottledException;
use InstagramAPI\Exception\UploadFailedException;
use InstagramAPI\Request\Metadata\Internal as InternalMetadata;
use InstagramAPI\Response;
use InstagramAPI\Signatures;
use InstagramAPI\Utils;

/**
 * Instagram Direct messaging functions.
 *
 * Be aware that many of the functions can take either a list of users or a
 * thread ID as their "recipient". If a thread already exists with those
 * user(s), you MUST use the "thread" recipient method (otherwise Instagram
 * rejects your bad API call). If no thread exists yet, you MUST use the
 * "users" recipient method a SINGLE time to create the thread first!
 */
class Direct extends RequestCollection
{
    /**
     * Get direct inbox messages for your account.
     *
     * @param string|null $cursorId           Next "cursor ID", used for pagination.
     * @param string|null $seqId              Sequence ID.
     * @param int         $limit              Number of threads. From 0 to 20.
     * @param int|null    $threadMessageLimit (optional) Number of messages per thread
     * @param bool        $prefetch           (optional) Indicates if the request is called from prefetch.
     * @param string      $filter             Filter: all, unread, relevant, flagged.
     * @param string|null $fetchReason        Values: manual_refresh or page_scroll.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectInboxResponse
     */
    public function getInbox(
        $cursorId = null,
        $seqId = null,
        $limit = 15,
        $prefetch = false,
        $filter = 'all',
        $fetchReason = null
    ) {
        if ($limit < 0 || $limit > 20) {
            throw new \InvalidArgumentException('Invalid value provided to limit.');
        }

        $request = $this->ig->request('direct_v2/inbox/')
            ->addParam('persistentBadging', 'true')
            ->addParam('visual_message_return_type', 'unseen')
            ->addParam('eb_device_id', '0') // 0x2081091D005B1C12
            ->addParam('igd_request_log_tracking_id', Signatures::generateUUID());

        /*
        $limit = $this->ig->getExperimentParam('59489', 7, 0);
        if ($limit <= 0) {
            $limit = $this->ig->getExperimentParam('56394', 0, -1);
            $request->addParam('limit', $limit == -1 ? 20 : $limit);
        } else {
            $request->addParam('limit', $limit);
        }
        */
        $request->addParam('limit', $this->ig->getExperimentParam('26104', 0, 15));

        if ($cursorId !== null) {
            $request->addParam('cursor', $cursorId);
            $request->addParam('direction', 'older');
        }
        if ($prefetch) {
            $request->addHeader('X-IG-Prefetch-Request', 'foreground');
        } else {
            $request->addParam('is_prefetching', 'false');
        }
        if ($filter !== 'all') {
            $request->addParam('selected_filter', $filter);
        }
        if ($filter !== null) {
            $request->addParam('fetch_reason', $fetchReason);
        }
        // if ($this->ig->isExperimentEnabled('45863', 0, false, true)) {
        $request->addParam('no_pending_badge', 'true');
        if ($seqId !== null) {
            $request->addParam('seq_id', $seqId);
        }
        if ($fetchReason === 'initial_snapshot') {
            $batchSize = $this->ig->getExperimentParam('59489', 1, null);
            if ($batchSize !== null) {
                $request->addParam('batch_size', $batchSize);
            }
            $request->addParam('thread_message_limit', $this->ig->getExperimentParam('26104', 1, 5));
        }

        return $request->getResponse(new Response\DirectInboxResponse());
    }

    /**
     * Get if has interop upgraded.
     *
     * @throws InstagramException
     *
     * @return Response\HasInteropUpgradedResponse
     */
    public function getHasInteropUpgraded()
    {
        return $this->ig->request('direct_v2/has_interop_upgraded/')
            ->getResponse(new Response\HasInteropUpgradedResponse());
    }

    /**
     * Get pending inbox data.
     *
     * @param string|null $cursorId Next "cursor ID", used for pagination.
     *
     * @throws InstagramException
     *
     * @return Response\DirectPendingInboxResponse
     */
    public function getPendingInbox(
        $cursorId = null
    ) {
        $request = $this->ig->request('direct_v2/pending_inbox/')
            ->addParam('visual_message_return_type', 'unseen')
            ->addParam('persistentBadging', 'true');
        if ($cursorId !== null) {
            $request->addParam('cursor', $cursorId);
        }

        return $request->getResponse(new Response\DirectPendingInboxResponse());
    }

    /**
     * Get spam inbox data.
     *
     * @param string|null $cursorId Next "cursor ID", used for pagination.
     *
     * @throws InstagramException
     *
     * @return Response\DirectPendingInboxResponse
     */
    public function getSpamInbox(
        $cursorId = null
    ) {
        $request = $this->ig->request('direct_v2/spam_inbox/')
            ->addParam('visual_message_return_type', 'unseen')
            ->addParam('persistentBadging', 'true');
        if ($cursorId !== null) {
            $request->addParam('cursor', $cursorId);
        }

        return $request->getResponse(new Response\DirectPendingInboxResponse());
    }

    /**
     * Approve pending threads by given identifiers.
     *
     * @param array $threads One or more thread identifiers.
     * @param int   $folder  Folder used for Business accounts. ONLY for Business accounts.
     *                       Primary folder: 0.
     *                       General folder: 1.
     * @param array $options Options.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function approvePendingThreads(
        array $threads,
        $folder = null,
        array $options = []
    ) {
        if (!count($threads)) {
            throw new \InvalidArgumentException('Please provide at least one thread to approve.');
        }

        // Validate threads.
        foreach ($threads as &$thread) {
            if (!is_scalar($thread)) {
                throw new \InvalidArgumentException('Thread identifier must be scalar.');
            } elseif (!ctype_digit($thread) && (!is_int($thread) || $thread < 0)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid thread identifier.', $thread));
            }
            $thread = (string) $thread;
        }
        unset($thread);
        // Choose appropriate endpoint.
        if (count($threads) > 1) {
            $request = $this->ig->request('direct_v2/threads/approve_multiple/')
                ->addPost('thread_ids', json_encode($threads));
        } else {
            /** @var string $thread */
            $thread = reset($threads);
            $request = $this->ig->request("direct_v2/threads/{$thread}/approve/");
        }

        if ($folder !== null) {
            if ($folder !== 0 && $folder !== 1) {
                throw new \InvalidArgumentException(sprintf('%d is not a valid folder value.', $folder));
            }
            $request->addPost('folder', $folder)
                    ->addPost('origin_folder', 0)
                    ->addPost('filter', 'DEFAULT');
        }

        if (isset($options['client_context'])) {
            $request->addPost('client_context', $options['client_context']);
        }

        return $request
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->setSignedPost(false)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Decline pending threads by given identifiers.
     *
     * @param array $threads One or more thread identifiers.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function declinePendingThreads(
        array $threads
    ) {
        if (!count($threads)) {
            throw new \InvalidArgumentException('Please provide at least one thread to decline.');
        }
        // Validate threads.
        foreach ($threads as &$thread) {
            if (!is_scalar($thread)) {
                throw new \InvalidArgumentException('Thread identifier must be scalar.');
            } elseif (!ctype_digit($thread) && (!is_int($thread) || $thread < 0)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid thread identifier.', $thread));
            }
            $thread = (string) $thread;
        }
        unset($thread);
        // Choose appropriate endpoint.
        if (count($threads) > 1) {
            $request = $this->ig->request('direct_v2/threads/decline_multiple/')
                ->addPost('thread_ids', json_encode($threads));
        } else {
            /** @var string $thread */
            $thread = reset($threads);
            $request = $this->ig->request("direct_v2/threads/{$thread}/decline/");
        }

        return $request
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->setSignedPost(false)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Decline all pending threads.
     *
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function declineAllPendingThreads()
    {
        return $this->ig->request('direct_v2/threads/decline_all/')
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->setSignedPost(false)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get a list of activity statuses for users who you follow or message.
     *
     * @throws InstagramException
     *
     * @return Response\PresencesResponse
     */
    public function getPresences()
    {
        $request = $this->ig->request('direct_v2/get_presence/');

        if ($this->ig->isExperimentEnabled('41171', 1, false)) {
            $request->addParam('suggested_followers_limit', $this->ig->getExperimentParam('41171', 4, 0));
        }

        return $request->getResponse(new Response\PresencesResponse());
    }

    /**
     * Get a list of activity statuses for users who you follow or message that are active now.
     *
     * @throws InstagramException
     *
     * @return Response\PresencesResponse
     */
    public function getPresencesActiveNow()
    {
        return $this->ig->request('direct_v2/get_presence_active_now/')
            ->addParam('recent_thread_limit', 0)
            ->addParam('suggested_followers_limit', $this->ig->getExperimentParam('41171', 4, 0))
            ->getResponse(new Response\PresencesResponse());
    }

    /**
     * Get ranked list of recipients.
     *
     * WARNING: This is a special, very heavily throttled API endpoint.
     * Instagram REQUIRES that you wait several minutes between calls to it.
     *
     * @param string      $mode        Either "reshare" or "raven".
     * @param bool        $showThreads Whether to include existing threads into response.
     * @param string|null $query       (optional) The user to search for.
     *
     * @throws InstagramException
     *
     * @return Response\DirectRankedRecipientsResponse|null Will be NULL if throttled by Instagram.
     */
    public function getRankedRecipients(
        $mode,
        $showThreads,
        $query = null
    ) {
        try {
            $request = $this->ig->request('direct_v2/ranked_recipients/')
                ->addParam('mode', $mode)
                ->addParam('show_threads', $showThreads ? 'true' : 'false');
            if ($query !== null) {
                $request->addParam('query', $query);
            }

            return $request
                ->getResponse(new Response\DirectRankedRecipientsResponse());
        } catch (ThrottledException $e) {
            // Throttling is so common that we'll simply return NULL in that case.
            return null;
        }
    }

    /**
     * Get a thread by the recipients list.
     *
     * @param string[]|int[] $users Array of numerical UserPK IDs.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectThreadResponse
     */
    public function getThreadByParticipants(
        array $users
    ) {
        if (!count($users)) {
            throw new \InvalidArgumentException('Please provide at least one participant.');
        }
        foreach ($users as $user) {
            if (!is_scalar($user)) {
                throw new \InvalidArgumentException('User identifier must be scalar.');
            }
            if (!ctype_digit($user) && (!is_int($user) || $user < 0)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid user identifier.', $user));
            }
        }
        $request = $this->ig->request('direct_v2/threads/get_by_participants/')
            ->addParam('recipient_users', '['.implode(',', $users).']')
            ->addParam('seq_id', $this->ig->navigationSequence);

        $limit = $this->ig->getExperimentParam('59489', 7, 0);
        if ($limit <= 0) {
            $limit = $this->ig->getExperimentParam('56394', 0, -1);
            $request->addParam('limit', $limit == -1 ? 20 : $limit);
        } else {
            $request->addParam('limit', $limit);
        }

        return $request->getResponse(new Response\DirectThreadResponse());
    }

    /**
     * Get direct message thread.
     *
     * @param string      $threadId Thread ID.
     * @param string|null $cursorId Next "cursor ID", used for pagination.
     * @param string|null $seqId    Sequence ID.
     *
     * @throws InstagramException
     *
     * @return Response\DirectThreadResponse
     */
    public function getThread(
        $threadId,
        $cursorId = null,
        $seqId = null
    ) {
        $request = $this->ig->request("direct_v2/threads/$threadId/")
            ->addParam('visual_message_return_type', 'unseen');

        $limit = $this->ig->getExperimentParam('59489', 7, 0);
        if ($limit <= 0) {
            $limit = $this->ig->getExperimentParam('56394', 0, -1);
            $request->addParam('limit', $limit == -1 ? 20 : $limit);
        } else {
            $request->addParam('limit', $limit);
        }

        if ($cursorId !== null) {
            $request->addParam('cursor', $cursorId)
                    ->addParam('direction', 'older');
        }
        if ($seqId !== null) {
            $request->addParam('seq_id', $seqId);
        }

        return $request->getResponse(new Response\DirectThreadResponse());
    }

    /**
     * Get direct visual thread.
     *
     * `NOTE:` This "visual" endpoint is only used for Direct stories.
     *
     * @param string      $threadId Thread ID.
     * @param string|null $cursorId Next "cursor ID", used for pagination.
     *
     * @throws InstagramException
     *
     * @return Response\DirectVisualThreadResponse
     *
     * @deprecated Visual inbox has been superseded by the unified inbox.
     * @see Direct::getThread()
     */
    public function getVisualThread(
        $threadId,
        $cursorId = null
    ) {
        $request = $this->ig->request("direct_v2/visual_threads/{$threadId}/");
        if ($cursorId !== null) {
            $request->addParam('cursor', $cursorId);
        }

        return $request->getResponse(new Response\DirectVisualThreadResponse());
    }

    /**
     * Update thread title.
     *
     * @param string $threadId Thread ID.
     * @param string $title    New title.
     *
     * @throws InstagramException
     *
     * @return Response\DirectThreadResponse
     */
    public function updateThreadTitle(
        $threadId,
        $title
    ) {
        return $this->ig->request("direct_v2/threads/{$threadId}/update_title/")
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('title', trim($title))
            ->setSignedPost(false)
            ->getResponse(new Response\DirectThreadResponse());
    }

    /**
     * Move thread to folder.
     *
     * Only available for business accounts.
     * 0 - Primary folder.
     * 1 - General folder.
     *
     * @param string $threadId Thread ID.
     * @param int    $folder   Folder ID.
     *
     * @throws InstagramException
     *
     * @return Response\DirectThreadResponse
     */
    public function moveThread(
        $threadId,
        $folder
    ) {
        return $this->ig->request("direct_v2/threads/{$threadId}/move/")
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('folder', $folder)
            ->setSignedPost(false)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Mute direct thread.
     *
     * @param string $threadId Thread ID.
     *
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function muteThread(
        $threadId
    ) {
        return $this->ig->request("direct_v2/threads/{$threadId}/mute/")
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->setSignedPost(false)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Unmute direct thread.
     *
     * @param string $threadId Thread ID.
     *
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function unmuteThread(
        $threadId
    ) {
        return $this->ig->request("direct_v2/threads/{$threadId}/unmute/")
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->setSignedPost(false)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Fetch and subscribe user presence.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws InstagramException
     *
     * @return Response\DirectPresenceResponse
     */
    public function fetchAndSubscribePresence(
        $userId
    ) {
        return $this->ig->request('direct_v2/fetch_and_subscribe_presence/')
            ->setSignedPost(false)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('subscriptions_off', 'false')
            ->addPost('request_data', json_encode([$userId]))
            ->getResponse(new Response\DirectPresenceResponse());
    }

    /**
     * Create a private story sharing group.
     *
     * NOTE: In the official app, when you create a story, you can choose to
     * send it privately. And from there you can create a new group thread. So
     * this group creation endpoint is only meant to be used for "direct
     * stories" anf for emulation web direct, which first creates a group thread
     * and then all communication is done through MQTT (Realtime Client).
     *
     * @param string[]|int[] $userIds     Array of numerical UserPK IDs.
     * @param string|null    $threadTitle Name of the group thread.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectCreateGroupThreadResponse
     */
    public function createGroupThread(
        array $userIds,
        $threadTitle = null
    ) {
        if (count($userIds) < 1) {
            throw new \InvalidArgumentException('You must invite at least 1 user to create a group.');
        }
        foreach ($userIds as &$user) {
            if (!is_scalar($user)) {
                throw new \InvalidArgumentException('User identifier must be scalar.');
            } elseif (!ctype_digit($user) && (!is_int($user) || $user < 0)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid user identifier.', $user));
            }
            $user = (string) $user;
        }

        $request = $this->ig->request('direct_v2/create_group_thread/')
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('recipient_users', json_encode($userIds))
            ->addPost('_uid', $this->ig->account_id);

        if (count($userIds) > 1) {
            if ($threadTitle === null) {
                throw new \InvalidArgumentException('You must set a thread title for the group.');
            }
            $request->addPost('thread_title', $threadTitle);
        }

        return $request->getResponse(new Response\DirectCreateGroupThreadResponse());
    }

    /**
     * Add users to thread.
     *
     * @param string         $threadId Thread ID.
     * @param string[]|int[] $users    Array of numerical UserPK IDs.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectThreadResponse
     */
    public function addUsersToThread(
        $threadId,
        array $users
    ) {
        if (!count($users)) {
            throw new \InvalidArgumentException('Please provide at least one user.');
        }
        foreach ($users as &$user) {
            if (!is_scalar($user)) {
                throw new \InvalidArgumentException('User identifier must be scalar.');
            } elseif (!ctype_digit($user) && (!is_int($user) || $user < 0)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid user identifier.', $user));
            }
            $user = (string) $user;
        }

        return $this->ig->request("direct_v2/threads/{$threadId}/add_user/")
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('user_ids', json_encode($users))
            ->addPost('_uuid', $this->ig->uuid)
            ->setSignedPost(false)
            ->getResponse(new Response\DirectThreadResponse());
    }

    /**
     * Leave direct thread.
     *
     * @param string $threadId Thread ID.
     *
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function leaveThread(
        $threadId
    ) {
        return $this->ig->request("direct_v2/threads/{$threadId}/leave/")
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->setSignedPost(false)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Hide direct thread.
     *
     * @param string $threadId Thread ID.
     *
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function hideThread(
        $threadId
    ) {
        return $this->ig->request("direct_v2/threads/{$threadId}/hide/")
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->setSignedPost(false)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Send a direct text message to a user's inbox.
     *
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param string $text       Text message.
     * @param array  $options    An associative array of optional parameters, including:
     *                           "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     *                           "exclude_text" is used for excluding text in extractURLs function.
     *                           "shh_mode" still being researched. Default value 0.
     *                           "send_attribution" the dialog context from where you initiated sending the message. Default value 'inbox'.
     *                           Other values: 'inbox_search', 'message_button', 'direct_thread' and 'more_menu'.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendText(
        array $recipients,
        $text,
        array $options = []
    ) {
        if (!strlen($text)) {
            throw new \InvalidArgumentException('Text can not be empty.');
        }

        if (!isset($options['exclude_text'])) {
            $options['exclude_text'] = [];
        }
        $urls = Utils::extractURLs($text, $options['exclude_text']);
        if (count($urls)) {
            /** @var Response\DirectSendItemResponse $result */
            $result = $this->_sendDirectItem('links', $recipients, array_merge($options, [
                'link_urls' => json_encode(array_map(function (array $url) {
                    return $url['fullUrl'];
                }, $urls)),
                'link_text' => $text,
            ]));
        } else {
            /** @var Response\DirectSendItemResponse $result */
            $result = $this->_sendDirectItem('message', $recipients, array_merge($options, [
                'text' => $text,
            ]));
        }

        return $result;
    }

    /**
     * Send reaction from a story media.
     *
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param string $reaction   The reaction.
     * @param array  $options    An associative array of optional parameters, including:
     *                           "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     * @param string $mediaId
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendStoryReaction(
        array $recipients,
        $reaction,
        $mediaId,
        $options = []
    ) {
        // TODO: Add emoji checker on $reaction.

        if ($mediaId === null) {
            throw new \InvalidArgumentException('Media ID can not be null.');
        }

        /** @var Response\DirectSendItemResponse $result */
        $result = $this->_sendDirectItem('reel_reaction', $recipients, array_merge($options, [
            'reaction' => $reaction,
            'media_id' => $mediaId,
        ]));

        return $result;
    }

    /**
     * Share an existing media post via direct message to a user's inbox.
     *
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param string $mediaId    The media ID in Instagram's internal format (ie "3482384834_43294").
     * @param array  $options    An associative array of additional parameters, including:
     *                           "media_type" (required) - either "photo" or "video";
     *                           "client_context" and "mutation_token" (optional) - predefined UUID used to prevent double-posting;
     *                           "text" (optional) - text message.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemsResponse
     *
     * @see https://help.instagram.com/1209246439090858 For more information.
     */
    public function sendPost(
        array $recipients,
        $mediaId,
        array $options = []
    ) {
        if (!preg_match('#^\d+_\d+$#D', $mediaId)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid media ID.', $mediaId));
        }
        if (!isset($options['media_type'])) {
            throw new \InvalidArgumentException('Please provide media_type in options.');
        }
        if ($options['media_type'] !== 'photo' && $options['media_type'] !== 'video') {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid media_type.', $options['media_type']));
        }

        return $this->_sendDirectItems('media_share', $recipients, array_merge($options, [
            'media_id' => $mediaId,
        ]));
    }

    /**
     * Send a photo (upload) via direct message to a user's inbox.
     *
     * @param array  $recipients    An array with "users" or "thread" keys.
     *                              To start a new thread, provide "users" as an array
     *                              of numerical UserPK IDs. To use an existing thread
     *                              instead, provide "thread" with the thread ID.
     * @param string $photoFilename The photo filename.
     * @param array  $options       An associative array of optional parameters, including:
     *                              "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendPhoto(
        array $recipients,
        $photoFilename,
        array $options = []
    ) {
        // Direct videos use different upload IDs.
        $internalMetadata = new InternalMetadata(Utils::generateUploadId(true));
        // Attempt to upload the video data.
        $internalMetadata = $this->ig->internal->uploadSinglePhoto(Constants::FEED_DIRECT, $photoFilename, $internalMetadata);

        // We must use the same client_context and mutation_token for all attempts to prevent double-posting.
        if (!isset($options['client_context']) || !isset($options['mutation_token'])) {
            $clientContext = Utils::generateClientContext();
            $options['client_context'] = $options['client_context'] ?? $clientContext;
            $options['mutation_token'] = $options['mutation_token'] ?? $clientContext;
        }

        // Send the uploaded photo to recipients.
        try {
            /** @var Response\DirectSendItemResponse $result */
            $result = $this->ig->internal->configureWithRetries(
                function () use ($internalMetadata, $recipients, $options) {
                    // Attempt to configure photo parameters (which sends it to the thread).
                    return $this->_sendDirectItem('photo', $recipients, array_merge($options, [
                        'upload_id'    => $internalMetadata->getUploadId(),
                    ]));
                }
            );
        } catch (InstagramException $e) {
            // Pass Instagram's error as is.
            throw $e;
        } catch (\Exception $e) {
            // Wrap runtime errors.
            throw new UploadFailedException(
                sprintf(
                    'Upload of "%s" failed: %s',
                    $internalMetadata->getPhotoDetails()->getBasename(),
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        return $result;
    }

    /**
     * Send a permanent photo (upload) via direct message to a user's inbox.
     *
     * @param array  $recipients       An array with "users" or "thread" keys.
     *                                 To start a new thread, provide "users" as an array
     *                                 of numerical UserPK IDs. To use an existing thread
     *                                 instead, provide "thread" with the thread ID.
     * @param string $photoFilename    The photo filename.
     * @param array  $externalMetadata (optional) User-provided metadata key-value pairs.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws InstagramException
     *
     * @return Response\ConfigureResponse
     *
     * @see Internal::configureSinglePhoto() for available metadata fields.
     */
    public function sendPermanentPhoto(
        array $recipients,
        $photoFilename,
        array $externalMetadata = []
    ) {
        $internalMetadata = new InternalMetadata();
        $internalMetadata->setDirectRecipients($this->_prepareRecipients($recipients, true));
        $internalMetadata->setStoryViewMode(Constants::STORY_VIEW_MODE_PERMANENT);

        return $this->ig->internal->uploadSinglePhoto(Constants::FEED_DIRECT_STORY, $photoFilename, $internalMetadata, $externalMetadata);
    }

    /**
     * Send a disappearing photo (upload) via direct message to a user's inbox.
     *
     * @param array  $recipients       An array with "users" or "thread" keys.
     *                                 To start a new thread, provide "users" as an array
     *                                 of numerical UserPK IDs. To use an existing thread
     *                                 instead, provide "thread" with the thread ID.
     * @param string $photoFilename    The photo filename.
     * @param array  $externalMetadata (optional) User-provided metadata key-value pairs.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws InstagramException
     *
     * @return Response\ConfigureResponse
     *
     * @see Internal::configureSinglePhoto() for available metadata fields.
     */
    public function sendDisappearingPhoto(
        array $recipients,
        $photoFilename,
        array $externalMetadata = []
    ) {
        $internalMetadata = new InternalMetadata();
        $internalMetadata->setDirectRecipients($this->_prepareRecipients($recipients, true));
        $internalMetadata->setStoryViewMode(Constants::STORY_VIEW_MODE_ONCE);

        return $this->ig->internal->uploadSinglePhoto(Constants::FEED_DIRECT_STORY, $photoFilename, $internalMetadata, $externalMetadata);
    }

    /**
     * Send a replayable photo (upload) via direct message to a user's inbox.
     *
     * @param array  $recipients       An array with "users" or "thread" keys.
     *                                 To start a new thread, provide "users" as an array
     *                                 of numerical UserPK IDs. To use an existing thread
     *                                 instead, provide "thread" with the thread ID.
     * @param string $photoFilename    The photo filename.
     * @param array  $externalMetadata (optional) User-provided metadata key-value pairs.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws InstagramException
     *
     * @return Response\ConfigureResponse
     *
     * @see Internal::configureSinglePhoto() for available metadata fields.
     */
    public function sendReplayablePhoto(
        array $recipients,
        $photoFilename,
        array $externalMetadata = []
    ) {
        $internalMetadata = new InternalMetadata();
        $internalMetadata->setDirectRecipients($this->_prepareRecipients($recipients, true));
        $internalMetadata->setStoryViewMode(Constants::STORY_VIEW_MODE_REPLAYABLE);

        return $this->ig->internal->uploadSinglePhoto(Constants::FEED_DIRECT_STORY, $photoFilename, $internalMetadata, $externalMetadata);
    }

    /**
     * Send a video (upload) via direct message to a user's inbox.
     *
     * @param array  $recipients    An array with "users" or "thread" keys.
     *                              To start a new thread, provide "users" as an array
     *                              of numerical UserPK IDs. To use an existing thread
     *                              instead, provide "thread" with the thread ID.
     * @param string $videoFilename The video filename.
     * @param array  $options       An associative array of optional parameters, including:
     *                              "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws InstagramException
     * @throws UploadFailedException     If the video upload fails.
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendVideo(
        array $recipients,
        $videoFilename,
        array $options = []
    ) {
        // Direct videos use different upload IDs.
        $internalMetadata = new InternalMetadata(Utils::generateUploadId(true));
        // Attempt to upload the video data.
        $internalMetadata = $this->ig->internal->uploadVideo(Constants::FEED_DIRECT, $videoFilename, $internalMetadata);

        // We must use the same client_context and mutation_token for all attempts to prevent double-posting.
        if (!isset($options['client_context']) || !isset($options['mutation_token'])) {
            $clientContext = Utils::generateClientContext();
            $options['client_context'] = $options['client_context'] ?? $clientContext;
            $options['mutation_token'] = $options['mutation_token'] ?? $clientContext;
        }

        // Send the uploaded video to recipients.
        try {
            /** @var Response\DirectSendItemResponse $result */
            $result = $this->ig->internal->configureWithRetries(
                function () use ($internalMetadata, $recipients, $options) {
                    $videoUploadResponse = $internalMetadata->getVideoUploadResponse();

                    // Attempt to configure video parameters (which sends it to the thread).
                    return $this->_sendDirectItem('video', $recipients, array_merge($options, [
                        'upload_id'    => $internalMetadata->getUploadId(),
                        'video_result' => $videoUploadResponse !== null ? $videoUploadResponse->getResult() : '',
                    ]));
                }
            );
        } catch (InstagramException $e) {
            // Pass Instagram's error as is.
            throw $e;
        } catch (\Exception $e) {
            // Wrap runtime errors.
            throw new UploadFailedException(
                sprintf(
                    'Upload of "%s" failed: %s',
                    $internalMetadata->getPhotoDetails()->getBasename(),
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        return $result;
    }

    /**
     * Send a audio (upload) via direct message to a user's inbox.
     *
     * @param array  $recipients    An array with "users" or "thread" keys.
     *                              To start a new thread, provide "users" as an array
     *                              of numerical UserPK IDs. To use an existing thread
     *                              instead, provide "thread" with the thread ID.
     * @param string $videoFilename The video filename.
     * @param array  $options       An associative array of optional parameters, including:
     *                              "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws InstagramException
     * @throws UploadFailedException     If the video upload fails.
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendAudio(
        array $recipients,
        $videoFilename,
        array $options = []
    ) {
        // Direct videos use different upload IDs.
        $internalMetadata = new InternalMetadata(Utils::generateUploadId(true));
        // Attempt to upload the video data.
        $internalMetadata = $this->ig->internal->facebookUpload(Constants::FEED_DIRECT_AUDIO, $videoFilename, $internalMetadata);

        // We must use the same client_context and mutation_token for all attempts to prevent double-posting.
        if (!isset($options['client_context']) || !isset($options['mutation_token'])) {
            $clientContext = Utils::generateClientContext();
            $options['client_context'] = $options['client_context'] ?? $clientContext;
            $options['mutation_token'] = $options['mutation_token'] ?? $clientContext;
        }

        // Send the uploaded video to recipients.
        try {
            /** @var Response\DirectSendItemResponse $result */
            /*
            $result = $this->ig->internal->configureWithRetries(
                function () use ($internalMetadata, $recipients, $options) {
                    $videoUploadResponse = $internalMetadata->getVideoUploadResponse();
                    // Attempt to configure video parameters (which sends it to the thread).
                    return $this->_sendDirectItem('share_voice', $recipients, array_merge($options, [
                        'upload_id'    => $internalMetadata->getUploadId(),
                        'video_result' => $videoUploadResponse !== null ? $videoUploadResponse->getResult() : '',
                    ]));
                }
            );
            */
            $result = $this->_sendDirectItem('share_voice', $recipients, array_merge($options, [
                'upload_id'         => $internalMetadata->getUploadId(),
                'attachment_fbid'   => $internalMetadata->getFbAttachmentId(),
            ]));
        } catch (InstagramException $e) {
            // Pass Instagram's error as is.
            throw $e;
        } catch (\Exception $e) {
            // Wrap runtime errors.
            throw new UploadFailedException(
                sprintf(
                    'Upload of "%s" failed: %s',
                    $internalMetadata->getPhotoDetails()->getBasename(),
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        return $result;
    }

    /**
     * Send a disappearing video (upload) via direct message to a user's inbox.
     *
     * @param array  $recipients       An array with "users" or "thread" keys.
     *                                 To start a new thread, provide "users" as an array
     *                                 of numerical UserPK IDs. To use an existing thread
     *                                 instead, provide "thread" with the thread ID.
     * @param string $videoFilename    The video filename.
     * @param array  $externalMetadata (optional) User-provided metadata key-value pairs.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws InstagramException
     * @throws UploadFailedException     If the video upload fails.
     *
     * @return Response\ConfigureResponse
     *
     * @see Internal::configureSingleVideo() for available metadata fields.
     */
    public function sendDisappearingVideo(
        array $recipients,
        $videoFilename,
        array $externalMetadata = []
    ) {
        $internalMetadata = new InternalMetadata();
        $internalMetadata->setDirectRecipients($this->_prepareRecipients($recipients, true));
        $internalMetadata->setStoryViewMode(Constants::STORY_VIEW_MODE_ONCE);

        return $this->ig->internal->uploadSingleVideo(Constants::FEED_DIRECT_STORY, $videoFilename, $internalMetadata, $externalMetadata);
    }

    /**
     * Send a replayable video (upload) via direct message to a user's inbox.
     *
     * @param array  $recipients       An array with "users" or "thread" keys.
     *                                 To start a new thread, provide "users" as an array
     *                                 of numerical UserPK IDs. To use an existing thread
     *                                 instead, provide "thread" with the thread ID.
     * @param string $videoFilename    The video filename.
     * @param array  $externalMetadata (optional) User-provided metadata key-value pairs.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws InstagramException
     * @throws UploadFailedException     If the video upload fails.
     *
     * @return Response\ConfigureResponse
     *
     * @see Internal::configureSingleVideo() for available metadata fields.
     */
    public function sendReplayableVideo(
        array $recipients,
        $videoFilename,
        array $externalMetadata = []
    ) {
        $internalMetadata = new InternalMetadata();
        $internalMetadata->setDirectRecipients($this->_prepareRecipients($recipients, true));
        $internalMetadata->setStoryViewMode(Constants::STORY_VIEW_MODE_REPLAYABLE);

        return $this->ig->internal->uploadSingleVideo(Constants::FEED_DIRECT_STORY, $videoFilename, $internalMetadata, $externalMetadata);
    }

    /**
     * Send a like to a user's inbox.
     *
     * @param array $recipients An array with "users" or "thread" keys.
     *                          To start a new thread, provide "users" as an array
     *                          of numerical UserPK IDs. To use an existing thread
     *                          instead, provide "thread" with the thread ID.
     * @param array $options    An associative array of optional parameters, including:
     *                          "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     *
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendLike(
        array $recipients,
        array $options = []
    ) {
        return $this->_sendDirectItem('like', $recipients, $options);
    }

    /**
     * Send a hashtag to a user's inbox.
     *
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param string $hashtag    Hashtag to share.
     * @param array  $options    An associative array of optional parameters, including:
     *                           "client_context" and "mutation_token" - predefined UUID used to prevent double-posting;
     *                           "text" - text message.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendHashtag(
        array $recipients,
        $hashtag,
        array $options = []
    ) {
        if (!strlen($hashtag)) {
            throw new \InvalidArgumentException('Hashtag can not be empty.');
        }

        return $this->_sendDirectItem('hashtag', $recipients, array_merge($options, [
            'hashtag' => $hashtag,
        ]));
    }

    /**
     * Send a location to a user's inbox.
     *
     * You must provide a valid Instagram location ID, which you get via other
     * functions such as Location::search().
     *
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param string $locationId Instagram's internal ID for the location.
     * @param array  $options    An associative array of optional parameters, including:
     *                           "client_context" and "mutation_token" - predefined UUID used to prevent double-posting;
     *                           "text" - text message.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     *
     * @see Location::search()
     */
    public function sendLocation(
        array $recipients,
        $locationId,
        array $options = []
    ) {
        if (!ctype_digit($locationId) && (!is_int($locationId) || $locationId < 0)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid location ID.', $locationId));
        }

        return $this->_sendDirectItem('location', $recipients, array_merge($options, [
            'venue_id' => $locationId,
        ]));
    }

    /**
     * Send a profile to a user's inbox.
     *
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param string $userId     Numerical UserPK ID.
     * @param array  $options    An associative array of optional parameters, including:
     *                           "client_context" and "mutation_token" - predefined UUID used to prevent double-posting;
     *                           "text" - text message.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendProfile(
        array $recipients,
        $userId,
        array $options = []
    ) {
        if (!ctype_digit($userId) && (!is_int($userId) || $userId < 0)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid numerical UserPK ID.', $userId));
        }

        return $this->_sendDirectItem('profile', $recipients, array_merge($options, [
            'profile_user_id' => $userId,
        ]));
    }

    /**
     * Send a reaction to an existing thread item.
     *
     * @param string $threadId     Thread identifier.
     * @param string $threadItemId ThreadItemIdentifier.
     * @param string $reactionType One of: "like".
     * @param array  $options      An associative array of optional parameters, including:
     *                             "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendReaction(
        $threadId,
        $threadItemId,
        $reactionType,
        array $options = []
    ) {
        return $this->_handleReaction($threadId, $threadItemId, $reactionType, 'created', $options);
    }

    /**
     * Share an existing story post via direct message to a user's inbox.
     *
     * You are able to share your own stories, as well as public stories from
     * other people.
     *
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param string $storyId    The story ID in Instagram's internal format (ie "3482384834_43294").
     * @param string $reelId     The reel ID in Instagram's internal format (ie "highlight:12970012453081168")
     * @param array  $options    An associative array of additional parameters, including:
     *                           "media_type" (required) - either "photo" or "video";
     *                           "client_context" and "mutation_token" - predefined UUID used to prevent double-posting;
     *                           "text" - text message.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemsResponse
     *
     * @see https://help.instagram.com/188382041703187 For more information.
     */
    public function sendStory(
        array $recipients,
        $storyId,
        $reelId = null,
        array $options = []
    ) {
        if (!preg_match('#^\d+_\d+$#D', $storyId)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid story ID.', $storyId));
        }
        if ($reelId !== null) {
            if (!preg_match('#^highlight:\d+$#D', $reelId)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid reel ID.', $reelId));
            }
            $options = array_merge(
                $options,
                [
                    'reel_id' => $reelId,
                ]
            );
        }
        if (!isset($options['media_type'])) {
            throw new \InvalidArgumentException('Please provide media_type in options.');
        }
        if ($options['media_type'] !== 'photo' && $options['media_type'] !== 'video') {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid media_type.', $options['media_type']));
        }

        return $this->_sendDirectItems('story_share', $recipients, array_merge($options, [
            'story_media_id' => $storyId,
        ]));
    }

    /**
     * Share reel (aka clips internally).
     *
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param string $mediaId    The media ID in Instagram's internal format (ie "3482384834_43294").
     * @param array  $options    An associative array of additional parameters, including:
     *                           "client_context" and "mutation_token" (optional) - predefined UUID used to prevent double-posting;
     *                           "text" (optional) - text message.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemsResponse
     *
     * @see https://help.instagram.com/1209246439090858 For more information.
     */
    public function shareReel(
        array $recipients,
        $mediaId,
        array $options = []
    ) {
        if (!preg_match('#^\d+_\d+$#D', $mediaId)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid media ID.', $mediaId));
        }

        return $this->_sendDirectItems('clip_share', $recipients, array_merge($options, [
            'media_id' => $mediaId,
        ]));
    }

    /**
     * Share an occurring or archived live stream via direct message to a user's inbox.
     *
     * You are able to share your own broadcasts, as well as broadcasts from
     * other people.
     *
     * @param array  $recipients  An array with "users" or "thread" keys.
     *                            To start a new thread, provide "users" as an array
     *                            of numerical UserPK IDs. To use an existing thread
     *                            instead, provide "thread" with the thread ID.
     * @param string $broadcastId The broadcast ID in Instagram's internal format (ie "17854587811139572").
     * @param array  $options     An associative array of optional parameters, including:
     *                            "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    public function sendLive(
        array $recipients,
        $broadcastId,
        array $options = []
    ) {
        return $this->_sendDirectItem('live', $recipients, array_merge($options, [
            'broadcast_id'      => $broadcastId,
            'send_attribution'  => 'live_broadcast',
        ]), true);
    }

    /**
     * Delete a reaction to an existing thread item.
     *
     * @param string $threadId     Thread identifier.
     * @param string $threadItemId ThreadItemIdentifier.
     * @param string $reactionType One of: "like".
     * @param array  $options      An associative array of optional parameters, including:
     *                             "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    public function deleteReaction(
        $threadId,
        $threadItemId,
        $reactionType,
        array $options = []
    ) {
        return $this->_handleReaction($threadId, $threadItemId, $reactionType, 'deleted', $options);
    }

    /**
     * Delete an item from given thread.
     *
     * @param string $threadId     Thread ID.
     * @param string $threadItemId Thread item ID.
     *
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function deleteItem(
        $threadId,
        $threadItemId
    ) {
        return $this->ig->request("direct_v2/threads/{$threadId}/items/{$threadItemId}/delete/")
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->setSignedPost(false)
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Marks an item from given thread as seen.
     *
     * @param string $threadId      Thread ID.
     * @param string $threadItemId  Thread item ID.
     * @param string $clientContext Client context.
     *
     * @throws InstagramException
     *
     * @return Response\DirectSeenItemResponse
     */
    public function markItemSeen(
        $threadId,
        $threadItemId,
        $clientContext
    ) {
        return $this->ig->request("direct_v2/threads/{$threadId}/items/{$threadItemId}/seen/")
            ->addPost('action', 'mark_seen')
            ->addPost('thread_id', $threadId)
            ->addPost('item_id', $threadItemId)
            ->addPost('_uuid', $this->ig->uuid)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('client_context', $clientContext)
            ->addPost('offline_threading_id', $clientContext)
            ->setSignedPost(false)
            ->getResponse(new Response\DirectSeenItemResponse());
    }

    /**
     * Marks visual items from given thread as seen.
     *
     * `NOTE:` This "visual" endpoint is only used for Direct stories.
     *
     * @param string          $threadId      Thread ID.
     * @param string|string[] $threadItemIds One or more thread item IDs.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function markVisualItemsSeen(
        $threadId,
        $threadItemIds
    ) {
        if (!is_array($threadItemIds)) {
            $threadItemIds = [$threadItemIds];
        } elseif (!count($threadItemIds)) {
            throw new \InvalidArgumentException('Please provide at least one thread item ID.');
        }

        return $this->ig->request("direct_v2/visual_threads/{$threadId}/item_seen/")
            ->addPost('item_ids', '['.implode(',', $threadItemIds).']')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Marks visual items from given thread as replayed.
     *
     * `NOTE:` This "visual" endpoint is only used for Direct stories.
     *
     * @param string          $threadId      Thread ID.
     * @param string|string[] $threadItemIds One or more thread item IDs.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\GenericResponse
     */
    public function markVisualItemsReplayed(
        $threadId,
        $threadItemIds
    ) {
        if (!is_array($threadItemIds)) {
            $threadItemIds = [$threadItemIds];
        } elseif (!count($threadItemIds)) {
            throw new \InvalidArgumentException('Please provide at least one thread item ID.');
        }

        return $this->ig->request("direct_v2/visual_threads/{$threadId}/item_replayed/")
            ->addPost('item_ids', '['.implode(',', $threadItemIds).']')
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Validate and prepare recipients for direct messaging.
     *
     * @param array $recipients An array with "users" or "thread" keys.
     *                          To start a new thread, provide "users" as an array
     *                          of numerical UserPK IDs. To use an existing thread
     *                          instead, provide "thread" with the thread ID.
     * @param bool  $useQuotes  Whether to put IDs into quotes.
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function _prepareRecipients(
        array $recipients,
        $useQuotes
    ) {
        $result = [];
        // users
        if (isset($recipients['users'])) {
            if (!is_array($recipients['users'])) {
                throw new \InvalidArgumentException('"users" must be an array.');
            }
            foreach ($recipients['users'] as $userId) {
                if (!is_scalar($userId)) {
                    throw new \InvalidArgumentException('User identifier must be scalar.');
                } elseif (!ctype_digit($userId) && (!is_int($userId) || $userId < 0)) {
                    throw new \InvalidArgumentException(sprintf('"%s" is not a valid user identifier.', $userId));
                }
            }
            // Although this is an array of groups, you will get "Only one group is supported." error
            // if you will try to use more than one group here.
            // We can't use json_encode() here, because each user id must be a number.
            $result['users'] = '[['.implode(',', $recipients['users']).']]';
        }
        // thread
        if (isset($recipients['thread'])) {
            if (!is_scalar($recipients['thread'])) {
                throw new \InvalidArgumentException('Thread identifier must be scalar.');
            } elseif (!ctype_digit($recipients['thread']) && (!is_int($recipients['thread']) || $recipients['thread'] < 0)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid thread identifier.', $recipients['thread']));
            }
            // Although this is an array, you will get "Need to specify thread ID or recipient users." error
            // if you will try to use more than one thread identifier here.
            if (!$useQuotes) {
                // We can't use json_encode() here, because thread id must be a number.
                $result['thread'] = '['.$recipients['thread'].']';
            } else {
                // We can't use json_encode() here, because thread id must be a string.
                $result['thread'] = '["'.$recipients['thread'].'"]';
            }
        }
        if (!count($result)) {
            throw new \InvalidArgumentException('Please provide at least one recipient.');
        } elseif (isset($result['thread']) && isset($result['users'])) {
            throw new \InvalidArgumentException('You can not mix "users" with "thread".');
        }

        return $result;
    }

    /**
     * Send a direct message to specific users or thread.
     *
     * @param string $type       One of: "message", "like", "hashtag", "location", "profile", "photo",
     *                           "video", "links", "live".
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param array  $options    Depends on $type:
     *                           "message" uses "client_context", "mutation_token" and "text";
     *                           "like" uses "client_context" and "mutation_token";
     *                           "hashtag" uses "client_context", "mutation_token", "hashtag" and "text";
     *                           "location" uses "client_context", "mutation_token", "venue_id" and "text";
     *                           "profile" uses "client_context", "mutation_token", "profile_user_id" and "text";
     *                           "photo" uses "client_context", "mutation_token" and "filepath";
     *                           "video" uses "client_context", "mutation_token", "upload_id" and "video_result";
     *                           "links" uses "client_context", "mutation_token", "link_text" and "link_urls".
     *                           "live" uses "client_context", "mutation_token" and "text".
     * @param mixed  $signedPost
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    protected function _sendDirectItem(
        $type,
        array $recipients,
        array $options = [],
        $signedPost = false
    ) {
        $recipients = $this->_prepareRecipients($recipients, false);

        // Handle the request...
        switch ($type) {
            case 'message':
                $request = $this->ig->request('direct_v2/threads/broadcast/text/');
                // Check and set text.
                if (!isset($options['text'])) {
                    throw new \InvalidArgumentException('No text message provided.');
                }
                if (!isset($options['mentioned_users_id'])) {
                    $request->addPost('mentioned_users_id', json_encode([]));
                } else {
                    $request->addPost('mentioned_users_id', $options['mentioned_users_id']);
                }
                $request->addPost('text', $options['text'])
                        ->addPost('is_x_transport_forward', 'false');
                break;
            case 'like':
                $request = $this->ig->request('direct_v2/threads/broadcast/like/');
                break;
            case 'hashtag':
                $request = $this->ig->request('direct_v2/threads/broadcast/hashtag/');
                // Check and set hashtag.
                if (!isset($options['hashtag'])) {
                    throw new \InvalidArgumentException('No hashtag provided.');
                }
                $request->addPost('hashtag', $options['hashtag']);
                // Set text if provided.
                if (isset($options['text']) && strlen($options['text'])) {
                    $request->addPost('text', $options['text']);
                }
                break;
            case 'location':
                $request = $this->ig->request('direct_v2/threads/broadcast/location/');
                // Check and set venue_id.
                if (!isset($options['venue_id'])) {
                    throw new \InvalidArgumentException('No venue_id provided.');
                }
                $request->addPost('venue_id', $options['venue_id']);
                // Set text if provided.
                if (isset($options['text']) && strlen($options['text'])) {
                    $request->addPost('text', $options['text']);
                }
                break;
            case 'profile':
                $request = $this->ig->request('direct_v2/threads/broadcast/profile/');
                // Check and set profile_user_id.
                if (!isset($options['profile_user_id'])) {
                    throw new \InvalidArgumentException('No profile_user_id provided.');
                }
                $request->addPost('profile_user_id', $options['profile_user_id']);
                // Set text if provided.
                if (isset($options['text']) && strlen($options['text'])) {
                    $request->addPost('text', $options['text']);
                }
                break;
            case 'photo':
                $request = $this->ig->request('direct_v2/threads/broadcast/photo_attachment/');
                // Check and set upload_id.
                if (!isset($options['upload_id'])) {
                    throw new \InvalidArgumentException('No upload_id provided.');
                }
                $request->addPost('attachment_fbid', $options['upload_id'])
                        ->addPost('is_x_transport_forward', 'false')
                        ->addPost('allow_full_aspect_ratio', true);
                break;
            case 'video':
                $request = $this->ig->request('direct_v2/threads/broadcast/configure_video/');
                // Check and set upload_id.
                if (!isset($options['upload_id'])) {
                    throw new \InvalidArgumentException('No upload_id provided.');
                }
                $request->addPost('upload_id', $options['upload_id']);
                // Set video_result if provided.
                if (isset($options['video_result'])) {
                    $request->addPost('video_result', $options['video_result']);
                }
                break;
            case 'share_voice':
                $request = $this->ig->request('direct_v2/threads/broadcast/voice_attachment/');
                // Check and set upload_id.
                if (!isset($options['upload_id'])) {
                    throw new \InvalidArgumentException('No upload_id provided.');
                }
                $request->addPost('upload_id', $options['upload_id']);
                if (!isset($options['attachment_fbid'])) {
                    throw new \InvalidArgumentException('No attachment_fbid provided.');
                }
                $request->addPost('attachment_fbid', $options['attachment_fbid']);

                $samplingFreq = ($options['waveform_sampling_frequency_hz'] ?? 10);
                $waveform = [];
                for ($i = 0; $i < 20; $i++) {
                    $waveform[] = round(sin($i * (M_PI / 10)) * 0.5 + 0.5, 2);
                }
                $request->addPost('waveform_sampling_frequency_hz', $samplingFreq)
                        ->addPost('waveform', json_encode($waveform))
                        ->addPost('is_shh_mode', 'false')
                        ->addPost('send_attribution', 'inbox_search');
                break;
            case 'links':
                $request = $this->ig->request('direct_v2/threads/broadcast/link/');
                // Check and set link_urls.
                if (!isset($options['link_urls'])) {
                    throw new \InvalidArgumentException('No link_urls provided.');
                }
                $request->addPost('link_urls', $options['link_urls']);
                // Check and set link_text.
                if (!isset($options['link_text'])) {
                    throw new \InvalidArgumentException('No link_text provided.');
                }
                $request->addPost('link_text', $options['link_text']);
                break;
            case 'reaction':
                $request = $this->ig->request('direct_v2/threads/broadcast/reaction/');
                // Check and set reaction_type.
                if (!isset($options['reaction_type'])) {
                    throw new \InvalidArgumentException('No reaction_type provided.');
                }
                $request->addPost('reaction_type', $options['reaction_type']);
                // Check and set reaction_status.
                if (!isset($options['reaction_status'])) {
                    throw new \InvalidArgumentException('No reaction_status provided.');
                }
                $request->addPost('reaction_status', $options['reaction_status']);
                // Check and set item_id.
                if (!isset($options['item_id'])) {
                    throw new \InvalidArgumentException('No item_id provided.');
                }
                $request->addPost('item_id', $options['item_id']);
                // Check and set node_type.
                if (!isset($options['node_type'])) {
                    throw new \InvalidArgumentException('No node_type provided.');
                }
                $request->addPost('node_type', $options['node_type']);
                break;
            case 'live':
                $request = $this->ig->request('direct_v2/threads/broadcast/live_viewer_invite/');
                // Check and set broadcast id.
                if (!isset($options['broadcast_id'])) {
                    throw new \InvalidArgumentException('No broadcast_id provided.');
                }
                $request->addPost('broadcast_id', $options['broadcast_id'])
                        ->addPost('btt_dual_send', 'false')
                        ->addPost('is_ae_dual_send', 'false');
                // Set text if provided.
                if (isset($options['text']) && strlen($options['text'])) {
                    $request->addPost('text', $options['text']);
                }
                break;
            case 'reel_reaction':
                $request = $this->ig->request('direct_v2/threads/broadcast/reel_react/')
                    ->addPost('media_id', $options['media_id']);

                $request->addPost('entry', 'reel');
                $request->addPost('text', $options['reaction']);
                $request->addPost('reaction_emoji', $options['reaction']);

                // Set reel_id which is just the user id
                if (isset($recipients['users'])) {
                    $request->addPost('reel_id', $recipients['users'][0]);
                }
                break;
            default:
                throw new \InvalidArgumentException('Unsupported _sendDirectItem() type.');
        }

        // Add recipients.
        if (isset($recipients['users'])) {
            $request->addPost('recipient_users', $recipients['users']);
        } elseif (isset($recipients['thread'])) {
            $request->addPost('thread_ids', $recipients['thread']);
        } else {
            throw new \InvalidArgumentException('Please provide at least one recipient.');
        }

        if (isset($options['shh_mode'])) {
            if (!is_bool($options['shh_mode'])) {
                throw new \InvalidArgumentException('shh_mode must be a boolean value.');
            }
            $request->addPost('is_shh_mode', intval($options['shh_mode']));
        } else {
            $request->addPost('is_shh_mode', 0);
        }

        if (isset($options['send_attribution'])) {
            $request->addPost('send_attribution', $options['send_attribution']);
        } else {
            $request->addPost('send_attribution', 'inbox');
        }

        // Handle client_context.
        if (!isset($options['client_context']) || !isset($options['mutation_token']) || !isset($options['offline_threading_id'])) {
            // WARNING: Must be random every time otherwise we can only
            // make a single post per direct-discussion thread.
            $clientContext = Utils::generateClientContext();
            $options['client_context'] = $options['client_context'] ?? $clientContext;
            $options['mutation_token'] = $options['mutation_token'] ?? $clientContext;
            $options['offline_threading_id'] = $options['offline_threading_id'] ?? $clientContext;
        }

        // Add some additional data if signed post.
        if ($signedPost) {
            $request->addPost('_uid', $this->ig->account_id);
        }

        // Execute the request with all data used by both signed and unsigned.
        return $request->setSignedPost($signedPost)
            ->addPost('action', 'send_item')
            ->addPost('client_context', $options['client_context'])
            ->addPost('mutation_token', $options['mutation_token'])
            ->addPost('offline_threading_id', $options['offline_threading_id'])
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('nav_chain', $this->ig->getNavChain())
            ->addPost('device_id', $this->ig->device_id)
            ->getResponse(new Response\DirectSendItemResponse());
    }

    /**
     * Send a direct messages to specific users or thread.
     *
     * @param string $type       One of: "media_share", "story_share".
     * @param array  $recipients An array with "users" or "thread" keys.
     *                           To start a new thread, provide "users" as an array
     *                           of numerical UserPK IDs. To use an existing thread
     *                           instead, provide "thread" with the thread ID.
     * @param array  $options    Depends on $type:
     *                           "media_share" uses "client_context", "mutation_token, ""media_id", "media_type" and "text";
     *                           "story_share" uses "client_context", "mutation_token", "story_media_id", "media_type" and "text".
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemsResponse
     */
    protected function _sendDirectItems(
        $type,
        array $recipients,
        array $options = []
    ) {
        // Most requests are unsigned, but some use signing by overriding this.
        $signedPost = false;

        // Handle the request...
        switch ($type) {
            case 'media_share':
                $request = $this->ig->request('direct_v2/threads/broadcast/media_share/');
                // Check and set media_id.
                if (!isset($options['media_id'])) {
                    throw new \InvalidArgumentException('No media_id provided.');
                }
                $request->addPost('media_id', $options['media_id']);
                // Set text if provided.
                if (isset($options['text']) && strlen($options['text'])) {
                    $request->addPost('text', $options['text']);
                }
                // Check and set media_type.
                if (isset($options['media_type']) && $options['media_type'] === 'video') {
                    $request->addParam('media_type', 'video');
                } else {
                    $request->addParam('media_type', 'photo');
                }
                break;
            case 'story_share':
                $signedPost = true; // This must be a signed post!
                $request = $this->ig->request('direct_v2/threads/broadcast/story_share/');
                // Check and set story_media_id.
                if (!isset($options['story_media_id'])) {
                    throw new \InvalidArgumentException('No story_media_id provided.');
                }
                $request->addPost('story_media_id', $options['story_media_id']);
                // Set reel_id if provided.
                if (isset($options['reel_id'])) {
                    $request->addPost('reel_id', $options['reel_id']);
                }
                // Set text if provided.
                if (isset($options['text']) && strlen($options['text'])) {
                    $request->addPost('text', $options['text']);
                }
                // Check and set media_type.
                if (isset($options['media_type']) && $options['media_type'] === 'video') {
                    $request->addParam('media_type', 'video');
                } else {
                    $request->addParam('media_type', 'photo');
                }
                break;
            case 'clip_share':
                $request = $this->ig->request('direct_v2/threads/broadcast/clip_share/');
                // Check and set media_id.
                if (!isset($options['media_id'])) {
                    throw new \InvalidArgumentException('No media_id provided.');
                }
                $request->addPost('media_id', $options['media_id']);

                // Set text if provided.
                if (isset($options['text']) && strlen($options['text'])) {
                    $request->addPost('text', $options['text']);
                }
                break;
            default:
                throw new \InvalidArgumentException('Unsupported _sendDirectItems() type.');
        }

        // Add recipients.
        $recipients = $this->_prepareRecipients($recipients, false);
        if (isset($recipients['users'])) {
            $request->addPost('recipient_users', $recipients['users']);
        } elseif (isset($recipients['thread'])) {
            $request->addPost('thread_ids', $recipients['thread']);
        } else {
            throw new \InvalidArgumentException('Please provide at least one recipient.');
        }

        // Handle client_context and mutation_token.
        if (!isset($options['client_context']) || !isset($options['mutation_token']) || !isset($options['offline_threading_id'])) {
            // WARNING: Must be random every time otherwise we can only
            // make a single post per direct-discussion thread.
            $clientContext = Utils::generateClientContext();
            $options['client_context'] = $options['client_context'] ?? $clientContext;
            $options['mutation_token'] = $options['mutation_token'] ?? $clientContext;
            $options['offline_threading_id'] = $options['offline_threading_id'] ?? $clientContext;
        }

        // Add some additional data if signed post.
        if ($signedPost) {
            $request->addPost('_uid', $this->ig->account_id);
        }

        // Execute the request with all data used by both signed and unsigned.
        return $request->setSignedPost($signedPost)
            ->addPost('action', 'send_item')
            ->addPost('unified_broadcast_format', '1')
            ->addPost('client_context', $options['client_context'])
            ->addPost('mutation_token', $options['mutation_token'])
            ->addPost('offline_threading_id', $options['offline_threading_id'])
            // ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('nav_chain', $this->ig->getNavChain())
            ->addPost('_uuid', $this->ig->uuid)
            ->getResponse(new Response\DirectSendItemsResponse());
    }

    /**
     * Handle a reaction to an existing thread item.
     *
     * @param string $threadId       Thread identifier.
     * @param string $threadItemId   ThreadItemIdentifier.
     * @param string $reactionType   One of: "like".
     * @param string $reactionStatus One of: "created", "deleted".
     * @param array  $options        An associative array of optional parameters, including:
     *                               "client_context" and "mutation_token" - predefined UUID used to prevent double-posting.
     *
     * @throws \InvalidArgumentException
     * @throws InstagramException
     *
     * @return Response\DirectSendItemResponse
     */
    protected function _handleReaction(
        $threadId,
        $threadItemId,
        $reactionType,
        $reactionStatus,
        array $options = []
    ) {
        if (!ctype_digit($threadId) && (!is_int($threadId) || $threadId < 0)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid thread ID.', $threadId));
        }
        if (!ctype_digit($threadItemId) && (!is_int($threadItemId) || $threadItemId < 0)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid thread item ID.', $threadItemId));
        }
        if (!in_array($reactionType, ['like'], true)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a supported reaction type.', $reactionType));
        }

        return $this->_sendDirectItem('reaction', ['thread' => $threadId], array_merge($options, [
            'reaction_type'   => $reactionType,
            'reaction_status' => $reactionStatus,
            'item_id'         => $threadItemId,
            'node_type'       => 'item',
        ]));
    }
}
