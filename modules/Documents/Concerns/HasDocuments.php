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

namespace Modules\Documents\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Deals\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Documents\Enums\DocumentStatus;

/** @mixin \Modules\Core\Models\Model */
trait HasDocuments
{
    /**
     * Get all of the associated documents for the contact.
     */
    public function documents(): MorphToMany
    {
        return $this->morphToMany(\Modules\Documents\Models\Document::class, 'documentable');
    }

    /**
     * Get the documents the user is authorized to see
     */
    public function documentsForUser(): MorphToMany
    {
        return $this->documents()->criteria(ViewAuthorizedDealsCriteria::class);
    }

    /**
     * Get the draft documents the user is authorized to see
     */
    public function draftDocumentsForUser(): MorphToMany
    {
        return $this->documentsForUser()->where('status', DocumentStatus::DRAFT);
    }
}
