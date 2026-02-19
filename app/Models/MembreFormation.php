<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembreFormation extends Model
{
    protected $fillable = [
        'membre_id',
        'type_formation',
        'statut_formation',
    ];

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class);
    }
}
