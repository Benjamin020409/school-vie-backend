<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->foreignId('annee_scolaire_id')->nullable()->after('eleve_id')
                ->constrained('annees_scolaires')->restrictOnDelete();
            $table->index(['eleve_id', 'annee_scolaire_id']);
        });

        // Rattrape les paiements existants : l'année dont les dates contiennent le paiement,
        // sinon l'année active.
        $anneeActiveId = DB::table('annees_scolaires')->where('active', true)->value('id');

        foreach (DB::table('paiements')->get(['id', 'date_paiement']) as $p) {
            $anneeId = DB::table('annees_scolaires')
                ->where('date_debut', '<=', $p->date_paiement)
                ->where('date_fin', '>=', $p->date_paiement)
                ->value('id') ?? $anneeActiveId;

            if ($anneeId) {
                DB::table('paiements')->where('id', $p->id)->update(['annee_scolaire_id' => $anneeId]);
            }
        }

        // Une fois tout rattaché, la colonne devient obligatoire
        if (DB::table('paiements')->whereNull('annee_scolaire_id')->count() === 0) {
            DB::statement('ALTER TABLE paiements MODIFY annee_scolaire_id BIGINT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropIndex(['eleve_id', 'annee_scolaire_id']);
            $table->dropForeign(['annee_scolaire_id']);
            $table->dropColumn('annee_scolaire_id');
        });
    }
};