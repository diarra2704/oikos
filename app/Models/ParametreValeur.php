<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametreValeur extends Model
{
    protected $fillable = [
        'type',
        'valeur',
        'libelle',
        'ordre',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type)->where('actif', true)->orderBy('ordre')->orderBy('libelle');
    }

    public function scopeTousParType($query, string $type)
    {
        return $query->where('type', $type)->orderBy('ordre')->orderBy('libelle');
    }

    /**
     * Types de paramètres gérés.
     */
    public static function types(): array
    {
        return [
            'statut_spirituel' => 'Statut spirituel',
            'source' => 'Source',
            'profession' => 'Profession',
            'situation_personnelle' => 'Situation personnelle',
            'niveau_etude' => 'Niveau d\'étude',
            'secteur_activite' => 'Secteur d\'activité',
            'quartier' => 'Quartier',
            'statut_formation' => 'Statut formation',
            'type_formation' => 'Type formation',
            'statut_famille_impact' => 'Statut famille d\'impact',
        ];
    }
}
