<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StatutSpirituel;
use App\Models\FamilleDisciples;
use App\Models\Membre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperviseursSeeder extends Seeder
{
    /**
     * Superviseurs à créer avec leur FD (nom exact en base : EXCELLENCE, FOI, PERSÉVÉRANCE, etc.)
     * email = identifiant de connexion unique.
     */
    private function superviseurs(): array
    {
        return [
            ['prenom' => 'Jean-Yves', 'nom' => 'BALEGUEL', 'fd_nom' => 'EXCELLENCE', 'email' => 'jean-yves.baleguel@oikos.local'],
            ['prenom' => 'Jeff', 'nom' => 'MVONDO', 'fd_nom' => 'FOI', 'email' => 'jeff.mvondo@oikos.local'],
            ['prenom' => 'Stéphanie', 'nom' => 'TCHAMO', 'fd_nom' => 'PERSÉVÉRANCE', 'email' => 'stephanie.tchamo@oikos.local'],
            ['prenom' => 'Willy', 'nom' => 'NDOUMEN', 'fd_nom' => 'PRODUCTIVITÉ', 'email' => 'willy.ndoumen@oikos.local'],
            ['prenom' => 'Stéphanie', 'nom' => 'MATEKE', 'fd_nom' => 'INVINCIBLES', 'email' => 'stephanie.mateke@oikos.local'],
            ['prenom' => 'Dorette', 'nom' => 'UM', 'fd_nom' => 'PROSPÉRITÉ', 'email' => 'dorette.um@oikos.local'],
        ];
    }

    public function run(): void
    {
        $password = Hash::make('password');
        $telephoneBase = '2377900000'; // préfixe dédié superviseurs pour éviter conflits

        foreach ($this->superviseurs() as $i => $s) {
            $fd = FamilleDisciples::where('nom', $s['fd_nom'])->first();
            if (!$fd) {
                $this->command->warn("FD « {$s['fd_nom']} » introuvable, superviseur {$s['prenom']} {$s['nom']} ignoré.");
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'nom' => $s['nom'],
                    'prenom' => $s['prenom'],
                    'telephone' => $telephoneBase . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'password' => $password,
                    'role' => Role::SUPERVISEUR,
                    'statut_spirituel' => StatutSpirituel::FAISEUR_DISCIPLE,
                    'fd_id' => $fd->id,
                    'actif' => true,
                ]
            );

            if ($user->wasRecentlyCreated) {
                $this->command->info("Compte superviseur créé : {$s['prenom']} {$s['nom']} ({$s['email']}) — FD {$s['fd_nom']}. Connexion : mot de passe par défaut « password ».");
            }

            $fd->update(['superviseur_id' => $user->id]);

            // Le superviseur est aussi un membre de sa FD
            $telephone = $telephoneBase . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            Membre::firstOrCreate(
                [
                    'fd_id' => $fd->id,
                    'email' => $s['email'],
                ],
                [
                    'nom' => $s['nom'],
                    'prenom' => $s['prenom'],
                    'telephone' => $telephone,
                    'statut_spirituel' => 'faiseur_disciple',
                    'actif' => true,
                    'source' => 'invitation',
                ]
            );
        }
    }
}
