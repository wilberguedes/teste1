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

namespace Modules\Core\Http\Resources;

use App\Http\Resources\ProvidesCommonData;
use Illuminate\Http\Request;
use Modules\Core\JsonResource;

/** @mixin \Modules\Core\Models\MailableTemplate */
class MailableResource extends JsonResource
{
    use ProvidesCommonData;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            'locale' => $this->locale,
            'subject' => $this->getSubject(),
            'html_template' => clean($this->getHtmlTemplate()),
            'text_template' => clean($this->getTextTemplate()),
            'placeholders' => $this->getPlaceholders(),
        ], $request);
    }
}
