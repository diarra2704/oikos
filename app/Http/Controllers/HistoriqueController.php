<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Cellule;
use App\Models\Membre;
use App\Models\Transfert;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistoriqueController extends Controller
{
    /**
     * Dernières modifications sur membres, cellules, transferts (admin + superviseur).
     * Pagination : on fusionne les 3 sources, on trie, on pagine en mémoire.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->isSuperviseur()) {
            abort(403, 'Accès réservé aux administrateurs et superviseurs.');
        }

        $perPage = min(max((int) $request->input('per_page', 20), 5), 50);
        $page = max(1, (int) $request->input('page', 1));
        $type = $request->input('type', '');
        $viewMode = $request->input('view', 'modifs'); // modifs | audit
        $allowedTypes = ['membre', 'cellule', 'transfert', 'rapport', 'rapport_mensuel', 'famille_impact', 'parametre'];
        if ($type !== '' && ! in_array($type, $allowedTypes, true)) {
            $type = '';
        }

        if ($viewMode === 'audit') {
            return $this->indexAudit($request, $user, $perPage, $page, $type);
        }

        $all = $this->buildDernieresModifications($user, 500);

        if ($type !== '') {
            $all = array_values(array_filter($all, fn ($i) => $i['type'] === $type));
        }

        $total = count($all);
        $lastPage = (int) ceil($total / $perPage) ?: 1;
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($all, $offset, $perPage);

        $queryParams = array_filter(['per_page' => $perPage, 'type' => $type ?: null, 'view' => 'modifs']);
        $baseUrl = $request->url() . '?' . http_build_query($queryParams);
        $links = $this->buildPaginationLinks($page, $lastPage, $baseUrl);

        return Inertia::render('Historique/Index', [
            'viewMode' => 'modifs',
            'items' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total ? $offset + 1 : null,
                'to' => $total ? min($offset + $perPage, $total) : null,
            ],
            'links' => $links,
            'filters' => ['type' => $type],
        ]);
    }

    /**
     * Journal d'audit : toutes les actions (création, modification, suppression) enregistrées.
     */
    private function indexAudit(Request $request, $user, int $perPage, int $page, string $type): \Inertia\Response
    {
        $query = AuditLog::query()
            ->with('user:id,nom,prenom')
            ->orderByDesc('created_at');

        if ($user->isSuperviseur() && $user->fd_id) {
            $membreIds = Membre::where('fd_id', $user->fd_id)->pluck('id')->toArray();
            $celluleIds = Cellule::where('fd_id', $user->fd_id)->pluck('id')->toArray();
            $transfertIds = Transfert::where('fd_source_id', $user->fd_id)->orWhere('fd_destination_id', $user->fd_id)->pluck('id')->toArray();
            if ($membreIds === [] && $celluleIds === [] && $transfertIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where(function ($q) use ($membreIds, $celluleIds, $transfertIds) {
                    if ($membreIds !== []) {
                        $q->orWhere(fn ($q2) => $q2->where('auditable_type', Membre::class)->whereIn('auditable_id', $membreIds));
                    }
                    if ($celluleIds !== []) {
                        $q->orWhere(fn ($q2) => $q2->where('auditable_type', Cellule::class)->whereIn('auditable_id', $celluleIds));
                    }
                    if ($transfertIds !== []) {
                        $q->orWhere(fn ($q2) => $q2->where('auditable_type', Transfert::class)->whereIn('auditable_id', $transfertIds));
                    }
                });
            }
        }

        $typeMap = [
            'membre' => Membre::class,
            'cellule' => Cellule::class,
            'transfert' => Transfert::class,
            'rapport' => \App\Models\Rapport::class,
            'rapport_mensuel' => \App\Models\RapportMensuel::class,
            'famille_impact' => \App\Models\FamilleImpact::class,
            'parametre' => \App\Models\ParametreValeur::class,
        ];
        if ($type !== '' && isset($typeMap[$type])) {
            $query->where('auditable_type', $typeMap[$type]);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $paginator->getCollection()->transform(function (AuditLog $log) {
            $label = $this->auditLogLabel($log);
            $url = $this->auditLogUrl($log);
            return [
                'id' => $log->id,
                'type' => 'audit',
                'auditable_type' => $log->auditable_type,
                'auditable_type_label' => $log->auditable_type_label,
                'auditable_id' => $log->auditable_id,
                'action' => $log->action,
                'label' => $label,
                'sublabel' => ($log->user ? $log->user->prenom . ' ' . $log->user->nom : 'Système') . ' · ' . $this->actionLabel($log->action),
                'created_at' => $log->created_at?->toIso8601String(),
                'updated_at' => null,
                'created_by' => $log->user ? $log->user->prenom . ' ' . $log->user->nom : null,
                'updated_by' => null,
                'url' => $url,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ];
        });

        $queryParams = array_filter(['per_page' => $perPage, 'type' => $type ?: null, 'view' => 'audit']);
        $baseUrl = $request->url() . '?' . http_build_query($queryParams);
        $links = $this->buildPaginationLinks($paginator->currentPage(), $paginator->lastPage(), $request->url() . '?' . http_build_query($queryParams));

        return Inertia::render('Historique/Index', [
            'viewMode' => 'audit',
            'items' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => $links,
            'filters' => ['type' => $type],
        ]);
    }

    private function auditLogLabel(AuditLog $log): string
    {
        $type = class_basename($log->auditable_type);
        return $type . ' #' . $log->auditable_id . ' · ' . $this->actionLabel($log->action);
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            default => $action,
        };
    }

    private function auditLogUrl(AuditLog $log): ?string
    {
        $id = $log->auditable_id;
        return match ($log->auditable_type) {
            Membre::class => route('membres.show', $id),
            Cellule::class => route('cellules.show', $id),
            Transfert::class => route('transferts.show', $id),
            \App\Models\Rapport::class => route('rapports.show', $id),
            \App\Models\RapportMensuel::class => route('rapport-mensuel.show', $id),
            default => null,
        };
    }

    /**
     * Liens de pagination au format Laravel (pour affichage précédent/suivant/numéros).
     */
    private function buildPaginationLinks(int $currentPage, int $lastPage, string $baseUrl): array
    {
        $links = [];

        $links[] = [
            'url' => $currentPage <= 1 ? null : $baseUrl . '&page=' . ($currentPage - 1),
            'label' => '&laquo; Précédent',
            'active' => false,
        ];

        $start = max(1, $currentPage - 2);
        $end = min($lastPage, $currentPage + 2);
        for ($i = $start; $i <= $end; $i++) {
            $links[] = [
                'url' => $i === $currentPage ? null : $baseUrl . '&page=' . $i,
                'label' => (string) $i,
                'active' => $i === $currentPage,
            ];
        }

        $links[] = [
            'url' => $currentPage >= $lastPage ? null : $baseUrl . '&page=' . ($currentPage + 1),
            'label' => 'Suivant &raquo;',
            'active' => false,
        ];

        return $links;
    }

    /**
     * Construit la liste unifiée des dernières modifications (pour dashboard ou page).
     */
    public static function dernieresModifications($user, int $limit = 10): array
    {
        return (new self)->buildDernieresModifications($user, $limit);
    }

    private function buildDernieresModifications($user, int $limit): array
    {
        $out = [];

        $membresQuery = Membre::query()
            ->orderBy('updated_at', 'desc')
            ->with(['updatedBy:id,nom,prenom', 'createdBy:id,nom,prenom', 'familleDisciples:id,nom']);
        if ($user->isSuperviseur() && $user->fd_id) {
            $membresQuery->where('fd_id', $user->fd_id);
        }
        foreach ($membresQuery->limit($limit)->get() as $m) {
            $out[] = [
                'type' => 'membre',
                'id' => $m->id,
                'label' => $m->prenom . ' ' . $m->nom,
                'sublabel' => $m->familleDisciples?->nom,
                'created_at' => $m->created_at?->toIso8601String(),
                'updated_at' => $m->updated_at?->toIso8601String(),
                'created_by' => $m->createdBy ? $m->createdBy->prenom . ' ' . $m->createdBy->nom : null,
                'updated_by' => $m->updatedBy ? $m->updatedBy->prenom . ' ' . $m->updatedBy->nom : null,
                'url' => route('membres.show', $m),
            ];
        }

        $cellulesQuery = Cellule::query()
            ->orderBy('updated_at', 'desc')
            ->with(['updatedBy:id,nom,prenom', 'createdBy:id,nom,prenom', 'familleDisciples:id,nom']);
        if ($user->isSuperviseur() && $user->fd_id) {
            $cellulesQuery->where('fd_id', $user->fd_id);
        }
        foreach ($cellulesQuery->limit($limit)->get() as $c) {
            $out[] = [
                'type' => 'cellule',
                'id' => $c->id,
                'label' => $c->nom,
                'sublabel' => $c->familleDisciples?->nom,
                'created_at' => $c->created_at?->toIso8601String(),
                'updated_at' => $c->updated_at?->toIso8601String(),
                'created_by' => $c->createdBy ? $c->createdBy->prenom . ' ' . $c->createdBy->nom : null,
                'updated_by' => $c->updatedBy ? $c->updatedBy->prenom . ' ' . $c->updatedBy->nom : null,
                'url' => route('cellules.show', $c),
            ];
        }

        $transfertsQuery = Transfert::query()
            ->orderBy('updated_at', 'desc')
            ->with(['updatedBy:id,nom,prenom', 'demandeur:id,nom,prenom', 'membre:id,nom,prenom']);
        if ($user->isSuperviseur() && $user->fd_id) {
            $transfertsQuery->where(function ($q) use ($user) {
                $q->where('fd_source_id', $user->fd_id)->orWhere('fd_destination_id', $user->fd_id);
            });
        }
        foreach ($transfertsQuery->limit($limit)->get() as $t) {
            $out[] = [
                'type' => 'transfert',
                'id' => $t->id,
                'label' => 'Transfert ' . ($t->membre ? $t->membre->prenom . ' ' . $t->membre->nom : '#' . $t->id),
                'sublabel' => $t->statut,
                'created_at' => $t->created_at?->toIso8601String(),
                'updated_at' => $t->updated_at?->toIso8601String(),
                'created_by' => $t->demandeur ? $t->demandeur->prenom . ' ' . $t->demandeur->nom : null,
                'updated_by' => $t->updatedBy ? $t->updatedBy->prenom . ' ' . $t->updatedBy->nom : null,
                'url' => route('transferts.show', $t),
            ];
        }

        usort($out, fn ($a, $b) => strcmp(
            (string) ($b['updated_at'] ?? $b['created_at'] ?? ''),
            (string) ($a['updated_at'] ?? $a['created_at'] ?? '')
        ));

        return array_slice($out, 0, $limit);
    }
}
