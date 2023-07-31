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

use Modules\Core\Filters\HasMany;
use Modules\Core\Filters\Number;
use Modules\Core\Filters\Numeric;
use Modules\Core\Filters\Operand;
use Modules\Core\Filters\Text;

class ResourceDocumentsFilter extends HasMany
{
    /**
     * Initialize ResourceDocumentsFilter class
     *
     * @param  string  $singularLabel
     */
    public function __construct()
    {
        parent::__construct('documents', __('documents::document.documents'));

        $this->setOperands([
            Operand::make('amount', __('documents::fields.documents.amount'))->filter(
                Numeric::make('amount')
            ),

            Operand::make('status', __('documents::document.status.status'))->filter(
                DocumentStatusFilter::make()
            ),

            Operand::make('document_type_id', __('documents::fields.documents.type.name'))->filter(
                DocumentTypeFilter::make()
            ),

            Operand::make('brand_id', __('documents::fields.documents.brand.name'))->filter(
                DocumentBrandFilter::make()
            ),

            Operand::make('name', __('documents::fields.documents.title'))->filter(
                Text::make('name')
            ),

            Operand::make('total_count', __('documents::document.total_documents'))->filter(
                Number::make('total_count')->countableRelation('documents')
            ),
        ]);
    }
}
