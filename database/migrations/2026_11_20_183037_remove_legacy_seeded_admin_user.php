<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $legacyEmails = array_unique(array_filter([
            'admin@heimdall.local',
            strtolower(trim((string) config('heimdall.admin_email', ''))),
        ]));

        DB::table('users')
            ->whereIn('email', $legacyEmails)
            ->delete();

        DB::table('users')
            ->where('name', 'Standaard PIN')
            ->whereNull('email')
            ->whereNull('pin_last_used_at')
            ->delete();
    }

    public function down(): void
    {
        // One-way cleanup of legacy seeded default credentials; /admin/setup handles fresh setup.
    }
};
