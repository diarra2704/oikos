<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['type' => 'statut_famille_impact', 'valeur' => 'n_a_pas_encore_integre', 'libelle' => 'N\'a pas encore intégré', 'ordre' => 1],
            ['type' => 'statut_famille_impact', 'valeur' => 'viens_d_integrer', 'libelle' => 'Vient d\'intégrer', 'ordre' => 2],
            ['type' => 'statut_famille_impact', 'valeur' => 'est_regulier', 'libelle' => 'Est régulier', 'ordre' => 3],
            ['type' => 'statut_famille_impact', 'valeur' => 'est_engage', 'libelle' => 'Est engagé', 'ordre' => 4],
        ];
        $now = now();
        foreach ($rows as $row) {
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
        DB::table('parametre_valeurs')->where('type', 'statut_famille_impact')->delete();
    }
};
