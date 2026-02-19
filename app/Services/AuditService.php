<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Enregistre une action dans le journal d'audit.
     */
    public function log(
        Model $model,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        $request = Request::instance();

        return AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Retourne les attributs à inclure dans l'audit (exclut timestamps, tokens, etc.).
     */
    public function getAuditableAttributes(Model $model): array
    {
        $hidden = array_merge(
            $model->getHidden(),
            ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at']
        );

        $heavyKeys = ['contenu', 'donnees']; // JSON volumineux : on n'enregistre pas le détail en audit

        $attrs = $model->getAttributes();
        $result = [];

        foreach ($attrs as $key => $value) {
            if (in_array($key, $hidden, true) || in_array($key, $heavyKeys, true)) {
                continue;
            }
            if (property_exists($model, 'auditExclude') && in_array($key, $model->auditExclude, true)) {
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
