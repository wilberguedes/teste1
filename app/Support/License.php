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

namespace App\Support;

use Illuminate\Support\Facades\Http;

class License
{
    /**
     * Verify the given license
     *
     * @param  string  $key
     * @return bool
     */
    public static function verify($key)
    {
        $response = Http::withHeaders([
            'X-Concord-Installation' => true,
        ])
            ->contentType('application/json')
            ->get('https://www.concordcrm.com/verify-license/'.$key);

        if ($response->successful()) {
            return $response['valid'];
        }

        return false;
    }
}
