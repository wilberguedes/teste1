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

namespace Modules\Activities\Resource;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Activities\Fields\ActivityDueDate;
use Modules\Activities\Fields\ActivityEndDate;
use Modules\Activities\Fields\ActivityType as ActivityTypeField;
use Modules\Activities\Fields\GuestsSelect;
use Modules\Activities\Models\ActivityType;
use Modules\Contacts\Fields\Companies;
use Modules\Contacts\Fields\Contacts;
use Modules\Core\Date\Carbon;
use Modules\Core\Fields\BelongsTo;
use Modules\Core\Fields\DateTime;
use Modules\Core\Fields\Editor;
use Modules\Core\Fields\IntroductionField;
use Modules\Core\Fields\Reminder;
use Modules\Core\Fields\Text;
use Modules\Core\Fields\User;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Table\BelongsToColumn;
use Modules\Core\Table\Column;
use Modules\Core\Table\DateTimeColumn;
use Modules\Deals\Fields\Deals;
use Modules\Users\Models\User as UserModel;

class ActivityFields
{
    /**
     * Provides the activity resource available fields.
     */
    public function __invoke(Activity $instance, Request $request): array
    {
        return [
            Text::make('title', __('activities::activity.title'))
                ->primary()
                ->withMeta(['attributes' => ['placeholder' => __('activities::activity.title')]])
                ->tapIndexColumn(fn (Column $column) => $column->width('400px')->minWidth('340px'))
                ->creationRules(['required', 'string'])
                ->updateRules(['filled', 'string'])
                ->rules('max:191')
                ->required(true),

            ActivityTypeField::make()
                ->primary()
                ->rules('filled')
                ->required(is_null(ActivityType::getDefaultType()))
                ->creationRules(Rule::requiredIf(is_null(ActivityType::getDefaultType()))),

            ActivityDueDate::make(__('activities::activity.due_date'))
                ->tapIndexColumn(fn (Column $column) => $column->queryWhenHidden()) // for row class
                ->colClass('col-span-12 sm:col-span-6')
                ->rules('required_with:due_time')
                ->creationRules('required')
                ->required(true)
                ->updateRules(['required_with:end_date', 'required_with:end_time', 'filled']),

            ActivityEndDate::make(__('activities::activity.end_date'))
                ->rules(['required_with:end_time', 'filled'])
                ->updateRules(['required_with:due_date', 'required_with:due_time'])
                ->colClass('col-span-12 sm:col-span-6')
                ->hideFromIndex(),

            Reminder::make('reminder_minutes_before', __('activities::activity.reminder').($request->isZapier() ? ' (minutes before due)' : ''))
                ->withDefaultValue(30)
                ->help($instance->resource?->is_reminded ? __('activities::activity.reminder_update_info') : null)
                ->strictlyForForms()
                // Max is 40320 minutes, 4 weeks, as Google events max is 4 weeks
                ->rules('regex:/^[0-9]+$|(\d{4})-(\d{1,2})-(\d{1,2})\s(\d{1,2}):/', 'not_in:0', 'max:40320')
                ->provideSampleValueUsing(fn () => config('activities.defaults.reminder_minutes'))
                ->importUsing(function ($value, $row, $original, $field) {
                    // NOTE: The reminder field must be always after the due date because
                    // we use the due date to create \Carbon\Carbon instance
                    // We will check if the actual reminder field is provided as date
                    // if it's date, we will convert the difference between the due date and the
                    // provided date to determine the actual minutes
                    // Matches: Y-m-d H:
                    if (! is_null($value) && preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})\s(\d{1,2}):/', $value)) {
                        $reminderAt = Carbon::parse($value);
                        $dueDate = Carbon::parse($row['due_date'].($row['due_time'] ? ' '.$row['due_time'] : ''));
                        $value = $reminderAt->isPast() ? null : $dueDate->diffInMinutes($reminderAt);
                    }

                    return [$field->attribute => $value];
                })
                ->cancelable(),

