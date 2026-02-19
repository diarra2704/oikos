<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = [
        'nom',
        'slug',
        'description',
        'icone',
        'criteres',
        'couleur',
    ];

    protected function casts(): array
    {
        return [
            'criteres' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('attribue_le')
            ->withTimestamps();
    }
}
