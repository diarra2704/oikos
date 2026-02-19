<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suivis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faiseur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('membre_id')->constrained('membres')->cascadeOnDelete();
            $table->string('statut')->default('actif'); // actif, pause, termine
            $table->text('notes')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->unique(['faiseur_id', 'membre_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suivis');
    }
};
