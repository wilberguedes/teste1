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

namespace Modules\Notes\Tests\Unit;

use Illuminate\Support\Facades\Notification;
use Modules\Contacts\Models\Contact;
use Modules\Notes\Models\Note;
use Modules\Notes\Services\NoteService;
use Modules\Users\Notifications\UserMentioned;
use Modules\Users\Tests\Concerns\TestsMentions;
use Tests\TestCase;

class NoteServiceTest extends TestCase
{
    use TestsMentions;

    public function test_it_send_notifications_to_mentioned_users_when_note_is_created()
    {
        $user = $this->signIn();

        $mentionUser = $this->createUser();
        $contact = Contact::factory()->create();

        $attributes = array_merge(Note::factory()->for($user)->make()->toArray(), [
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
            'body' => 'Other Text - '.$this->mentionText($mentionUser->id, $mentionUser->name),
        ]);

        Notification::fake();

        $note = (new NoteService())->create($attributes);

        Notification::assertSentTo($mentionUser, UserMentioned::class, function ($notification) use ($contact, $note) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=notes&resourceId={$note->id}";
        });
    }

    public function test_it_send_notifications_to_mentioned_users_when_note_is_updated()
    {
        $user = $this->signIn();

        $mentionUser = $this->createUser();
        $note = Note::factory()->for($user)->create();
        $contact = Contact::factory()->create();

        $attributes = [
            'body' => $note->body.$this->mentionText($mentionUser->id, $mentionUser->name),
            'via_resource' => 'contacts',
            'via_resource_id' => $contact->id,
        ];

        Notification::fake();

        (new NoteService())->update($note, $attributes);

        Notification::assertSentTo($mentionUser, UserMentioned::class, function ($notification) use ($contact, $note) {
            return $notification->mentionUrl === "/contacts/{$contact->id}?section=notes&resourceId={$note->id}";
        });
    }
}
