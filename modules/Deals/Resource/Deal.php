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

use App\Http\View\FrontendComposers\Template;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Activities\Filters\ResourceActivitiesFilter;
use Modules\Activities\Filters\ResourceNextActivityDate as ResourceNextActivityDateFilter;
use Modules\Billable\Contracts\BillableResource;
use Modules\Billable\Filters\BillableProductsFilter;
use Modules\Comments\Contracts\PipesComments;
use Modules\Core\Actions\DeleteAction;
use Modules\Core\Contracts\Resources\AcceptsCustomFields;
use Modules\Core\Contracts\Resources\Exportable;
use Modules\Core\Contracts\Resources\Importable;
use Modules\Core\Contracts\Resources\Mediable;
use Modules\Core\Contracts\Resources\Resourceful;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Facades\Permissions;
use Modules\Core\Filters\Date as DateFilter;
use Modules\Core\Filters\DateTime as DateTimeFilter;
use Modules\Core\Filters\MultiSelect as MultiSelectFilter;
use Modules\Core\Filters\Numeric as NumericFilter;
use Modules\Core\Filters\Select as SelectFilter;
use Modules\Core\Filters\Tags as TagsFilter;
use Modules\Core\Filters\Text as TextFilter;
use Modules\Core\Menu\MenuItem;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Resource\Import\Import;
use Modules\Core\Resource\Resource;
use Modules\Core\Settings\SettingsMenuItem;
use Modules\Core\Table\Table;
use Modules\Deals\Criteria\ViewAuthorizedDealsCriteria;
use Modules\Deals\Filters\DealStatusFilter;
use Modules\Deals\Http\Resources\DealResource;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Models\Stage;
use Modules\Deals\Resource\Frontend\ViewComponent;
use Modules\Deals\Services\DealService;
use Modules\Documents\Filters\ResourceDocumentsFilter;
use Modules\MailClient\Filters\ResourceEmailsFilter;
use Modules\Users\Filters\ResourceUserTeamFilter;
use Modules\Users\Filters\UserFilter;
use Modules\WebForms\Models\WebForm;

class Deal extends Resource implements Resourceful, Tableable, Mediable, Importable, Exportable, BillableResource, AcceptsCustomFields, PipesComments
{
    /**
     * Indicates whether the resource has Zapier hooks
     */
    public static bool $hasZapierHooks = true;

    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * Indicates whether the resource is globally searchable
     */
    public static bool $globallySearchable = true;

    /**
     * Indicates whether the resource fields are customizeable
     */
    public static bool $fieldsCustomizable = true;

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Deals\Models\Deal';

    /**
     * Get the resource service for CRUD operations.
     */
    public function service(): DealService
    {
        return new DealService();
    }

    /**
     * Get the menu items for the resource
     */
    public function menu(): array
    {
        return [
            MenuItem::make(static::label(), '/deals', 'Banknotes')
                ->position(5)
                ->inQuickCreate()
                ->keyboardShortcutChar('D'),
        ];
    }

    /**
     * Get the resource relationship name when it's associated
     */
    public function associateableName(): string
    {
        return 'deals';
    }

    /**
     * Get the resource available cards
     */
    public function cards(): array
    {
        return [
            (new \Modules\Deals\Cards\ClosingDeals)->onlyOnDashboard()
                ->withUserSelection(function ($instance) {
                    return $instance->canViewOtherUsersCardData() ? auth()->id() : false;
                })
                ->help(__('deals::deal.cards.closing_info')),

            (new \Modules\Deals\Cards\DealsByStage)->refreshOnActionExecuted()
                ->help(__('core::app.cards.creation_date_info')),

            (new \Modules\Deals\Cards\DealsLostInStage)->color('danger')
                ->onlyOnDashboard(),

            (new \Modules\Deals\Cards\DealsWonInStage)->color('success')
                ->onlyOnDashboard(),

            (new \Modules\Deals\Cards\WonDealsByDay)->refreshOnActionExecuted()
                ->withUserSelection(function ($instance) {
                    return $instance->canViewOtherUsersCardData();
                })->color('success'),

            (new \Modules\Deals\Cards\WonDealsByMonth)->withUserSelection(function ($instance) {
                return $instance->canViewOtherUsersCardData();
            })->color('success')->onlyOnDashboard(),

            (new \Modules\Deals\Cards\RecentlyCreatedDeals)->onlyOnDashboard(),

            (new \Modules\Deals\Cards\RecentlyModifiedDeals)->onlyOnDashboard(),

            (new \Modules\Deals\Cards\WonDealsRevenueByMonth)->color('success')
                ->canSeeWhen('is-super-admin')
                ->onlyOnDashboard(),

            (new \Modules\Deals\Cards\CreatedDealsBySaleAgent)->canSee(function ($request) {
                return $request->user()?->canAny(['view all deals', 'view team deals']);
            })
                ->onlyOnDashboard(),

            (new \Modules\Deals\Cards\AssignedDealsBySaleAgent)->canSee(function ($request) {
                return $request->user()?->canAny(['view all deals', 'view team deals']);
            })
                ->onlyOnDashboard(),
        ];
    }

