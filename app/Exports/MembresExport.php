<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MembresExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private Builder $query
    ) {}

    public function query(): Builder
    {
        return $this->query
            ->with([
                'familleDisciples:id,nom',
                'cellule:id,nom',
                'faiseur:id,nom,prenom',
                'familleImpact:id,nom,quartier',
                'departement:id,nom',
                'formations',
            ])
            ->orderBy('nom')
            ->orderBy('prenom');
    }

    public function headings(): array
    {
        return [
            'Nom', 'Prénom', 'Email', 'Téléphone', 'Statut spirituel', 'FD', 'Cellule',
            'Faiseur', 'Famille d\'impact', 'Quartier FI', 'Département / service', 'Statut famille d\'impact',
            'En service depuis', 'Formations',
            'Date naissance', 'Anniv. conversion', 'Actif', 'Dernière présence', 'Notes',
        ];
    }

    public function map($m): array
    {
        $formationsStr = $m->formations->isEmpty()
            ? ''
            : $m->formations->map(fn ($f) => $f->type_formation . ' (' . ($f->statut_formation ?? '') . ')')->implode(' ; ');

        return [
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
        ];
    }
}
