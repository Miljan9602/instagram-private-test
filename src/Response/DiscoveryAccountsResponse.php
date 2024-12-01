<?php

namespace InstagramAPI\Response;

use InstagramAPI\Response;

/**
 * DiscoveryAccountsResponse.
 *
 * @method Model\DiscoveryAccountsCategories[] getCategories()
 * @method mixed getMessage()
 * @method string getStatus()
 * @method Model\_Message[] get_Messages()
 * @method bool isCategories()
 * @method bool isMessage()
 * @method bool isStatus()
 * @method bool is_Messages()
 * @method $this setCategories(Model\DiscoveryAccountsCategories[] $value)
 * @method $this setMessage(mixed $value)
 * @method $this setStatus(string $value)
 * @method $this set_Messages(Model\_Message[] $value)
 * @method $this unsetCategories()
 * @method $this unsetMessage()
 * @method $this unsetStatus()
 * @method $this unset_Messages()
 */
class DiscoveryAccountsResponse extends Response
{
    public const JSON_PROPERTY_MAP = [
        'categories'  => 'Model\DiscoveryAccountsCategories[]',
    ];
}
