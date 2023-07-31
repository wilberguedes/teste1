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

namespace Modules\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Table\Table;

class TableRequestCriteria extends RequestCriteria
{
    /**
     * Initialize new TableRequestCriteria instance.
     */
    public function __construct(protected Collection $columns, protected Table $table)
    {
        parent::__construct();
    }

    /**
     * Apply order for the current request.
     *
     * @param  mixed  $order
     * @return void
     */
    protected function applyOrder($order, Builder $query): Builder
    {
        // No order applied
        if (empty($order)) {
            return $query;
        }

        // Filter any invalid ordering
        $order = collect($order)->reject(
            fn ($order) => empty($order['attribute'])
        );

        // Remove any default order
        if ($order->isNotEmpty()) {
            $query->reorder();
        }

        $order->map(fn ($order) => array_merge($order, [
            'direction' => ($order['direction'] ?? '') ?: 'asc',
        ]))->each(function ($order) use (&$query) {
            $query = $this->table->getColumn($order['attribute'])->orderBy($query, $order);
        });

        return $query;
    }
}
