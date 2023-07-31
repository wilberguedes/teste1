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

namespace Modules\Contacts\Database\State;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Tag;

class EnsureDefaultContactTagsArePresent
{
    public array $tags = [
        'Customer' => '#10b981',
        'Hot Lead' => '#DC2626',
        'Cold Lead' => '#2563eb',
        'Warm Lead' => '#f59e0b',
    ];

    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        foreach ($this->tags as $tag => $color) {
            $tag = Tag::findOrCreate($tag, 'contacts');

            $tag->swatch_color = $color;

            $tag->save();
        }
    }

    private function present(): bool
    {
        return DB::table('tags')->where('type', 'contacts')->count() > 0;
    }
}