    /**
     * Provide the resource table class
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function table($query, ResourceRequest $request): Table
    {
        return new DealTable($query, $request);
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return DealResource::class;
    }

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): string
    {
        return ViewAuthorizedDealsCriteria::class;
    }

    /**
     * Provides the resource available CRUD fields
     */
    public function fields(Request $request): array
    {
        return (new DealFields)($this);
    }

    /**
     * Get the resource importable class
     */
    public function importable(): Import
    {
        return new DealImport($this);
    }

    /**
     * Get the resource rules available for create and update
     */
    public function rules(ResourceRequest $request): array
    {
        return [
            'lost_reason' => 'sometimes|nullable|string|max:191',
        ];
    }

    /**
     * Get the resource available filters
     */
    public function filters(ResourceRequest $request): array
    {
        return [
            TextFilter::make('name', __('deals::fields.deals.name'))->withoutNullOperators(),
            NumericFilter::make('amount', __('deals::fields.deals.amount')),
            DateFilter::make('expected_close_date', __('deals::fields.deals.expected_close_date')),

            SelectFilter::make('pipeline_id', __('deals::fields.deals.pipeline.name'))
                ->labelKey('name')
                ->valueKey('id')
                ->options(function () {
                    return Pipeline::select(['id', 'name'])
                        ->visible()
                        ->userOrdered()
                        ->get();
                }),

            MultiSelectFilter::make('stage_id', __('deals::fields.deals.stage.name'))
                ->labelKey('name')
                ->valueKey('id')
                ->options(function () use ($request) {
                    return Stage::allStagesForOptions($request->user());
                }),

            DateTimeFilter::make('stage_changed_date', __('deals::deal.stage.changed_date')),

            TagsFilter::make('tags', __('core::tags.tags'))->forType('deals'),

            DateTimeFilter::make('won_date', __('deals::deal.won_date'))
                ->help(__('deals::deal.status_related_filter_notice', ['status' => __('deals::deal.status.won')])),

            DateTimeFilter::make('lost_date', __('deals::deal.lost_date'))
                ->help(__('deals::deal.status_related_filter_notice', ['status' => __('deals::deal.status.lost')])),

            DealStatusFilter::make(),

            TextFilter::make('lost_reason', __('deals::deal.lost_reasons.lost_reason')),

            UserFilter::make(__('deals::fields.deals.user.name')),
            ResourceUserTeamFilter::make(__('users::team.owner_team')),
            DateTimeFilter::make('owner_assigned_date', __('deals::fields.deals.owner_assigned_date')),
            ResourceDocumentsFilter::make(),
            BillableProductsFilter::make(),
            ResourceActivitiesFilter::make(),
            ResourceEmailsFilter::make(),

            SelectFilter::make('web_form_id', __('webforms::form.form'))
                ->labelKey('title')
                ->valueKey('id')
                ->options(fn () => WebForm::select(['id', 'title'])->get())
                ->canSeeWhen('is-super-admin'),

            ResourceNextActivityDateFilter::make(),
            UserFilter::make(__('core::app.created_by'), 'created_by')->withoutNullOperators()->canSeeWhen('view all deals'),
            DateTimeFilter::make('updated_at', __('core::app.updated_at')),
            DateTimeFilter::make('created_at', __('core::app.created_at')),
        ];
    }

    /**
     * Provides the resource available actions
     */
    public function actions(ResourceRequest $request): array
    {
        return [
            (new \Modules\Users\Actions\AssignOwnerAction)->onlyOnIndex(),
            new \Modules\Deals\Actions\ChangeDealStage,
            (new \Modules\Deals\Actions\MarkAsWon)->withoutConfirmation(),
            new \Modules\Deals\Actions\MarkAsLost,
            (new \Modules\Deals\Actions\MarkAsOpen)->withoutConfirmation(),

            (new DeleteAction)->setName(__('core::app.delete')),

            (new DeleteAction)->isBulk()
                ->setName(__('core::app.delete'))
                ->authorizedToRunWhen(
                    fn ($request, $model) => $request->user()->can('bulk delete deals')
                ),
        ];
    }

    /**
     * Prepare global search query.
     */
    public function globalSearchQuery(Builder $query = null): Builder
    {
        return parent::globalSearchQuery($query)->select(['id', 'name', 'created_at']);
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('deals::deal.deals');
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('deals::deal.deal');
    }

    /**
     * Register permissions for the resource
     */
    public function registerPermissions(): void
    {
        $this->registerCommonPermissions();

        Permissions::register(function ($manager) {
            $manager->group($this->name(), function ($manager) {
                $manager->view('export', [
                    'permissions' => [
                        'export deals' => __('core::app.export.export'),
                    ],
                ]);
            });
        });
    }

    /**
     * Register the settings menu items for the resource
     */
    public function settingsMenu(): array
    {
        return [
            SettingsMenuItem::make(__('deals::deal.deals'), '/settings/deals', 'Folder')->order(22),
        ];
    }

    /**
     * Get the resource frontend template
     */
    public function frontendTemplate(): Template
    {
        return (new Template)->viewComponent(new ViewComponent);
    }

    /**
     * Serialize the resource
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'frontend' => $this->frontendTemplate(),
        ]);
    }
}
