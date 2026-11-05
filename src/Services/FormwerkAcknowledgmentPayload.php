<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
use Hwkdo\IntranetAppFormwerk\Services\jwtService;
use Hwkdo\IntranetAppFormwerk\Support\GvpBusinessUnitResolver;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class FormwerkAcknowledgmentPayload
{
    /**
     * @var list<string>
     */
    public const DEFAULT_FIELDS = [
        'username',
        'vorname',
        'nachname',
        'email',
        'gb',
        'abteilung',
        'standort',
        'einstellungsdatum',
        'tel',
    ];

    public function __construct(private jwtService $jwt) {}

    /**
     * @return array<string, string|null>
     */
    public function datasetFor(Model $user): array
    {
        $user->loadMissing(['gvp.parent.parent', 'standort']);

        $gvp = $user->gvp ?? null;
        $einstellungsdatum = null;
        $eintritt = $user->eintrittsdatum ?? null;
        if ($eintritt instanceof \DateTimeInterface) {
            $einstellungsdatum = $eintritt->format('d.m.Y');
        } elseif (is_string($eintritt) && $eintritt !== '') {
            $einstellungsdatum = $eintritt;
        }

        $standort = $user->standort ?? null;
        $standortLabel = null;
        if ($standort !== null) {
            $standortLabel = trim((string) ($standort->name ?? $standort->bezeichnung ?? $standort->ort ?? ''));
            $standortLabel = $standortLabel !== '' ? $standortLabel : null;
        }

        $abteilung = $gvp ? trim((string) ($gvp->bezeichnung ?? '')) : '';

        return [
            'username' => $this->nullableString($user->username ?? null),
            'vorname' => $this->nullableString($user->vorname ?? null),
            'nachname' => $this->nullableString($user->nachname ?? null),
            'email' => $this->nullableString($user->email ?? null),
            'gb' => GvpBusinessUnitResolver::label($gvp),
            'abteilung' => $abteilung !== '' ? $abteilung : null,
            'standort' => $standortLabel,
            'einstellungsdatum' => $einstellungsdatum,
            'tel' => $this->nullableString($user->telefon ?? null),
        ];
    }

    /**
     * @return list<string>
     */
    public function selectedFields(): array
    {
        $configured = IntranetAppDokumenteSettings::resolvedAppSettings()->formwerkAcknowledgmentPayloadFields;
        $fields = [];
        foreach ($configured as $field) {
            if (is_string($field) && $field !== '') {
                $fields[] = $field;
            }
        }

        return $fields === [] ? self::DEFAULT_FIELDS : $fields;
    }

    public function formUrlFor(Document $document): string
    {
        $custom = trim((string) ($document->formwerk_form_url ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        $standard = trim((string) (IntranetAppDokumenteSettings::resolvedAppSettings()->formwerkAcknowledgmentFormUrl ?? ''));
        if ($standard === '') {
            throw new RuntimeException(
                'Die Standard-Formwerk-URL für die Kenntnisnahme ist nicht gesetzt (Dokumente-Einstellungen).',
            );
        }

        return $standard;
    }

    /**
     * @return array<string, string|null>
     */
    public function payloadFor(Document $document, Model $user, string $publicId): array
    {
        $dataset = $this->datasetFor($user);
        $custom = $document->formwerk_use_custom_payload && is_array($document->formwerk_payload) && $document->formwerk_payload !== [];

        $payload = [];
        if ($custom) {
            foreach ($document->formwerk_payload as $key => $template) {
                $payload[(string) $key] = $this->applyTemplate((string) $template, $dataset);
            }
        } else {
            foreach ($this->selectedFields() as $field) {
                $payload[$field] = $dataset[$field] ?? null;
            }
        }

        $payload['kenntnisnahme_ref'] = $publicId;
        $payload['document_title'] = (string) $document->title;
        $payload['document_link'] = route('apps.dokumente.index', ['document' => $document->id]);

        return $payload;
    }

    public function urlFor(Document $document, Model $user, string $publicId): string
    {
        $base = $this->formUrlFor($document);
        $token = $this->jwt->make($this->payloadFor($document, $user, $publicId));
        $separator = str_contains($base, '?') ? '&' : '?';

        return $base.$separator.'token='.$token;
    }

    /**
     * @param  array<string, string|null>  $dataset
     */
    private function applyTemplate(string $template, array $dataset): string
    {
        return (string) preg_replace_callback(
            '/\{([A-Za-z0-9_]+)\}/',
            fn (array $matches): string => (string) ($dataset[$matches[1]] ?? ''),
            $template,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string !== '' ? $string : null;
    }
}
