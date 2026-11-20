<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reolink_cameras', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role')->default('garden');
            $table->string('go2rtc_stream')->nullable();
            $table->string('stream_url')->nullable();
            $table->string('snapshot_url')->nullable();
            $table->boolean('supports_two_way_audio')->default(false);
            $table->boolean('show_on_doorbell')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reolink_cameras');
    }
};
