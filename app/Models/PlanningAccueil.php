<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningAccueil extends Model
{
    protected $table = 'planning_accueil';

    protected $fillable = [
        'fd_id',
        'mois',
        'annee',
    ];

    public function familleDisciples(): BelongsTo
    {
        return $this->belongsTo(FamilleDisciples::class, 'fd_id');
    }

    public function scopeAnnee($query, int $annee)
    {
        return $query->where('annee', $annee);
    }

    public function scopeMoisCourant($query)
    {
        return $query->where('mois', now()->month)->where('annee', now()->year);
    }
}
