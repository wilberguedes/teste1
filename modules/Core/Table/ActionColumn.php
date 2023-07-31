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

namespace Modules\Core\Table;

class ActionColumn extends Column
{
    /**
     * This column is not sortable
     */
    public bool $sortable = false;

    /**
     * Indicates whether the column can be customized.
     */
    public bool $customizeable = false;

    /**
     * Initialize new ActionColumn instance.
     */
    public function __construct(?string $label = null, string $attribute = 'actions')
    {
        // Set the attribute to null to prevent showing on re-order table options
        parent::__construct($attribute, $label);

        $this->width('150px');
    }
}
