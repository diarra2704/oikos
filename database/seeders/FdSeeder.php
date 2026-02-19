<?php

namespace Database\Seeders;

use App\Models\FamilleDisciples;
use Illuminate\Database\Seeder;

class FdSeeder extends Seeder
{
    public function run(): void
    {
        $familles = [
            ['nom' => 'EXCELLENCE', 'description' => 'Famille de Disciples Excellence', 'couleur' => '#3B82F6'],
            ['nom' => 'FOI', 'description' => 'Famille de Disciples Foi', 'couleur' => '#10B981'],
            ['nom' => 'PERSÉVÉRANCE', 'description' => 'Famille de Disciples Persévérance', 'couleur' => '#F59E0B'],
            ['nom' => 'PRODUCTIVITÉ', 'description' => 'Famille de Disciples Productivité', 'couleur' => '#EF4444'],
            ['nom' => 'INVINCIBLES', 'description' => 'Famille de Disciples Invincibles', 'couleur' => '#8B5CF6'],
            ['nom' => 'PROSPÉRITÉ', 'description' => 'Famille de Disciples Prospérité', 'couleur' => '#EC4899'],
        ];

        foreach ($familles as $famille) {
            FamilleDisciples::firstOrCreate(
                ['nom' => $famille['nom']],
                $famille
            );
        }
    }
}
