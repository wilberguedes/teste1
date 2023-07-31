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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Billable\Contracts\BillableResource;
use Modules\Billable\Filters\BillableProductsFilter;
use Modules\Contacts\Fields\Companies;
use Modules\Contacts\Fields\Contacts;
use Modules\Core\Actions\DeleteAction;
use Modules\Core\Contracts\Resources\Cloneable;
use Modules\Core\Contracts\Resources\Resourceful;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Fields\BelongsTo;
use Modules\Core\Fields\DateTime;
use Modules\Core\Fields\Numeric;
use Modules\Core\Fields\Text;
use Modules\Core\Fields\User;
use Modules\Core\Filters\DateTime as DateTimeFilter;
use Modules\Core\Filters\Numeric as NumericFilter;
use Modules\Core\Filters\Text as TextFilter;
use Modules\Core\Menu\MenuItem;
use Modules\Core\Models\Model;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Resource\Resource;
use Modules\Core\Rules\VisibleModelRule;
use Modules\Core\Settings\SettingsMenuItem;
use Modules\Core\Table\BelongsToColumn;
use Modules\Core\Table\Column;
use Modules\Core\Table\Table;
use Modules\Deals\Fields\Deals;
use Modules\Documents\Concerns\ValidatesDocument;
use Modules\Documents\Criteria\ViewAuthorizedDocumentsCriteria;
use Modules\Documents\Enums\DocumentStatus;
use Modules\Documents\Filters\DocumentBrandFilter;
use Modules\Documents\Filters\DocumentStatusFilter;
use Modules\Documents\Filters\DocumentTypeFilter;
use Modules\Documents\Http\Resources\DocumentResource;
use Modules\Documents\Http\Resources\DocumentTypeResource;
use Modules\Documents\Models\DocumentType;
use Modules\Documents\Services\DocumentCloneService;
use Modules\Documents\Services\DocumentService;
use Modules\Users\Filters\ResourceUserTeamFilter;
use Modules\Users\Filters\UserFilter;
use Modules\Users\Models\User as UserModel;

class Document extends Resource implements Resourceful, Tableable, Cloneable, BillableResource
{
    use ValidatesDocument;

    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'title';

    /**
     * Indicates whether the resource is globally searchable
     */
    public static bool $globallySearchable = true;

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Documents\Models\Document';

    /**
     * Get the resource service for CRUD operations.
     */
    public function service(): DocumentService
    {
        return new DocumentService();
    }

    /**
     * Get the menu items for the resource
     */
    public function menu(): array
    {
        return [
            MenuItem::make(static::label(), '/documents', 'DocumentText')
                ->position(20)
                ->inQuickCreate()
                ->keyboardShortcutChar('F'),
        ];
    }

    /**
     * Get the resource relationship name when it's associated
     */
    public function associateableName(): string
    {
        return 'documents';
    }

    /**
     * Get the resource available cards
     */
    public function cards(): array
    {
        return [
            (new \Modules\Documents\Cards\SentDocumentsByDay)->withUserSelection(function ($instance) {
                return $instance->canViewOtherUsersCardData();
            })->color('success'),
            (new \Modules\Documents\Cards\DocumentsByType)->onlyOnDashboard()->help(__('core::app.cards.creation_date_info')),
            (new \Modules\Documents\Cards\DocumentsByStatus)->refreshOnActionExecuted()->help(__('core::app.cards.creation_date_info')),
        ];
    }

