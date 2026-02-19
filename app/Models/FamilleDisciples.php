<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilleDisciples extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'familles_disciples';

    protected $fillable = [
        'nom',
        'description',
        'couleur',
        'superviseur_id',
    ];

    // ──── Relations ────

    public function superviseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superviseur_id');
    }

    public function cellules(): HasMany
    {
        return $this->hasMany(Cellule::class, 'fd_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'fd_id');
    }

    public function membres(): HasMany
    {
        return $this->hasMany(Membre::class, 'fd_id');
    }

    public function rapports(): HasMany
    {
        return $this->hasMany(Rapport::class, 'fd_id');
    }

    public function evenements(): HasMany
    {
        return $this->hasMany(Evenement::class, 'fd_id');
    }

    public function planningAccueil(): HasMany
    {
        return $this->hasMany(PlanningAccueil::class, 'fd_id');
    }

    // ──── Scopes ────

    public function scopeAvecEffectifs($query)
    {
        return $query->withCount(['users', 'membres', 'cellules']);
    }

    // ──── Helpers ────

    public function getEffectifTotal(): int
    {
        return $this->membres()->count();
    }

    public function getTauxRetention(int $mois = 6): float
    {
        $total = $this->membres()->count();
        if ($total === 0) return 0;

        $actifs = $this->membres()
            ->where('actif', true)
            ->where('derniere_presence', '>=', now()->subMonths($mois))
            ->count();

        return round(($actifs / $total) * 100, 1);
    }
}
