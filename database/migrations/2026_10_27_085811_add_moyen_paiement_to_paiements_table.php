<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->enum('moyen_paiement', ['especes', 'banque'])->default('especes')->after('type_tranche');
            $table->string('justificatif_path')->nullable()->after('moyen_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn(['moyen_paiement', 'justificatif_path']);
        });
    }
};