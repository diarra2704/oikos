<?php

namespace App\Models;

use App\Enums\StatutSuivi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suivi extends Model
{
    protected $fillable = [
        'faiseur_id',
        'membre_id',
        'statut',
        'notes',
        'date_debut',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutSuivi::class,
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function faiseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faiseur_id');
    }

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class);
    }

    public function scopeActif($query)
    {
        return $query->where('statut', StatutSuivi::ACTIF);
    }
}
