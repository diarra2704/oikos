<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Cellule;
use App\Models\FamilleDisciples;
use App\Models\Membre;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Recherche globale : membres, cellules, familles de disciples.
     * Résultats filtrés selon le rôle de l'utilisateur.
     */
    public function index(Request $request)
    {
        $q = $request->input('q', '');
        $q = trim($q);
        if (strlen($q) < 2) {
            return response()->json([
                'membres' => [],
                'cellules' => [],
                'familles_disciples' => [],
            ]);
        }

        $user = $request->user();
        $term = '%' . $q . '%';

        $membres = $this->searchMembres($user, $term);
        $cellules = $this->searchCellules($user, $term);
        $fd = $this->searchFamillesDisciples($user, $term);

        return response()->json([
            'membres' => $membres,
            'cellules' => $cellules,
            'familles_disciples' => $fd,
        ]);
    }

    private function searchMembres($user, string $term): array
    {
        $query = Membre::query()
            ->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                    ->orWhere('prenom', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('telephone', 'like', $term);
            })
            ->orderBy('prenom')
            ->orderBy('nom')
            ->limit(10);

        match ($user->role) {
            Role::ADMIN => null,
            Role::SUPERVISEUR => $query->where('fd_id', $user->fd_id),
            Role::LEADER_CELLULE => $query->where('cellule_id', $user->cellule_id),
            Role::FAISEUR => $query->where('suivi_par', $user->id),
        };

        return $query->get()->map(fn (Membre $m) => [
            'id' => $m->id,
            'label' => $m->full_name,
            'sublabel' => $m->email ?: $m->telephone,
            'url' => route('membres.show', $m),
        ])->values()->all();
    }

    private function searchCellules($user, string $term): array
    {
        $query = Cellule::query()
            ->where('nom', 'like', $term)
            ->with('familleDisciples:id,nom')
            ->orderBy('nom')
            ->limit(8);

        match ($user->role) {
            Role::ADMIN => null,
            Role::SUPERVISEUR => $query->where('fd_id', $user->fd_id),
            Role::LEADER_CELLULE, Role::FAISEUR => $user->cellule_id ? $query->where('id', $user->cellule_id) : $query->whereRaw('0=1'),
        };

        return $query->get()->map(fn (Cellule $c) => [
            'id' => $c->id,
            'label' => $c->nom,
            'sublabel' => $c->familleDisciples?->nom,
            'url' => route('cellules.show', $c),
        ])->values()->all();
    }

    private function searchFamillesDisciples($user, string $term): array
    {
        if ($user->role === Role::FAISEUR || $user->role === Role::LEADER_CELLULE) {
            return [];
        }

        $query = FamilleDisciples::query()
            ->where('nom', 'like', $term)
            ->orderBy('nom')
            ->limit(5);

        if ($user->role === Role::SUPERVISEUR) {
            $query->where('id', $user->fd_id);
        }

        return $query->get()->map(fn (FamilleDisciples $f) => [
            'id' => $f->id,
            'label' => $f->nom,
            'sublabel' => $f->description ? \Str::limit($f->description, 40) : null,
            'url' => route('fd.show', $f),
        ])->values()->all();
    }
}
