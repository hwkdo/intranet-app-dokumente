<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Http\Controllers;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentAcknowledgment;
use Hwkdo\IntranetAppDokumente\Services\DocumentAcknowledgmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadSignedAcknowledgmentController
{
    use AuthorizesRequests;

    public function __invoke(Request $request, int $document, int $acknowledgment): StreamedResponse
    {
        $documentModel = Document::query()->withTrashed()->findOrFail($document);
        $this->authorize('viewAcknowledgmentReport', $documentModel);

        $acknowledgmentModel = DocumentAcknowledgment::query()->findOrFail($acknowledgment);
        $version = $acknowledgmentModel->version;

        if ($version === null || (int) $version->document_id !== (int) $documentModel->id) {
            abort(404);
        }

        if ($acknowledgmentModel->confirmation_method !== DocumentAcknowledgmentService::METHOD_FORMWERK) {
            abort(404);
        }

        $media = $acknowledgmentModel->getFirstMedia('signed_pdf');
        if ($media === null) {
            abort(404);
        }

        return $media->toResponse($request);
    }
}
