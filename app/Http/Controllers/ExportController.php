<?php

namespace App\Http\Controllers;

use App\Exports\MembresExport;
use App\Models\Membre;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export des membres en CSV (filtres selon rôle + paramètres optionnels).
     * Inclut : famille d'impact, département, formations.
     */
    public function membresCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $query = $this->membresQuery($user, $request);

        $filename = 'oikos-membres-' . now()->format('Y-m-d-His') . '.csv';

        return new StreamedResponse(function () use ($query) {
            $stream = fopen('php://output', 'w');
            fprintf($stream, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            $headers = [
                'Nom', 'Prénom', 'Email', 'Téléphone', 'Statut spirituel', 'FD', 'Cellule',
                'Faiseur', 'Famille d\'impact', 'Quartier FI', 'Département / service', 'Statut famille d\'impact',
                'En service depuis', 'Formations',
                'Date naissance', 'Anniv. conversion', 'Actif', 'Dernière présence', 'Notes',
            ];
            fputcsv($stream, $headers, ';');

            $query->with(['familleDisciples:id,nom', 'cellule:id,nom', 'faiseur:id,nom,prenom', 'familleImpact:id,nom,quartier', 'departement:id,nom', 'formations'])
                ->orderBy('nom')
                ->orderBy('prenom')
                ->chunk(200, function ($membres) use ($stream) {
                    foreach ($membres as $m) {
                        $formationsStr = $m->formations->isEmpty()
                            ? ''
                            : $m->formations->map(fn ($f) => $f->type_formation . ' (' . ($f->statut_formation ?? '') . ')')->implode(' ; ');

                        fputcsv($stream, [
                            $m->nom,
                            $m->prenom,
                            $m->email ?? '',
                            $m->telephone ?? '',
                            $m->statut_spirituel ?? '',
                            $m->familleDisciples?->nom ?? '',
                            $m->cellule?->nom ?? '',
                            $m->faiseur ? $m->faiseur->prenom . ' ' . $m->faiseur->nom : '',
                            $m->familleImpact?->nom ?? '',
                            $m->familleImpact?->quartier ?? '',
                            $m->departement?->nom ?? '',
                            $m->statut_famille_impact ?? '',
                            $m->en_service_depuis?->format('d/m/Y') ?? '',
                            $formationsStr,
                            $m->date_naissance?->format('d/m/Y') ?? '',
                            $m->date_conversion?->format('d/m/Y') ?? '',
                            $m->actif ? 'Oui' : 'Non',
                            $m->derniere_presence?->format('d/m/Y H:i') ?? '',
                            $m->notes ?? '',
                        ], ';');
                    }
                });

            fclose($stream);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export des membres en Excel (mêmes filtres et colonnes que le CSV).
     * Inclut : famille d'impact, département, formations.
     */
    public function membresExcel(Request $request)
    {
        $user = $request->user();
        $query = $this->membresQuery($user, $request);
        $filename = 'oikos-membres-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new MembresExport($query), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    private function membresQuery($user, Request $request)
    {
        $query = Membre::query();

        if ($user->isSuperviseur()) {
            $query->where('fd_id', $user->fd_id);
        } elseif ($user->isLeaderCellule()) {
            $query->where('cellule_id', $user->cellule_id);
        } elseif ($user->isFaiseur()) {
            $query->where('suivi_par', $user->id);
        }

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

        return $query;
    }
}
