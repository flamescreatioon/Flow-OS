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

    // Every organization lives in the landlord connection, so no separate
    // tenant or landlord connection is configured.
    'tenant_database_connection_name' => null,
    'landlord_database_connection_name' => null,

    'shared_routes_cache' => false,

    // Jobs are not tenant aware unless they opt in by implementing
    // Spatie\Multitenancy\Jobs\TenantAware. A tenant aware job resolves the
    // organization from the context captured when it was dispatched and fails
    // loudly when that context is missing, rather than silently running
    // without an organization.
    'queues_are_tenant_aware_by_default' => false,

    // The container key holds the organization that is current for the request.
    'current_tenant_container_key' => 'multitenancy.current_tenant',

    // The context key carries the organization identifier into queued work.
    'current_tenant_context_key' => 'multitenancy.organization_id',

    // The tenant artisan command searches organizations by identifier only.
    // This application has no per-tenant database or domain columns.
    'tenant_artisan_search_fields' => ['id'],

    'actions' => [
        'make_tenant_current_action' => MakeTenantCurrentAction::class,
        'forget_current_tenant_action' => ForgetCurrentTenantAction::class,
        'make_queue_tenant_aware_action' => MakeQueueTenantAwareAction::class,
        'migrate_tenant' => MigrateTenantAction::class,
    ],

];
