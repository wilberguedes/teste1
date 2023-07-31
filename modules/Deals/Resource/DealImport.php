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

use Modules\Core\Resource\Import\Import;

class DealImport extends Import
{
    /**
     * Map single rows keys with the actual field attributes
     *
     * @see mapRowsKeysWithActualFieldAttribute
     *
     * @param  array  $row
     */
    public function map($row): array
    {
        if (request()->missing('pipeline_id')) {
            throw new \LogicException('Pipeline ID must be provided.');
        }

        $row = parent::map($row);

        $row['pipeline_id'] = request()->integer('pipeline_id');

        return $row;
    }
}
