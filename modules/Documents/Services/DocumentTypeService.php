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

namespace Modules\Documents\Services;

use Modules\Core\Contracts\Services\CreateService;
use Modules\Core\Contracts\Services\Service;
use Modules\Core\Contracts\Services\UpdateService;
use Modules\Core\Models\Model;
use Modules\Documents\Models\DocumentType;

class DocumentTypeService implements Service, CreateService, UpdateService
{
    /**
     * Create new type in storage.
     */
    public function create(array $attributes): DocumentType
    {
        $model = DocumentType::create($attributes);

        $model->saveVisibilityGroup($attributes['visibility_group'] ?? []);

        return $model;
    }

    /**
     * Update the given type in storage.
     */
    public function update(Model $model, array $attributes): DocumentType
    {
        $model->fill($attributes)->save();

        if ($attributes['visibility_group'] ?? false) {
            $model->saveVisibilityGroup($attributes['visibility_group']);
        }

        return $model;
    }
}
