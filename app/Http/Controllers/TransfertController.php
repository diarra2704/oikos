<?php

namespace App\Http\Controllers;

use App\Models\FamilleDisciples;
use App\Models\Membre;
use App\Models\Transfert;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransfertController extends Controller
{
    /**
     * Liste des demandes de transfert.
     * - Admin : toutes les demandes
     * - Superviseur : les demandes qu'il a soumises
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Transfert::with(['membre', 'demandeur', 'fdSource', 'fdDestination', 'traitePar', 'updatedBy:id,nom,prenom']);

        if ($user->isSuperviseur()) {
            $query->where('demandeur_id', $user->id);
        } elseif (!$user->isAdmin()) {
            abort(403);
        }

        $transferts = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $enAttente = $user->isAdmin()
            ? Transfert::enAttente()->count()
            : 0;

        return Inertia::render('Transferts/Index', [
            'transferts' => $transferts,
            'enAttente' => $enAttente,
            'userRole' => $user->role->value,
        ]);
    }

    /**
     * Formulaire de demande de transfert (superviseur).
     */
    public function create(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$user->isSuperviseur()) {
            abort(403);
        }

        // Le superviseur ne voit que les membres de sa FD
        if ($user->isSuperviseur()) {
            $membres = Membre::where('fd_id', $user->fd_id)
                ->where('actif', true)
                ->orderBy('prenom')
                ->get(['id', 'nom', 'prenom', 'fd_id', 'statut_spirituel']);
        } else {
            $membres = Membre::where('actif', true)
                ->orderBy('prenom')
                ->get(['id', 'nom', 'prenom', 'fd_id', 'statut_spirituel']);
        }

        $familles = FamilleDisciples::all(['id', 'nom', 'couleur']);

        return Inertia::render('Transferts/Create', [
            'membres' => $membres,
            'familles' => $familles,
            'userFdId' => $user->fd_id,
            'userRole' => $user->role->value,
        ]);
    }

    /**
     * Soumettre une demande de transfert (superviseur)
     * ou effectuer un transfert direct (admin).
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'fd_destination_id' => 'required|exists:familles_disciples,id',
            'motif' => 'nullable|string|max:1000',
        ]);

        $membre = Membre::findOrFail($validated['membre_id']);

        // Vérifier que la FD de destination est différente
        if ($membre->fd_id == $validated['fd_destination_id']) {
            return back()->withErrors(['fd_destination_id' => 'Le membre est déjà dans cette FD.']);
        }

        // Superviseur : vérifier que le membre est bien dans sa FD
        if ($user->isSuperviseur() && $membre->fd_id !== $user->fd_id) {
            abort(403, 'Vous ne pouvez transférer que les membres de votre FD.');
        }

        if ($user->isAdmin()) {
            // Admin : transfert direct, pas besoin de validation
            $transfert = Transfert::create([
                'membre_id' => $membre->id,
                'demandeur_id' => $user->id,
                'fd_source_id' => $membre->fd_id,
                'fd_destination_id' => $validated['fd_destination_id'],
                'motif' => $validated['motif'],
                'statut' => 'valide',
                'traite_par' => $user->id,
                'traite_le' => now(),
            ]);

            // Effectuer le transfert immédiatement
            $this->effectuerTransfert($membre, $validated['fd_destination_id']);

            $fdDest = FamilleDisciples::find($validated['fd_destination_id']);
            return redirect()->route('transferts.index')
                ->with('success', "{$membre->prenom} {$membre->nom} transféré(e) vers FD {$fdDest->nom}.");
        }

        // Superviseur : créer une demande en attente
        Transfert::create([
            'membre_id' => $membre->id,
            'demandeur_id' => $user->id,
            'fd_source_id' => $membre->fd_id,
            'fd_destination_id' => $validated['fd_destination_id'],
            'motif' => $validated['motif'],
            'statut' => 'en_attente',
        ]);

        return redirect()->route('transferts.index')
            ->with('success', 'Demande de transfert soumise. L\'administrateur sera notifié.');
    }

    /**
     * Page de traitement d'une demande (admin uniquement).
     */
    public function show(Request $request, Transfert $transfert)
    {
        $user = $request->user();

        if (!$user->isAdmin() && $transfert->demandeur_id !== $user->id) {
            abort(403);
        }

        $transfert->load(['membre.familleDisciples', 'demandeur', 'fdSource', 'fdDestination', 'traitePar', 'updatedBy:id,nom,prenom']);

        $familles = FamilleDisciples::all(['id', 'nom', 'couleur']);

        return Inertia::render('Transferts/Show', [
            'transfert' => $transfert,
            'familles' => $familles,
            'userRole' => $user->role->value,
        ]);
    }

    /**
     * Valider ou rejeter une demande de transfert (admin uniquement).
     */
    public function traiter(Request $request, Transfert $transfert)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            abort(403);
        }

        if ($transfert->statut !== 'en_attente') {
            return back()->withErrors(['statut' => 'Cette demande a déjà été traitée.']);
        }

        $validated = $request->validate([
            'decision' => 'required|in:valide,rejete',
            'fd_destination_id' => 'required_if:decision,valide|exists:familles_disciples,id',
            'commentaire_admin' => 'nullable|string|max:1000',
        ]);

        $transfert->update([
            'statut' => $validated['decision'],
            'fd_destination_id' => $validated['fd_destination_id'] ?? $transfert->fd_destination_id,
            'traite_par' => $user->id,
            'traite_le' => now(),
            'commentaire_admin' => $validated['commentaire_admin'],
            'updated_by_id' => $user->id,
        ]);

        if ($validated['decision'] === 'valide') {
            $this->effectuerTransfert(
                $transfert->membre,
                $validated['fd_destination_id'] ?? $transfert->fd_destination_id
            );

            return redirect()->route('transferts.index')
                ->with('success', "Transfert validé. {$transfert->membre->prenom} a été transféré(e).");
        }

        return redirect()->route('transferts.index')
            ->with('success', 'Demande de transfert rejetée.');
    }

    /**
     * Effectue le transfert effectif du membre.
     */
    private function effectuerTransfert(Membre $membre, int $fdDestinationId): void
    {
        $membre->update([
            'fd_id' => $fdDestinationId,
            'cellule_id' => null,   // Retirer de l'ancienne cellule
            'suivi_par' => null,     // Retirer l'ancien faiseur
        ]);
    }
}
