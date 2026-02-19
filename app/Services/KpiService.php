<?php

namespace App\Services;

use App\Enums\StatutSpirituel;
use App\Models\FamilleDisciples;
use App\Models\Invitation;
use App\Models\Membre;
use App\Models\PointLog;
use App\Models\Presence;
use App\Models\Rapport;
use App\Models\Temoignage;
use App\Models\User;
use Illuminate\Support\Carbon;

class KpiService
{
    /** Libellés des actions du système de points (pour les KPI). */
    private const POINTS_ACTION_LABELS = [
        'presence_culte' => 'Présence culte (âmes)',
        'invitation_venue' => 'Invité venu',
        'rapport_soumis' => 'Rapport soumis à temps',
        'rapport_mensuel_soumis' => 'Rapport mensuel à temps',
        'formation_debut' => 'Formation commencée',
        'formation_validee' => 'Formation validée',
        'famille_impact_regulier' => 'FI régulier',
        'famille_impact_engage' => 'FI engagé',
        'service_debut' => 'Service (département)',
        'temoignage' => 'Témoignage',
        'membre_amene' => 'Membre amené',
        'invitation' => 'Invitation',
        'presence' => 'Présence',
    ];
    /**
     * KPI globaux (admin) -- les 4 axes stratégiques.
     */
    public function global(string $periodeDebut = null, string $periodeFin = null): array
    {
        $debut = $periodeDebut ? Carbon::parse($periodeDebut) : now()->startOfMonth();
        $fin = $periodeFin ? Carbon::parse($periodeFin) : now();

        $parFd = $this->kpiParFd($debut, $fin);
        $pointsGlobal = $this->axePoints($debut, $fin, null);
        foreach ($parFd as $i => $fd) {
            $parFd[$i]['points_total'] = $this->pointsTotalPourFd($fd['id'], $debut, $fin);
        }

        return [
            'croissance' => $this->axeCroissance($debut, $fin),
            'fidelisation' => $this->axeFidelisation($debut, $fin),
            'transformation' => $this->axeTransformation($debut, $fin),
            'deploiement' => $this->axeDeploiement($debut, $fin),
            'points' => $pointsGlobal,
            'par_fd' => $parFd,
            'tendances' => $this->tendances(),
        ];
    }

    /**
     * KPI pour une FD spécifique.
     */
    public function pourFd(int $fdId, Carbon $debut = null, Carbon $fin = null): array
    {
        $debut = $debut ?? now()->startOfMonth();
        $fin = $fin ?? now();
        $membreIds = Membre::where('fd_id', $fdId)->pluck('id');

        $totalMembres = $membreIds->count();
        $membresActifs = Membre::where('fd_id', $fdId)->where('actif', true)->count();
        $na = Membre::where('fd_id', $fdId)->where('statut_spirituel', 'NA')->count();
        $nc = Membre::where('fd_id', $fdId)->where('statut_spirituel', 'NC')->count();
        $fideles = Membre::where('fd_id', $fdId)->where('statut_spirituel', 'fidele')->count();
        $stars = Membre::where('fd_id', $fdId)->where('statut_spirituel', 'STAR')->count();

        // Nouveaux membres ce mois
        $nouveaux = Membre::where('fd_id', $fdId)
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        // Présences de la période
        $totalPresences = Presence::whereIn('membre_id', $membreIds)
            ->whereBetween('date_evenement', [$debut, $fin])
            ->where('present', true)
            ->count();

        $totalPointages = Presence::whereIn('membre_id', $membreIds)
            ->whereBetween('date_evenement', [$debut, $fin])
            ->count();

        $tauxPresence = $totalPointages > 0 ? round(($totalPresences / $totalPointages) * 100) : 0;

        // Absents 3+ semaines
        $absents3sem = Membre::where('fd_id', $fdId)->absentDepuis(3)->count();

        // Invitations
        $userIds = User::where('fd_id', $fdId)->pluck('id');
        $invitations = Invitation::whereIn('inviteur_id', $userIds)
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        // Taux de rétention (membres actifs / total)
        $tauxRetention = $totalMembres > 0 ? round(($membresActifs / $totalMembres) * 100) : 0;

        $pointsFd = $this->axePoints($debut, $fin, $fdId);

        return [
            'total_membres' => $totalMembres,
            'membres_actifs' => $membresActifs,
            'nouveaux' => $nouveaux,
            'na' => $na,
            'nc' => $nc,
            'fideles' => $fideles,
            'stars' => $stars,
            'taux_presence' => $tauxPresence,
            'absents_3sem' => $absents3sem,
            'invitations' => $invitations,
            'taux_retention' => $tauxRetention,
            'points' => $pointsFd,
        ];
    }

