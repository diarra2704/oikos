<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inviteur_id')->constrained('users')->cascadeOnDelete();
            $table->string('nom_invite');
            $table->string('telephone_invite')->nullable();
            $table->date('date_evenement');
            $table->boolean('est_venu')->default(false);
            $table->boolean('devenu_membre')->default(false);
            $table->foreignId('nouveau_membre_id')->nullable()->constrained('membres')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
