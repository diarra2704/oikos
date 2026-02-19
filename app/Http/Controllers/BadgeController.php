<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BadgeService;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BadgeController extends Controller
{
    /**
     * Tableau d'honneur : classement filtré par FD.
     */
    public function honneur(Request $request, PointsService $pointsService)
    {
        $user = $request->user();

        $query = User::where('actif', true)->withCount('badges');

        // Filtrer par FD selon le rôle
        if ($user->isSuperviseur()) {
            $query->where('fd_id', $user->fd_id);
        } elseif ($user->isLeaderCellule()) {
            $query->where('cellule_id', $user->cellule_id);
        } elseif ($user->isFaiseur()) {
            $query->where('fd_id', $user->fd_id);
        }

        $users = $query->get()
            ->map(function ($u) use ($pointsService) {
                return [
                    'id' => $u->id,
                    'nom' => $u->nom,
                    'prenom' => $u->prenom,
                    'role' => $u->role->value,
                    'fd_id' => $u->fd_id,
                    'total_points' => $pointsService->getTotalPoints($u),
                    'badges_count' => $u->badges_count,
                    'palier' => $pointsService->getPalierActuel($u),
                ];
            })
            ->sortByDesc('total_points')
            ->values()
            ->take(50);

        return Inertia::render('Badges/Honneur', [
            'classement' => $users,
        ]);
    }

    /**
     * Profil gamification de l'utilisateur connecté.
     */
    public function profil(Request $request, BadgeService $badgeService, PointsService $pointsService)
    {
        $user = $request->user();

        $nouveauxBadges = $badgeService->verifierEtAttribuer($user);
        $badges = $badgeService->getBadgesAvecStatut($user);
        $progression = $pointsService->getProgression($user);

        $pointsRecents = $user->pointsLog()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Badges/Profil', [
            'badges' => $badges,
            'progression' => $progression,
            'pointsRecents' => $pointsRecents,
            'nouveauxBadges' => $nouveauxBadges,
        ]);
    }
}
