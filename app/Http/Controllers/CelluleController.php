<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Cellule;
use App\Models\FamilleDisciples;
use App\Models\Membre;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CelluleController extends Controller
{
    private function autoriseCellule(User $user, Cellule $cellule): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isSuperviseur()) return $cellule->fd_id === $user->fd_id;
        if ($user->isLeaderCellule()) return $cellule->id === $user->cellule_id;
        if ($user->isFaiseur()) return $cellule->id === $user->cellule_id;
        return false;
    }

    public function show(Request $request, Cellule $cellule)
    {
        $user = $request->user();

        if (!$this->autoriseCellule($user, $cellule)) {
            abort(403, 'Vous n\'avez pas accès à cette cellule.');
        }

        $cellule->load(['familleDisciples', 'leader', 'createdBy:id,nom,prenom', 'updatedBy:id,nom,prenom']);

        $faiseurs = User::where('cellule_id', $cellule->id)
            ->whereIn('role', [Role::FAISEUR, Role::LEADER_CELLULE])
            ->withCount('membresAffecter')
            ->get();

        $membresQuery = Membre::where('cellule_id', $cellule->id);
        if ($user->isFaiseur()) {
            $membresQuery->where('suivi_par', $user->id);
        }
        $membres = $membresQuery->with('faiseur')->get();

        // Leaders potentiels pour le changement (faiseurs de la FD)
        $leadersPotentiels = [];
        $autresCellules = [];
        if ($user->isAdmin() || $user->isSuperviseur()) {
            $leadersPotentiels = User::where('fd_id', $cellule->fd_id)
                ->whereIn('role', [Role::FAISEUR, Role::LEADER_CELLULE])
                ->select('id', 'nom', 'prenom', 'cellule_id', 'role')
                ->get();

            $autresCellules = Cellule::where('fd_id', $cellule->fd_id)
                ->where('id', '!=', $cellule->id)
                ->get(['id', 'nom']);
        }

        return Inertia::render('Cellules/Show', [
            'cellule' => $cellule,
            'faiseurs' => $faiseurs,
            'membres' => $membres,
            'leadersPotentiels' => $leadersPotentiels,
            'autresCellules' => $autresCellules,
            'userRole' => $user->role->value,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'fd_id' => 'required|exists:familles_disciples,id',
            'leader_id' => 'nullable|exists:users,id',
        ]);

        if (!$user->isAdmin() && $validated['fd_id'] != $user->fd_id) {
            abort(403, 'Vous ne pouvez créer des cellules que dans votre FD.');
        }

        $validated['created_by_id'] = $user->id;
        $cellule = Cellule::create($validated);

        // Si un leader est assigné, mettre à jour sa cellule et son rôle
        if (!empty($validated['leader_id'])) {
            $leader = User::find($validated['leader_id']);
            if ($leader) {
                $leader->update([
                    'cellule_id' => $cellule->id,
                    'role' => Role::LEADER_CELLULE,
                ]);
            }
        }

        return back()->with('success', "Cellule \"{$cellule->nom}\" créée.");
    }

    public function update(Request $request, Cellule $cellule)
    {
        $user = $request->user();

        if (!$user->isAdmin() && $cellule->fd_id !== $user->fd_id) {
            abort(403, 'Vous ne pouvez modifier que les cellules de votre FD.');
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:100',
            'leader_id' => 'nullable|exists:users,id',
        ]);

        // Si le leader change, mettre à jour les rôles
        if (isset($validated['leader_id']) && $validated['leader_id'] != $cellule->leader_id) {
            // Ancien leader → redevient faiseur
            if ($cellule->leader_id) {
                $ancienLeader = User::find($cellule->leader_id);
                if ($ancienLeader && $ancienLeader->role === Role::LEADER_CELLULE) {
                    $ancienLeader->update(['role' => Role::FAISEUR]);
                }
            }

            // Nouveau leader → passe leader_cellule
            if ($validated['leader_id']) {
                $nouveauLeader = User::find($validated['leader_id']);
                if ($nouveauLeader) {
                    $nouveauLeader->update([
                        'cellule_id' => $cellule->id,
                        'role' => Role::LEADER_CELLULE,
                    ]);
                }
            }
        }

        $validated['updated_by_id'] = $user->id;
        $cellule->update($validated);

        return back()->with('success', 'Cellule mise à jour.');
    }

    public function destroy(Request $request, Cellule $cellule)
    {
        $user = $request->user();

        if (!$user->isAdmin() && $cellule->fd_id !== $user->fd_id) {
            abort(403, 'Vous ne pouvez supprimer que les cellules de votre FD.');
        }

        $nom = $cellule->nom;

        // Désaffecter les faiseurs et membres de cette cellule
        User::where('cellule_id', $cellule->id)->update(['cellule_id' => null]);
        Membre::where('cellule_id', $cellule->id)->update(['cellule_id' => null]);

        $cellule->delete();

        return back()->with('success', "Cellule \"{$nom}\" supprimée. Les faiseurs et membres ont été désaffectés.");
    }

    /**
     * Réaffecter un faiseur à une autre cellule (superviseur+).
     */
    public function reaffecterFaiseur(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'cellule_id' => 'required|exists:cellules,id',
        ]);

        $faiseur = User::findOrFail($validated['user_id']);
        $cellule = Cellule::findOrFail($validated['cellule_id']);

        // Vérifier les droits : même FD
        if (!$user->isAdmin()) {
            if ($faiseur->fd_id !== $user->fd_id || $cellule->fd_id !== $user->fd_id) {
                abort(403, 'Vous ne pouvez réaffecter que dans votre FD.');
            }
        }

        $faiseur->update(['cellule_id' => $cellule->id]);

        // Réaffecter aussi les membres suivis par ce faiseur vers la nouvelle cellule
        Membre::where('suivi_par', $faiseur->id)->update(['cellule_id' => $cellule->id]);

        return back()->with('success', "{$faiseur->prenom} {$faiseur->nom} réaffecté(e) à la cellule \"{$cellule->nom}\".");
    }
}
