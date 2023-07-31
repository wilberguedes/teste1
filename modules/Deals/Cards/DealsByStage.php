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

namespace Modules\Deals\Cards;

use Illuminate\Http\Request;
use Modules\Deals\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\Models\Deal;

class DealsByStage extends DealPresentationCard
{
    /**
     * The default renge/period selected
     *
     * @var int
     */
    public string|int|null $defaultRange = 30;

    /**
     * Calculate the deals by stage
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = Deal::open()
            ->criteria(ViewAuthorizedDealsCriteria::class)
            ->where('pipeline_id', $this->getPipeline($request));

        return $this->withStageLabels(
            $this->byDays('created_at')->count(
                $request,
                $query,
                'stage_id'
            )
        );
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('deals::deal.cards.by_stage');
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
}
