<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Membre;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvitationController extends Controller
{
    /**
     * Liste des invitations : faiseur = les siennes, superviseur = sa FD, admin = toutes.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Invitation::query()
            ->with(['inviteur:id,nom,prenom', 'nouveauMembre:id,nom,prenom'])
            ->orderByDesc('date_evenement')
            ->orderByDesc('created_at');

        if ($user->isFaiseur() || $user->isLeaderCellule()) {
            $query->where('inviteur_id', $user->id);
        } elseif ($user->isSuperviseur() && $user->fd_id) {
            $userIds = User::where('fd_id', $user->fd_id)->pluck('id');
            $query->whereIn('inviteur_id', $userIds);
        }

        $invitations = $query->paginate(20)->withQueryString();

        return Inertia::render('Invitations/Index', [
            'invitations' => $invitations,
            'canCreate' => true,
            'canEditAll' => $user->isAdmin() || $user->isSuperviseur(),
        ]);
    }

    /**
     * Formulaire de création.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $mesAmes = Membre::where('suivi_par', $user->id)->orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']);

        return Inertia::render('Invitations/Create', [
            'mesAmes' => $mesAmes,
        ]);
    }

    /**
     * Enregistrer une nouvelle invitation.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $request->merge(['nouveau_membre_id' => $request->input('nouveau_membre_id') ?: null]);

        $validated = $request->validate([
            'nom_invite' => 'required|string|max:191',
            'telephone_invite' => 'nullable|string|max:20',
            'date_evenement' => 'required|date',
            'est_venu' => 'sometimes|boolean',
            'devenu_membre' => 'sometimes|boolean',
            'nouveau_membre_id' => 'nullable|exists:membres,id',
        ]);

        $validated['inviteur_id'] = $user->id;
        $validated['est_venu'] = $validated['est_venu'] ?? false;
        $validated['devenu_membre'] = $validated['devenu_membre'] ?? false;
        $validated['nouveau_membre_id'] = $validated['nouveau_membre_id'] ?? null;

        Invitation::create($validated);

        return redirect()->route('invitations.index')->with('success', 'Invitation enregistrée.');
    }

    /**
     * Formulaire d'édition (marquer venu, devenu membre, lier à un membre).
     */
    public function edit(Request $request, Invitation $invitation)
    {
        $user = $request->user();
        $this->autoriseInvitation($user, $invitation);

        $invitation->load('nouveauMembre:id,nom,prenom');
        $mesAmes = Membre::where('suivi_par', $invitation->inviteur_id)->orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']);

        return Inertia::render('Invitations/Edit', [
            'invitation' => $invitation,
            'mesAmes' => $mesAmes,
        ]);
    }

    /**
     * Mise à jour (notamment est_venu → déclenche les points).
     */
    public function update(Request $request, Invitation $invitation)
    {
        $user = $request->user();
        $this->autoriseInvitation($user, $invitation);
        $request->merge(['nouveau_membre_id' => $request->input('nouveau_membre_id') ?: null]);

        $validated = $request->validate([
            'nom_invite' => 'sometimes|required|string|max:191',
            'telephone_invite' => 'nullable|string|max:20',
            'date_evenement' => 'sometimes|required|date',
            'est_venu' => 'sometimes|boolean',
            'devenu_membre' => 'sometimes|boolean',
            'nouveau_membre_id' => 'nullable|exists:membres,id',
        ]);
        $validated['nouveau_membre_id'] = $validated['nouveau_membre_id'] ?? null;

        $invitation->update($validated);

        return redirect()->route('invitations.index')->with('success', 'Invitation mise à jour.');
    }

    /**
     * Suppression.
     */
    public function destroy(Invitation $invitation)
    {
        $user = request()->user();
        $this->autoriseInvitation($user, $invitation);

        $invitation->delete();
        return redirect()->route('invitations.index')->with('success', 'Invitation supprimée.');
    }

    private function autoriseInvitation(User $user, Invitation $invitation): void
    {
        if ($user->isAdmin()) {
            return;
        }
        if ($user->isSuperviseur() && $user->fd_id) {
            $inviteur = User::find($invitation->inviteur_id);
            if ($inviteur && $inviteur->fd_id === $user->fd_id) {
                return;
            }
        }
        if ($invitation->inviteur_id === $user->id) {
            return;
        }
        abort(403, 'Vous ne pouvez pas modifier cette invitation.');
    }
}
