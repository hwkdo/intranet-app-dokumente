<?php

namespace Hwkdo\IntranetAppDokumente\Http\Controllers;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDocumentController
{
    public function __invoke(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('see-app-dokumente');

        $media = $document->getFirstMedia('document');
        if (! $media) {
            abort(404, 'Dokumentdatei nicht gefunden.');
        }

        return $media->toResponse($request);
    }
}
