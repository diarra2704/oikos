<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeParType($query, string $auditableType)
    {
        return $query->where('auditable_type', $auditableType);
    }

    public function scopeParAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Libellé court du type audité (pour affichage).
     */
    public function getAuditableTypeLabelAttribute(): string
    {
        $map = [
            Membre::class => 'Membre',
            Cellule::class => 'Cellule',
            Transfert::class => 'Transfert',
            Rapport::class => 'Rapport',
            RapportMensuel::class => 'Rapport mensuel',
            FamilleImpact::class => 'Famille d\'impact',
            ParametreValeur::class => 'Paramètre',
            Invitation::class => 'Invitation',
            Interaction::class => 'Interaction',
        ];
        return $map[$this->auditable_type] ?? class_basename($this->auditable_type);
    }
}
