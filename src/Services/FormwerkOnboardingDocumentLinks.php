<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppWorkflows\Contracts\OnboardingSignatureDocumentSource;
use Illuminate\Database\Eloquent\Model;

final class FormwerkOnboardingDocumentLinks implements OnboardingSignatureDocumentSource
{
    public function linksFor(Model $user): array
    {
        return Document::query()
            ->formwerkOnboarding()
            ->orderBy('title')
            ->get()
            ->map(fn (Document $document): array => [
                'title' => str_replace(['[', ']'], '', (string) $document->title),
                'url' => route('apps.dokumente.index', ['document' => $document->id]),
            ])
            ->all();
    }
}
