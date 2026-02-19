<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fd_id')->nullable()->constrained('familles_disciples')->nullOnDelete();
            $table->string('type'); // evangelisation, agape, anniversaire, reunion, formation
            $table->string('titre');
            $table->text('description')->nullable();
            $table->datetime('date_debut');
            $table->datetime('date_fin')->nullable();
            $table->string('lieu')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};
