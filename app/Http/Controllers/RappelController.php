<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Membre;
use App\Models\Rappel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RappelController extends Controller
{
    /**
     * Liste des rappels du faiseur (pour page dédiée optionnelle).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== Role::FAISEUR) {
            abort(403, 'Réservé aux faiseurs.');
        }

        $rappels = Rappel::pourFaiseur($user->id)
            ->nonFait()
            ->with('membre:id,nom,prenom')
            ->orderBy('date_souhaitee')
            ->orderBy('id')
            ->paginate(20);

        return Inertia::render('Rappels/Index', [
            'rappels' => $rappels,
        ]);
    }

    /**
     * Créer un rappel (depuis fiche membre ou dashboard).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'type' => 'required|in:contacter,relance_interaction',
            'date_souhaitee' => 'required|date|after_or_equal:today',
            'libelle' => 'nullable|string|max:255',
        ]);

        $membre = Membre::findOrFail($validated['membre_id']);
        if ($membre->suivi_par !== $user->id && ! $user->isAdmin() && ! $user->isSuperviseur()) {
            abort(403, 'Vous ne suivez pas ce membre.');
        }

        Rappel::create([
            'membre_id' => $membre->id,
            'user_id' => $user->id,
            'type' => $validated['type'],
            'date_souhaitee' => $validated['date_souhaitee'],
            'libelle' => $validated['libelle'] ?? null,
        ]);

        if ($request->wantsJson() || $request->header('X-Inertia')) {
            return back()->with('success', 'Rappel créé.');
        }
        return redirect()->back()->with('success', 'Rappel créé.');
    }

    /**
     * Marquer un rappel comme fait.
     */
    public function marquerFait(Request $request, Rappel $rappel)
    {
        $user = $request->user();
        if ($rappel->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        $rappel->update(['fait_at' => now()]);

        return back()->with('success', 'Rappel marqué comme fait.');
    }

    /**
     * Supprimer un rappel.
     */
    public function destroy(Rappel $rappel)
    {
        $user = request()->user();
        if ($rappel->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        $rappel->delete();

        return back()->with('success', 'Rappel supprimé.');
    }
}
