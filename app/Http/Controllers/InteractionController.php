<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use App\Models\Membre;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function store(Request $request, Membre $membre)
    {
        $user = $request->user();

        // Seul le faiseur assigné, le superviseur de la FD ou l'admin peut ajouter une interaction
        if ($user->id !== $membre->suivi_par && !$user->isAdmin() && !($user->isSuperviseur() && $membre->fd_id === $user->fd_id)) {
            abort(403, 'Vous ne pouvez ajouter des interactions que pour vos propres âmes.');
        }

        $validated = $request->validate([
            'type_canal' => 'required|string|in:appel,whatsapp,visite,sms,rencontre_eglise,autre',
            'date_interaction' => 'required|date',
            'resume' => 'required|string|max:2000',
            'duree_minutes' => 'nullable|integer|min:1|max:600',
            'prochain_rdv' => 'nullable|date|after_or_equal:today',
            'prochain_objectif' => 'nullable|string|max:500',
        ]);

        $validated['membre_id'] = $membre->id;
        $validated['faiseur_id'] = $user->id;

        Interaction::create($validated);

        return back()->with('success', 'Interaction enregistree.');
    }

    public function destroy(Request $request, Interaction $interaction)
    {
        $user = $request->user();

        // Seul l'auteur ou l'admin peut supprimer
        if ($interaction->faiseur_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Vous ne pouvez supprimer que vos propres interactions.');
        }

        $interaction->delete();

        return back()->with('success', 'Interaction supprimee.');
    }
}
