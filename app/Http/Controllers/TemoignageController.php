<?php

namespace App\Http\Controllers;

use App\Models\Temoignage;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemoignageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Temoignage::with('user');

        if ($user->isAdmin()) {
            // Admin voit tout
        } elseif ($user->isSuperviseur()) {
            // Superviseur voit les témoignages de sa FD
            $userIds = User::where('fd_id', $user->fd_id)->pluck('id');
            $query->whereIn('user_id', $userIds);
        } elseif ($user->isLeaderCellule()) {
            // Leader voit les témoignages de sa cellule + les validés
            $userIds = User::where('cellule_id', $user->cellule_id)->pluck('id');
            $query->where(function ($q) use ($userIds) {
                $q->whereIn('user_id', $userIds)
                  ->orWhere('statut', 'valide');
            });
        } else {
            // Faiseur voit les siens + les validés de sa FD
            $userIdsFd = User::where('fd_id', $user->fd_id)->pluck('id');
            $query->where(function ($q) use ($user, $userIdsFd) {
                $q->where('user_id', $user->id)
                  ->orWhere(function ($q2) use ($userIdsFd) {
                      $q2->whereIn('user_id', $userIdsFd)->where('statut', 'valide');
                  });
            });
        }

        $temoignages = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Temoignages/Index', [
            'temoignages' => $temoignages,
            'userRole' => $user->role->value,
        ]);
    }

    public function create()
    {
        return Inertia::render('Temoignages/Create');
    }

    public function store(Request $request, PointsService $pointsService)
    {
        $validated = $request->validate([
            'contenu' => 'required|string|min:20|max:5000',
        ]);

        $temoignage = Temoignage::create([
            'user_id' => $request->user()->id,
            'contenu' => $validated['contenu'],
            'statut' => 'en_attente',
        ]);

        $pointsService->attribuerPoints(
            $request->user(),
            'temoignage',
            'Témoignage soumis',
            $temoignage
        );

        return redirect()->route('temoignages.index')
            ->with('success', 'Témoignage soumis pour validation.');
    }

    public function show(Request $request, Temoignage $temoignage)
    {
        $user = $request->user();

        // Vérifier accès
        if (!$user->isAdmin()) {
            if ($temoignage->user_id !== $user->id) {
                // Superviseur peut voir ceux de sa FD
                if ($user->isSuperviseur()) {
                    $temoignageUser = User::find($temoignage->user_id);
                    if (!$temoignageUser || $temoignageUser->fd_id !== $user->fd_id) {
                        abort(403, 'Vous ne pouvez voir que les témoignages de votre FD.');
                    }
                } elseif ($temoignage->statut !== 'valide') {
                    abort(403, 'Vous ne pouvez voir que vos propres témoignages.');
                }
            }
        }

        $temoignage->load(['user', 'validePar']);

        return Inertia::render('Temoignages/Show', [
            'temoignage' => $temoignage,
        ]);
    }

    /**
     * Valider/Rejeter un témoignage (superviseur+ de la même FD).
     */
    public function valider(Request $request, Temoignage $temoignage, BadgeService $badgeService)
    {
        $user = $request->user();

        // Vérifier que le superviseur peut valider (même FD)
        if (!$user->isAdmin()) {
            $temoignageUser = User::find($temoignage->user_id);
            if (!$temoignageUser || $temoignageUser->fd_id !== $user->fd_id) {
                abort(403, 'Vous ne pouvez valider que les témoignages de votre FD.');
            }
        }

        $validated = $request->validate([
            'statut' => 'required|in:valide,rejete',
        ]);

        $temoignage->update([
            'statut' => $validated['statut'],
            'valide_par' => $user->id,
            'valide_le' => now(),
        ]);

        if ($validated['statut'] === 'valide') {
            $badgeService->verifierEtAttribuer($temoignage->user);
        }

        $label = $validated['statut'] === 'valide' ? 'validé' : 'rejeté';

        return back()->with('success', "Témoignage {$label}.");
    }
}
