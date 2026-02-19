<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presence extends Model
{
    protected $fillable = [
        'membre_id',
        'pointe_par',
        'type_evenement',
        'date_evenement',
        'present',
        'remarque',
    ];

    protected function casts(): array
    {
        return [
            'date_evenement' => 'date',
            'present' => 'boolean',
        ];
    }

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class);
    }

    public function pointePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pointe_par');
    }

    public function scopeCulte($query)
    {
        return $query->where('type_evenement', 'culte');
    }

    public function scopePresent($query)
    {
        return $query->where('present', true);
    }

    public function scopePeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_evenement', [$debut, $fin]);
    }
}
