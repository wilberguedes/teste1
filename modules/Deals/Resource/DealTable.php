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

namespace Modules\Deals\Resource;

use Modules\Core\Table\LengthAwarePaginator;
use Modules\Core\Table\Table;
use Modules\Deals\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\Models\Deal;
use Modules\Deals\Services\SummaryService;

class DealTable extends Table
{
    /**
     * Additional database columns to select for the table query.
     */
    protected array $select = [
        'user_id', // user_id is for the policy checks
        'expected_close_date', // falls_behind_expected_close_date check
        'status', // falls_behind_expected_close_date check
    ];

    /**
     * Attributes to be appended with the response.
     */
    protected array $appends = [
        'falls_behind_expected_close_date', // row class
    ];

    /**
     * Whether the table columns can be customized.
     */
    public bool $customizeable = true;

    /**
     * Tap the response
     */
    protected function tapResponse(LengthAwarePaginator $response): void
    {
        $query = Deal::criteria([
            $this->createTableRequestCriteria(),
            $this->createFilterRulesCriteria(),
            ViewAuthorizedDealsCriteria::class,
        ]);

        $summary = (new SummaryService())->calculate($query);

        $response->merge(['summary' => [
            'count' => $summary->sum('count'),
            'value' => $summary->sum('value'),
            'weighted_value' => $summary->sum('weighted_value'),
        ]]);
    }

    /**
     * Boot table
     */
    public function boot(): void
    {
        $this->orderBy('created_at', 'desc');
    }
}
