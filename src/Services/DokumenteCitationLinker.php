<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use Hwkdo\IntranetAppBase\Contracts\LightRagCitationLinker;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentVersion;
use Illuminate\Support\Facades\Route;

class DokumenteCitationLinker implements LightRagCitationLinker
{
    public function instance(): string
    {
        return 'dokumente';
    }

    public function pattern(): string
    {
        return '/(?<![\p{L}\p{N}_-])dokument-\d+-v\d+\.[A-Za-z0-9]{1,8}(?![\p{L}\p{N}_-])/iu';
    }

    public function resolveMany(array $fileNames): array
    {
        if (! Route::has('apps.dokumente.inline')) {
            return [];
        }

        /** @var array<string, array{id: int, version: int}> $names */
        $names = [];
        foreach ($fileNames as $fileName) {
            $key = $this->key($fileName);
            if (preg_match('/^dokument-(\d+)-v(\d+)\.[a-z0-9]{1,8}$/', $key, $match) !== 1) {
                continue;
            }

            $names[$key] = ['id' => (int) $match[1], 'version' => (int) $match[2]];
        }

        if ($names === []) {
            return [];
        }

        $ids = array_values(array_unique(array_column($names, 'id')));
        $documents = Document::query()->whereIn('id', $ids)->get(['id', 'title'])->keyBy('id');
        $versions = DocumentVersion::query()
            ->whereIn('document_id', $ids)
            ->get(['document_id', 'version_number']);

        $existingVersions = [];
        foreach ($versions as $version) {
            $existingVersions[(int) $version->document_id][(int) $version->version_number] = true;
        }

        $resolved = [];
        foreach ($names as $key => $name) {
            $document = $documents->get($name['id']);
            if ($document === null || ! ($existingVersions[$name['id']][$name['version']] ?? false)) {
                continue;
            }

            $title = trim((string) $document->title);
            $resolved[$key] = [
                'url' => route('apps.dokumente.inline', $document),
                'title' => $title !== '' ? $title : $key,
            ];
        }

        return $resolved;
    }

    private function key(string $fileName): string
    {
        return strtolower(basename(str_replace('\\', '/', trim($fileName))));
    }
}
