<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Cellule;
use App\Models\FamilleDisciples;
use App\Models\Membre;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FdController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Superviseur → redirigé vers sa propre FD
        if ($user->isSuperviseur() && $user->fd_id) {
            return redirect()->route('fd.show', $user->fd_id);
        }

        // Seul l'admin voit la liste complète
        if (!$user->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        $fds = FamilleDisciples::withCount([
            'users',
            'membres',
            'cellules',
            'users as faiseurs_count' => fn ($q) => $q->whereIn('role', [Role::FAISEUR, Role::LEADER_CELLULE]),
            'membres as na_count' => fn ($q) => $q->where('statut_spirituel', 'NA'),
            'membres as nc_count' => fn ($q) => $q->where('statut_spirituel', 'NC'),
            'membres as fideles_count' => fn ($q) => $q->where('statut_spirituel', 'fidele'),
            'membres as star_count' => fn ($q) => $q->where('statut_spirituel', 'STAR'),
        ])
            ->with('superviseur')
            ->get();

        $superviseurs = User::whereIn('role', [Role::ADMIN, Role::SUPERVISEUR])->orderBy('nom')->get(['id', 'nom', 'prenom', 'role']);

        return Inertia::render('Fd/Index', [
            'familles' => $fds,
            'superviseurs' => $superviseurs,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            abort(403, 'Seul un administrateur peut créer une Famille de Disciples.');
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:100|unique:familles_disciples,nom',
            'description' => 'nullable|string|max:500',
            'couleur' => 'nullable|string|max:7',
            'superviseur_id' => 'nullable|exists:users,id',
        ]);

        $validated['couleur'] = $validated['couleur'] ?? '#3b82f6';

        FamilleDisciples::create($validated);

        return redirect()->route('fd.index')->with('success', 'Famille de Disciples créée.');
    }

    public function show(Request $request, FamilleDisciples $fd)
    {
        $user = $request->user();

        // Vérifier que l'utilisateur a le droit de voir cette FD
        if (!$user->isAdmin()) {
            if ($user->fd_id !== $fd->id) {
                abort(403, 'Vous ne pouvez voir que votre propre Famille de Disciples.');
            }
        }

        $fd->load('superviseur');
        $fd->loadCount(['users', 'membres', 'cellules']);

        $cellules = Cellule::where('fd_id', $fd->id)
            ->with('leader')
            ->withCount(['faiseurs', 'membres'])
            ->get();

        // Filtrer les membres selon le rôle
        $membresQuery = Membre::where('fd_id', $fd->id);
        if ($user->isLeaderCellule()) {
            $membresQuery->where('cellule_id', $user->cellule_id);
        } elseif ($user->isFaiseur()) {
            $membresQuery->where('suivi_par', $user->id);
        }

        $membres = $membresQuery->with('faiseur')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $faiseurs = User::where('fd_id', $fd->id)
            ->whereIn('role', [Role::FAISEUR, Role::LEADER_CELLULE])
            ->withCount('membresAffecter')
            ->get();

        $superviseurs = $user->isAdmin()
            ? User::whereIn('role', [Role::ADMIN, Role::SUPERVISEUR])->orderBy('nom')->get(['id', 'nom', 'prenom', 'role'])
            : collect();

        $stats = [
            'total_membres' => Membre::where('fd_id', $fd->id)->count(),
            'na' => Membre::where('fd_id', $fd->id)->where('statut_spirituel', 'NA')->count(),
            'nc' => Membre::where('fd_id', $fd->id)->where('statut_spirituel', 'NC')->count(),
            'fideles' => Membre::where('fd_id', $fd->id)->where('statut_spirituel', 'fidele')->count(),
            'absents_3sem' => Membre::where('fd_id', $fd->id)->absentDepuis(3)->count(),
        ];

        return Inertia::render('Fd/Show', [
            'fd' => $fd,
            'cellules' => $cellules,
            'membres' => $membres,
            'faiseurs' => $faiseurs,
            'superviseurs' => $superviseurs,
            'stats' => $stats,
            'userRole' => $user->role->value,
        ]);
    }

    public function update(Request $request, FamilleDisciples $fd)
    {
        $user = $request->user();

        // Seul l'admin ou le superviseur de cette FD peut modifier
        if (!$user->isAdmin() && $user->fd_id !== $fd->id) {
            abort(403, 'Vous ne pouvez modifier que votre propre FD.');
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'couleur' => 'nullable|string|max:7',
            'superviseur_id' => 'nullable|exists:users,id',
        ]);

        if (!$user->isAdmin() && array_key_exists('superviseur_id', $validated)) {
            unset($validated['superviseur_id']);
        }

        $fd->update($validated);

        return back()->with('success', 'Famille de Disciples mise à jour.');
    }

    public function destroy(Request $request, FamilleDisciples $fd)
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            abort(403, 'Seul un administrateur peut supprimer une Famille de Disciples.');
        }

        $nom = $fd->nom;
        $fd->delete();

        return redirect()->route('fd.index')->with('success', "Famille de Disciples « {$nom} » supprimée.");
    }
}
