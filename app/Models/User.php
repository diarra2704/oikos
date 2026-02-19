<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\StatutSpirituel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'password',
        'role',
        'statut_spirituel',
        'fd_id',
        'cellule_id',
        'date_naissance',
        'genre',
        'quartier',
        'date_arrivee_eglise',
        'date_conversion',
        'actif',
        'derniere_presence',
        'photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'statut_spirituel' => StatutSpirituel::class,
            'date_naissance' => 'date',
            'date_arrivee_eglise' => 'date',
            'date_conversion' => 'date',
            'derniere_presence' => 'datetime',
            'actif' => 'boolean',
        ];
    }

    // ──── Accesseurs ────

    public function getFullNameAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    // ──── Relations ────

    public function familleDisciples(): BelongsTo
    {
        return $this->belongsTo(FamilleDisciples::class, 'fd_id');
    }

    public function cellule(): BelongsTo
    {
        return $this->belongsTo(Cellule::class);
    }

    public function fdSupervisee(): HasMany
    {
        return $this->hasMany(FamilleDisciples::class, 'superviseur_id');
    }

    public function celluleDirigee(): HasMany
    {
        return $this->hasMany(Cellule::class, 'leader_id');
    }

    public function suivis(): HasMany
    {
        return $this->hasMany(Suivi::class, 'faiseur_id');
    }

    public function membresAffecter(): HasMany
    {
        return $this->hasMany(Membre::class, 'suivi_par');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'inviteur_id');
    }

    public function rapports(): HasMany
    {
        return $this->hasMany(Rapport::class, 'auteur_id');
    }

    public function temoignages(): HasMany
    {
        return $this->hasMany(Temoignage::class);
    }

    public function rappels(): HasMany
    {
        return $this->hasMany(Rappel::class);
    }

    public function membreVues(): HasMany
    {
        return $this->hasMany(MembreVue::class);
    }

    public function badges(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('attribue_le')
            ->withTimestamps();
    }

    public function pointsLog(): HasMany
    {
        return $this->hasMany(PointLog::class);
    }

    // ──── Scopes ────

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeParRole($query, Role $role)
    {
        return $query->where('role', $role);
    }

    public function scopeParFd($query, int $fdId)
    {
        return $query->where('fd_id', $fdId);
    }

    // ──── Helpers ────

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function isSuperviseur(): bool
    {
        return $this->role === Role::SUPERVISEUR;
    }

    public function isLeaderCellule(): bool
    {
        return $this->role === Role::LEADER_CELLULE;
    }

    public function isFaiseur(): bool
    {
        return $this->role === Role::FAISEUR;
    }

    public function hasRoleAtLeast(Role $role): bool
    {
        return $this->role->isAtLeast($role);
    }

    public function getTotalPoints(): int
    {
        return $this->pointsLog()->sum('points');
    }
}
