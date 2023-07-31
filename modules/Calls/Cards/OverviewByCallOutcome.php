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

namespace Modules\Calls\Cards;

use Illuminate\Http\Request;
use Modules\Calls\Models\Call;
use Modules\Calls\Models\CallOutcome;
use Modules\Core\Charts\Presentation;
use Modules\Core\Criteria\RelatedCriteria;
use Modules\Users\Criteria\QueriesByUserCriteria;

class OverviewByCallOutcome extends Presentation
{
    /**
     * The default renge/period selected
     *
     * @var int
     */
    public string|int|null $defaultRange = 30;

    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $outcomes;

    /**
     * Calculated overview by call outcome
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = (new Call)->newQuery();

        if ($request->user()->isSuperAdmin() && $request->filled('user_id')) {
            $query->criteria(new QueriesByUserCriteria($request->integer('user_id')));
        } else {
            $query->criteria(RelatedCriteria::class);
        }

        return $this->byDays('date')
            ->count($request, $query, 'call_outcome_id')
            ->label(function ($value) {
                return $this->outcomes()->find($value)->name;
            })->colors($this->outcomes()->mapWithKeys(function (CallOutcome $outcome) {
                return [$outcome->name => $outcome->swatch_color];
            })->all());
    }

    /**
     * Get the ranges available for the chart.
     */
    public function ranges(): array
    {
        return [
            7 => __('core::dates.periods.7_days'),
            15 => __('core::dates.periods.15_days'),
            30 => __('core::dates.periods.30_days'),
            60 => __('core::dates.periods.60_days'),
            90 => __('core::dates.periods.90_days'),
            365 => __('core::dates.periods.365_days'),
        ];
    }

    /**
     * Get all available outcomes
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function outcomes()
    {
        if (! $this->outcomes) {
            $this->outcomes = CallOutcome::select(
                ['id', 'name', 'swatch_color']
            )->get();
        }

        return $this->outcomes;
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('calls::call.cards.outcome_overview');
    }
}
