<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->foreignId('famille_impact_id')->nullable()->after('cellule_id')->constrained('familles_impact')->nullOnDelete();
            $table->string('statut_famille_impact', 50)->nullable()->after('famille_impact_id');
        });
    }

    public function down(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->dropForeign(['famille_impact_id']);
            $table->dropColumn(['famille_impact_id', 'statut_famille_impact']);
        });
    }
};
