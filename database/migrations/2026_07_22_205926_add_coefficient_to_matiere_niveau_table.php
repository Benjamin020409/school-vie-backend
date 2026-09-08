<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matiere_niveau', function (Blueprint $table) {
            $table->float('coefficient')->default(1)->after('niveau_id');
        });
    }

    public function down(): void
    {
        Schema::table('matiere_niveau', function (Blueprint $table) {
            $table->dropColumn('coefficient');
        });
    }
};