<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer de base pour l'audit : enregistre created, updated, deleted dans audit_logs.
 * À attacher aux modèles audités.
 */
class AuditObserver
{
    private static array $oldValues = [];

    public function __construct(
        private AuditService $auditService
    ) {}

    public function created(Model $model): void
    {
        if ($this->shouldAudit($model)) {
            $this->auditService->log(
                $model,
                AuditLog::ACTION_CREATED,
                null,
                $this->auditService->getAuditableAttributes($model)
            );
        }
    }

    public function updating(Model $model): void
    {
        if ($this->shouldAudit($model)) {
            self::$oldValues[$this->key($model)] = $model->getOriginal();
        }
    }

    public function updated(Model $model): void
    {
        if ($this->shouldAudit($model)) {
            $key = $this->key($model);
            $old = self::$oldValues[$key] ?? null;
            unset(self::$oldValues[$key]);

            $this->auditService->log(
                $model,
                AuditLog::ACTION_UPDATED,
                $old ? $this->filterAuditable($old) : null,
                $this->auditService->getAuditableAttributes($model)
            );
        }
    }

    public function deleted(Model $model): void
    {
        if ($this->shouldAudit($model)) {
            $this->auditService->log(
                $model,
                AuditLog::ACTION_DELETED,
                $this->auditService->getAuditableAttributes($model),
                null
            );
        }
    }

    private function key(Model $model): string
    {
        return $model->getMorphClass() . ':' . $model->getKey();
    }

    private function shouldAudit(Model $model): bool
    {
        return auth()->check();
    }

    private function filterAuditable(array $attrs): array
    {
        $exclude = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];
        return array_diff_key($attrs, array_flip($exclude));
    }
}
