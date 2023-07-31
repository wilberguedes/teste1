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

namespace Modules\Documents\Http\Resources;

use App\Http\Resources\ProvidesCommonData;
use Illuminate\Http\Request;
use Modules\Contacts\Http\Resources\CompanyResource;
use Modules\Contacts\Http\Resources\ContactResource;
use Modules\Core\Http\Resources\ChangelogResource;
use Modules\Core\Resource\Http\JsonResource;
use Modules\Deals\Http\Resources\DealResource;

/** @mixin \Modules\Documents\Models\Document */
class DocumentResource extends JsonResource
{
    use ProvidesCommonData;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\Resource\Http\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'requires_signature' => $this->requires_signature,
            'content' => clean($this->content),
            'view_type' => $this->view_type,
            'brand_id' => $this->brand_id,
            'send_at' => $this->send_at,
            'original_date_sent' => $this->original_date_sent,
            'last_date_sent' => $this->last_date_sent,
            'accepted_at' => $this->accepted_at,
            'marked_accepted_by' => $this->marked_accepted_by,
            'locale' => $this->locale,
            'created_by' => $this->created_by,

            'public_url' => $this->when($request->user()->can('view', $this->resource), $this->publicUrl),

            'signers' => DocumentSignerResource::collection($this->whenLoaded('signers')),

            'recipients' => $this->data['recipients'] ?? [],
            'send_mail_account_id' => ($this->data['send_mail_account_id'] ?? null) ? (int) $this->data['send_mail_account_id'] : null,
            'send_mail_subject' => $this->data['send_mail_subject'] ?? null,
            'send_mail_body' => $this->data['send_mail_body'] ?? null,
            'pdf' => $this->data['pdf'] ?? new \stdClass(),
            'google_fonts' => $this->content->usedGoogleFonts(),

            $this->mergeWhen(! $request->isZapier() && $this->userCanViewCurrentResource(), [
                'changelog' => ChangelogResource::collection($this->whenLoaded('changelog')),
                'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
                'companies' => CompanyResource::collection($this->whenLoaded('companies')),
                'deals' => DealResource::collection($this->whenLoaded('deals')),
            ]),
        ], $request);
    }
}
