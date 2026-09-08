<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('periode_id')->constrained('periodes')->cascadeOnDelete();
            $table->float('moyenne_generale')->nullable();
            $table->unsignedInteger('rang')->nullable();
            $table->unsignedInteger('effectif_classe')->nullable();
            $table->string('mention')->nullable();
            $table->json('detail_matieres')->nullable();
            $table->enum('statut', ['prepare', 'valide'])->default('prepare');
            $table->timestamp('date_validation')->nullable();
            $table->timestamps();

            $table->unique(['eleve_id', 'periode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletins');
    }
};