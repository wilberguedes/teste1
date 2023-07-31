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

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PreventPasswordReset;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Modules\Core\Facades\ReCaptcha;
use Modules\Core\Rules\ValidRecaptchaRule;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */
    use SendsPasswordResetEmails;

    /**
     * Initialize new ForgotPasswordController instance.
     */
    public function __construct()
    {
        $this->middleware(PreventPasswordReset::class);
    }

    /**
     * Validate the email for the given request.
     */
    protected function validateEmail(Request $request): void
    {
        $request->validate([
            'email' => 'required|email',
            ...ReCaptcha::shouldShow() ? ['g-recaptcha-response' => ['required', new ValidRecaptchaRule]] : [],
        ]);
    }
}
