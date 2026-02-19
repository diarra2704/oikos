<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'nom' => 'Semeur',
                'slug' => 'semeur',
                'description' => 'A invité 3 nouvelles personnes à une rencontre',
                'icone' => 'seed',
                'couleur' => '#10B981',
                'criteres' => ['type' => 'invitations', 'seuil' => 3],
            ],
            [
                'nom' => 'Restaurateur',
                'slug' => 'restaurateur',
                'description' => 'A aidé à réengager 2 membres éloignés',
                'icone' => 'heart-handshake',
                'couleur' => '#3B82F6',
                'criteres' => ['type' => 'reengagements', 'seuil' => 2],
            ],
            [
                'nom' => 'Fidèle',
                'slug' => 'fidele',
                'description' => '100% de participation aux rencontres sur 3 mois',
                'icone' => 'shield-check',
                'couleur' => '#F59E0B',
                'criteres' => ['type' => 'participation', 'seuil' => 100, 'periode_mois' => 3],
            ],
            [
                'nom' => 'Transformé',
                'slug' => 'transforme',
                'description' => 'A partagé un témoignage de guérison/délivrance validé',
                'icone' => 'sparkles',
                'couleur' => '#8B5CF6',
                'criteres' => ['type' => 'temoignage_valide', 'seuil' => 1],
            ],
            [
                'nom' => 'Pionnier',
                'slug' => 'pionnier',
                'description' => 'A lancé un nouveau ministère ou projet dans la FD',
                'icone' => 'rocket',
                'couleur' => '#EF4444',
                'criteres' => ['type' => 'projet_lance', 'seuil' => 1],
            ],
            [
                'nom' => 'Connecteur',
                'slug' => 'connecteur',
                'description' => 'A intégré 3 nouveaux membres dans la FD',
                'icone' => 'users',
                'couleur' => '#06B6D4',
                'criteres' => ['type' => 'membres_integres', 'seuil' => 3],
            ],
            [
                'nom' => 'Ambassadeur',
                'slug' => 'ambassadeur',
                'description' => 'A invité 5 personnes ou partagé sa foi sur les réseaux',
                'icone' => 'megaphone',
                'couleur' => '#EC4899',
                'criteres' => ['type' => 'invitations', 'seuil' => 5],
            ],
            [
                'nom' => 'Serviteur',
                'slug' => 'serviteur',
                'description' => 'A servi dans 3 activités communautaires',
                'icone' => 'hand-helping',
                'couleur' => '#84CC16',
                'criteres' => ['type' => 'services', 'seuil' => 3],
            ],
        ];

        foreach ($badges as $badge) {
            Badge::firstOrCreate(
                ['slug' => $badge['slug']],
                $badge
            );
        }
    }
}
