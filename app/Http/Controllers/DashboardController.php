<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatutSpirituel;
use App\Models\FamilleDisciples;
use App\Models\Membre;
use App\Http\Controllers\HistoriqueController;
use App\Models\Rappel;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match ($user->role) {
            Role::ADMIN => $this->adminDashboard($user),
            Role::SUPERVISEUR => $this->superviseurDashboard($user),
            Role::LEADER_CELLULE => $this->leaderDashboard($user),
            Role::FAISEUR => $this->faiseurDashboard($user),
        };
    }

    private function anniversairesDuMois($user): array
    {
        $query = Membre::query();
        if ($user->role === Role::SUPERVISEUR && $user->fd_id) {
            $query->where('fd_id', $user->fd_id);
        } elseif ($user->role === Role::LEADER_CELLULE && $user->cellule_id) {
            $query->where('cellule_id', $user->cellule_id);
        } elseif ($user->role === Role::FAISEUR) {
            $query->where('suivi_par', $user->id);
        }

        $naissance = (clone $query)->anniversairesNaissanceMois()->orderByRaw('DAY(date_naissance)')->limit(15)->get();
        $conversion = (clone $query)->anniversairesConversionMois()->orderByRaw('DAY(date_conversion)')->limit(15)->get();

        $items = [];
        foreach ($naissance as $m) {
            $items[] = ['type' => 'naissance', 'label' => $m->full_name, 'jour' => (int) $m->date_naissance->day, 'membre_id' => $m->id];
        }
        foreach ($conversion as $m) {
            $items[] = ['type' => 'conversion', 'label' => $m->full_name, 'jour' => (int) $m->date_conversion->day, 'membre_id' => $m->id];
        }
        usort($items, fn ($a, $b) => $a['jour'] <=> $b['jour']);
        return array_slice($items, 0, 15);
    }

    private function adminDashboard(User $user)
    {
        $fds = FamilleDisciples::withCount(['users', 'membres', 'cellules'])
            ->with('superviseur')
            ->get();

        $stats = [
            'total_fd' => $fds->count(),
            'total_membres' => Membre::count(),
            'total_faiseurs' => User::where('role', Role::FAISEUR)->count(),
            'total_na' => Membre::where('statut_spirituel', StatutSpirituel::NA)->count(),
            'total_nc' => Membre::where('statut_spirituel', StatutSpirituel::NC)->count(),
            'membres_actifs' => Membre::where('actif', true)->count(),
            'absents_3_semaines' => Membre::absentDepuis(3)->count(),
            'fd_stats' => $fds,
        ];

        $stats['anniversaires_du_mois'] = $this->anniversairesDuMois($user);
        $stats['dernieres_modifications'] = HistoriqueController::dernieresModifications($user, 5);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'role' => 'admin',
        ]);
    }

    private function superviseurDashboard(User $user)
    {
        $fd = FamilleDisciples::withCount(['users', 'membres', 'cellules'])
            ->find($user->fd_id);

        $membres = $fd
            ? Membre::where('fd_id', $fd->id)->get()
            : collect();

        $stats = [
            'fd' => $fd,
            'total_membres' => $membres->count(),
            'total_na' => $membres->where('statut_spirituel', StatutSpirituel::NA)->count(),
            'total_nc' => $membres->where('statut_spirituel', StatutSpirituel::NC)->count(),
            'membres_actifs' => $membres->where('actif', true)->count(),
            'absents_3_semaines' => $fd ? Membre::where('fd_id', $fd->id)->absentDepuis(3)->count() : 0,
        ];

        $stats['anniversaires_du_mois'] = $this->anniversairesDuMois($user);
        $stats['dernieres_modifications'] = HistoriqueController::dernieresModifications($user, 5);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'role' => 'superviseur',
        ]);
    }

    private function leaderDashboard(User $user)
    {
        $membres = Membre::where('cellule_id', $user->cellule_id)->get();
        $faiseurs = User::where('cellule_id', $user->cellule_id)
            ->where('role', Role::FAISEUR)
            ->withCount('membresAffecter')
            ->get();

        $stats = [
            'total_membres' => $membres->count(),
            'total_faiseurs' => $faiseurs->count(),
            'faiseurs' => $faiseurs,
            'absents_3_semaines' => Membre::where('cellule_id', $user->cellule_id)->absentDepuis(3)->count(),
        ];
        $stats['anniversaires_du_mois'] = $this->anniversairesDuMois($user);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'role' => 'leader_cellule',
        ]);
    }

    private function faiseurDashboard(User $user)
    {
        $mesAmes = Membre::where('suivi_par', $user->id)
            ->orderBy('derniere_presence', 'asc')
            ->get();

        $prochainsRappels = Rappel::pourFaiseur($user->id)
            ->nonFait()
            ->prochains(14)
            ->with('membre:id,nom,prenom')
            ->limit(15)
            ->get();

        $stats = [
            'total_ames' => $mesAmes->count(),
            'ames_actives' => $mesAmes->where('actif', true)->count(),
            'absents' => $mesAmes->filter(fn ($m) => ! $m->derniere_presence || $m->derniere_presence->lt(now()->subWeeks(3)))->count(),
            'mes_ames' => $mesAmes,
            'prochains_rappels' => $prochainsRappels,
        ];
        $stats['anniversaires_du_mois'] = $this->anniversairesDuMois($user);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'role' => 'faiseur',
        ]);
    }
}
