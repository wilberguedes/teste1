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

namespace Modules\Core;

class Html2Text
{
    /**
     * Convert HTML to Text
     *
     * @param  string  $html
     * @return string
     */
    public static function convert($html)
    {
        return \Soundasleep\Html2Text::convert($html, ['ignore_errors' => true]);
    }
}
