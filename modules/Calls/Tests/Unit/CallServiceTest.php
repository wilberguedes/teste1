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

namespace Modules\Calls\Tests\Unit;

use Illuminate\Support\Facades\Notification;
use Modules\Calls\Models\Call;
use Modules\Calls\Services\CallService;
use Modules\Contacts\Models\Contact;
use Modules\Users\Notifications\UserMentioned;
use Modules\Users\Tests\Concerns\TestsMentions;
use Tests\TestCase;

class CallServiceTest extends TestCase
{
    use TestsMentions;

    public function test_it_send_notifications_to_mentioned_users_when_call_is_created()
    {
        $user = $this->signIn();

        $mentionUser = $this->createUser();
        $contact = Contact::factory()->create();

        $attributes = array_merge(Call::factory()->for($user)->make()->toArray(), [
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
            'body' => 'Other Text - '.$this->mentionText($mentionUser->id, $mentionUser->name),
        ]);

        Notification::fake();

        $call = (new CallService())->create($attributes);

        Notification::assertSentTo($mentionUser, UserMentioned::class, function ($notification) use ($contact, $call) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=calls&resourceId={$call->id}";
        });
    }

    public function test_it_send_notifications_to_mentioned_users_when_call_is_updated()
    {
        $user = $this->signIn();

        $mentionUser = $this->createUser();
        $call = Call::factory()->for($user)->create();
        $contact = Contact::factory()->create();

        $attributes = [
            'call_outcome_id' => $call->call_outcome_id,
            'body' => $call->body.$this->mentionText($mentionUser->id, $mentionUser->name),
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ];

        Notification::fake();

        $call = (new CallService())->update($call, $attributes);

        Notification::assertSentTo($mentionUser, UserMentioned::class, function ($notification) use ($contact, $call) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=calls&resourceId={$call->id}";
        });
    }
}
