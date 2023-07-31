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

namespace Modules\MailClient\Mail;

use Modules\Core\MailableTemplate\MailableTemplate as BaseMailableTemplate;

abstract class MailableTemplate extends BaseMailableTemplate
{
    use SendsMailableViaEmailAccount;

    /**
     * Provide the email account id
     */
    protected function emailAccountId(): ?int
    {
        if ($account = settings('system_email_account_id')) {
            return (int) $account;
        }

        return null;
    }

    /**
     * Get custom account from name text
     */
    protected function accountFromName(): string
    {
        return config('app.name') ?: '[APP NAME NOT SET]';
    }
}
