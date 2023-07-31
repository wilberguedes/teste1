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

namespace Modules\Core\OAuth;

class AccessTokenProvider
{
    /**
     * Initialize the acess token provider class
     */
    public function __construct(protected string $token, protected string $email)
    {
    }

    /**
     * Get the access token
     */
    public function getAccessToken(): string
    {
        return $this->token;
    }

    /**
     * Get the token email adress
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
