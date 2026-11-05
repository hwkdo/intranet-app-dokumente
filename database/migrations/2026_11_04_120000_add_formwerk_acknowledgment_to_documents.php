<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intranet_app_dokumente_documents', function (Blueprint $table): void {
            $table->string('acknowledgment_method', 32)->default('password')->after('requires_acknowledgment');
            $table->string('formwerk_form_url', 2048)->nullable()->after('acknowledgment_method');
            $table->boolean('formwerk_use_custom_payload')->default(false)->after('formwerk_form_url');
            $table->json('formwerk_payload')->nullable()->after('formwerk_use_custom_payload');
        });

        Schema::create('intranet_app_dokumente_formwerk_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('document_version_id');
            $table->unsignedBigInteger('user_id');
            $table->uuid('public_id');
            $table->string('formwerk_uuid')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamps();

            $table->foreign('document_version_id', 'iad_fw_req_version_fk')
                ->references('id')
                ->on('intranet_app_dokumente_document_versions')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'iad_fw_req_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->unique('public_id', 'iad_fw_req_public_uq');
            $table->unique(['user_id', 'document_version_id'], 'iad_fw_req_user_ver_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_app_dokumente_formwerk_requests');

        Schema::table('intranet_app_dokumente_documents', function (Blueprint $table): void {
            $table->dropColumn([
                'acknowledgment_method',
                'formwerk_form_url',
                'formwerk_use_custom_payload',
                'formwerk_payload',
            ]);
        });
    }
};
