<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Initial Seeder Account (alleen gebruikt door DatabaseSeeder)
    |--------------------------------------------------------------------------
    |
    | Alle applicatie-instellingen (Homey URL, versleutelde API-key, Flows,
    | timers, weerlocatie) en PIN-codes worden uitsluitend in de database
    | opgeslagen en beheerd via het Filament Admin Panel (/admin).
    |
    */
    'admin_name' => env('FILAMENT_ADMIN_NAME', 'Heimdall Beheerder'),
    'admin_email' => env('FILAMENT_ADMIN_EMAIL', 'admin@heimdall.local'),
    'admin_password' => env('FILAMENT_ADMIN_PASSWORD', 'heimdall-admin'),
];
