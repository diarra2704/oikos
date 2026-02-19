<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membre_id')->constrained('membres')->cascadeOnDelete();
            $table->foreignId('pointe_par')->nullable()->constrained('users')->nullOnDelete(); // faiseur qui a pointé
            $table->string('type_evenement')->default('culte'); // culte, priere, reunion_fd, fi, formation
            $table->date('date_evenement');
            $table->boolean('present')->default(true);
            $table->text('remarque')->nullable();
            $table->timestamps();

            $table->unique(['membre_id', 'type_evenement', 'date_evenement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
