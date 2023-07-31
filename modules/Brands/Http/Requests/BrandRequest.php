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

namespace Modules\Brands\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Modules\Brands\Models\Brand;
use Modules\Core\Rules\UniqueRule;

class BrandRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                UniqueRule::make(Brand::class, 'brand'),
                'max:191',
            ],
            'display_name' => 'required|string|max:191',
            'is_default' => 'nullable|required|boolean',
            'config.primary_color' => 'required|string|max:7',
            'config.pdf.font' => [
                Rule::requiredIf($this->isMethod('PUT')),
                'string',
                Rule::in(Arr::pluck(config('contentbuilder.fonts'), 'font-family')),
            ],
            'config.pdf.orientation' => [Rule::requiredIf($this->isMethod('PUT')), 'string', 'in:portrait,landscape'],
            'config.pdf.size' => [Rule::requiredIf($this->isMethod('PUT')), 'string', 'in:a4,letter'],
            'config.signature.bound_text' => [function (string $attribute, mixed $value, Closure $fail) {
                if (count(array_filter((array) $value)) === 0) {
                    $fail('validation.required')->translate(['attribute' => __('brands::brand.form.signature.bound_text')]);
                }
            }, ],
            'config.document.mail_subject' => [function (string $attribute, mixed $value, Closure $fail) {
                if (count(array_filter((array) $value)) === 0) {
                    $fail('validation.required')->translate(['attribute' => __('brands::brand.form.document.send.subject')]);
                }
            }, ],
            'config.document.mail_message' => [function (string $attribute, mixed $value, Closure $fail) {
                if (count(array_filter((array) $value)) === 0) {
                    $fail('validation.required')->translate(['attribute' => __('brands::brand.form.document.send.message')]);
                }
            }, ],
            'config.document.mail_button_text' => [function (string $attribute, mixed $value, Closure $fail) {
                if (count(array_filter((array) $value)) === 0) {
                    $fail('validation.required')->translate(['attribute' => __('brands::brand.form.document.send.button_text')]);
                }
            }, ],
            'config.document.signed_mail_subject' => [function (string $attribute, mixed $value, Closure $fail) {
                if (count(array_filter((array) $value)) === 0) {
                    $fail('validation.required')->translate(['attribute' => __('brands::brand.form.document.sign.subject')]);
                }
            }, ],
            'config.document.signed_mail_message' => [function (string $attribute, mixed $value, Closure $fail) {
                if (count(array_filter((array) $value)) === 0) {
                    $fail('validation.required')->translate(['attribute' => __('brands::brand.form.document.sign.message')]);
                }
            }, ],
            'config.document.signed_thankyou_message' => [function (string $attribute, mixed $value, Closure $fail) {
                if (count(array_filter((array) $value)) === 0) {
                    $fail('validation.required')->translate(['attribute' => __('brands::brand.form.document.sign.after_sign_message')]);
                }
            }, ],
            'config.document.accepted_thankyou_message' => [function (string $attribute, mixed $value, Closure $fail) {
                if (count(array_filter((array) $value)) === 0) {
                    $fail('validation.required')->translate(['attribute' => __('brands::brand.form.document.accept.after_accept_message')]);
                }
            }, ],
        ];
    }
}
