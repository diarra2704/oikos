<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membre_formations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membre_id')->constrained('membres')->cascadeOnDelete();
            $table->string('type_formation', 50); // valeur du param type_formation (ex: 12_piliers, 001)
            $table->string('statut_formation', 50); // valeur du param statut_formation (en_cours, validee, non_achevee)
            $table->timestamps();

            $table->unique(['membre_id', 'type_formation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membre_formations');
    }
};
