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

namespace Modules\Deals\Enums;

use Modules\Core\InteractsWithEnums;

enum DealStatus: int
{
    use InteractsWithEnums;

    case open = 1;
    case won = 2;
    case lost = 3;

    /**
     * Get the deal status badge variant.
     */
    public function badgeVariant(): string
    {
        return static::badgeVariants()[$this->name];
    }

    /**
     * Get the available badge variants.
     */
    public static function badgeVariants(): array
    {
        return [
            DealStatus::open->name => 'neutral',
            DealStatus::won->name => 'success',
            DealStatus::lost->name => 'danger',
        ];
    }
}
