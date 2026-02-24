<?php

namespace Database\Seeders;

use App\Models\Departement;
use Illuminate\Database\Seeder;

class DepartementSeeder extends Seeder
{
    public function run(): void
    {
        $noms = [
            'Accueil',
            'Audiovisuel',
            'Intercession',
            'Intégration',
            'Protocole',
            'Coordination',
            'Finance',
            'Logistique',
            'Restauration',
            'Louange',
            'Impact Junior',
            'Librairie',
            'Sécurité',
            'Evangélisation',
            'Oeuvres Sociales',
            'Communication',
        ];

        foreach ($noms as $nom) {
            Departement::firstOrCreate(
                ['nom' => $nom],
                ['description' => null, 'actif' => true]
            );
        }
    }
}
