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

namespace Modules\Documents\Cards;

use Illuminate\Http\Request;
use Modules\Core\Charts\Progression;
use Modules\Documents\Criteria\ViewAuthorizedDocumentsCriteria;
use Modules\Documents\Models\Document;
use Modules\Users\Criteria\QueriesByUserCriteria;

class SentDocumentsByDay extends Progression
{
    /**
     * Calculates sent documents by day
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $query = Document::criteria(ViewAuthorizedDocumentsCriteria::class);

        if ($filterByUser = $this->getUser()) {
            $query->criteria(new QueriesByUserCriteria($filterByUser));
        }

        return $this->countByDays($request, $query, 'original_date_sent');
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
        ];
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('documents::document.cards.sent_by_day');
    }

    /**
     * Get the user for the card query
     */
    protected function getUser(): ?int
    {
        if ($this->canViewOtherUsersCardData()) {
            return request()->filled('user_id') ? request()->integer('user_id') : null;
        }

        return null;
    }

    public function canViewOtherUsersCardData(): bool
    {
        return request()->user()->canAny(['view all documents', 'view team documents']);
    }
}
