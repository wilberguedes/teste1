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

namespace Modules\Users\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\MailableTemplate\DefaultMailable;
use Modules\Core\Placeholders\ActionButtonPlaceholder;
use Modules\Core\Placeholders\Placeholders as BasePlaceholders;
use Modules\Core\Placeholders\PrivacyPolicyPlaceholder;
use Modules\Core\Placeholders\UrlPlaceholder;
use Modules\MailClient\Mail\MailableTemplate;
use Modules\Users\Models\User;
use Modules\Users\Placeholders\UserPlaceholder;

class UserMentioned extends MailableTemplate implements ShouldQueue
{
    /**
     * Create a new mailable template instance.
     */
    public function __construct(protected User $mentioned, protected string $mentionUrl, protected User $mentioner)
    {
    }

    /**
     * Provide the defined mailable template placeholders
     */
    public function placeholders(): BasePlaceholders
    {
        return new BasePlaceholders([
            UserPlaceholder::make(fn () => $this->mentioned->name, 'mentioned_user')
                ->description(__('core::mail_template.placeholders.mentioned_user')),

            UserPlaceholder::make(fn () => $this->mentioner->name)
                ->description(__('core::mail_template.placeholders.user_that_mentions')),

            UrlPlaceholder::make(fn () => $this->mentionUrl, 'url')
                ->description(__('core::mail_template.placeholders.mention_url')),

            ActionButtonPlaceholder::make(fn () => $this->mentionUrl),

            PrivacyPolicyPlaceholder::make(),
        ]);
    }

    /**
     * Provides the mail template default configuration
     */
    public static function default(): DefaultMailable
    {
        return new DefaultMailable(static::defaultHtmlTemplate(), static::defaultSubject());
    }

    /**
     * Provides the mail template default message
     */
    public static function defaultHtmlTemplate(): string
    {
        return '<p>Hello {{ mentioned_user }}<br /></p>
                <p>{{ user }} mentioned you.<br /></p>
                <p>{{#action_button}}View Record{{/action_button}}</p>';
    }

    /**
     * Provides the mail template default subject
     */
    public static function defaultSubject(): string
    {
        return 'You Were Mentioned by {{ user }}';
    }
}
