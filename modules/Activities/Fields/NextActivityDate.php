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

namespace Modules\Activities\Fields;

use Illuminate\Database\Eloquent\Builder;
use Modules\Activities\Models\Activity;
use Modules\Core\Facades\Format;
use Modules\Core\Fields\BelongsTo;
use Modules\Core\Table\BelongsToColumn;

class NextActivityDate extends BelongsTo
{
    /**
     * Initialize new NextActivityDate instance
     */
    public function __construct()
    {
        parent::__construct(
            'nextActivity',
            Activity::class,
            __('activities::activity.next_activity_date'),
            'next_activity_date'
        );

        $this->exceptOnForms()
            ->excludeFromImport()
            ->help(__('activities::activity.next_activity_date_info'))
            ->hidden();

        $this->resolveUsing(function ($model) {
            if ($model->relationLoaded('nextActivity')) {
                return $model->nextActivity?->full_due_date;
            }
        });

        $this->displayUsing(function ($model) {
            if ($model->relationLoaded('nextActivity') && $model->nextActivity) {
                return Format::separateDateAndTime(
                    $model->nextActivity->due_date,
                    $model->nextActivity->due_time
                );
            }
        });

        $this->tapIndexColumn(function (BelongsToColumn $column) {
            $column->orderByUsing(function (Builder $query, string $attribute, string $direction) {
                return $query->orderBy(Activity::dueDateQueryExpression(), $direction);
            })
                ->select(['due_time'])
                ->queryAs(Activity::dueDateQueryExpression('next_activity_date'))
                ->displayAs(function ($model) {
                    if ($model->nextActivity) {
                        $method = $model->nextActivity->due_time ? 'dateTime' : 'date';

                        return Format::$method($model->nextActivity->next_activity_date);
                    }

                    return '--';
                });
        });
    }
}
