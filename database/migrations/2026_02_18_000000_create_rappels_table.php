<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rappels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membre_id')->constrained('membres')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // faiseur qui a créé le rappel
            $table->string('type', 50); // contacter, relance_interaction
            $table->date('date_souhaitee');
            $table->string('libelle', 255)->nullable(); // texte libre optionnel
            $table->dateTime('fait_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date_souhaitee']);
            $table->index(['membre_id', 'date_souhaitee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rappels');
    }
};
