<?php

declare(strict_types=1);

use Hwkdo\IntranetAppDokumente\Services\DokumenteCitationLinker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropIfExists('intranet_app_dokumente_document_versions');
    Schema::dropIfExists('intranet_app_dokumente_documents');

    Schema::create('intranet_app_dokumente_documents', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->timestamp('deleted_at')->nullable();
        $table->timestamps();
    });

    Schema::create('intranet_app_dokumente_document_versions', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('document_id');
        $table->unsignedInteger('version_number');
    });
});

it('links dokument filenames of an existing version to the inline route', function () {
    DB::table('intranet_app_dokumente_documents')->insert([
        'id' => 7,
        'title' => 'Reisekosten',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('intranet_app_dokumente_document_versions')->insert([
        'document_id' => 7,
        'version_number' => 2,
    ]);

    $resolved = app(DokumenteCitationLinker::class)->resolveMany([
        'dokument-7-v2.md',
        'dokument-7-v2.pdf',
        'dokument-7-v9.md',
    ]);

    expect($resolved)->toHaveKey('dokument-7-v2.md')
        ->and($resolved['dokument-7-v2.pdf']['title'])->toBe('Reisekosten')
        ->and($resolved['dokument-7-v2.pdf']['url'])->toContain('/apps/dokumente/inline/7')
        ->and($resolved)->not->toHaveKey('dokument-7-v9.md');
});
