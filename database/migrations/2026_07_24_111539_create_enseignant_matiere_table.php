<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignant_matiere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['enseignant_id', 'matiere_id']);
        });

        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropColumn('specialite');
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->string('specialite')->nullable();
        });

        Schema::dropIfExists('enseignant_matiere');
    }
};