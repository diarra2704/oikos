<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfert extends Model
{
    protected $fillable = [
        'membre_id',
        'demandeur_id',
        'fd_source_id',
        'fd_destination_id',
        'motif',
        'statut',
        'traite_par',
        'traite_le',
        'commentaire_admin',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'traite_le' => 'datetime',
        ];
    }

    // ── Relations ──

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class);
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function fdSource(): BelongsTo
    {
        return $this->belongsTo(FamilleDisciples::class, 'fd_source_id');
    }

    public function fdDestination(): BelongsTo
    {
        return $this->belongsTo(FamilleDisciples::class, 'fd_destination_id');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    // ── Scopes ──

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }
}
