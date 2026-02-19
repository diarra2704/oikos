<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cellules', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('fd_id')->constrained('familles_disciples')->cascadeOnDelete();
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Ajouter la FK cellule_id sur users
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('cellule_id')->references('id')->on('cellules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cellule_id']);
        });
        Schema::dropIfExists('cellules');
    }
};
