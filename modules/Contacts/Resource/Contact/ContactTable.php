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

namespace Modules\Contacts\Resource\Contact;

use Modules\Core\Criteria\SearchByFirstNameAndLastNameCriteria;
use Modules\Core\Criteria\TableRequestCriteria;
use Modules\Core\Table\Table;

class ContactTable extends Table
{
    /**
     * Additional database columns to select for the table query.
     */
    protected array $select = [
        'user_id',     // user_id is for the policy checks
        'avatar',      // avatar is for the first column avatar
        'first_name',  // For concat display_name
        'last_name',   // For concat display_name
    ];

    /**
     * Attributes to be appended with the response.
     */
    protected array $appends = ['avatar_url'];

    /**
     * Indicates whether the user can customize columns orders and visibility
     */
    public bool $customizeable = true;

    /**
     * Create new TableRequestCriteria criteria instance
     */
    protected function createTableRequestCriteria(): TableRequestCriteria
    {
        return (new TableRequestCriteria(
            $this->getUserColumns(),
            $this
        ))->appends(fn ($query) => $query->orWhere(function ($query) {
            $query->criteria(SearchByFirstNameAndLastNameCriteria::class);
        }));
    }

    /**
     * Boot table
     */
    public function boot(): void
    {
        $this->orderBy('created_at', 'desc');
    }
}
