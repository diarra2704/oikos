<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembreVue extends Model
{
    protected $table = 'membre_vues';

    protected $fillable = [
        'user_id',
        'nom',
        'filtres',
    ];

    protected function casts(): array
    {
        return [
            'filtres' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
