<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Users\Models\User;
use Modules\Users\Notifications\ResetPassword;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    public function test_user_receives_an_email_with_password_reset_link()
    {
        Notification::fake();

        $user = $this->createUser();

        $this->post('/password/email', [
            'email' => $user->email,
        ]);

        $this->assertNotNull($token = DB::table('password_resets')->first());

        Notification::assertSentTo($user, ResetPassword::class, function ($notification, $channels) use ($token) {
            return Hash::check($notification->token, $token->token) === true;
        });
    }

    public function test_user_does_not_receive_email_when_not_exists()
    {
        Notification::fake();

        $response = $this->from('/password/email')->post('/password/email', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect('/password/email');
        $response->assertSessionHasErrors('email');

        Notification::assertNotSentTo(User::factory()->make(['email' => 'nobody@example.com']), ResetPassword::class);
    }

    public function test_email_is_required_to_request_password_reset()
    {
        $response = $this->from('/password/email')->post('/password/email', []);
        $response->assertRedirect('/password/email');
        $response->assertSessionHasErrors('email');
    }

    public function test_it_validate_the_password_reset_email()
    {
        $response = $this->from('/password/email')->post('/password/email', [
            'email' => 'invalid-email',
        ]);

        $response->assertRedirect('/password/email');
        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_can_be_disabled()
    {
        settings()->set('disable_password_forgot', true)->save();

        $this->get('/password/reset')->assertNotFound();
        $this->post('/password/email')->assertNotFound();
    }
}
