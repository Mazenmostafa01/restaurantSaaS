<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Cache TTLs (in seconds)
    |--------------------------------------------------------------------------
    |
    | Configure time-to-live for tenant Redis caches. Set to 0 to bypass caching.
    | These can be overridden via environment variables without rebuilding code.
    |
    */
    'menu_ttl' => (int) env('TENANT_CACHE_MENU_TTL', 3600),
    'dashboard_ttl' => (int) env('TENANT_CACHE_DASHBOARD_TTL', 60),
    'restaurant_ttl' => (int) env('TENANT_CACHE_RESTAURANT_TTL', 600),
];
