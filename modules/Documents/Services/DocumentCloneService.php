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

namespace Modules\Documents\Services;

use Illuminate\Support\Arr;
use Modules\Billable\Services\BillableService;
use Modules\Documents\Enums\DocumentStatus;
use Modules\Documents\Models\Document;

class DocumentCloneService
{
    public function clone(Document $document, int $userId): Document
    {
        $newDocument = $document->replicate([
            'uuid', 'accepted_at', 'marked_accepted_by', 'send_at', 'original_date_sent',
            'last_date_sent', 'sent_by', 'approved_by', 'approved_date', 'approval_feedback', 'user_id',
            'owner_assigned_date', 'created_by', 'data',
        ]);

        $newDocument->forceFill([
            'status' => DocumentStatus::DRAFT,
            'data' => array_merge($document->data ?? [], [
                'send_initiated_by' => null,
                'recipients' => collect($document->data['recipients'] ?? [])->map(function ($recipient) {
                    Arr::forget($recipient, 'sent_at');

                    return $recipient;
                }),
            ]),
            'title' => clone_prefix($document->title),
            'user_id' => $userId,
            'created_by' => $userId,
        ])->save();

        $newDocument->signers()->createMany(
            $document->signers->map(fn ($signer) => $signer->only(['name', 'email', 'send_email']))
        );

        if ($document->billable) {
            (new BillableService())->save([
                'tax_type' => $document->billable->tax_type,
                'products' => $document->billable->products->map(function ($product) {
                    return $product->only($product->formAttributes());
                })->all(),
            ], $newDocument);
        }

        $document->resource()->availableAssociations()
            ->each(function ($resource) use ($document, $newDocument) {
                $relation = $resource->associateableName();

                $newDocument->{$relation}()->attach($document->{$relation}->modelKeys());
            });

        return $newDocument;
    }
}
