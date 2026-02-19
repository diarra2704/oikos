<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membre extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'statut_spirituel',
        'fd_id',
        'cellule_id',
        'famille_impact_id',
        'statut_famille_impact',
        'en_service_depuis',
        'departement_id',
        'suivi_par',
        'date_naissance',
        'genre',
        'quartier',
        'profession',
        'situation_personnelle',
        'niveau_etude',
        'secteur_activite',
        'nombre_enfants',
        'competences_centres_interet',
        'contact_urgence_nom',
        'contact_urgence_telephone',
        'besoins_particuliers',
        'date_premiere_visite',
        'date_conversion',
        'source',
        'invite_par',
        'actif',
        'en_pcnc',
        'en_fi',
        'regulier_eglise',
        'niveau_integration',
        'motif_sortie',
        'derniere_presence',
        'notes',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'en_service_depuis' => 'date',
            'date_premiere_visite' => 'date',
            'date_conversion' => 'date',
            'derniere_presence' => 'datetime',
            'actif' => 'boolean',
            'en_pcnc' => 'boolean',
            'en_fi' => 'boolean',
            'regulier_eglise' => 'boolean',
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

    public function familleImpact(): BelongsTo
    {
        return $this->belongsTo(FamilleImpact::class, 'famille_impact_id');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }

    public function faiseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suivi_par');
    }

    public function invitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invite_par');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function suivis(): HasMany
    {
        return $this->hasMany(Suivi::class);
    }

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function rappels(): HasMany
    {
        return $this->hasMany(Rappel::class);
    }

    public function formations(): HasMany
    {
        return $this->hasMany(MembreFormation::class, 'membre_id');
    }

    // ──── Scopes ────

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeParFd($query, int $fdId)
    {
        return $query->where('fd_id', $fdId);
    }

    public function scopeParStatut($query, string $statut)
    {
        return $query->where('statut_spirituel', $statut);
    }

    public function scopeAbsentDepuis($query, int $semaines = 3)
    {
        return $query->where(function ($q) use ($semaines) {
            $q->whereNull('derniere_presence')
              ->orWhere('derniere_presence', '<', now()->subWeeks($semaines));
        });
    }

    public function scopeSansFd($query)
    {
        return $query->whereNull('fd_id');
    }

    public function scopeSansFaiseur($query)
    {
        return $query->whereNull('suivi_par');
    }

    /**
     * Anniversaires de naissance dans un mois donné (jour quelconque).
     */
    public function scopeAnniversairesNaissanceMois($query, ?int $mois = null, ?int $annee = null)
    {
        $mois = $mois ?? (int) now()->month;
        $annee = $annee ?? (int) now()->year;
        return $query->whereNotNull('date_naissance')
            ->whereMonth('date_naissance', $mois);
    }

    /**
     * Anniversaires de conversion dans un mois donné.
     */
    public function scopeAnniversairesConversionMois($query, ?int $mois = null, ?int $annee = null)
    {
        $mois = $mois ?? (int) now()->month;
        return $query->whereNotNull('date_conversion')
            ->whereMonth('date_conversion', $mois);
    }
}
