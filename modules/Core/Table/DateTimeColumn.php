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

use Modules\Core\Facades\Format;

class DateTimeColumn extends Column
{
    /**
     * Initialize new DateTimeColumn instance.
     */
    public function __construct(...$params)
    {
        parent::__construct(...$params);

        $this->displayAs(fn ($model) => Format::dateTime($model->{$this->attribute}));
    }
}
