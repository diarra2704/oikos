<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\FamilleDisciples;
use App\Models\Invitation;
use App\Models\Membre;
use App\Models\Presence;
use App\Models\Rapport;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RapportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Rapport::with(['auteur', 'familleDisciples']);

        if ($user->isSuperviseur()) {
            // Superviseur voit les rapports de sa FD uniquement
            $query->where('fd_id', $user->fd_id);
        } elseif ($user->isLeaderCellule()) {
            // Leader voit les rapports de sa cellule (auteurs de sa cellule)
            $auteurIds = \App\Models\User::where('cellule_id', $user->cellule_id)->pluck('id');
            $query->whereIn('auteur_id', $auteurIds);
        } elseif ($user->isFaiseur()) {
            // Faiseur voit uniquement ses propres rapports
            $query->where('auteur_id', $user->id);
        }

        $rapports = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Rapports/Index', [
            'rapports' => $rapports,
        ]);
    }

    /**
     * Formulaire de rapport hebdomadaire (wizard mobile).
     */
    public function create(Request $request)
    {
        $user = $request->user();

        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

        $mesAmes = Membre::where('suivi_par', $user->id)->get();

        $presencesSemaine = Presence::whereIn('membre_id', $mesAmes->pluck('id'))
            ->whereBetween('date_evenement', [$debutSemaine, $finSemaine])
            ->where('present', true)
            ->get();

        $invitationsSemaine = Invitation::where('inviteur_id', $user->id)
            ->whereBetween('created_at', [$debutSemaine, $finSemaine])
            ->get();

        return Inertia::render('Rapports/Create', [
            'mesAmes' => $mesAmes,
            'presencesSemaine' => $presencesSemaine,
            'invitationsSemaine' => $invitationsSemaine,
            'periode' => [
                'debut' => $debutSemaine->format('Y-m-d'),
                'fin' => $finSemaine->format('Y-m-d'),
            ],
        ]);
    }

    public function store(Request $request, PointsService $pointsService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'periode_debut' => 'required|date',
            'periode_fin' => 'required|date|after_or_equal:periode_debut',
            'type' => 'required|in:hebdomadaire,mensuel',
            'contenu' => 'required|array',
            'contenu.ames_presentes' => 'nullable|array',
            'contenu.total_ames_suivies' => 'nullable|integer|min:0',
            'contenu.invitations_faites' => 'nullable|integer|min:0',
            'contenu.invitations_abouties' => 'nullable|integer|min:0',
            'contenu.immersions_touchees' => 'nullable|integer|min:0',
            'contenu.immersions_disposes' => 'nullable|integer|min:0',
            // Anciens champs conservés pour rétrocompatibilité
            'contenu.invitations_lancees' => 'nullable|integer|min:0',
            'contenu.immersions_realisees' => 'nullable|integer|min:0',
            'contenu.difficultes' => 'nullable|string',
            'contenu.actions_semaine' => 'nullable|string',
            'contenu.sujets_priere' => 'nullable|string',
        ]);

        $rapport = Rapport::create([
            'auteur_id' => $user->id,
            'fd_id' => $user->fd_id, // Toujours la FD de l'utilisateur
            'type' => $validated['type'],
            'periode_debut' => $validated['periode_debut'],
            'periode_fin' => $validated['periode_fin'],
            'contenu' => $validated['contenu'],
            'statut' => 'soumis',
        ]);

        $pointsService->attribuerPointsRapport($rapport);

        return redirect()->route('rapports.index')
            ->with('success', 'Rapport soumis avec succès.');
    }

    public function show(Request $request, Rapport $rapport)
    {
        $user = $request->user();

        // Vérifier l'accès
        if (!$user->isAdmin()) {
            if ($user->isSuperviseur() && $rapport->fd_id !== $user->fd_id) {
                abort(403, 'Vous ne pouvez voir que les rapports de votre FD.');
            }
            if (($user->isLeaderCellule() || $user->isFaiseur()) && $rapport->auteur_id !== $user->id) {
                // Leader peut aussi voir les rapports de sa cellule
                if ($user->isLeaderCellule()) {
                    $auteurIds = \App\Models\User::where('cellule_id', $user->cellule_id)->pluck('id')->toArray();
                    if (!in_array($rapport->auteur_id, $auteurIds)) {
                        abort(403, 'Vous ne pouvez voir que les rapports de votre cellule.');
                    }
                } else {
                    abort(403, 'Vous ne pouvez voir que vos propres rapports.');
                }
            }
        }

        $rapport->load(['auteur', 'familleDisciples', 'validePar']);

        return Inertia::render('Rapports/Show', [
            'rapport' => $rapport,
        ]);
    }
}
