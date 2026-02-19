<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    protected $fillable = [
        'membre_id',
        'faiseur_id',
        'type_canal',
        'date_interaction',
        'resume',
        'duree_minutes',
        'prochain_rdv',
        'prochain_objectif',
    ];

    protected function casts(): array
    {
        return [
            'date_interaction' => 'datetime',
            'prochain_rdv' => 'date',
            'duree_minutes' => 'integer',
        ];
    }

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class);
    }

    public function faiseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faiseur_id');
    }
}
