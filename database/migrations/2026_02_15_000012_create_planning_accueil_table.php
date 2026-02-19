<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_accueil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fd_id')->constrained('familles_disciples')->cascadeOnDelete();
            $table->unsignedTinyInteger('mois'); // 1-12
            $table->unsignedSmallInteger('annee');
            $table->timestamps();

            $table->unique(['mois', 'annee']); // une seule FD par mois
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_accueil');
    }
};
