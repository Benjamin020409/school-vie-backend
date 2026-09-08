<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->unsignedBigInteger('montant');
            $table->enum('mode_paiement', ['bloc', 'tranche']);
            $table->enum('type_tranche', ['mensuel', 'trimestriel', 'semestriel'])->nullable();
            $table->string('numero_recu')->unique();
            $table->date('date_paiement');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};