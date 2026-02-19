<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametre_valeurs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // statut_spirituel, source, profession, situation_personnelle, niveau_etude, secteur_activite, quartier
            $table->string('valeur', 100); // code stocké en base (ex: NA, marie_sans_enfant)
            $table->string('libelle', 191); // libellé affiché
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::table('parametre_valeurs', function (Blueprint $table) {
            $table->index(['type', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametre_valeurs');
    }
};
