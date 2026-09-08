<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->enum('jour', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi']);
            $table->unsignedTinyInteger('heure');
            $table->timestamps();

            $table->unique(['classe_id', 'annee_scolaire_id', 'jour', 'heure'], 'seances_classe_creneau_unique');
            $table->unique(['enseignant_id', 'annee_scolaire_id', 'jour', 'heure'], 'seances_enseignant_creneau_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};