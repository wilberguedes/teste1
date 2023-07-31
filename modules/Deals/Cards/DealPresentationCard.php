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

use Illuminate\Support\Facades\Request;
use Modules\Core\Charts\Presentation;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Models\Stage;

abstract class DealPresentationCard extends Presentation
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $stages;

    /**
     * Add stages labels to the result
     *
     * @param  \Modules\Core\Charts\ChartResult  $result
     * @return \Modules\Core\Charts\ChartResult
     */
    protected function withStageLabels($result)
    {
        return $result->label(
            fn ($value) => $this->stages()->find($value)->name
        );
    }

    /**
     * Get the deals pipeline for the card
     *
     * @param  \Illuminate\Http\Request  $request
     */
    protected function getPipeline($request): int
    {
        return ! $request->filled('pipeline_id') ?
                Pipeline::visible()->userOrdered()->first()->getKey() :
                 $request->integer('pipeline_id');
    }

    /**
     * Get all available stages
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function stages()
    {
        return $this->stages ??= Stage::select(['id', 'name'])->get();
    }

    /**
     * The element's component.
     */
    public function component(): string
    {
        return 'deal-presentation-card';
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'pipeline_id' => $this->getPipeline(Request::instance()),
        ]);
    }
}
