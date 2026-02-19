<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->after('created_by_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('cellules', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()->after('leader_id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->after('created_by_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('transferts', function (Blueprint $table) {
            $table->foreignId('updated_by_id')->nullable()->after('traite_par')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropForeign(['updated_by_id']);
        });
        Schema::table('cellules', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropForeign(['updated_by_id']);
        });
        Schema::table('transferts', function (Blueprint $table) {
            $table->dropForeign(['updated_by_id']);
        });
    }
};
