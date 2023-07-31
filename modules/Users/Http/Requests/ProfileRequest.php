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

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Rules\UniqueRule;
use Modules\Core\Rules\ValidLocaleRule;
use Modules\Core\Rules\ValidTimezoneCheckRule;
use Modules\Users\Models\User;

class ProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => [
                'required',
                'email',
                'max:191',
                UniqueRule::make(User::class, $this->user()->id),
            ],
            'time_format' => ['required', 'string', Rule::in(config('core.time_formats'))],
            'date_format' => ['required', 'string', Rule::in(config('core.date_formats'))],
            'first_day_of_week' => ['required', 'in:1,6,0', 'numeric'],
            'locale' => ['required', 'string', new ValidLocaleRule],
            'timezone' => ['required', 'string', new ValidTimezoneCheckRule],
        ];
    }
}
