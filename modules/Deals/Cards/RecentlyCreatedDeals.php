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

use Modules\Core\Card\TableCard;
use Modules\Core\Date\Carbon;
use Modules\Deals\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\Models\Deal;

class RecentlyCreatedDeals extends TableCard
{
    /**
     * Limit the number of records shown in the table
     *
     * @var int
     */
    protected $limit = 20;

    /**
     * Created in the last 30 days
     *
     * @var int
     */
    protected $days = 30;

    /**
     * Provide the table items
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function items(): iterable
    {
        return Deal::select(['id', 'name', 'created_at', 'stage_id'])
            ->criteria(ViewAuthorizedDealsCriteria::class)
            ->with('stage')
            ->where('created_at', '>', Carbon::asCurrentTimezone()->subDays($this->days)->inAppTimezone())
            ->latest()
            ->limit($this->limit)
            ->get()
            ->map(fn (Deal $deal) => [
                'id' => $deal->id,
                'name' => $deal->name,
                'stage' => $deal->stage,
                'created_at' => $deal->created_at,
                'path' => $deal->path,
            ]);
    }

    /**
     * Provide the table fields
     */
    public function fields(): array
    {
        return [
            ['key' => 'name', 'label' => __('deals::fields.deals.name')],
            ['key' => 'stage.name', 'label' => __('deals::fields.deals.stage.name')],
            ['key' => 'created_at', 'label' => __('core::app.created_at')],
        ];
    }

    /**
     * Card title
     */
    public function name(): string
    {
        return __('deals::deal.cards.recently_created');
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'help' => __('deals::deal.cards.recently_created_info', ['total' => $this->limit, 'days' => $this->days]),
        ]);
    }
}
