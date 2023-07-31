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

namespace Modules\Core\Database\State;

use Modules\Core\Settings\DefaultSettings;

class EnsureDefaultSettingsArePresent
{
    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        settings()->flush();

        $settings = array_merge(DefaultSettings::get(), ['_seeded' => true]);

        foreach ($settings as $setting => $value) {
            settings()->set([$setting => $value]);
        }

        settings()->save();
    }

    private function present(): bool
    {
        return settings('_seeded') === true;
    }
}
