<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cellule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'fd_id',
        'leader_id',
        'created_by_id',
        'updated_by_id',
    ];

    // ──── Relations ────

    public function familleDisciples(): BelongsTo
    {
        return $this->belongsTo(FamilleDisciples::class, 'fd_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function faiseurs(): HasMany
    {
        return $this->hasMany(User::class, 'cellule_id');
    }

    public function membres(): HasMany
    {
        return $this->hasMany(Membre::class, 'cellule_id');
    }
}
