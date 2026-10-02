<?php

use App\Models\Organization;
use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Actions\MigrateTenantAction;

return [

    'tenant_model' => Organization::class,

    // Automatic request-time lookup would run before route middleware and
    // therefore makes public routes depend on tenant database availability.
    // TenantMiddleware resolves tenants explicitly on tenant-protected routes.
    'tenant_finder' => null,

    // This shared-schema application currently establishes only request tenant
    // context. Cache and route-cache switching require separate, verified
    // configuration and are intentionally deferred.
    'switch_tenant_tasks' => [],

    'queues' => [
        'tenant_key' => 'organization_id',
    ],

    'actions' => [
        'make_tenant_current' => MakeTenantCurrentAction::class,
        'forget_current_tenant' => ForgetCurrentTenantAction::class,
        'make_queue_tenant_aware' => MakeQueueTenantAwareAction::class,
        'migrate_tenant' => MigrateTenantAction::class,
    ],

    'tenant_attributes' => [
        'id',
        'domains',
        'database',
        'database_name',
        'database_prefix',
    ],

    'current_tenant_container_key' => 'multitenancy.current_tenant',
];
