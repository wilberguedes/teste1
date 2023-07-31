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

namespace Modules\Activities\Workflow\Actions;

use Modules\Activities\Models\Activity;
use Modules\Activities\Models\ActivityType;
use Modules\Activities\Services\ActivityService;
use Modules\Core\Fields\Editor;
use Modules\Core\Fields\Select;
use Modules\Core\Fields\Text;
use Modules\Core\Workflow\Action;
use Modules\Users\Models\User;

class CreateActivityAction extends Action
{
    /**
     * Indicates whether to add dynamic assignees in the assigned options
     */
    protected bool $withDynamicUsers = true;

    protected ActivityService $service;

    /**
     * Initialize CreateActivityAction
     */
    public function __construct()
    {
        $this->service = new ActivityService();
    }

    /**
     * Action name
     */
    public static function name(): string
    {
        return __('activities::activity.workflows.actions.create');
    }

    /**
     * Run the trigger
     *
     * @return \Modules\Activities\Models\Activity|null
     */
    public function run()
    {
        $activity = $this->createActivity();

        if ($activity && $this->viaModelTrigger()) {
            $activity->{$this->resource->associateableName()}()->sync($this->model->id);
        }

        return $activity;
    }

    /**
     * Action available fields
     */
    public function fields(): array
    {
        return [
            $this->getDueDateField(),
            $this->getUserField(),
            $this->getActivityTypeField(),

            Text::make('activity_title')->withMeta([
                'attributes' => [
                    'placeholder' => __('activities::activity.workflows.fields.create.title'),
                ],
            ])->rules('required'),

            Editor::make('note')->withMeta([
                'attributes' => [
                    'placeholder' => __('activities::activity.workflows.fields.create.note'),
                    'with-image' => false,
                ],
            ]),
        ];
    }

    /**
     * Get the dynamic users
     *
     * @return array
     */
    protected function getDynamicUsers()
    {
        return $this->withDynamicUsers === false ? [] : [
            [
                'value' => 'owner',
                'label' => __('core::workflow.fields.for_owner'),
            ],
        ];
    }

    /**
     * Create the activity for the action
     *
     * @return \Modules\Activities\Models\Activity
     */
    protected function createActivity()
    {
        return Activity::unguarded(function () {
            $dueDate = $this->getDueDate();

            // E.q. user selected to assign activity to deal owner (is optional)
            // But when deal owner is not specified, no activity will be created
            if (! $owner = $this->getOwner()) {
                return;
            }

            return $this->service->create([
                'title' => $this->activity_title,
                'note' => $this->note,
                'activity_type_id' => $this->activity_type_id,
                'user_id' => $owner,
                'due_date' => $dueDate->format('Y-m-d'),
                'due_time' => $this->due_date === 'now' ? $dueDate->format('H:i').':00' : null,
                'end_date' => $dueDate->format('Y-m-d'),
                'reminder_minutes_before' => config('activities.defaults.reminder_minutes'),
                'created_by' => $this->workflow->created_by,
                // We will add few seconds to ensure that it's properly sorted in the activity tabs
                // and the created activity is always at the bottom
                'created_at' => now()->addSecond(3),
            ]);
        });
    }

    /**
     * Add dynamic users incude flag
     */
    public function withoutDynamicUsers(bool $value = true): static
    {
        $this->withDynamicUsers = $value === false;

        return $this;
    }

    /**
     * Get the new activity owner
     */
    protected function getOwner(): ?int
    {
        return match ($this->user_id) {
            'owner' => $this->model->user_id,
            default => $this->user_id,
        };
    }

    /**
     * Get the new activity due date
     *
     * @return \Modules\Core\Date\Carbon
     */
    protected function getDueDate()
    {
        $now = now();

        return match ($this->due_date) {
            'in_1_day' => $now->addDays(1),
            'in_2_days' => $now->addDays(2),
            'in_3_days' => $now->addDays(3),
            'in_4_days' => $now->addDays(4),
            'in_5_days' => $now->addDays(5),
            default => $now,
        };
    }

    /**
     * Get the user field
     *
     * @return \Modules\Core\Fields\Select
     */
    protected function getUserField()
    {
        return Select::make('user_id')->options(function () {
            return collect($this->getDynamicUsers())
                ->merge(User::get()->map(fn (User $user) => [
                    'value' => $user->id,
                    'label' => $user->name,
                ]));
        })
            ->withDefaultValue('owner')
            ->rules('required');
    }

    /**
     * Get the activity type field
     *
     * @return \Modules\Core\Fields\Select
     */
    protected function getActivityTypeField()
    {
        return Select::make('activity_type_id')->options(function () {
            return ActivityType::orderBy('name')
                ->get()
                ->map(fn ($type) => [
                    'value' => $type->id,
                    'label' => $type->name,
                ])->all();
        })
            ->label(null)
            ->withDefaultValue(
                fn () => ActivityType::findByFlag('task')->getKey()
            )
            ->rules('required');
    }

    /**
     * Get the due date field
     *
     * @return \Modules\Core\Fields\Select
     */
    protected function getDueDateField()
    {
        return Select::make('due_date')->options([
            'now' => __('core::workflow.fields.dates.now'),
            'in_1_day' => __('core::workflow.fields.dates.in_1_day'),
            'in_2_days' => __('core::workflow.fields.dates.in_2_days'),
            'in_3_days' => __('core::workflow.fields.dates.in_3_days'),
            'in_4_days' => __('core::workflow.fields.dates.in_4_days'),
            'in_5_days' => __('core::workflow.fields.dates.in_5_days'),
        ])
            ->withDefaultValue('now')
            ->withMeta(['attributes' => ['clearable' => false, 'placeholder' => __('core::workflow.fields.dates.due_at')]])
            ->rules('required');
    }
}
