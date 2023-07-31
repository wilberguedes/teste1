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

namespace Modules\Users\Tests\Concerns;

trait TestsMentions
{
    protected function mentionText($id, $name, $notified = 'false')
    {
        return '<span class="mention" data-mention-id="'.$id.'" data-notified="'.$notified.'">@'.$name.'</span>';
    }
}
