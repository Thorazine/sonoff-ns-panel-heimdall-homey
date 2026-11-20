<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosk_menu_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kiosk_menu_id')->constrained('kiosk_menus')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('type')->default('empty');
            $table->string('label')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('module_key')->nullable();
            $table->foreignId('submenu_id')->nullable()->constrained('kiosk_menus')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kiosk_menu_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_menu_blocks');
    }
};
