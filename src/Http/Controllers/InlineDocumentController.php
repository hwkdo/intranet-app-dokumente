<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Http\Controllers;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InlineDocumentController
{
    use AuthorizesRequests;

    public function __invoke(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        $targetVersion = $document->currentVersion;
        if (! $targetVersion) {
            abort(404, 'Dokumentdatei nicht gefunden.');
        }

        $media = $targetVersion->getFirstMedia('document');
        if (! $media) {
            abort(404, 'Dokumentdatei nicht gefunden.');
        }

        $response = $media->toInlineResponse($request);
        $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');

        return $response;
    }
}
