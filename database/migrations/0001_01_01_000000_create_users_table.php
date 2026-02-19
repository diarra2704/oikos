<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone')->unique();
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('faiseur'); // admin, superviseur, leader_cellule, faiseur
            $table->string('statut_spirituel')->default('fidele'); // NA, NC, fidele, STAR, faiseur_disciple
            $table->unsignedBigInteger('fd_id')->nullable();
            $table->unsignedBigInteger('cellule_id')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('genre', 1)->nullable(); // M, F
            $table->string('quartier')->nullable();
            $table->date('date_arrivee_eglise')->nullable();
            $table->date('date_conversion')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamp('derniere_presence')->nullable();
            $table->string('photo')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