    /**
     * Provide the resource table class
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function table($query, ResourceRequest $request): Table
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return (new Table($query, $request))->customizeable()
            ->appends('public_url')
            ->select([
                'uuid', // for public_url append
                'user_id', // user_id is for the policy checks
                'status', // for showing the dropdown send document item
            ])->orderBy('created_at', 'desc');
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return DocumentResource::class;
    }

    /**
     * Prepare global search query.
     */
    public function globalSearchQuery(Builder $query = null): Builder
    {
        return parent::globalSearchQuery($query)->select(['id', 'title', 'created_at']);
    }

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): string
    {
        return ViewAuthorizedDocumentsCriteria::class;
    }

    /**
     * Clone the resource record from the given id
     */
    public function clone(Model $model, int $userId): Model
    {
        return (new DocumentCloneService())->clone($model, $userId);
    }

    /**
     * Provides the resource available CRUD fields
     */
    public function fields(Request $request): array
    {
        return [
            Text::make('title', __('documents::fields.documents.title'))
                ->primary()
                ->tapIndexColumn(fn (Column $column) => $column->width('340px')->minWidth('340px'))
                ->creationRules(['required', 'string'])
                ->updateRules(['filled', 'string'])
                ->rules('max:191')
                ->required(true),

            BelongsTo::make('type', DocumentType::class, __('documents::document.type.type'))
                ->rules(
                    [
                        'filled',
                        new VisibleModelRule(new DocumentType),
                    ]
                )
                ->required(true)
                ->creationRules(
                    Rule::requiredIf(
                        function () use ($request) {
                            $defaultId = DocumentType::getDefaultType();

                            if (is_null($defaultId)) {
                                return true;
                            }

                            try {
                                $type = DocumentType::findOrFail($defaultId);

                                return $request->user()->cant('view', $type);
                            } catch (ModelNotFoundException) {
                                return true;
                            }
                        }
                    )
                )
                ->setJsonResource(DocumentTypeResource::class)
                ->tapIndexColumn(function (BelongsToColumn $column) {
                    $column->label(__('documents::document.type.type'))
                        ->select(['swatch_color'])
                        ->appends('icon')
                        ->primary(false)
                        ->width('130px')
                        ->minWidth('130px')
                        ->useComponent('table-data-option-column');
                }),

            Text::make('status', __('documents::document.status.status'))
                ->resolveUsing(fn ($model) => $model->status->value)
                ->displayUsing(fn ($model, $value) => DocumentStatus::tryFrom($value)->displayName()) // For mail placeholder
                ->tapIndexColumn(function (Column $column) {
                    $column->centered()->displayAs(fn ($model) => $model->status->value);
                }),

            User::make(__('documents::fields.documents.user.name'))
                ->primary()
                ->acceptLabelAsValue(false)
                ->rules('required')
                ->notification(\Modules\Documents\Notifications\UserAssignedToDocument::class)
                ->trackChangeDate('owner_assigned_date')
                ->tapIndexColumn(
                    fn (BelongsToColumn $column) => $column->primary(false)
                        ->select('avatar')
                        ->appends('avatar_url')
                        ->useComponent('table-data-avatar-column')
                )
                ->showValueWhenUnauthorizedToView(),

            Numeric::make('amount', __('documents::fields.documents.amount'))
                ->currency(),

            Contacts::make()
                ->exceptOnForms()
                ->hidden(),

            Companies::make()
                ->exceptOnForms()
                ->hidden(),

            Deals::make()
                ->exceptOnForms()
                ->hidden(),

            BelongsTo::make('creator', UserModel::class, __('core::app.created_by'))
                ->excludeFromImport()
                ->strictlyForIndex()
                ->tapIndexColumn(
                    fn (BelongsToColumn $column) => $column->minWidth('100px')
                        ->select('avatar')
                        ->appends('avatar_url')
                        ->useComponent('table-data-avatar-column')
                )
                ->hidden(),

            DateTime::make('last_date_sent', __('documents::fields.documents.last_date_sent'))
                ->exceptOnForms()
                ->hidden(),

            DateTime::make('original_date_sent', __('documents::fields.documents.original_date_sent'))
                ->exceptOnForms()
                ->hidden(),

            DateTime::make('accepted_at', __('documents::fields.documents.accepted_at'))
                ->exceptOnForms()
                ->hidden(),

            DateTime::make('owner_assigned_date', __('documents::fields.documents.owner_assigned_date'))
                ->exceptOnForms()
                ->hidden(),

            DateTime::make('updated_at', __('core::app.updated_at'))
                ->excludeFromImportSample()
                ->strictlyForIndex()
                ->hidden(),

            DateTime::make('created_at', __('core::app.created_at'))
                ->excludeFromImportSample()
                ->strictlyForIndex()
                ->hidden(),

        ];
    }

    /**
     * Get the resource available Filters
     */
    public function filters(ResourceRequest $request): array
    {
        return [
            TextFilter::make('title', __('documents::fields.documents.title'))->withoutNullOperators(),
            DocumentTypeFilter::make(),
            NumericFilter::make('amount', __('documents::fields.documents.amount')),
            DocumentBrandFilter::make(),
            DocumentStatusFilter::make(),
            DateTimeFilter::make('accepted_at', __('documents::fields.documents.accepted_at')),
            UserFilter::make(__('documents::fields.documents.user.name'))->withoutNullOperators(),
            ResourceUserTeamFilter::make(__('users::team.owner_team')),
            DateTimeFilter::make('owner_assigned_date', __('documents::fields.documents.owner_assigned_date')),
            BillableProductsFilter::make(),
            DateTimeFilter::make('last_date_sent', __('documents::fields.documents.last_date_sent')),
            DateTimeFilter::make('original_date_sent', __('documents::fields.documents.original_date_sent')),
            UserFilter::make(__('documents::document.sent_by'), 'sent_by')->canSeeWhen('view all documents'),
            UserFilter::make(__('core::app.created_by'), 'created_by')->withoutNullOperators()->canSeeWhen('view all documents'),
            DateTimeFilter::make('updated_at', __('core::app.updated_at')),
            DateTimeFilter::make('created_at', __('core::app.created_at')),
        ];
    }

    /**
     * Create query when the resource is associated for index.
     */
    public function associatedIndexQuery(Model $primary, bool $applyOrder = true): Builder
    {
        $query = parent::associatedIndexQuery($primary, $applyOrder);
        [$with, $withCount] = static::getEagerLoadable($this->fieldsForIndexQuery());

        // For associations keys to be included in the JSON resource
        return $query->with(
            array_merge(
                $this->availableAssociations()->map->associateableName()->all(),
                ['pinnedTimelineSubjects'],
                $with->all()
            )
        )->withCount($withCount->all())
            ->criteria($this->viewAuthorizedRecordsCriteria())
            ->withCommon();
    }

    /**
     * Provides the resource available actions
     */
    public function actions(ResourceRequest $request): array
    {
        return [
            new \Modules\Users\Actions\AssignOwnerAction,

            (new DeleteAction)->setName(__('core::app.delete')),

            (new DeleteAction)->isBulk()
                ->setName(__('core::app.delete'))
                ->authorizedToRunWhen(
                    fn ($request, $model) => $request->user()->can('bulk delete documents')
                ),
        ];
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('documents::document.documents');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('documents::document.document');
    }

    /**
     * Register permissions for the resource
     */
    public function registerPermissions(): void
    {
        $this->registerCommonPermissions();
    }

    /**
     * Register the settings menu items for the resource
     */
    public function settingsMenu(): array
    {
        return [
            SettingsMenuItem::make(__('documents::document.documents'), '/settings/documents', 'DocumentText')->order(23),
        ];
    }
}
