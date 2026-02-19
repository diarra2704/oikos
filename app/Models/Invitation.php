<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $fillable = [
        'inviteur_id',
        'nom_invite',
        'telephone_invite',
        'date_evenement',
        'est_venu',
        'devenu_membre',
        'nouveau_membre_id',
    ];

    protected function casts(): array
    {
        return [
            'date_evenement' => 'date',
            'est_venu' => 'boolean',
            'devenu_membre' => 'boolean',
        ];
    }

    public function inviteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviteur_id');
    }

    public function nouveauMembre(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'nouveau_membre_id');
    }
}
