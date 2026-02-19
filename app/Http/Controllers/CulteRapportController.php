<?php

namespace App\Http\Controllers;

use App\Models\FamilleDisciples;
use App\Models\Membre;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CulteRapportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Date du dimanche sélectionné (par défaut : dimanche le plus récent)
        $dateParam = $request->input('date');
        if ($dateParam) {
            $dimancheSelectionne = Carbon::parse($dateParam)->startOfDay();
        } else {
            $dimancheSelectionne = $this->dernierDimanche();
        }

        // Les 4 dimanches : le sélectionné + les 3 précédents
        $dimanches = [];
        for ($i = 0; $i < 4; $i++) {
            $dimanches[] = $dimancheSelectionne->copy()->subWeeks($i);
        }

        // Charger toutes les FD
        $fds = FamilleDisciples::select('id', 'nom', 'couleur')->orderBy('nom')->get();

        // Calculer les stats pour chaque dimanche
        $rapportParDimanche = [];
        foreach ($dimanches as $dimanche) {
            $rapportParDimanche[] = $this->calculerStatsDimanche($dimanche, $fds, $user);
        }

        // Le rapport courant (dimanche sélectionné)
        $courant = $rapportParDimanche[0];

        // Tendances par rapport au dimanche précédent
        $tendances = $this->calculerTendances($rapportParDimanche[0], $rapportParDimanche[1]);

        return Inertia::render('Culte/RapportCulte', [
            'dimancheSelectionne' => $dimancheSelectionne->format('Y-m-d'),
            'courant' => $courant,
            'historique' => $rapportParDimanche,
            'tendances' => $tendances,
            'fds' => $fds,
            'userRole' => $user->role->value,
        ]);
    }

    /**
     * Calcule les statistiques NA/NC pour un dimanche donné.
     */
    private function calculerStatsDimanche(Carbon $dimanche, $fds, $user): array
    {
        $debut = $dimanche->copy()->startOfDay();
        $fin = $dimanche->copy()->endOfDay();

        // Requête de base : membres créés ce dimanche
        $baseQuery = Membre::whereBetween('date_premiere_visite', [$debut, $fin]);

        // Filtrer par FD si superviseur
        if ($user->isSuperviseur()) {
            $baseQuery->where('fd_id', $user->fd_id);
        }

        $totalNA = (clone $baseQuery)->where('statut_spirituel', 'NA')->count();
        $totalNC = (clone $baseQuery)->where('statut_spirituel', 'NC')->count();
        $total = $totalNA + $totalNC;

        // Détail par FD
        $parFd = [];
        foreach ($fds as $fd) {
            // Si superviseur, ne montrer que sa FD
            if ($user->isSuperviseur() && $fd->id !== $user->fd_id) {
                continue;
            }

            $fdQuery = Membre::where('fd_id', $fd->id)
                ->whereBetween('date_premiere_visite', [$debut, $fin]);

            $naFd = (clone $fdQuery)->where('statut_spirituel', 'NA')->count();
            $ncFd = (clone $fdQuery)->where('statut_spirituel', 'NC')->count();

            $parFd[] = [
                'fd_id' => $fd->id,
                'fd_nom' => $fd->nom,
                'fd_couleur' => $fd->couleur,
                'na' => $naFd,
                'nc' => $ncFd,
                'total' => $naFd + $ncFd,
            ];
        }

        // Membres sans FD assignée
        if ($user->isAdmin()) {
            $sansFdQuery = Membre::whereNull('fd_id')
                ->whereBetween('date_premiere_visite', [$debut, $fin]);
            $naSansFd = (clone $sansFdQuery)->where('statut_spirituel', 'NA')->count();
            $ncSansFd = (clone $sansFdQuery)->where('statut_spirituel', 'NC')->count();

            if ($naSansFd > 0 || $ncSansFd > 0) {
                $parFd[] = [
                    'fd_id' => null,
                    'fd_nom' => 'Non affectes',
                    'fd_couleur' => '#94a3b8',
                    'na' => $naSansFd,
                    'nc' => $ncSansFd,
                    'total' => $naSansFd + $ncSansFd,
                ];
            }
        }

        // Liste des membres enregistrés ce jour (pour le rapport détaillé)
        $membresQuery = Membre::with(['familleDisciples:id,nom,couleur', 'faiseur:id,nom,prenom'])
            ->whereBetween('date_premiere_visite', [$debut, $fin])
            ->whereIn('statut_spirituel', ['NA', 'NC']);

        if ($user->isSuperviseur()) {
            $membresQuery->where('fd_id', $user->fd_id);
        }

        $membres = $membresQuery->orderBy('statut_spirituel')
            ->orderBy('nom')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'nom' => $m->nom,
                'prenom' => $m->prenom,
                'telephone' => $m->telephone,
                'statut_spirituel' => $m->statut_spirituel,
                'fd_nom' => $m->familleDisciples?->nom ?? 'Non affecte',
                'fd_couleur' => $m->familleDisciples?->couleur,
                'faiseur_nom' => $m->faiseur ? "{$m->faiseur->prenom} {$m->faiseur->nom}" : null,
                'source' => $m->source,
            ]);

        return [
            'date' => $dimanche->format('Y-m-d'),
            'date_formatee' => $dimanche->translatedFormat('l j F Y'),
            'total_na' => $totalNA,
            'total_nc' => $totalNC,
            'total' => $total,
            'par_fd' => $parFd,
            'membres' => $membres,
        ];
    }

    /**
     * Calcule les tendances entre le dimanche courant et le précédent.
     */
    private function calculerTendances(array $courant, array $precedent): array
    {
        $diffNA = $courant['total_na'] - $precedent['total_na'];
        $diffNC = $courant['total_nc'] - $precedent['total_nc'];
        $diffTotal = $courant['total'] - $precedent['total'];

        return [
            'na' => [
                'diff' => $diffNA,
                'pct' => $precedent['total_na'] > 0
                    ? round(($diffNA / $precedent['total_na']) * 100, 1)
                    : ($diffNA > 0 ? 100 : 0),
            ],
            'nc' => [
                'diff' => $diffNC,
                'pct' => $precedent['total_nc'] > 0
                    ? round(($diffNC / $precedent['total_nc']) * 100, 1)
                    : ($diffNC > 0 ? 100 : 0),
            ],
            'total' => [
                'diff' => $diffTotal,
                'pct' => $precedent['total'] > 0
                    ? round(($diffTotal / $precedent['total']) * 100, 1)
                    : ($diffTotal > 0 ? 100 : 0),
            ],
        ];
    }

    /**
     * Retourne le dernier dimanche (aujourd'hui si dimanche, sinon le dimanche passé).
     */
    private function dernierDimanche(): Carbon
    {
        $now = Carbon::now();
        if ($now->isSunday()) {
            return $now->startOfDay();
        }
        return $now->previous(Carbon::SUNDAY)->startOfDay();
    }
}
