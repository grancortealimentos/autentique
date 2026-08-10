<?php

namespace App\Models\Concerns;

use App\Services\AuditService;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->registrarAuditoria(
                action: 'created',
                beforeData: null,
                afterData: $model->dadosAuditaveis($model->getAttributes()),
            );
        });

        static::updated(function ($model) {
            $after = $model->dadosAuditaveis($model->getChanges());

            // Só mudou timestamp/campo excluído → não registra ruído.
            if (empty($after)) {
                return;
            }

            $before = $model->dadosAuditaveis(
                array_intersect_key($model->getOriginal(), $model->getChanges())
            );

            $model->registrarAuditoria(action: 'updated', beforeData: $before, afterData: $after);
        });

        static::deleted(function ($model) {
            $model->registrarAuditoria(
                action: 'deleted',
                beforeData: $model->dadosAuditaveis($model->getAttributes()),
                afterData: null,
            );
        });

        // Só existe em models com SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->registrarAuditoria(
                    action: 'restored',
                    beforeData: null,
                    afterData: $model->dadosAuditaveis($model->getAttributes()),
                );
            });
        }
    }

    //monta e grava a entrada: entity_type é o nome curto da classe
    protected function registrarAuditoria(
        string $action, 
        ?array $beforeData,
        ?array $afterData
    ): void
    {
        app(AuditService::class)->register(
            action: $action,
            entityType: class_basename($this),
            entityId: (string) $this->getKey(),
            beforeData: $beforeData,
            afterData: $afterData,
        );
    }

    //Remove campos sensiveis/ruido antes de gravar em before/after
    protected function dadosAuditaveis(array $dados): array
    {
        return collect($dados)->except($this->camposExcluidosDaAuditoria())->all();
    }

    //nunca auditar: $hidden do model + timestamps + lista extra opcional
    protected function camposExcluidosDaAuditoria(): array
    {
        return array_merge(
            $this->getHidden(),
            ['created_at', 'updated_at', 'deleted_at'],
            property_exists($this, 'auditExclude') ? $this->auditExclude : [],
        );
    }
}