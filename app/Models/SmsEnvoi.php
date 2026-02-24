<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsEnvoi extends Model
{
    protected $fillable = [
        'nom',
        'message',
        'fd_id',
        'membre_ids',
        'statut',
        'date_programmee',
        'envoye_at',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'membre_ids' => 'array',
            'date_programmee' => 'datetime',
            'envoye_at' => 'datetime',
        ];
    }

    public function familleDisciples(): BelongsTo
    {
        return $this->belongsTo(FamilleDisciples::class, 'fd_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopeProgrammes($query)
    {
        return $query->where('statut', 'programme');
    }

    public function scopeEnvoyes($query)
    {
        return $query->where('statut', 'envoye');
    }
}
