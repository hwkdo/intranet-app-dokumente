<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('intranet_app_dokumente_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $categories = [
            'Dienstanweisungen / Richtlinien / Verordnungen',
            'Handbücher / Konzepte / Verzeichnisse',
            'Prozessbeschreibungen / Organisationspläne',
            'Vorlagen / Formulare / Muster',
            'News / Info-Schreiben',
            'Sonstiges',
        ];

        foreach ($categories as $index => $name) {
            DB::table('intranet_app_dokumente_categories')->insert([
                'name' => $name,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intranet_app_dokumente_categories');
    }
};
