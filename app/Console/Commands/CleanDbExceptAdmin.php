<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Invitation;
use App\Models\Membre;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDbExceptAdmin extends Command
{
    protected $signature = 'db:clean-except-admin
                            {--force : Ne pas demander de confirmation}';

    protected $description = 'Supprime tous les utilisateurs (sauf un admin), tous les membres et les données liées (présences, rapports, invitations, points, etc.).';

    public function handle(): int
    {
        $admin = User::where('role', Role::ADMIN)->orderBy('id')->first();
        if (!$admin) {
            $this->error('Aucun compte administrateur trouvé. Créez-en un avant de lancer cette commande.');
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm("Conserver uniquement l'admin « {$admin->prenom} {$admin->nom} » ({$admin->email}) et tout supprimer d'autre ?")) {
            return self::SUCCESS;
        }

        $this->info('Nettoyage en cours…');

        DB::transaction(function () use ($admin) {
            $adminId = $admin->id;

            // ── Données liées aux membres (à supprimer avant les membres) ──
            Presence::query()->delete();
            DB::table('interactions')->delete();
            DB::table('membre_formations')->delete();
            DB::table('rappels')->delete();
            DB::table('suivis')->delete();
            DB::table('transferts')->delete();

            // Décrocher les invitations des membres avant suppression des membres
            Invitation::query()->update(['nouveau_membre_id' => null]);

            // Suppression définitive de tous les membres (y compris soft-deleted)
            Membre::withTrashed()->forceDelete();

            // ── Données liées aux utilisateurs (hors admin) ──
            DB::table('points_log')->where('user_id', '!=', $adminId)->delete();
            DB::table('membre_vues')->where('user_id', '!=', $adminId)->delete();
            DB::table('rapport_mensuels')->where('faiseur_id', '!=', $adminId)->delete();
            DB::table('rapports')->where('auteur_id', '!=', $adminId)->delete();
            Invitation::where('inviteur_id', '!=', $adminId)->delete();
            DB::table('temoignages')->where('user_id', '!=', $adminId)->delete();
            DB::table('user_badges')->where('user_id', '!=', $adminId)->delete();
            DB::table('audit_logs')->where('user_id', '!=', $adminId)->delete();
            DB::table('audit_logs')->whereNull('user_id')->delete();

            // Décrocher cellules et FD des users qu'on va supprimer
            DB::table('cellules')->where('leader_id', '!=', $adminId)->update(['leader_id' => null]);
            DB::table('familles_disciples')->where('superviseur_id', '!=', $adminId)->update(['superviseur_id' => null]);

            // Suppression définitive des utilisateurs non-admin
            User::where('id', '!=', $adminId)->forceDelete();
        });

        $this->info('Nettoyage terminé. Seul le compte admin a été conservé.');
        return self::SUCCESS;
    }
}
