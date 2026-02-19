<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membre_id')->constrained('membres')->cascadeOnDelete();
            $table->foreignId('faiseur_id')->constrained('users')->cascadeOnDelete();
            $table->string('type_canal'); // appel, whatsapp, visite, sms, rencontre_eglise, autre
            $table->dateTime('date_interaction');
            $table->text('resume'); // ce qui a été dit/fait
            $table->unsignedSmallInteger('duree_minutes')->nullable(); // durée approximative
            $table->date('prochain_rdv')->nullable(); // date du prochain contact prévu
            $table->text('prochain_objectif')->nullable(); // objectif du prochain contact
            $table->timestamps();

            $table->index(['membre_id', 'date_interaction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
