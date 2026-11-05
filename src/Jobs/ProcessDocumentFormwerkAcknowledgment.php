<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Jobs;

use Hwkdo\IntranetAppDokumente\Enums\FormwerkRequestStatus;
use Hwkdo\IntranetAppDokumente\Models\DocumentFormwerkRequest;
use Hwkdo\IntranetAppDokumente\Services\DocumentAcknowledgmentService;
use Hwkdo\MsGraphLaravel\Interfaces\MsGraphMailServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Generated\Models\FileAttachment;
use Throwable;

/**
 * Ablauf: ProcessFormwerkMail → dispatch(mailResource, identifier=kenntnisnahme_ref, uuid).
 * Die Kenntnisnahme wird erst gesetzt, wenn ein PDF-Anhang gespeichert wurde.
 */
class ProcessDocumentFormwerkAcknowledgment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $mailResource,
        public string $identifier,
        public ?string $uuid = null,
    ) {}

    public function handle(): void
    {
        $request = DocumentFormwerkRequest::query()
            ->with(['version.document'])
            ->where('public_id', $this->identifier)
            ->first();

        if ($request === null || $request->version === null) {
            Log::error('ProcessDocumentFormwerkAcknowledgment: Request nicht gefunden', [
                'identifier' => $this->identifier,
                'uuid' => $this->uuid,
            ]);

            return;
        }

        $request->forceFill([
            'status' => FormwerkRequestStatus::WebhookReceived,
            'formwerk_uuid' => $this->uuid ?: $request->formwerk_uuid,
        ])->save();

        $mailService = app(MsGraphMailServiceInterface::class);
        $mail = $mailService->get($this->mailResource);

        if (! $mail->getHasAttachments()) {
            Log::warning('ProcessDocumentFormwerkAcknowledgment: Mail ohne Anhänge', [
                'identifier' => $this->identifier,
            ]);

            return;
        }

        $upn = str($this->mailResource)->after('Users/')->before('/Messages/')->value();
        $messageId = str($this->mailResource)->after('/Messages/')->value();
        $attachments = $mailService->listAttachmentsByUpnAndId($upn, $messageId);

        $savedPdf = null;
        $tempDir = storage_path('app/temp/formwerk-kenntnisnahme/');

        if ($attachments?->getValue()) {
            foreach ($attachments->getValue() as $attachment) {
                if (! $attachment instanceof FileAttachment) {
                    continue;
                }

                $contentType = (string) ($attachment->getContentType() ?? '');
                $name = (string) ($attachment->getName() ?? '');
                $isPdf = str_contains(strtolower($contentType), 'pdf')
                    || str_ends_with(strtolower($name), '.pdf');

                if (! $isPdf) {
                    continue;
                }

                File::ensureDirectoryExists($tempDir);
                $savedPdf = $mailService->saveAttachment($attachment, $tempDir, $request->public_id.'.pdf');
                break;
            }
        }

        if ($savedPdf === null || ! is_file($savedPdf)) {
            Log::error('ProcessDocumentFormwerkAcknowledgment: Kein PDF-Anhang', [
                'identifier' => $this->identifier,
                'uuid' => $this->uuid,
            ]);

            return;
        }

        DB::transaction(function () use ($request, $savedPdf): void {
            $acknowledgment = app(DocumentAcknowledgmentService::class)->acknowledge(
                $request->version,
                (int) $request->user_id,
                DocumentAcknowledgmentService::METHOD_FORMWERK,
            );

            $acknowledgment->addMedia($savedPdf)
                ->usingFileName($request->public_id.'.pdf')
                ->toMediaCollection('signed_pdf');

            if ($acknowledgment->getFirstMedia('signed_pdf') === null) {
                throw new \RuntimeException('Signiertes PDF wurde nicht gespeichert.');
            }

            $request->forceFill([
                'status' => FormwerkRequestStatus::Completed,
                'formwerk_uuid' => $this->uuid ?: $request->formwerk_uuid,
            ])->save();
        });

        try {
            $mailService->setRead($upn, $messageId);
        } catch (Throwable $e) {
            Log::warning('ProcessDocumentFormwerkAcknowledgment: setRead fehlgeschlagen', [
                'message' => $e->getMessage(),
            ]);
        }

        Log::info('ProcessDocumentFormwerkAcknowledgment: abgeschlossen', [
            'identifier' => $this->identifier,
            'user_id' => $request->user_id,
            'document_version_id' => $request->document_version_id,
        ]);
    }
}
