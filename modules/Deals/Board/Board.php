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

namespace Modules\Deals\Board;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Core\Contracts\Criteria\QueryCriteria;
use Modules\Core\Criteria\FilterRulesCriteria;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Resource\Resource;
use Modules\Deals\Models\Stage;
use Modules\Deals\Services\SummaryService;

class Board
{
    /**
     * @var string
     */
    const RESOURCE_NAME = 'deals';

    /**
     * @var string
     */
    const FILTERS_VIEW = 'deals-board';

    /**
     * Optimize query by selecting fewer columns
     */
    protected array $columns = [
        'id',
        'stage_id',
        'swatch_color',
        'user_id',
        'name',
        'expected_close_date',
        'amount',
        'status',
    ];

    /**
     * The total number of deals to load per stage
     */
    public static int $perPage = 50;

    /**
     * Initialize new Board instance.
     */
    public function __construct(protected Request $request)
    {
    }

    /**
     * Get the board data
     */
    public function data(Builder $query, int $pipelineId, array $pages = []): EloquentCollection
    {
        $pages = array_map('intval', $pages);

        $stages = Stage::where('pipeline_id', $pipelineId)->get();
        $summary = $this->summary($query, $pipelineId);

        return $stages->map(function (Stage $stage) use ($query, $summary, $pages) {
            $deals = $this->getDealsForStage($query, $stage->getKey(), $pages[$stage->getKey()] ?? null);

            $stage->setAttribute('deals', $deals);
            $stage->setAttribute('summary', $summary[$stage->getKey()]);

            return $stage;
        });
    }

    /**
     * Load more details for the given stage
     */
    public function load(Builder $query, int $stageId): Stage
    {
        $stage = Stage::find($stageId);

        $stage->setAttribute('deals', $this->getDealsForStage($query, $stageId));

        return $stage;
    }

    protected function getDealsForStage(Builder $baseQuery, int $stageId, ?int $loadTillPage = null): EloquentCollection
    {
        $count = ['incompleteActivitiesForUser as incomplete_activities_for_user_count'];
        $filtersCriteria = $this->createFiltersCriteria();

        $query = $baseQuery->clone()
            ->select($this->columns)
            ->where('stage_id', $stageId)
            ->withCount($count)
            ->criteria($filtersCriteria);

        $deals = new EloquentCollection(
            $query->paginate(static::$perPage)->items()
        );

        // For refresh, to keep old deals in place
        if ($loadTillPage) {
            $deals = $deals->merge(
                $baseQuery->clone()
                    ->select($this->columns)
                    ->where('stage_id', $stageId)
                    ->whereNotIn('id', $deals->modelKeys())
                    ->withCount($count)
                    ->criteria($filtersCriteria)
                    ->limit(($loadTillPage * static::$perPage) - count($deals->modelKeys()))
                    ->get()
            );
        }

        return $deals;
    }

    /**
     * Updates the board
     */
    public function update(array $data): void
    {
        $updater = new BoardUpdater($data, $this->request->user());

        $updater->perform();
    }

    /**
     * Get the summary for the board
     */
    public function summary(Builder $query, int $pipelineId): Collection
    {
        return (new SummaryService())->calculate(
            $query->clone()->criteria($this->createFiltersCriteria()),
            $pipelineId
        );
    }

    /**
     * Create the criteria instance for the filters
     */
    protected function createFiltersCriteria(): QueryCriteria
    {
        $resource = $this->getResource();

        $criteria = new FilterRulesCriteria(
            $this->request->get('rules'),
            $resource->filtersForResource(
                app(ResourceRequest::class)->setResource($resource->name())
            ),
            $this->request
        );

        return $criteria->setIdentifier($resource->name())->setView(static::FILTERS_VIEW);
    }

    /**
     * Get the deals resource instance
     */
    protected function getResource(): Resource
    {
        return Innoclapps::resourceByName(static::RESOURCE_NAME);
    }
}
