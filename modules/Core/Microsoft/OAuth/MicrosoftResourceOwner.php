<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */

namespace Modules\Core\Microsoft\OAuth;

use Modules\Core\OAuth\ResourceOwner;

class MicrosoftResourceOwner extends ResourceOwner
{
    /**
     * Get the resource owner email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->response['email'] ?? $this->response['userPrincipalName'];
    }
}
