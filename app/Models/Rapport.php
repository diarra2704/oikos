<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rapport extends Model
{
    protected $fillable = [
        'auteur_id',
        'fd_id',
        'type',
        'periode_debut',
        'periode_fin',
        'contenu',
        'statut',
        'valide_par',
        'valide_le',
    ];

    protected function casts(): array
    {
        return [
            'periode_debut' => 'date',
            'periode_fin' => 'date',
            'contenu' => 'array',
            'valide_le' => 'datetime',
        ];
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function familleDisciples(): BelongsTo
    {
        return $this->belongsTo(FamilleDisciples::class, 'fd_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function scopeHebdomadaire($query)
    {
        return $query->where('type', 'hebdomadaire');
    }

    public function scopeMensuel($query)
    {
        return $query->where('type', 'mensuel');
    }

    public function scopeSoumis($query)
    {
        return $query->where('statut', 'soumis');
    }
}
