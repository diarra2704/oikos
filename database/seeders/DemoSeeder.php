<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StatutSpirituel;
use App\Models\Cellule;
use App\Models\FamilleDisciples;
use App\Models\Membre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin (Leadership) ──
        $admin = User::firstOrCreate(
            ['email' => 'admin@oikos.local'],
            [
                'nom' => 'Leadership',
                'prenom' => 'Admin',
                'telephone' => '237600000000',
                'password' => Hash::make('password'),
                'role' => Role::ADMIN,
                'statut_spirituel' => StatutSpirituel::FAISEUR_DISCIPLE,
                'actif' => true,
            ]
        );

        $fds = FamilleDisciples::all();

        foreach ($fds as $index => $fd) {
            // ── Superviseur pour chaque FD ──
            $superviseur = User::firstOrCreate(
                ['email' => "superviseur.{$fd->id}@oikos.local"],
                [
                    'nom' => "Superviseur",
                    'prenom' => $fd->nom,
                    'telephone' => '23769' . str_pad($fd->id, 7, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password'),
                    'role' => Role::SUPERVISEUR,
                    'statut_spirituel' => StatutSpirituel::FAISEUR_DISCIPLE,
                    'fd_id' => $fd->id,
                    'actif' => true,
                ]
            );

            $fd->update(['superviseur_id' => $superviseur->id]);

            // ── 2 Cellules par FD ──
            for ($c = 1; $c <= 2; $c++) {
                $cellule = Cellule::firstOrCreate(
                    ['nom' => "Cellule {$c}", 'fd_id' => $fd->id],
                    ['fd_id' => $fd->id]
                );

                // Leader de cellule
                $leader = User::firstOrCreate(
                    ['email' => "leader.{$fd->id}.{$c}@oikos.local"],
                    [
                        'nom' => "Leader{$c}",
                        'prenom' => $fd->nom,
                        'telephone' => '23768' . str_pad(($fd->id * 10 + $c), 7, '0', STR_PAD_LEFT),
                        'password' => Hash::make('password'),
                        'role' => Role::LEADER_CELLULE,
                        'statut_spirituel' => StatutSpirituel::FAISEUR_DISCIPLE,
                        'fd_id' => $fd->id,
                        'cellule_id' => $cellule->id,
                        'actif' => true,
                    ]
                );

                $cellule->update(['leader_id' => $leader->id]);

                // ── 3 Faiseurs par cellule ──
                for ($f = 1; $f <= 3; $f++) {
                    $faiseur = User::firstOrCreate(
                        ['email' => "faiseur.{$fd->id}.{$c}.{$f}@oikos.local"],
                        [
                            'nom' => "Faiseur{$f}",
                            'prenom' => "Cellule{$c}",
                            'telephone' => '23767' . str_pad(($fd->id * 100 + $c * 10 + $f), 7, '0', STR_PAD_LEFT),
                            'password' => Hash::make('password'),
                            'role' => Role::FAISEUR,
                            'statut_spirituel' => StatutSpirituel::FAISEUR_DISCIPLE,
                            'fd_id' => $fd->id,
                            'cellule_id' => $cellule->id,
                            'actif' => true,
                        ]
                    );

                    // ── 4 Membres/âmes par faiseur ──
                    for ($m = 1; $m <= 4; $m++) {
                        $statuts = [StatutSpirituel::NA, StatutSpirituel::NC, StatutSpirituel::FIDELE, StatutSpirituel::NC];
                        Membre::firstOrCreate(
                            ['telephone' => '23766' . str_pad(($fd->id * 1000 + $c * 100 + $f * 10 + $m), 7, '0', STR_PAD_LEFT)],
                            [
                                'nom' => "Membre{$m}",
                                'prenom' => "FD{$fd->id}-C{$c}-F{$f}",
                                'statut_spirituel' => $statuts[$m - 1],
                                'fd_id' => $fd->id,
                                'cellule_id' => $cellule->id,
                                'suivi_par' => $faiseur->id,
                                'source' => $m <= 2 ? 'invitation' : 'evangelisation',
                                'invite_par' => $faiseur->id,
                                'actif' => true,
                                'date_premiere_visite' => now()->subDays(rand(7, 180)),
                            ]
                        );
                    }
                }
            }
        }
    }
}
