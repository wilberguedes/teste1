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

namespace Modules\MailClient\Http\Requests;

use App\Installer\RequirementsChecker;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\RequiredIf;
use Modules\Core\Rules\UniqueRule;
use Modules\MailClient\Client\ClientManager;
use Modules\MailClient\Client\ConnectionType;
use Modules\MailClient\Models\EmailAccount;

class EmailAccountRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'connection_type' => [Rule::requiredIf($this->isMethod('POST')), new Enum(ConnectionType::class)],
            'email' => $this->getEmailFieldRules(),
            'password' => $this->route('account') ? 'nullable' : 'required',
            'sent_folder_id' => Rule::requiredIf($this->isMethod('PUT')),
            'trash_folder_id' => Rule::requiredIf($this->isMethod('PUT')),
            'from_name_header' => $this->getFromNameHeaderRules(),
            'initial_sync_from' => $this->getInitialSyncFromRules(),
            'imap_server' => ['max:191', $this->getRequiredIfRuleForImapField()],
            'imap_port' => [$this->getRequiredIfRuleForImapField(), 'numeric'],
            'imap_encryption' => ['nullable', Rule::in(ClientManager::ENCRYPTION_TYPES)],
            'smtp_server' => ['max:191', $this->getRequiredIfRuleForImapField()],
            'smtp_port' => [$this->getRequiredIfRuleForImapField(), 'numeric'],
            'smtp_encryption' => ['nullable', Rule::in(ClientManager::ENCRYPTION_TYPES)],
            'validate_cert' => 'boolean|nullable',
            'folders' => ['array', $this->getRequiredIfRuleForImapField()],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->isImapConnectionType() && ! (new RequirementsChecker)->passes('imap')) {
                abort(409, 'In order to use IMAP account type, you will need to enable the PHP extension "imap".');
            }

            if ($this->isMethod('POST') && $this->isSharedAccountRequest() && ! $this->user()->isSuperAdmin()) {
                abort(403, 'Only super administrators can create shared email accounts.');
            }
        });
    }

    /**
     * Get the email field validation rules
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function getEmailFieldRules(): array
    {
        return [
            'email',
            'max:191',
            Rule::requiredIf(function () {
                // Not required as the email can't be updated once
                // the account is created
                if ($this->isMethod('PUT')) {
                    return false;
                }

                return $this->isImapConnectionType();
            }),
            UniqueRule::make(EmailAccount::class, 'account'),
        ];
    }

    /**
     * Get the form_name_header field rule
     *
     * NOTE: from_name_header field is only for shared email accounts
     */
    protected function getFromNameHeaderRules(): RequiredIf
    {
        return Rule::requiredIf(function () {
            if ($this->isMethod('POST') && $this->isSharedAccountRequest()) {
                return true;
            } elseif ($this->isMethod('PUT')) {
                $account = EmailAccount::find($this->route('account'));

                return $account->isShared();
            }

            return false;
        });
    }

    /**
     * Get the intial_sync_period field rule
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function getInitialSyncFromRules(): array
    {
        return [
            'date',
            function (string $attribute, mixed $value, Closure $fail) {
                if ($this->isMethod('PUT')) {
                    return;
                }
                // API Usage
                if ($value && Carbon::parse($value)->diffInMonths() > 6) {
                    $fail('The initial synchronization date must not be older then 6 months.');
                }
            },
            Rule::requiredIf($this->isMethod('POST')),
        ];
    }

    /**
     * Get the requiredIf rule for the IMAP connection type fields.
     */
    protected function getRequiredIfRuleForImapField(): RequiredIf
    {
        return Rule::requiredIf($this->isImapConnectionType());
    }

    /**
     * Check whether the account uses IMAP connection.
     */
    protected function isImapConnectionType(): bool
    {
        return $this->connection_type === ConnectionType::Imap->value;
    }

    /**
     * Check whether the request is for creation shared account.
     */
    protected function isSharedAccountRequest(): bool
    {
        return is_null($this->user_id);
    }
}
