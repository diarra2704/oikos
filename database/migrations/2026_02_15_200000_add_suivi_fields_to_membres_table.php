<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->boolean('en_pcnc')->default(false)->after('actif');
            $table->boolean('en_fi')->default(false)->after('en_pcnc');
            $table->boolean('regulier_eglise')->default(false)->after('en_fi');
            $table->string('niveau_integration')->default('decouverte')->after('regulier_eglise');
            $table->string('motif_sortie')->nullable()->after('niveau_integration');
        });
    }

    public function down(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->dropColumn(['en_pcnc', 'en_fi', 'regulier_eglise', 'niveau_integration', 'motif_sortie']);
        });
    }
};
