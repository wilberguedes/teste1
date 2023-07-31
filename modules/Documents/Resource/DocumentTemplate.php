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

namespace Modules\Documents\Resource;

use Illuminate\Validation\Rules\Enum;
use Modules\Core\Contracts\Resources\Cloneable;
use Modules\Core\Contracts\Resources\Resourceful;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Models\Model;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Resource\Resource;
use Modules\Core\Rules\UniqueResourceRule;
use Modules\Core\Table\BelongsToColumn;
use Modules\Core\Table\BooleanColumn;
use Modules\Core\Table\Column;
use Modules\Core\Table\DateColumn;
use Modules\Core\Table\ID;
use Modules\Core\Table\Table;
use Modules\Documents\Criteria\TemplatesForUserCriteria;
use Modules\Documents\Enums\DocumentViewType;
use Modules\Documents\Http\Resources\DocumentTemplateResource;
use Modules\Documents\Models\DocumentTemplate as DocumentTemplateModel;

class DocumentTemplate extends Resource implements Resourceful, Tableable, Cloneable
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Documents\Models\DocumentTemplate';

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): string
    {
        return TemplatesForUserCriteria::class;
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return DocumentTemplateResource::class;
    }

    /**
     * Clone the resource record from the given id
     */
    public function clone(Model $model, int $userId): Model
    {
        return $model->clone($userId);
    }

    /**
     * Provide the resource table class
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function table($query, ResourceRequest $request): Table
    {
        return (new Table($query, $request))->addColumns([
            ID::make(__('core::app.id')),
            Column::make('name', __('documents::document.template.name')),
            BooleanColumn::make('is_shared', __('documents::document.template.is_shared')),
            BelongsToColumn::make('user', 'name', __('core::app.created_by')),
            DateColumn::make('created_at', __('core::app.created_at')),
            DateColumn::make('updated_at', __('core::app.updated_at')),
        ]);
    }

    /**
     * Set the resource rules available for create and update
     */
    public function rules(ResourceRequest $request): array
    {
        return [
            'name' => [
                'required',
                'string',
                UniqueResourceRule::make(DocumentTemplateModel::class),
                'max:191',
            ],
            'content' => 'required|string',
            'is_shared' => 'nullable|boolean',
            'view_type' => ['nullable', new Enum(DocumentViewType::class)],
        ];
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('documents::document.template.template');
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('documents::document.template.templates');
    }
}
