<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilleImpact extends Model
{
    protected $table = 'familles_impact';

    protected $fillable = [
        'nom',
        'quartier',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function membres(): HasMany
    {
        return $this->hasMany(Membre::class, 'famille_impact_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
