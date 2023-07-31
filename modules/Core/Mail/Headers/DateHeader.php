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

namespace Modules\Core\Mail\Headers;

use Illuminate\Support\Carbon;

class DateHeader extends Header
{
    /**
     * Get the header value
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function getValue()
    {
        $tz = config('app.timezone');

        $dateString = $this->value;

        // https://github.com/briannesbitt/Carbon/issues/685
        if (is_string($dateString)) {
            $dateString = trim(preg_replace('/\(.*$/', '', $dateString));
        }

        return $dateString ? Carbon::parse($dateString)->tz($tz) : null;
    }
}
