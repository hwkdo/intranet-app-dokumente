<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use Hwkdo\IntranetAppDokumente\Exceptions\LightRagDocumentException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LightRagDokumenteClient
{
    /**
     * @return array{track_id: string}
     */
    public function insertText(string $text, string $fileSource): array
    {
        $response = $this->request()->post($this->baseUrl().'/documents/text', [
            'text' => $text,
            'file_source' => $fileSource,
        ]);

        if ($response->status() === 409) {
            throw new LightRagDocumentException('LightRAG enthält die Datei bereits.');
        }

        $response->throw();

        $trackId = $response->json('track_id');
        if (! is_string($trackId) || $trackId === '') {
            throw new LightRagDocumentException('LightRAG hat keine track_id geliefert.');
        }

        return ['track_id' => $trackId];
    }

    /**
     * @return array{track_id: string}
     */
    public function uploadFile(string $absolutePath, string $fileName): array
    {
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new LightRagDocumentException('Die Dokumentdatei konnte nicht gelesen werden.');
        }

        try {
            $response = $this->request()
                ->attach('file', $handle, $fileName)
                ->post($this->baseUrl().'/documents/upload');
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if ($response->status() === 409) {
            throw new LightRagDocumentException('LightRAG enthält die Datei bereits.');
        }

        $response->throw();

        $trackId = $response->json('track_id');
        if (! is_string($trackId) || $trackId === '') {
            throw new LightRagDocumentException('LightRAG hat keine track_id geliefert.');
        }

        return ['track_id' => $trackId];
    }

    public function deleteDocument(string $docId): void
    {
        $response = $this->request()->post($this->baseUrl().'/documents/delete_document', [
            'doc_ids' => [$docId],
            'delete_file' => true,
            'delete_llm_cache' => true,
        ]);

        if ($response->status() === 404) {
            return;
        }

        $response->throw();
    }

    /**
     * @return array{status: string, doc_id: ?string, error_message: ?string}
     */
    public function trackStatus(string $trackId): array
    {
        try {
            $response = $this->request()
                ->get($this->baseUrl().'/documents/track_status/'.rawurlencode($trackId))
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('LightRAG-Status konnte nicht gelesen werden.', previous: $exception);
        }

        $document = $response->json('documents.0') ?? [];
        $status = is_array($document) ? (string) ($document['status'] ?? '') : '';

        return [
            'status' => $status,
            'doc_id' => is_array($document) && is_string($document['id'] ?? null) ? $document['id'] : null,
            'error_message' => is_array($document) && is_string($document['error_msg'] ?? null) ? $document['error_msg'] : null,
        ];
    }

    private function request(): PendingRequest
    {
        $apiKey = trim((string) config('intranet-app-dokumente.lightrag.api_key'));
        if ($apiKey === '' || $this->baseUrl() === '') {
            throw new LightRagDocumentException('Die LightRAG-Instanz für Dokumente ist nicht konfiguriert.');
        }

        return Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Accept' => 'application/json',
        ])->timeout(120);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('intranet-app-dokumente.lightrag.url'), '/');
    }
}
