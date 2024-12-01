<?php

namespace InstagramAPI\Response\Model;

use InstagramAPI\AutoPropertyMapper;

/**
 * FeedItem.
 *
 * @method Ad4ad getAd4ad()
 * @method int getAdLinkType()
 * @method mixed getEndOfFeedDemarcator()
 * @method mixed getExploreStory()
 * @method Item getMediaOrAd()
 * @method StoriesNetego getStoriesNetego()
 * @method SuggestedUsers getSuggestedUsers()
 * @method bool isAd4ad()
 * @method bool isAdLinkType()
 * @method bool isEndOfFeedDemarcator()
 * @method bool isExploreStory()
 * @method bool isMediaOrAd()
 * @method bool isStoriesNetego()
 * @method bool isSuggestedUsers()
 * @method $this setAd4ad(Ad4ad $value)
 * @method $this setAdLinkType(int $value)
 * @method $this setEndOfFeedDemarcator(mixed $value)
 * @method $this setExploreStory(mixed $value)
 * @method $this setMediaOrAd(Item $value)
 * @method $this setStoriesNetego(StoriesNetego $value)
 * @method $this setSuggestedUsers(SuggestedUsers $value)
 * @method $this unsetAd4ad()
 * @method $this unsetAdLinkType()
 * @method $this unsetEndOfFeedDemarcator()
 * @method $this unsetExploreStory()
 * @method $this unsetMediaOrAd()
 * @method $this unsetStoriesNetego()
 * @method $this unsetSuggestedUsers()
 */
class FeedItem extends AutoPropertyMapper
{
    public const JSON_PROPERTY_MAP = [
        'media_or_ad'            => 'Item',
        'stories_netego'         => 'StoriesNetego',
        'ad4ad'                  => 'Ad4ad',
        'suggested_users'        => 'SuggestedUsers',
        'end_of_feed_demarcator' => '',
        'explore_story'          => '',
        'ad_link_type'           => 'int',
    ];
}
