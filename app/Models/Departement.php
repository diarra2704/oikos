<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departement extends Model
{
    protected $fillable = ['nom', 'description', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function membres(): HasMany
    {
        return $this->hasMany(Membre::class, 'departement_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