    /**
     * Total de points gagnés par une FD sur la période (tous les faiseurs/leaders de la FD).
     */
    private function pointsTotalPourFd(int $fdId, Carbon $debut, Carbon $fin): int
    {
        $userIds = User::where('fd_id', $fdId)->pluck('id');
        if ($userIds->isEmpty()) {
            return 0;
        }
        return (int) PointLog::whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$debut, $fin])
            ->sum('points');
    }

    /**
     * KPI liés au système de points : total période, répartition par action, classement faiseurs.
     * Si $fdId est fourni, limite aux utilisateurs de cette FD.
     */
    private function axePoints(Carbon $debut, Carbon $fin, ?int $fdId = null): array
    {
        $query = User::query();
        if ($fdId !== null) {
            $query->where('fd_id', $fdId);
        }
        $userIds = $query->pluck('id');
        if ($userIds->isEmpty()) {
            return [
                'total_periode' => 0,
                'par_action' => [],
                'classement_faiseurs' => [],
            ];
        }

        $logs = PointLog::whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$debut, $fin])
            ->get(['action', 'points']);

        $totalPeriode = $logs->sum('points');
        $parAction = [];
        foreach ($logs->groupBy('action') as $action => $items) {
            $parAction[] = [
                'action' => $action,
                'label' => self::POINTS_ACTION_LABELS[$action] ?? $action,
                'points' => $items->sum('points'),
                'count' => $items->count(),
            ];
        }
        usort($parAction, fn ($a, $b) => $b['points'] <=> $a['points']);

        $totauxParUser = PointLog::whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$debut, $fin])
            ->selectRaw('user_id, sum(points) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $users = User::whereIn('id', $totauxParUser->pluck('user_id'))->get(['id', 'nom', 'prenom']);
        $classement = $totauxParUser->map(function ($row) use ($users) {
            $u = $users->firstWhere('id', $row->user_id);
            return [
                'user_id' => $row->user_id,
                'nom' => $u ? $u->prenom . ' ' . $u->nom : '—',
                'total_points' => (int) $row->total,
            ];
        })->values()->all();

        return [
            'total_periode' => $totalPeriode,
            'par_action' => $parAction,
            'classement_faiseurs' => $classement,
        ];
    }

    // ── Axes stratégiques ──

    private function axeCroissance(Carbon $debut, Carbon $fin): array
    {
        $nouveauxMembres = Membre::whereBetween('created_at', [$debut, $fin])->count();
        $invitations = Invitation::whereBetween('created_at', [$debut, $fin])->count();
        $invitationsVenues = Invitation::whereBetween('created_at', [$debut, $fin])->where('est_venu', true)->count();
        $conversions = Membre::where('statut_spirituel', 'NC')
            ->whereBetween('date_conversion', [$debut, $fin])
            ->count();

        return [
            'nouveaux_membres' => $nouveauxMembres,
            'invitations' => $invitations,
            'invitations_venues' => $invitationsVenues,
            'taux_conversion_invitation' => $invitations > 0 ? round(($invitationsVenues / $invitations) * 100) : 0,
            'conversions' => $conversions,
        ];
    }

    private function axeFidelisation(Carbon $debut, Carbon $fin): array
    {
        $totalMembres = Membre::count();
        $membresActifs = Membre::where('actif', true)->count();
        $absents3sem = Membre::absentDepuis(3)->count();
        $absents8sem = Membre::absentDepuis(8)->count();

        $membreIds = Membre::pluck('id');
        $totalPointages = Presence::whereIn('membre_id', $membreIds)
            ->whereBetween('date_evenement', [$debut, $fin])
            ->count();
        $presentsCount = Presence::whereIn('membre_id', $membreIds)
            ->whereBetween('date_evenement', [$debut, $fin])
            ->where('present', true)
            ->count();

        return [
            'taux_retention' => $totalMembres > 0 ? round(($membresActifs / $totalMembres) * 100) : 0,
            'taux_presence' => $totalPointages > 0 ? round(($presentsCount / $totalPointages) * 100) : 0,
            'absents_3_semaines' => $absents3sem,
            'absents_8_semaines' => $absents8sem,
            'membres_actifs' => $membresActifs,
        ];
    }

    private function axeTransformation(Carbon $debut, Carbon $fin): array
    {
        $temoignages = Temoignage::whereBetween('created_at', [$debut, $fin])->count();
        $temoignagesValides = Temoignage::where('statut', 'valide')
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        // Progression des statuts (combien de membres ont progressé)
        $na = Membre::where('statut_spirituel', 'NA')->count();
        $nc = Membre::where('statut_spirituel', 'NC')->count();
        $fideles = Membre::where('statut_spirituel', 'fidele')->count();
        $stars = Membre::where('statut_spirituel', 'STAR')->count();
        $faiseurs = Membre::where('statut_spirituel', 'faiseur_disciple')->count();

        return [
            'temoignages' => $temoignages,
            'temoignages_valides' => $temoignagesValides,
            'repartition_statuts' => [
                'NA' => $na,
                'NC' => $nc,
                'Fidèle' => $fideles,
                'STAR' => $stars,
                'Faiseur' => $faiseurs,
            ],
        ];
    }

    private function axeDeploiement(Carbon $debut, Carbon $fin): array
    {
        $totalFaiseurs = User::where('role', 'faiseur')->count();
        $totalLeaders = User::where('role', 'leader_cellule')->count();
        $rapportsSoumis = Rapport::whereBetween('created_at', [$debut, $fin])
            ->where('statut', '!=', 'brouillon')
            ->count();

        return [
            'total_faiseurs' => $totalFaiseurs,
            'total_leaders' => $totalLeaders,
            'rapports_soumis' => $rapportsSoumis,
        ];
    }

    private function kpiParFd(Carbon $debut, Carbon $fin): array
    {
        $fds = FamilleDisciples::all();
        $result = [];

        foreach ($fds as $fd) {
            $membreIds = Membre::where('fd_id', $fd->id)->pluck('id');
            $total = $membreIds->count();
            $actifs = Membre::where('fd_id', $fd->id)->where('actif', true)->count();
            $nouveaux = Membre::where('fd_id', $fd->id)->whereBetween('created_at', [$debut, $fin])->count();

            $totalPointages = Presence::whereIn('membre_id', $membreIds)
                ->whereBetween('date_evenement', [$debut, $fin])
                ->count();
            $presentsCount = Presence::whereIn('membre_id', $membreIds)
                ->whereBetween('date_evenement', [$debut, $fin])
                ->where('present', true)
                ->count();

            $result[] = [
                'id' => $fd->id,
                'nom' => $fd->nom,
                'couleur' => $fd->couleur,
                'total_membres' => $total,
                'membres_actifs' => $actifs,
                'nouveaux' => $nouveaux,
                'taux_presence' => $totalPointages > 0 ? round(($presentsCount / $totalPointages) * 100) : 0,
                'taux_retention' => $total > 0 ? round(($actifs / $total) * 100) : 0,
            ];
        }

        return $result;
    }

    /**
     * Tendances sur les 6 derniers mois.
     */
    private function tendances(): array
    {
        $mois = [];
        for ($i = 5; $i >= 0; $i--) {
            $debut = now()->subMonths($i)->startOfMonth();
            $fin = now()->subMonths($i)->endOfMonth();
            $label = $debut->translatedFormat('M Y');

            $mois[] = [
                'label' => $label,
                'nouveaux_membres' => Membre::whereBetween('created_at', [$debut, $fin])->count(),
                'presences' => Presence::whereBetween('date_evenement', [$debut, $fin])->where('present', true)->count(),
                'invitations' => Invitation::whereBetween('created_at', [$debut, $fin])->count(),
            ];
        }

        return $mois;
    }
}
