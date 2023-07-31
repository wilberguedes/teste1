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

namespace Modules\Activities\Cards;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Modules\Activities\Models\Activity;
use Modules\Core\Card\TableAsyncCard;
use Modules\Core\Date\Carbon;

class MyActivities extends TableAsyncCard
{
    /**
     * Default sort field
     */
    protected Expression|string|null $sortBy = null;

    /**
     * Provide the query that will be used to retrieve the items.
     */
    public function query(): Builder
    {
        return Activity::with('type')
            ->incomplete()
            ->where('user_id', Auth::id())
            ->orderBy(Activity::dueDateQueryExpression());
    }

    /**
     * Provide the table fields
     */
    public function fields(): array
    {
        return [
            ['key' => 'title', 'label' => __('activities::activity.title'), 'sortable' => true],
            ['key' => 'due_date', 'label' => __('activities::activity.due_date'), 'sortable' => true],
            ['key' => 'type.name', 'label' => __('activities::activity.type.type'), 'sortable' => false, 'select' => false],
        ];
    }

    /**
     * Get the columns that should be selected in the query
     */
    protected function selectColumns(): array
    {
        return array_merge(
            parent::selectColumns(),
            // select the due time for full_due_date column, user_id is for authorization
            ['activity_type_id', 'due_time', 'user_id']
        );
    }

    /**
     * Map the given model into a row
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    protected function mapRow($model)
    {
        $row = parent::mapRow($model);

        // Remove the due time as we are using only to query and create the full_due_date attribute
        Arr::forget($row, 'due_time');

        return array_merge(
            $row,
            [
                'type' => [
                    'swatch_color' => $model->type->swatch_color,
                    'icon' => $model->type->icon,
                ],
                'is_completed' => $model->is_completed,
                'due_date' => $model->due_time ? Carbon::parse($model->full_due_date) : $model->due_date,
                'tdClass' => $model->isDue ? 'due' : 'not-due',
            ]
        );
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('activities::activity.cards.my_activities');
    }

    /**
     * Get the component name for the card.
     */
    public function component(): string
    {
        return 'my-activities-card';
    }
}
