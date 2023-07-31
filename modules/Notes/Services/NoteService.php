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

namespace Modules\Notes\Services;

use Modules\Activities\Concerns\CreatesFollowUpTask;
use Modules\Core\Contracts\Services\CreateService;
use Modules\Core\Contracts\Services\Service;
use Modules\Core\Contracts\Services\UpdateService;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Models\Model;
use Modules\Notes\Models\Note;
use Modules\Users\Mention\PendingMention;

class NoteService implements Service, CreateService, UpdateService
{
    use CreatesFollowUpTask;

    public function create(array $attributes): Note
    {
        $mention = new PendingMention($attributes['body']);
        $attributes['body'] = $mention->getUpdatedText();

        $model = Note::create($attributes);

        $this->notifyMentionedUsers(
            $mention,
            $model,
            $attributes['via_resource'],
            $attributes['via_resource_id']
        );

        // Handle create follow up task
        if ($this->shouldCreateFollowUpTask($attributes)) {
            $this->createFollowUpTask(
                $attributes['task_date'],
                $attributes['via_resource'],
                $attributes['via_resource_id'],
                ['note' => __('notes::note.follow_up_task_body', [
                    'content' => $model->body,
                ])]
            );
        }

        return $model;
    }

    public function update(Model $model, array $attributes): Note
    {
        $mention = new PendingMention($attributes['body']);
        $attributes['body'] = $mention->getUpdatedText();

        $model->fill($attributes)->save();

        $this->notifyMentionedUsers(
            $mention,
            $model,
            $attributes['via_resource'],
            $attributes['via_resource_id']
        );

        return $model;
    }

    /**
     * Notify the mentioned users for the given mention
     */
    protected function notifyMentionedUsers(
        PendingMention $mention,
        Note $note,
        string $viaResource,
        string $viaResourceId,
    ): void {
        $intermediate = Innoclapps::resourceByName($viaResource)->newModel()->find($viaResourceId);

        $mention->setUrl($intermediate->path)->withUrlQueryParameter([
            'section' => $note->resource()->name(),
            'resourceId' => $note->getKey(),
        ])->notify();
    }
}
