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

namespace Modules\Core\Contracts\Fields;

interface HandlesChangedMorphManyAttributes
{
    /**
     * Handle the attributes updated event
     */
    public function morphManyAtributesUpdated(string $relationName, array $new, array $old): void;
}
