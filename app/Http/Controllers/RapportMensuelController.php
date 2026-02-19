<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Membre;
use App\Models\RapportMensuel;
use App\Models\User;
use App\Services\PointsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RapportMensuelController extends Controller
{
    /**
     * Liste des rapports mensuels (faiseur : les siens, superviseur : ceux de sa FD, admin : tous).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = RapportMensuel::with(['faiseur:id,nom,prenom', 'familleDisciples:id,nom']);

        if ($user->isFaiseur() || $user->isLeaderCellule()) {
            $query->where('faiseur_id', $user->id);
        } elseif ($user->isSuperviseur()) {
            $query->where('fd_id', $user->fd_id);
        }

        $rapports = $query->orderByDesc('mois')->orderBy('faiseur_id')->paginate(20);

        return Inertia::render('RapportMensuel/Index', [
            'rapports' => $rapports,
            'userRole' => $user->role->value,
        ]);
    }

    /**
     * Formulaire de saisie : affiche toutes les âmes du faiseur avec les champs à renseigner.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $mois = $request->input('mois', now()->format('Y-m'));

        // Vérifier si un rapport existe déjà pour ce mois
        $existant = RapportMensuel::where('faiseur_id', $user->id)->where('mois', $mois)->first();

        // Toutes les âmes affectées à ce faiseur (y compris inactives pour le rapport de sortie)
        $ames = Membre::where('suivi_par', $user->id)
            ->orderByRaw("actif DESC, nom ASC")
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'nom' => $m->nom,
                'prenom' => $m->prenom,
                'telephone' => $m->telephone,
                'statut_spirituel' => $m->statut_spirituel,
                'actif' => $m->actif,
                'en_pcnc' => $m->en_pcnc,
                'en_fi' => $m->en_fi,
                'regulier_eglise' => $m->regulier_eglise,
                'niveau_integration' => $m->niveau_integration ?? 'decouverte',
                'motif_sortie' => $m->motif_sortie,
                'date_premiere_visite' => $m->date_premiere_visite?->format('Y-m-d'),
            ]);

        return Inertia::render('RapportMensuel/Create', [
            'ames' => $ames,
            'mois' => $mois,
            'existant' => $existant,
            'faiseurNom' => "{$user->prenom} {$user->nom}",
        ]);
    }

    /**
     * Sauvegarde : met à jour les statuts de chaque âme + génère le rapport.
     */
    public function store(Request $request, PointsService $pointsService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'mois' => 'required|string|size:7',
            'observations' => 'nullable|string|max:1000',
            'ames' => 'required|array|min:1',
            'ames.*.id' => 'required|exists:membres,id',
            'ames.*.actif' => 'required|boolean',
            'ames.*.en_pcnc' => 'required|boolean',
            'ames.*.en_fi' => 'required|boolean',
            'ames.*.regulier_eglise' => 'required|boolean',
            'ames.*.niveau_integration' => 'required|string|in:decouverte,progression,consolidation,confirme',
            'ames.*.motif_sortie' => 'nullable|string|in:injoignable,ne_repond_plus,en_deplacement,autre',
        ]);

        // 1. Mettre à jour chaque membre
        foreach ($validated['ames'] as $ameData) {
            $membre = Membre::where('id', $ameData['id'])
                ->where('suivi_par', $user->id)
                ->first();

            if (!$membre) continue;

            $membre->update([
                'actif' => $ameData['actif'],
                'en_pcnc' => $ameData['en_pcnc'],
                'en_fi' => $ameData['en_fi'],
                'regulier_eglise' => $ameData['regulier_eglise'],
                'niveau_integration' => $ameData['niveau_integration'],
                'motif_sortie' => $ameData['actif'] ? null : $ameData['motif_sortie'],
            ]);
        }

        // 2. Calculer les stats du rapport
        $donnees = $this->calculerDonnees($user);

        // 3. Créer ou mettre à jour le rapport
        RapportMensuel::updateOrCreate(
            ['faiseur_id' => $user->id, 'mois' => $validated['mois']],
            [
                'fd_id' => $user->fd_id,
                'donnees' => $donnees,
                'observations' => $validated['observations'],
            ]
        );

        $rapport = RapportMensuel::where('faiseur_id', $user->id)
            ->where('mois', $validated['mois'])
            ->first();

        $pointsService->attribuerPointsRapportMensuel($rapport);

        return redirect()->route('rapport-mensuel.show', $rapport->id)
            ->with('success', 'Rapport mensuel genere avec succes.');
    }

    /**
     * Affichage du rapport formaté.
     */
    public function show(Request $request, RapportMensuel $rapportMensuel)
    {
        $user = $request->user();

        // Vérifier l'accès
        if (!$user->isAdmin()) {
            if ($user->isSuperviseur() && $rapportMensuel->fd_id !== $user->fd_id) {
                abort(403);
            }
            if (($user->isFaiseur() || $user->isLeaderCellule()) && $rapportMensuel->faiseur_id !== $user->id) {
                abort(403);
            }
        }

        $rapportMensuel->load(['faiseur:id,nom,prenom', 'familleDisciples:id,nom']);

        // Formater le mois
        $moisCarbon = Carbon::createFromFormat('Y-m', $rapportMensuel->mois);
        $moisFormate = $moisCarbon->translatedFormat('F Y');

        return Inertia::render('RapportMensuel/Show', [
            'rapport' => $rapportMensuel,
            'moisFormate' => $moisFormate,
            'userRole' => $user->role->value,
        ]);
    }

    /**
     * Calcule les données structurées du rapport à partir des membres.
     */
    private function calculerDonnees(User $faiseur): array
    {
        $ames = Membre::where('suivi_par', $faiseur->id)->get();

        $total = $ames->count();
        $suivies = $ames->where('actif', true);
        $sorties = $ames->where('actif', false);

        $nbSuivies = $suivies->count();
        $nbSorties = $sorties->count();

        // A. Parmi les âmes encore suivies
        $enPcnc = $suivies->where('en_pcnc', true)->count();
        $enFi = $suivies->where('en_fi', true)->count();
        $regulier = $suivies->where('regulier_eglise', true)->count();

        // Niveau d'intégration (sur toutes les âmes)
        $decouverte = $ames->where('niveau_integration', 'decouverte')->count();
        $progression = $ames->where('niveau_integration', 'progression')->count();
        $consolidation = $ames->where('niveau_integration', 'consolidation')->count();
        $confirme = $ames->where('niveau_integration', 'confirme')->count();

        // B. Parmi les âmes sorties
        $injoignables = $sorties->where('motif_sortie', 'injoignable')->count();
        $neRepondPlus = $sorties->where('motif_sortie', 'ne_repond_plus')->count();
        $enDeplacement = $sorties->where('motif_sortie', 'en_deplacement')->count();
        $autreMotif = $sorties->where('motif_sortie', 'autre')->count();

        // Liste détaillée
        $listeAmes = $ames->map(fn ($m) => [
            'id' => $m->id,
            'nom' => $m->nom,
            'prenom' => $m->prenom,
            'actif' => $m->actif,
            'en_pcnc' => $m->en_pcnc,
            'en_fi' => $m->en_fi,
            'regulier_eglise' => $m->regulier_eglise,
            'niveau_integration' => $m->niveau_integration,
            'motif_sortie' => $m->motif_sortie,
            'statut_spirituel' => $m->statut_spirituel,
        ])->values()->toArray();

        return [
            'total' => $total,
            'suivies' => $nbSuivies,
            'sorties' => $nbSorties,
            'a_en_pcnc' => $enPcnc,
            'a_en_fi' => $enFi,
            'a_regulier' => $regulier,
            'integration' => [
                'decouverte' => $decouverte,
                'progression' => $progression,
                'consolidation' => $consolidation,
                'confirme' => $confirme,
            ],
            'b_injoignables' => $injoignables,
            'b_ne_repond_plus' => $neRepondPlus,
            'b_en_deplacement' => $enDeplacement,
            'b_autre' => $autreMotif,
            'liste_ames' => $listeAmes,
        ];
    }
}
