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

namespace Modules\Documents\Filters;

use Modules\Core\Filters\MultiSelect;
use Modules\Core\QueryBuilder\Parser;
use Modules\Documents\Enums\DocumentStatus;

class DocumentStatusFilter extends MultiSelect
{
    /**
     * Create new DocumentStatusFilter instance
     */
    public function __construct()
    {
        parent::__construct('status', __('documents::document.status.status'));

        $this->options(collect(DocumentStatus::cases())
            ->mapWithKeys(function (DocumentStatus $status) {
                return [$status->value => $status->displayName()];
            })->all())->query(function ($builder, $value, $condition, $sqlOperator, $rule, Parser $parser) {
                return $parser->makeArrayQueryIn(
                    $builder,
                    $rule,
                    $sqlOperator['operator'],
                    collect($value)->map(
                        fn ($status) => DocumentStatus::tryFrom($status)
                    )->filter()->all(),
                    $condition
                );
            });
    }
}
