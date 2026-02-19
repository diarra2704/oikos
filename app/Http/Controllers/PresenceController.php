<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\Presence;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PresenceController extends Controller
{
    /**
     * Retourne les IDs des membres visibles par l'utilisateur.
     */
    private function getMembresScope($user)
    {
        if ($user->isAdmin()) {
            return Membre::where('actif', true);
        }
        if ($user->isSuperviseur()) {
            return Membre::where('fd_id', $user->fd_id)->where('actif', true);
        }
        if ($user->isLeaderCellule()) {
            return Membre::where('cellule_id', $user->cellule_id)->where('actif', true);
        }
        // Faiseur
        return Membre::where('suivi_par', $user->id)->where('actif', true);
    }

    /**
     * Formulaire de pointage des présences (mobile-first).
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $date = $request->get('date', now()->format('Y-m-d'));
        $type = $request->get('type', 'culte');

        $membres = $this->getMembresScope($user)->orderBy('prenom')->get();

        // Récupérer les présences déjà pointées pour cette date
        $dejaPointees = Presence::where('date_evenement', $date)
            ->where('type_evenement', $type)
            ->whereIn('membre_id', $membres->pluck('id'))
            ->pluck('membre_id')
            ->toArray();

        return Inertia::render('Presences/Create', [
            'membres' => $membres,
            'dejaPointees' => $dejaPointees,
            'date' => $date,
            'type' => $type,
        ]);
    }

    /**
     * Enregistrer les présences en batch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_evenement' => 'required|date',
            'type_evenement' => 'required|string|in:culte,priere,reunion_fd,fi,formation',
            'presences' => 'required|array',
            'presences.*.membre_id' => 'required|exists:membres,id',
            'presences.*.present' => 'required|boolean',
        ]);

        $user = $request->user();

        // Vérifier que tous les membres soumis sont dans le scope de l'utilisateur
        $membresAutorises = $this->getMembresScope($user)->pluck('id')->toArray();

        foreach ($validated['presences'] as $p) {
            if (!in_array($p['membre_id'], $membresAutorises)) {
                abort(403, 'Vous ne pouvez pointer que vos propres membres.');
            }

            Presence::updateOrCreate(
                [
                    'membre_id' => $p['membre_id'],
                    'type_evenement' => $validated['type_evenement'],
                    'date_evenement' => $validated['date_evenement'],
                ],
                [
                    'present' => $p['present'],
                    'pointe_par' => $user->id,
                ]
            );

            // Mettre à jour la dernière présence du membre si présent
            if ($p['present']) {
                Membre::where('id', $p['membre_id'])->update([
                    'derniere_presence' => $validated['date_evenement'],
                ]);
            }
        }

        return redirect()->route('dashboard')
            ->with('success', 'Présences enregistrées avec succès.');
    }

    /**
     * Historique des présences (filtré par scope).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $membreIds = $this->getMembresScope($user)->pluck('id');

        $presences = Presence::with('membre')
            ->whereIn('membre_id', $membreIds)
            ->orderBy('date_evenement', 'desc')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Presences/Index', [
            'presences' => $presences,
        ]);
    }
}
