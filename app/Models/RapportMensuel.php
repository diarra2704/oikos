<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RapportMensuel extends Model
{
    protected $fillable = [
        'faiseur_id',
        'fd_id',
        'mois',
        'donnees',
        'observations',
    ];

    protected function casts(): array
    {
        return [
            'donnees' => 'array',
        ];
    }

    public function faiseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faiseur_id');
    }

    public function familleDisciples(): BelongsTo
    {
        return $this->belongsTo(FamilleDisciples::class, 'fd_id');
    }
}
