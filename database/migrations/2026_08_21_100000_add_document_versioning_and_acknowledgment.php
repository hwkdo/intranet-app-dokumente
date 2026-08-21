<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_app_dokumente_document_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedInteger('version_number');
            $table->unsignedBigInteger('uploader_id')->nullable();
            $table->timestamps();

            $table->foreign('document_id', 'dokumente_versions_document_fk')
                ->references('id')
                ->on('intranet_app_dokumente_documents')
                ->cascadeOnDelete();
            $table->foreign('uploader_id', 'dokumente_versions_uploader_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->unique(['document_id', 'version_number'], 'dokumente_versions_doc_num_unique');
        });

        Schema::create('intranet_app_dokumente_document_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('document_version_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('document_id', 'dokumente_histories_document_fk')
                ->references('id')
                ->on('intranet_app_dokumente_documents')
                ->cascadeOnDelete();
            $table->foreign('document_version_id', 'dokumente_histories_version_fk')
                ->references('id')
                ->on('intranet_app_dokumente_document_versions')
                ->nullOnDelete();
            $table->foreign('user_id', 'dokumente_histories_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['document_id', 'created_at'], 'dokumente_histories_doc_created_idx');
        });

        Schema::create('intranet_app_dokumente_document_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_version_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('acknowledged_at');
            $table->string('confirmation_method')->default('password');
            $table->timestamps();

            $table->foreign('document_version_id', 'dokumente_acks_version_fk')
                ->references('id')
                ->on('intranet_app_dokumente_document_versions')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'dokumente_acks_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(['user_id', 'document_version_id'], 'dokumente_acks_user_version_unique');
        });

        Schema::table('intranet_app_dokumente_documents', function (Blueprint $table) {
            $table->boolean('requires_acknowledgment')->default(false)->after('aktiv');
            $table->boolean('is_onboarding_it')->default(false)->after('requires_acknowledgment');
            $table->boolean('is_onboarding_perso')->default(false)->after('is_onboarding_it');
            $table->unsignedBigInteger('legacy_id')->nullable()->after('is_onboarding_perso');
            $table->unsignedBigInteger('current_version_id')->nullable()->after('legacy_id');
            $table->timestamp('last_review_notified_at')->nullable()->after('current_version_id');
            $table->softDeletes();

            $table->unique('legacy_id', 'dokumente_documents_legacy_unique');
            $table->foreign('current_version_id', 'dokumente_documents_current_version_fk')
                ->references('id')
                ->on('intranet_app_dokumente_document_versions')
                ->nullOnDelete();
        });

        $this->backfillVersionsAndMedia();
    }

    protected function backfillVersionsAndMedia(): void
    {
        $documents = DB::table('intranet_app_dokumente_documents')->orderBy('id')->get();

        foreach ($documents as $document) {
            $versionId = DB::table('intranet_app_dokumente_document_versions')->insertGetId([
                'document_id' => $document->id,
                'version_number' => 1,
                'uploader_id' => $document->uploader_id,
                'created_at' => $document->created_at ?? now(),
                'updated_at' => $document->updated_at ?? now(),
            ]);

            DB::table('intranet_app_dokumente_documents')
                ->where('id', $document->id)
                ->update(['current_version_id' => $versionId]);

            DB::table('intranet_app_dokumente_document_histories')->insert([
                'document_id' => $document->id,
                'document_version_id' => $versionId,
                'user_id' => $document->uploader_id,
                'event' => 'created',
                'meta' => json_encode(['backfill' => true]),
                'created_at' => $document->created_at ?? now(),
                'updated_at' => $document->updated_at ?? now(),
            ]);

            DB::table('media')
                ->where('model_type', 'Hwkdo\\IntranetAppDokumente\\Models\\Document')
                ->where('model_id', $document->id)
                ->where('collection_name', 'document')
                ->update([
                    'model_type' => 'Hwkdo\\IntranetAppDokumente\\Models\\DocumentVersion',
                    'model_id' => $versionId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('intranet_app_dokumente_documents', function (Blueprint $table) {
            $table->dropForeign('dokumente_documents_current_version_fk');
            $table->dropUnique('dokumente_documents_legacy_unique');
            $table->dropColumn([
                'requires_acknowledgment',
                'is_onboarding_it',
                'is_onboarding_perso',
                'legacy_id',
                'current_version_id',
                'last_review_notified_at',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('intranet_app_dokumente_document_acknowledgments');
        Schema::dropIfExists('intranet_app_dokumente_document_histories');
        Schema::dropIfExists('intranet_app_dokumente_document_versions');
    }
};
