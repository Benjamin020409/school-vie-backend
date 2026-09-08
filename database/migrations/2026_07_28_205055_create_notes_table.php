<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->float('valeur')->nullable();
            $table->string('appreciation')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_id', 'eleve_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};