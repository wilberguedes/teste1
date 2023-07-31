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

use Illuminate\Support\Facades\Mail;
use Modules\Documents\Enums\DocumentStatus;
use Modules\Documents\Mail\SendDocument;
use Modules\Documents\Models\Document;
use Modules\Users\Models\User;

class DocumentSendService
{
    /**
     * Send the given document.
     */
    public function send(Document $document): void
    {
        $recipients = [...$this->sendMailToRecipients($document), ...$this->sendMailToSigners($document)];
        $sender = User::find($document->data['send_initiated_by']);

        $document->forceFill(['last_date_sent' => now()]);

        if (! $document->original_date_sent && ! $document->sent_by) {
            $document->forceFill([
                'original_date_sent' => now(),
                'sent_by' => $sender->getKey(),
            ]);
        }

        if ($document->status === DocumentStatus::DRAFT) {
            $document->forceFill(['status' => DocumentStatus::SENT]);
        }

        $document->save();

        if (count($recipients) > 0) {
            $document->addActivity([
                'lang' => [
                    'key' => 'documents::document.activity.sent',
                    'attrs' => [
                        'user' => $sender->name,
                    ],
                ],
                'section' => [
                    'lang' => [
                        'key' => 'documents::document.recipients.recipients',
                    ],
                    'list' => collect($recipients)->map(function ($recipient) {
                        return [
                            'lang' => [
                                'key' => 'documents::document.activity.sent_recipient',
                                'attrs' => [
                                    'name' => $recipient['name'],
                                    'email' => $recipient['email'],
                                ],
                            ],
                        ];
                    })->all(),
                ],
            ]);
        }
    }

    /**
     * Send mail to the document signers
     */
    protected function sendMailToSigners(Document $document): array
    {
        $sentTo = [];

        $document->signers->filter(
            fn ($signer) => $signer['send_email'] ?? false === true
        )
            ->whenNotEmpty(function ($recipients) use ($document) {
                Mail::to($recipients)->send(new SendDocument($document));
            })->each(function ($signer) use ($document, &$sentTo) {
                $document->signers()->updateOrCreate(['email' => $signer['email']], [
                    'sent_at' => now(),
                    'send_email' => false,
                ]);

                $sentTo[] = $signer;
            });

        return $sentTo;
    }

    /**
     * Send mail to the document recipients
     */
    protected function sendMailToRecipients(Document $document): array
    {
        $sentTo = [];

        collect($document->data['recipients'] ?? [])->filter(
            fn ($recipient) => $recipient['send_email'] ?? false === true
        )
            ->whenNotEmpty(function ($recipients) use ($document) {
                Mail::to($recipients)->send(new SendDocument($document));
            })->each(function ($recipient, $key) use (&$document, &$sentTo) {
                $document->data = array_merge($document->data, [
                    'recipients' => with($document->data['recipients'], function ($recipients) use ($key) {
                        $recipients[$key]['sent_at'] = now();
                        $recipients[$key]['send_email'] = false;

                        return $recipients;
                    }),
                ]);

                $sentTo[] = $recipient;
            });

        return $sentTo;
    }
}
