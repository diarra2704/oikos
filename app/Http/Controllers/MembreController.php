<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatutSpirituel;
use App\Models\Cellule;
use App\Models\Departement;
use App\Models\FamilleDisciples;
use App\Models\FamilleImpact;
use App\Models\Membre;
use App\Models\MembreFormation;
use App\Models\MembreVue;
use App\Models\ParametreValeur;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Services\AffectationService;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MembreController extends Controller
{
    /**
     * Filtre les FD visibles selon le rôle.
     */
    private function getFamillesFiltered(User $user)
    {
        if ($user->isAdmin()) {
            return FamilleDisciples::all(['id', 'nom', 'couleur']);
        }
        // Tous les autres rôles ne voient que leur FD
        return FamilleDisciples::where('id', $user->fd_id)->get(['id', 'nom', 'couleur']);
    }

    /**
     * Filtre les cellules visibles selon le rôle.
     */
    private function getCellulesFiltered(User $user)
    {
        if ($user->isAdmin()) {
            return Cellule::all(['id', 'nom', 'fd_id']);
        }
        if ($user->isSuperviseur()) {
            return Cellule::where('fd_id', $user->fd_id)->get(['id', 'nom', 'fd_id']);
        }
        // Leader et faiseur : uniquement leur cellule
        return Cellule::where('id', $user->cellule_id)->get(['id', 'nom', 'fd_id']);
    }

    /**
     * Filtre les faiseurs visibles selon le rôle.
     */
    private function getFaiseursFiltered(User $user)
    {
        $query = User::whereIn('role', [Role::FAISEUR, Role::LEADER_CELLULE])
            ->select('id', 'nom', 'prenom', 'fd_id', 'cellule_id');

        if ($user->isAdmin()) {
            return $query->get();
        }
        if ($user->isSuperviseur()) {
            return $query->where('fd_id', $user->fd_id)->get();
        }
        if ($user->isFaiseur()) {
            return $query->where('id', $user->id)->get();
        }
        // Leader : faiseurs de sa cellule + faiseurs de la FD sans cellule
        return $query->where(function ($q) use ($user) {
            $q->where('cellule_id', $user->cellule_id)
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('fd_id', $user->fd_id)->whereNull('cellule_id');
              });
        })->get();
    }

    /**
     * Retourne les valeurs de paramètrage groupées par type (pour formulaires membres).
     */
    private function getParametresForMembre(): array
    {
        $types = ['statut_spirituel', 'source', 'profession', 'situation_personnelle', 'niveau_etude', 'secteur_activite', 'quartier', 'statut_formation', 'type_formation', 'statut_famille_impact'];
        $out = [];
        foreach ($types as $type) {
            $out[$type] = ParametreValeur::parType($type)->get(['id', 'valeur', 'libelle', 'ordre']);
        }
        return $out;
    }

    private function getStatutSpirituelRule(): array
    {
        $vals = ParametreValeur::parType('statut_spirituel')->pluck('valeur')->toArray();
        return ['required', 'string', Rule::in($vals ?: ['NA', 'NC', 'fidele', 'STAR', 'faiseur_disciple'])];
    }

    private function getSourceRule(): array
    {
        $vals = ParametreValeur::parType('source')->pluck('valeur')->toArray();
        return ['nullable', 'string', Rule::in($vals ?: ['invitation', 'evangelisation', 'culte', 'autre'])];
    }

    private function getListRule(string $type): array
    {
        $vals = ParametreValeur::parType($type)->pluck('valeur')->toArray();
        if (empty($vals)) {
            return ['nullable', 'string', 'max:100'];
        }
        return ['nullable', 'string', Rule::in($vals)];
    }

    /**
     * Vérifie que l'utilisateur a le droit de voir/modifier ce membre.
     */
    private function autoriseMembre(User $user, Membre $membre): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isSuperviseur()) return $membre->fd_id === $user->fd_id;
        if ($user->isLeaderCellule()) return $membre->cellule_id === $user->cellule_id;
        if ($user->isFaiseur()) return $membre->suivi_par === $user->id;
        return false;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Membre::with(['familleDisciples', 'faiseur']);

        // Filtrer selon le rôle
        if ($user->isSuperviseur()) {
            $query->where('fd_id', $user->fd_id);
        } elseif ($user->isLeaderCellule()) {
            $query->where('cellule_id', $user->cellule_id);
        } elseif ($user->isFaiseur()) {
            $query->where('suivi_par', $user->id);
        }

        // Filtres optionnels
        if ($request->filled('statut')) {
            $query->where('statut_spirituel', $request->statut);
        }
        if ($request->filled('fd_id') && ($user->isAdmin() || $user->isSuperviseur())) {
            $query->where('fd_id', $request->fd_id);
        }
        if ($request->filled('cellule_id') && ($user->isAdmin() || $user->isSuperviseur() || $user->isLeaderCellule())) {
            $query->where('cellule_id', $request->cellule_id);
        }
        if ($request->filled('suivi_par') && ($user->isAdmin() || $user->isSuperviseur() || $user->isLeaderCellule())) {
            $query->where('suivi_par', $request->suivi_par);
        }
        if ($request->filled('absent_depuis')) {
            $semaines = (int) $request->absent_depuis;
            if ($semaines > 0) {
                $query->absentDepuis($semaines);
            }
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        $membres = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $filterKeys = ['search', 'statut', 'fd_id', 'cellule_id', 'suivi_par', 'absent_depuis', 'actif'];

        return Inertia::render('Membres/Index', [
            'membres' => $membres,
            'familles' => $this->getFamillesFiltered($user),
            'cellules' => $this->getCellulesFiltered($user),
            'faiseurs' => $this->getFaiseursFiltered($user),
            'filters' => $request->only($filterKeys),
            'userRole' => $user->role->value,
            'vues' => $user->membreVues()->orderBy('nom')->get(['id', 'nom', 'filtres']),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Membres/Create', [
            'familles' => $this->getFamillesFiltered($user),
            'cellules' => $this->getCellulesFiltered($user),
            'familles_impact' => FamilleImpact::actif()->orderBy('nom')->get(['id', 'nom', 'quartier']),
            'departements' => Departement::actif()->orderBy('nom')->get(['id', 'nom']),
            'faiseurs' => $this->getFaiseursFiltered($user),
            'parametres' => $this->getParametresForMembre(),
            'defaultFdId' => $user->fd_id,
            'defaultCelluleId' => $user->cellule_id,
            'userRole' => $user->role->value,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->merge([
            'nombre_enfants' => $request->input('nombre_enfants') !== '' && $request->input('nombre_enfants') !== null
                ? (int) $request->input('nombre_enfants') : null,
            'en_service_depuis' => $request->input('en_service_depuis') ?: null,
            'departement_id' => $request->filled('departement_id') ? $request->input('departement_id') : null,
        ]);

        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:191',
            'statut_spirituel' => $this->getStatutSpirituelRule(),
            'fd_id' => 'required|exists:familles_disciples,id',
            'cellule_id' => 'nullable|exists:cellules,id',
            'famille_impact_id' => 'nullable|exists:familles_impact,id',
            'statut_famille_impact' => $this->getListRule('statut_famille_impact'),
            'en_service_depuis' => 'nullable|date',
            'departement_id' => 'nullable|exists:departements,id',
            'suivi_par' => 'nullable|exists:users,id',
            'date_naissance' => 'nullable|date',
            'genre' => 'nullable|string|in:M,F',
            'quartier' => 'nullable|string|max:191',
            'source' => $this->getSourceRule(),
            'notes' => 'nullable|string',
            'profession' => 'nullable|string|max:100',
            'situation_personnelle' => $this->getListRule('situation_personnelle'),
            'niveau_etude' => $this->getListRule('niveau_etude'),
            'secteur_activite' => $this->getListRule('secteur_activite'),
            'nombre_enfants' => 'nullable|integer|min:0|max:20',
            'competences_centres_interet' => 'nullable|string|max:2000',
            'contact_urgence_nom' => 'nullable|string|max:100',
            'contact_urgence_telephone' => 'nullable|string|max:20',
            'besoins_particuliers' => 'nullable|string|max:2000',
        ]);

        // Non-admin : restreindre à sa FD
        if (!$user->isAdmin()) {
            $validated['fd_id'] = $validated['fd_id'] ?? $user->fd_id;
            // Vérifier que la FD correspond
            if ($validated['fd_id'] != $user->fd_id) {
                abort(403, 'Vous ne pouvez ajouter des membres que dans votre FD.');
            }
        }

        // ── Vérification anti-doublon (nom + prénom + téléphone) ──
        $doublonQuery = Membre::withTrashed()
            ->whereRaw('LOWER(nom) = ?', [mb_strtolower($validated['nom'])])
            ->whereRaw('LOWER(prenom) = ?', [mb_strtolower($validated['prenom'])]);

        if (!empty($validated['telephone'])) {
            $doublonQuery->where('telephone', $validated['telephone']);
        } else {
            $doublonQuery->whereNull('telephone');
        }

        $doublon = $doublonQuery->first();

        if ($doublon) {
            $fdNom = $doublon->familleDisciples?->nom ?? 'Aucune FD';
            $statut = $doublon->trashed() ? 'supprimé' : 'actif';
            return back()->withErrors([
                'doublon' => "Un membre avec le même nom, prénom et téléphone existe déjà : {$doublon->prenom} {$doublon->nom} (FD : {$fdNom}, statut : {$statut}). Veuillez vérifier avant de continuer.",
            ])->withInput();
        }

        $validated['date_premiere_visite'] = now();
        $validated['invite_par'] = $user->id;
        $validated['actif'] = true;

        // Si faiseur, auto-assigner le suivi
        if ($user->isFaiseur() && empty($validated['suivi_par'])) {
            $validated['suivi_par'] = $user->id;
        }

        $validated['created_by_id'] = $user->id;
        $membre = Membre::create($validated);

        return redirect()->route('membres.index')
            ->with('success', "{$membre->prenom} {$membre->nom} a été ajouté(e).");
    }

    public function show(Request $request, Membre $membre)
    {
        $user = $request->user();

        if (!$this->autoriseMembre($user, $membre)) {
            abort(403, 'Vous n\'avez pas accès à ce membre.');
        }

        $membre->load(['familleDisciples', 'cellule', 'familleImpact', 'departement', 'faiseur', 'invitePar', 'createdBy:id,nom,prenom', 'updatedBy:id,nom,prenom', 'formations', 'presences' => function ($q) {
            $q->orderBy('date_evenement', 'desc')->limit(20);
        }, 'interactions' => function ($q) {
            $q->with('faiseur:id,nom,prenom')->orderBy('date_interaction', 'desc');
        }, 'rappels' => function ($q) use ($user) {
            $q->where('user_id', $user->id)->whereNull('fait_at')->orderBy('date_souhaitee');
        }]);

        return Inertia::render('Membres/Show', [
            'membre' => $membre,
            'parametres' => $this->getParametresForMembre(),
            'userRole' => $user->role->value,
            'canAddInteraction' => $user->id === $membre->suivi_par || $user->isAdmin() || $user->isSuperviseur(),
            'canAddRappel' => $user->id === $membre->suivi_par || $user->isAdmin() || $user->isSuperviseur(),
            'canDeleteMembre' => $user->isAdmin() || $user->isSuperviseur(),
            'canEditFormations' => $user->id === $membre->suivi_par || $user->isAdmin() || $user->isSuperviseur(),
        ]);
    }

    public function edit(Request $request, Membre $membre)
    {
        $user = $request->user();

        if (!$this->autoriseMembre($user, $membre)) {
            abort(403, 'Vous n\'avez pas accès à ce membre.');
        }

        return Inertia::render('Membres/Edit', [
            'membre' => $membre,
            'familles' => $this->getFamillesFiltered($user),
            'cellules' => $this->getCellulesFiltered($user),
            'familles_impact' => FamilleImpact::actif()->orderBy('nom')->get(['id', 'nom', 'quartier']),
            'departements' => Departement::actif()->orderBy('nom')->get(['id', 'nom']),
            'faiseurs' => $this->getFaiseursFiltered($user),
            'parametres' => $this->getParametresForMembre(),
            'userRole' => $user->role->value,
        ]);
    }

    public function update(Request $request, Membre $membre)
    {
        $user = $request->user();

        if (!$this->autoriseMembre($user, $membre)) {
            abort(403, 'Vous n\'avez pas accès à ce membre.');
        }

        $request->merge([
            'nombre_enfants' => $request->input('nombre_enfants') !== '' && $request->input('nombre_enfants') !== null
                ? (int) $request->input('nombre_enfants') : null,
            'en_service_depuis' => $request->input('en_service_depuis') ?: null,
            'departement_id' => $request->filled('departement_id') ? $request->input('departement_id') : null,
        ]);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:191',
            'statut_spirituel' => array_merge(['sometimes'], $this->getListRule('statut_spirituel')),
            'fd_id' => 'sometimes|required|exists:familles_disciples,id',
            'cellule_id' => 'nullable|exists:cellules,id',
            'famille_impact_id' => 'nullable|exists:familles_impact,id',
            'statut_famille_impact' => $this->getListRule('statut_famille_impact'),
            'en_service_depuis' => 'nullable|date',
            'departement_id' => 'nullable|exists:departements,id',
            'suivi_par' => 'nullable|exists:users,id',
            'date_naissance' => 'nullable|date',
            'genre' => 'nullable|string|in:M,F',
            'quartier' => 'nullable|string|max:191',
            'source' => $this->getSourceRule(),
            'actif' => 'sometimes|boolean',
            'notes' => 'nullable|string',
            'profession' => 'nullable|string|max:100',
            'situation_personnelle' => $this->getListRule('situation_personnelle'),
            'niveau_etude' => $this->getListRule('niveau_etude'),
            'secteur_activite' => $this->getListRule('secteur_activite'),
            'nombre_enfants' => 'nullable|integer|min:0|max:20',
            'competences_centres_interet' => 'nullable|string|max:2000',
            'contact_urgence_nom' => 'nullable|string|max:100',
            'contact_urgence_telephone' => 'nullable|string|max:20',
            'besoins_particuliers' => 'nullable|string|max:2000',
        ]);

        // Non-admin ne peut pas changer la FD d'un membre
        if (!$user->isAdmin() && isset($validated['fd_id']) && $validated['fd_id'] != $membre->fd_id) {
            abort(403, 'Vous ne pouvez pas transférer un membre vers une autre FD.');
        }

        // ── Vérification anti-doublon si nom/prénom/téléphone changent ──
        $nomChanged = isset($validated['nom']) && mb_strtolower($validated['nom']) !== mb_strtolower($membre->nom);
        $prenomChanged = isset($validated['prenom']) && mb_strtolower($validated['prenom']) !== mb_strtolower($membre->prenom);
        $telChanged = array_key_exists('telephone', $validated) && ($validated['telephone'] ?? null) !== ($membre->telephone ?? null);

        if ($nomChanged || $prenomChanged || $telChanged) {
            $newNom = mb_strtolower($validated['nom'] ?? $membre->nom);
            $newPrenom = mb_strtolower($validated['prenom'] ?? $membre->prenom);
            $newTel = $validated['telephone'] ?? $membre->telephone;

            $doublonQuery = Membre::withTrashed()
                ->where('id', '!=', $membre->id)
                ->whereRaw('LOWER(nom) = ?', [$newNom])
                ->whereRaw('LOWER(prenom) = ?', [$newPrenom]);

            if (!empty($newTel)) {
                $doublonQuery->where('telephone', $newTel);
            } else {
                $doublonQuery->whereNull('telephone');
            }

            $doublon = $doublonQuery->first();

            if ($doublon) {
                $fdNom = $doublon->familleDisciples?->nom ?? 'Aucune FD';
                return back()->withErrors([
                    'doublon' => "Un autre membre avec le même nom, prénom et téléphone existe déjà : {$doublon->prenom} {$doublon->nom} (FD : {$fdNom}). Modification annulée.",
                ])->withInput();
            }
        }

        $validated['updated_by_id'] = $user->id;
        $membre->update($validated);

        return back()->with('success', 'Membre mis à jour.');
    }

    /**
     * Mise à jour du parcours de formation du membre (faiseur, superviseur, admin).
     */
    public function updateFormations(Request $request, Membre $membre, PointsService $pointsService)
    {
        $user = $request->user();

        if (!$this->autoriseMembre($user, $membre)) {
            abort(403, 'Vous n\'avez pas accès à ce membre.');
        }

        if ($user->id !== $membre->suivi_par && !$user->isAdmin() && !$user->isSuperviseur()) {
            abort(403, 'Seul le faiseur assigné, le superviseur ou l\'admin peut modifier le parcours de formation.');
        }

        $validated = $request->validate([
            'formations' => 'required|array',
            'formations.*.type_formation' => 'required|string|max:50',
            'formations.*.statut_formation' => 'required|string|max:50',
        ]);

        $typesValides = ParametreValeur::parType('type_formation')->pluck('valeur')->toArray();
        $statutsValides = ParametreValeur::parType('statut_formation')->pluck('valeur')->toArray();

        $membre->formations()->delete();

        foreach ($validated['formations'] as $row) {
            if (!in_array($row['type_formation'], $typesValides, true) || !in_array($row['statut_formation'], $statutsValides, true)) {
                continue;
            }
            $formation = MembreFormation::create([
                'membre_id' => $membre->id,
                'type_formation' => $row['type_formation'],
                'statut_formation' => $row['statut_formation'],
            ]);
            if ($formation->statut_formation === 'en_cours') {
                $pointsService->attribuerPointsFormationDebut($formation);
            } elseif ($formation->statut_formation === 'validee') {
                $pointsService->attribuerPointsFormationValidee($formation);
            }
        }

        return back()->with('success', 'Parcours de formation mis à jour.');
    }

    /**
     * Suggestion intelligente de faiseur (appel AJAX).
     */
    public function suggererFaiseur(Request $request, AffectationService $service)
    {
        $request->validate([
            'fd_id' => 'nullable|exists:familles_disciples,id',
            'genre' => 'nullable|string|in:M,F',
            'date_naissance' => 'nullable|date',
        ]);

        $user = $request->user();

        // Determiner la FD : soit celle fournie, soit celle de l'utilisateur
        $fdId = $request->fd_id ?? $user->fd_id;

        $suggestions = $service->suggerer(
            fdId: $fdId ? (int) $fdId : null,
            genre: $request->genre,
            dateNaissance: $request->date_naissance,
            limit: 5,
        );

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Vérification en temps réel des doublons (appel AJAX).
     */
    public function checkDoublon(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'exclude_id' => 'nullable|integer',
        ]);

        $nom = mb_strtolower($request->nom);
        $prenom = mb_strtolower($request->prenom);
        $telephone = $request->telephone;

        // Recherche exacte : nom + prénom + téléphone
        $exactQuery = Membre::withTrashed()
            ->whereRaw('LOWER(nom) = ?', [$nom])
            ->whereRaw('LOWER(prenom) = ?', [$prenom]);

        if (!empty($telephone)) {
            $exactQuery->where('telephone', $telephone);
        } else {
            $exactQuery->whereNull('telephone');
        }

        if ($request->exclude_id) {
            $exactQuery->where('id', '!=', $request->exclude_id);
        }

        $exact = $exactQuery->with('familleDisciples:id,nom')
            ->select('id', 'nom', 'prenom', 'telephone', 'fd_id', 'statut_spirituel', 'actif', 'deleted_at')
            ->first();

        // Recherche similaire : nom + prénom (sans téléphone) pour avertissement
        $similairesQuery = Membre::withTrashed()
            ->whereRaw('LOWER(nom) = ?', [$nom])
            ->whereRaw('LOWER(prenom) = ?', [$prenom]);

        if ($request->exclude_id) {
            $similairesQuery->where('id', '!=', $request->exclude_id);
        }

        $similaires = $similairesQuery->with('familleDisciples:id,nom')
            ->select('id', 'nom', 'prenom', 'telephone', 'fd_id', 'statut_spirituel', 'actif', 'deleted_at')
            ->limit(5)
            ->get();

        return response()->json([
            'exact' => $exact,
            'similaires' => $similaires,
        ]);
    }

    public function destroy(Request $request, Membre $membre)
    {
        $user = $request->user();

        // Seuls admin et superviseur de la FD peuvent supprimer
        if (!$user->isAdmin() && !($user->isSuperviseur() && $membre->fd_id === $user->fd_id)) {
            abort(403, 'Vous n\'avez pas le droit de supprimer ce membre.');
        }

        $membre->delete();

        return redirect()->route('membres.index')
            ->with('success', 'Membre supprimé.');
    }

    /**
     * Enregistrer les filtres actuels comme vue favorite.
     */
    public function storeVue(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'filtres' => 'required|array',
            'filtres.search' => 'nullable|string|max:255',
            'filtres.statut' => 'nullable|string|max:50',
            'filtres.fd_id' => 'nullable',
            'filtres.cellule_id' => 'nullable',
            'filtres.suivi_par' => 'nullable',
            'filtres.absent_depuis' => 'nullable|integer|min:1|max:52',
            'filtres.actif' => 'nullable',
        ]);

        $filtres = array_filter($validated['filtres'], fn ($v) => $v !== '' && $v !== null);
        $request->user()->membreVues()->create([
            'nom' => $validated['nom'],
            'filtres' => $filtres,
        ]);

        return back()->with('success', 'Vue enregistrée.');
    }

    /**
     * Supprimer une vue enregistrée.
     */
    public function destroyVue(Request $request, MembreVue $membreVue)
    {
        if ($membreVue->user_id !== $request->user()->id) {
            abort(403);
        }
        $membreVue->delete();
        return back()->with('success', 'Vue supprimée.');
    }
}
