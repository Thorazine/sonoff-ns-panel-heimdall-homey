<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pin_codes', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('is_active');
        });

        if (DB::table('pin_codes')->exists() && DB::table('pin_codes')->where('is_admin', true)->doesntExist()) {
            DB::table('pin_codes')->update(['is_admin' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pin_codes', function (Blueprint $table): void {
            $table->dropColumn('is_admin');
        });
    }
};
