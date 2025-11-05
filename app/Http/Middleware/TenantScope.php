<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantScope
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->tenant_id) {
            // Set tenant context for multi-tenant queries
            $this->setTenantContext($user->tenant_id);

            // Add tenant_id to request for easy access
            $request->merge(['tenant_id' => $user->tenant_id]);
        }

        return $next($request);
    }

    /**
     * Set the tenant context for database queries
     */
    private function setTenantContext(int $tenantId): void
    {
        // This would be used with stancl/tenancy or custom tenant scoping
        // For now, we'll rely on global scopes in models

        // Store tenant ID in a way that can be accessed globally
        config(['app.current_tenant_id' => $tenantId]);

        // You could also set a database connection here if using separate DBs per tenant
        // config(['database.connections.tenant.database' => "site_{$tenantId}"]);
    }
}
