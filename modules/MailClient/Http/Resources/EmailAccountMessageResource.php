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

namespace Modules\MailClient\Http\Resources;

use App\Http\Resources\ProvidesCommonData;
use App\Support\AutoParagraph;
use Illuminate\Http\Request;
use Modules\Contacts\Http\Resources\CompanyResource;
use Modules\Contacts\Http\Resources\ContactResource;
use Modules\Core\Http\Resources\MediaResource;
use Modules\Core\Resource\Http\JsonResource;
use Modules\Deals\Http\Resources\DealResource;

/** @mixin \Modules\MailClient\Models\EmailAccountMessage */
class EmailAccountMessageResource extends JsonResource
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
            'email_account_id' => $this->email_account_id,
            'email_account_email' => $this->account->email,
            'remote_id' => $this->remote_id,
            'message_id' => $this->message_id,
            'subject' => $this->subject,
            'html_body' => $this->html_body,
            'text_body' => $this->text_body,
            'preview_text' => trim($this->previewText),
            'visible_text' => trim($this->visibleText),
            'hidden_text' => trim($this->hiddenText),
            'editor_text' => ! $this->html_body ? AutoParagraph::wrap($this->text_body) : $this->html_body,
            'is_draft' => $this->is_draft, // not used atm
            'is_read' => $this->is_read,
            'was_unread' => $this->wasChanged('is_read') && $this->is_read,
            'from' => $this->whenLoaded('from'),
            'to' => $this->whenLoaded('to'),
            'cc' => $this->whenLoaded('cc'),
            'bcc' => $this->whenLoaded('bcc'),
            'reply_to' => $this->whenLoaded('replyTo'),
            'sender' => $this->whenLoaded('sender'),
            'display_name' => $this->display_name,
            'path' => $this->path,
            'folders' => EmailAccountFolderResource::collection($this->whenLoaded('folders')),
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            'companies' => CompanyResource::collection($this->whenLoaded('companies')),
            'deals' => DealResource::collection($this->whenLoaded('deals')),
            'account_active_folders_tree' => $this->when(
                $this->relationLoaded('account') && $this->account->relationLoaded('folders'),
                function () use ($request) {
                    return $this->account->folders->createTreeFromActive($request);
                }
            ),
            'avatar_url' => isset($this->from->address) ? $this->getGravatarUrl($this->from->address) : null,
            'media' => $this->when($this->relationLoaded('attachments'), function () {
                return MediaResource::collection($this->attachments);
            }),
            'date' => $this->date,
            $this->mergeWhen($this->is_sent_via_app, [
                'opens' => (int) $this->opens ?: 0,
                'opened_at' => $this->opened_at,
            ]),
        ], $request);
    }
}
