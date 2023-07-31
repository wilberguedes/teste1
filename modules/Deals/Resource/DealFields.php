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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Modules\Activities\Fields\NextActivityDate;
use Modules\Contacts\Fields\Companies;
use Modules\Contacts\Fields\Contacts;
use Modules\Core\Date\Carbon;
use Modules\Core\Facades\Fields;
use Modules\Core\Fields\Date;
use Modules\Core\Fields\DateTime;
use Modules\Core\Fields\IntroductionField;
use Modules\Core\Fields\MorphToMany;
use Modules\Core\Fields\Numeric;
use Modules\Core\Fields\Tags;
use Modules\Core\Fields\Text;
use Modules\Core\Fields\User;
use Modules\Core\Table\BelongsToColumn;
use Modules\Core\Table\Column;
use Modules\Core\Table\DateColumn;
use Modules\Core\Table\NumericColumn;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Fields\LostReasonField;
use Modules\Deals\Fields\Pipeline;
use Modules\Deals\Fields\PipelineStage;
use Modules\Notes\Fields\ImportNote;

class DealFields
{
    /**
     * Provides the deal resource available fields.
     */
    public function __invoke(Deal $instance): array
    {
        return [
            Text::make('name', __('deals::fields.deals.name'))
                ->primary()
                ->tapIndexColumn(fn (Column $column) => $column->width('340px')->minWidth('340px'))
                ->creationRules(['required', 'string'])
                ->updateRules(['filled', 'string'])
                ->rules('max:191')
                ->hideFromDetail()
                ->excludeFromSettings(Fields::DETAIL_VIEW)
                ->required(true),

            $pipeline = Pipeline::make()->primary()
                ->withMeta(['attributes' => ['clearable' => false]])
                ->rules('filled')
                ->required(true)
                ->tapIndexColumn(fn (BelongsToColumn $column) => $column->primary(false))
                ->hideFromDetail()
                ->hideWhenUpdating()
                ->hideFromIndex()
                ->excludeFromImport()
                ->excludeFromSettings()
                ->showValueWhenUnauthorizedToView(),

            PipelineStage::make()->primary()
                ->withMeta(['attributes' => ['clearable' => false]])
                ->dependsOn($pipeline, 'stages')
                ->tapIndexColumn(fn (BelongsToColumn $column) => $column->primary(false))
                ->hideFromDetail()
                ->hideWhenUpdating()
                ->excludeFromSettings()
                ->showValueWhenUnauthorizedToView(),

            Numeric::make('amount', __('deals::fields.deals.amount'))
                ->readOnly(fn () => $instance->resource?->hasProducts() ?? false)
                ->primary()
                ->currency()
                ->tapIndexColumn(fn (NumericColumn $column) => $column->primary(false)),

            Date::make('expected_close_date', __('deals::fields.deals.expected_close_date'))
                ->primary()
                ->clearable()
                ->withDefaultValue(Carbon::parse()->endOfMonth()->format('Y-m-d'))
                ->tapIndexColumn(fn (DateColumn $column) => $column->primary(false)),

            Tags::make('tags', __('core::tags.tags'))
                ->forType('deals')
                ->excludeFromExport()
                ->hideFromDetail()
                ->excludeFromSettings(Fields::DETAIL_VIEW),

            Text::make('status', __('deals::deal.status.status'))
                ->exceptOnForms()
                ->excludeFromImport()
                ->rules(['sometimes', 'nullable', 'string', Rule::in(DealStatus::names())])
                ->showValueWhenUnauthorizedToView()
                ->resolveUsing(fn ($model) => $model->status->name)
                ->displayUsing(fn ($model, $value) => __('deals::deal.status.'.$value)) // For mail placeholder
                ->tapIndexColumn(function (Column $column) {
                    $column->centered()
                        ->displayAs(fn ($model) => $model->status->name)
                        ->withMeta(['badgeVariants' => DealStatus::badgeVariants()])
                        ->orderByUsing(function (Builder $query, string $attribute, string $direction) {
                            return $query->orderByRaw('CASE
                                WHEN '.$attribute.'="'.DealStatus::open->value.'" THEN 1
                                WHEN '.$attribute.'="'.DealStatus::lost->value.'" THEN 2
                                WHEN '.$attribute.'="'.DealStatus::won->value.'" THEN 3
                            END '.$direction);
                        });
                }),

            LostReasonField::make('lost_reason', __('deals::deal.lost_reasons.lost_reason'))
                ->strictlyForIndex()
                ->excludeFromImportSample()
                ->hidden(),

            User::make(__('deals::fields.deals.user.name'))
                ->primary()
                ->acceptLabelAsValue(false)
                ->withMeta(['attributes' => ['placeholder' => __('core::app.no_owner')]])
                ->notification(\Modules\Deals\Notifications\UserAssignedToDeal::class)
                ->trackChangeDate('owner_assigned_date')
                ->tapIndexColumn(fn (BelongsToColumn $column) => $column->primary(false)
                    ->select('avatar')
                    ->appends('avatar_url')
                    ->useComponent('table-data-avatar-column')
                )
                ->hideFromDetail()
                ->excludeFromSettings(Fields::DETAIL_VIEW)
                ->showValueWhenUnauthorizedToView(),

            IntroductionField::make(__('core::resource.associate_with_records'))
                ->strictlyForCreation()
                ->titleIcon('Link')
                ->order(1000),

            Companies::make()
                ->excludeFromSettings()
                ->strictlyForCreationAndIndex()
                ->hideFromIndex()
                ->order(1001),

            Contacts::make()
                ->excludeFromSettings()
                ->strictlyForCreationAndIndex()
                ->hideFromIndex()
                ->order(1002),

            DateTime::make('owner_assigned_date', __('deals::fields.deals.owner_assigned_date'))
                ->exceptOnForms()
                ->hidden(),

            MorphToMany::make('documents', __('documents::document.documents'))
                ->exceptOnForms()
                ->excludeFromZapierResponse()
                ->hidden()
                ->count(),

            Contacts::make()
                ->label(__('contacts::contact.total'))
                ->exceptOnForms()
                ->hidden()
                ->count(),

            Companies::make()
                ->label(__('contacts::company.total'))
                ->exceptOnForms()
                ->hidden()
                ->count(),

            MorphToMany::make('unreadEmailsForUser', __('mailclient::inbox.unread_count'))
                ->exceptOnForms()
                ->authRequired()
                ->excludeFromZapierResponse()
                ->hidden()
                ->count(),

            MorphToMany::make('incompleteActivitiesForUser', __('activities::activity.incomplete_activities'))
                ->exceptOnForms()
                ->authRequired()
                ->excludeFromZapierResponse()
                ->hidden()
                ->count(),

            MorphToMany::make('documentsForUser', __('documents::document.total_documents'))
                ->exceptOnForms()
                ->authRequired()
                ->excludeFromZapierResponse()
                ->hidden()
                ->count(),

            MorphToMany::make('draftDocumentsForUser', __('documents::document.total_draft_documents'))
                ->exceptOnForms()
                ->authRequired()
                ->excludeFromZapierResponse()
                ->hidden()
                ->count(),

            MorphToMany::make('calls', __('calls::call.total_calls'))
                ->exceptOnForms()
                ->excludeFromZapierResponse()
                ->hidden()
                ->count(),

            NextActivityDate::make(),

            ImportNote::make(),

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
}
