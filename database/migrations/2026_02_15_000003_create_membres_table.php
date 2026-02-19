<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des membres/âmes (personnes non-connectées, gérées comme fiches)
        Schema::create('membres', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('statut_spirituel')->default('NA'); // NA, NC, fidele, STAR, faiseur_disciple
            $table->foreignId('fd_id')->nullable()->constrained('familles_disciples')->nullOnDelete();
            $table->foreignId('cellule_id')->nullable()->constrained('cellules')->nullOnDelete();
            $table->foreignId('suivi_par')->nullable()->constrained('users')->nullOnDelete(); // faiseur qui suit cette âme
            $table->date('date_naissance')->nullable();
            $table->string('genre', 1)->nullable(); // M, F
            $table->string('quartier')->nullable();
            $table->date('date_premiere_visite')->nullable();
            $table->date('date_conversion')->nullable();
            $table->string('source')->nullable(); // invitation, evangelisation, culte, autre
            $table->foreignId('invite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->timestamp('derniere_presence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres');
    }
};
