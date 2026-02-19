<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $statuts = [
            ['type' => 'statut_formation', 'valeur' => 'en_cours', 'libelle' => 'En cours', 'ordre' => 1],
            ['type' => 'statut_formation', 'valeur' => 'validee', 'libelle' => 'Validée', 'ordre' => 2],
            ['type' => 'statut_formation', 'valeur' => 'non_achevee', 'libelle' => 'Non achevée', 'ordre' => 3],
        ];
        $types = [
            ['type' => 'type_formation', 'valeur' => '12_piliers', 'libelle' => '12 piliers', 'ordre' => 1],
            ['type' => 'type_formation', 'valeur' => '001', 'libelle' => '001 (Bienvenue Dans le Royaume)', 'ordre' => 2],
            ['type' => 'type_formation', 'valeur' => '101', 'libelle' => '101 (Les Fondements du Royaume)', 'ordre' => 3],
            ['type' => 'type_formation', 'valeur' => '201', 'libelle' => '201 (La Maturité Spirituelle)', 'ordre' => 4],
            ['type' => 'type_formation', 'valeur' => '202', 'libelle' => '202 (La Restauration de l\'âme)', 'ordre' => 5],
            ['type' => 'type_formation', 'valeur' => '301', 'libelle' => '301 (Poimano, l\'école des Bergers)', 'ordre' => 6],
        ];
        $now = now();
        foreach (array_merge($statuts, $types) as $row) {
            if (DB::table('parametre_valeurs')->where('type', $row['type'])->where('valeur', $row['valeur'])->exists()) {
                continue;
            }
            DB::table('parametre_valeurs')->insert([
                'type' => $row['type'],
                'valeur' => $row['valeur'],
                'libelle' => $row['libelle'],
                'ordre' => $row['ordre'],
                'actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('parametre_valeurs')->whereIn('type', ['statut_formation', 'type_formation'])->delete();
    }
};
