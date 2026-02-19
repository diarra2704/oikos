<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->string('profession', 100)->nullable()->after('quartier');
            $table->string('situation_personnelle', 50)->nullable()->after('profession');
            $table->string('niveau_etude', 50)->nullable()->after('situation_personnelle');
            $table->string('secteur_activite', 100)->nullable()->after('niveau_etude');
            $table->unsignedTinyInteger('nombre_enfants')->nullable()->after('secteur_activite');
            $table->text('competences_centres_interet')->nullable()->after('nombre_enfants');
            $table->string('contact_urgence_nom', 100)->nullable()->after('competences_centres_interet');
            $table->string('contact_urgence_telephone', 20)->nullable()->after('contact_urgence_nom');
            $table->text('besoins_particuliers')->nullable()->after('contact_urgence_telephone');
        });
    }

    public function down(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->dropColumn([
                'profession',
                'situation_personnelle',
                'niveau_etude',
                'secteur_activite',
                'nombre_enfants',
                'competences_centres_interet',
                'contact_urgence_nom',
                'contact_urgence_telephone',
                'besoins_particuliers',
            ]);
        });
    }
};
