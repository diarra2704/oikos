<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fd_id')->constrained('familles_disciples')->cascadeOnDelete();
            $table->string('type')->default('hebdomadaire'); // hebdomadaire, mensuel
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->json('contenu')->nullable(); // données structurées du rapport
            $table->string('statut')->default('brouillon'); // brouillon, soumis, valide
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports');
    }
};
