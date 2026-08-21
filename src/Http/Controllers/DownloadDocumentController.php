<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Http\Controllers;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentVersion;
use Hwkdo\IntranetAppDokumente\Services\DocumentAcknowledgmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDocumentController
{
    use AuthorizesRequests;

    public function __invoke(Request $request, Document $document, ?DocumentVersion $version = null): StreamedResponse
    {
        $this->authorize('view', $document);

        $targetVersion = $version;
        if ($targetVersion === null) {
            $targetVersion = $document->currentVersion;
        } elseif ((int) $targetVersion->document_id !== (int) $document->id) {
            abort(404, 'Dokumentdatei nicht gefunden.');
        }

        if (! $targetVersion) {
            abort(404, 'Dokumentdatei nicht gefunden.');
        }

        $media = $targetVersion->getFirstMedia('document');
        if (! $media) {
            abort(404, 'Dokumentdatei nicht gefunden.');
        }

        app(DocumentAcknowledgmentService::class)->markDownloadedForAcknowledgment($document, $targetVersion);

        return $media->toResponse($request);
    }
}
