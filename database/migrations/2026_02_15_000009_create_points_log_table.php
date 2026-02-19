<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // presence, invitation, temoignage, membre_amene, reengagement
            $table->integer('points');
            $table->string('description')->nullable();
            $table->foreignId('reference_id')->nullable(); // ID de l'objet lié
            $table->string('reference_type')->nullable(); // type polymorphe
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_log');
    }
};