            User::make(__('activities::activity.owner'))
                ->primary()
                ->acceptLabelAsValue(false)
                ->tapIndexColumn(fn (BelongsToColumn $column) => $column->primary(false)
                    ->select('avatar')
                    ->appends('avatar_url')
                    ->useComponent('table-data-avatar-column')
                )
                ->withMeta(['attributes' => ['clearable' => false]])
                ->creationRules('required')
                ->updateRules('filled')
                ->required(true)
                ->notification(\Modules\Activities\Notifications\UserAssignedToActivity::class)
                ->trackChangeDate('owner_assigned_date'),

            GuestsSelect::make('guests', __('activities::activity.guests'))
                ->excludeFromImport()
                ->withMeta(['activity' => value(function () use ($instance) {
                    return $instance->resource ? $instance->createJsonResource(
                        $instance->resource->loadMissing(['guests', 'guests.guestable'])
                    ) : null;
                })])
                ->toggleable()
                ->strictlyForForms()
                ->excludeFromExport()
                ->rules('nullable', 'array'),

            Editor::make('description', __('activities::activity.description'))
                ->help(__('activities::activity.description_info'))
                ->rules(['nullable', 'string'])
                ->helpDisplay('text')
                ->toggleable()
                ->strictlyForForms(),

            DateTime::make('owner_assigned_date', __('activities::activity.owner_assigned_date'))
                ->strictlyForIndex()
                ->excludeFromImport()
                ->hidden(),

            Editor::make('note', __('activities::activity.note'))
                ->withMeta([
                    'attributes' => [
                        'with-mention' => true,
                    ],
                ])
                ->help(__('activities::activity.note_info'))
                ->helpDisplay('text')
                ->hideFromIndex()
                ->rules(['nullable', 'string'])
                ->tapIndexColumn(fn (Column $column) => $column->asHtml()),

            BelongsTo::make('creator', UserModel::class, __('core::app.created_by'))
                ->excludeFromImport()
                ->strictlyForIndex()
                ->tapIndexColumn(fn (BelongsToColumn $column) => $column->minWidth('100px')
                    ->select('avatar')
                    ->appends('avatar_url')
                    ->useComponent('table-data-avatar-column')
                )
                ->hidden(),

            IntroductionField::make(__('core::resource.associate_with_records'))
                ->excludeFromUpdate(fn () => ! app(ResourceRequest::class)->viaResource())
                ->excludeFromCreate(fn () => ! app(ResourceRequest::class)->viaResource())
                ->titleIcon('Link'),

            Contacts::make()
                ->hideFromIndex()
                ->exceptOnForms(fn () => ! app(ResourceRequest::class)->viaResource())
                ->excludeFromSettings(),

            Companies::make()
                ->hideFromIndex()
                ->exceptOnForms(fn () => ! app(ResourceRequest::class)->viaResource())
                ->excludeFromSettings(),

            Deals::make()
                ->hideFromIndex()
                ->excludeFromIndex()
                ->exceptOnForms(fn () => ! app(ResourceRequest::class)->viaResource())
                ->excludeFromSettings(),

            DateTime::make('reminded_at', __('activities::activity.reminder_sent_date'))
                ->strictlyForIndex()
                ->excludeFromImport()
                ->hidden(),

            DateTime::make('completed_at', __('activities::activity.completed_at'))
                ->tapIndexColumn(fn (DateTimeColumn $column) => $column->queryWhenHidden())
                ->strictlyForIndex()
                ->excludeFromImport()
                ->hidden(),

            DateTime::make('updated_at', __('core::app.updated_at'))
                ->excludeFromImportSample()
                ->strictlyForIndex()
                ->hidden(),

            DateTime::make('created_at', __('core::app.created_at'))
                ->excludeFromImportSample()
                ->strictlyForIndex(),
        ];
    }
}
