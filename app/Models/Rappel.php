<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rappel extends Model
{
    protected $fillable = [
        'membre_id',
        'user_id',
        'type',
        'date_souhaitee',
        'libelle',
        'fait_at',
    ];

    protected function casts(): array
    {
        return [
            'date_souhaitee' => 'date',
            'fait_at' => 'datetime',
        ];
    }

    public const TYPE_CONTACTER = 'contacter';
    public const TYPE_RELANCE_INTERACTION = 'relance_interaction';

    public static function types(): array
    {
        return [
            self::TYPE_CONTACTER => 'Contacter',
            self::TYPE_RELANCE_INTERACTION => 'Relance interaction',
        ];
    }

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePourFaiseur($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeNonFait($query)
    {
        return $query->whereNull('fait_at');
    }

    public function scopeProchains($query, int $jours = 14)
    {
        return $query->where('date_souhaitee', '>=', now()->startOfDay())
            ->where('date_souhaitee', '<=', now()->addDays($jours)->endOfDay())
            ->orderBy('date_souhaitee')
            ->orderBy('id');
    }
}
