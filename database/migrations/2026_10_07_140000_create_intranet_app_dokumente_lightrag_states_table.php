<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_app_dokumente_lightrag_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id')->unique('iad_lightrag_document_uq');
            $table->unsignedBigInteger('document_version_id')->nullable();
            $table->string('lightrag_doc_id')->nullable();
            $table->string('track_id')->nullable();
            $table->string('status', 32);
            $table->text('error_message')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->index('status', 'iad_lightrag_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_app_dokumente_lightrag_states');
    }
};
