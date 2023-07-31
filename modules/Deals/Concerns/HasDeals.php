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

namespace Modules\Deals\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Deals\Criteria\ViewAuthorizedDealsCriteria;

/** @mixin \Modules\Core\Models\Model */
trait HasDeals
{
    /**
     * Get all of the deals that are associated with the model
     */
    public function deals(): MorphToMany
    {
        return $this->morphToMany(\Modules\Deals\Models\Deal::class, 'dealable');
    }

    /**
     * Initiate query for only deals the user is authorized to see
     */
    public function authorizedDeals(): MorphToMany
    {
        return $this->deals()->criteria(ViewAuthorizedDealsCriteria::class);
    }

    /**
     * Get all of the model open deals that the current logged-in user is authorized to see
     */
    public function authorizedOpenDeals(): MorphToMany
    {
        return $this->authorizedDeals()->open();
    }

    /**
     * Get all of the model won deals that the current logged-in user is authorized to see
     */
    public function authorizedWonDeals(): MorphToMany
    {
        return $this->authorizedDeals()->won();
    }

    /**
     * Get all of the model closed deals that the current logged-in user is authorized to see
     */
    public function authorizedClosedDeals(): MorphToMany
    {
        return $this->authorizedDeals()->closed();
    }

    /**
     * Get all of the model lost deals that the current logged-in user is authorized to see
     */
    public function authorizedLostDeals(): MorphToMany
    {
        return $this->authorizedDeals()->lost();
    }
}
