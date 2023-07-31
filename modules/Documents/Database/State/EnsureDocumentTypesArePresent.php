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

namespace Modules\Documents\Database\State;

use Illuminate\Support\Facades\DB;

class EnsureDocumentTypesArePresent
{
    public array $types = [
        'Proposal' => '#a3e635',
        'Quote' => '#64748b',
        'Contract' => '#ffd600',
    ];

    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        foreach ($this->types as $name => $color) {
            $model = \Modules\Documents\Models\DocumentType::create([
                'name' => $name,
                'swatch_color' => $color,
                'flag' => strtolower($name),
            ]);

            if ($model->flag === 'proposal') {
                $model::setDefault($model->getKey());
            }
        }
    }

    private function present(): bool
    {
        return DB::table('document_types')->count() > 0;
    }
}
