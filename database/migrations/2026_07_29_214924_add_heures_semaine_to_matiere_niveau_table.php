<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matiere_niveau', function (Blueprint $table) {
            $table->unsignedTinyInteger('heures_semaine')->default(2)->after('coefficient');
        });
    }

    public function down(): void
    {
        Schema::table('matiere_niveau', function (Blueprint $table) {
            $table->dropColumn('heures_semaine');
        });
    }
};