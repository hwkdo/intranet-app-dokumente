<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Http\Controllers;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Services\DocumentAcknowledgmentService;
use Hwkdo\IntranetAppDokumente\Services\FormwerkAcknowledgmentRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class FormwerkAcknowledgmentRedirectController
{
    public function __invoke(Request $request, Document $document, FormwerkAcknowledgmentRedirect $redirect): RedirectResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->can('view', $document)) {
            abort(403);
        }

        $back = redirect()->route('apps.dokumente.index', ['document' => $document->id]);

        if (! $document->usesFormwerkAcknowledgment() || $document->currentVersion === null) {
            return $back->with('message', 'Für dieses Dokument ist keine Unterschrift erforderlich.');
        }

        if (app(DocumentAcknowledgmentService::class)->hasAcknowledged($document->currentVersion, (int) $user->getKey())) {
            return $back->with('message', 'Bereits zur Kenntnis genommen.');
        }

        try {
            return redirect()->away($redirect->urlFor($document, $user));
        } catch (RuntimeException $e) {
            throw new HttpException(503, $e->getMessage(), $e);
        }
    }
}
