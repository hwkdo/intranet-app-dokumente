<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use Hwkdo\IntranetAppDokumente\Enums\FormwerkRequestStatus;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentFormwerkRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class FormwerkAcknowledgmentRedirect
{
    public function __construct(private FormwerkAcknowledgmentPayload $payload) {}

    public function urlFor(Document $document, Model $user): string
    {
        $version = $document->currentVersion;
        if ($version === null) {
            throw new \RuntimeException('Das Dokument hat keine aktuelle Version.');
        }

        $request = DocumentFormwerkRequest::query()->firstOrCreate(
            [
                'document_version_id' => $version->id,
                'user_id' => (int) $user->getKey(),
            ],
            [
                'public_id' => (string) Str::uuid(),
                'status' => FormwerkRequestStatus::Pending,
            ],
        );

        return $this->payload->urlFor($document, $user, (string) $request->public_id);
    }
}
