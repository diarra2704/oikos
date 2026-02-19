<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familles_disciples', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique(); // EXCELLENCE, FOI, etc.
            $table->string('description')->nullable();
            $table->string('couleur', 7)->default('#3B82F6'); // hex color
            $table->foreignId('superviseur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Ajouter les FK sur users après la création de familles_disciples
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('fd_id')->references('id')->on('familles_disciples')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['fd_id']);
        });
        Schema::dropIfExists('familles_disciples');
    }
};
