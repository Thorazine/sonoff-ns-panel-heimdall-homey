<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosk_menus', function (Blueprint $table): void {
            $table->foreignId('wall_panel_id')->nullable()->after('is_root')->constrained('wall_panels')->cascadeOnDelete();
            $table->unsignedTinyInteger('grid_size')->default(4)->after('wall_panel_id');
        });
    }

    public function down(): void
    {
        Schema::table('kiosk_menus', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('wall_panel_id');
            $table->dropColumn('grid_size');
        });
    }
};
