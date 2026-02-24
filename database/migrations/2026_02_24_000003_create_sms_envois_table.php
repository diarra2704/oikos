<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_envois', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100)->nullable();
            $table->text('message');
            $table->foreignId('fd_id')->nullable()->constrained('familles_disciples')->nullOnDelete();
            $table->json('membre_ids')->nullable(); // IDs des membres ciblés (null = tous de la FD)
            $table->string('statut', 20)->default('programme'); // programme, en_cours, envoye, annule
            $table->timestamp('date_programmee')->nullable();
            $table->timestamp('envoye_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_envois');
    }
};
