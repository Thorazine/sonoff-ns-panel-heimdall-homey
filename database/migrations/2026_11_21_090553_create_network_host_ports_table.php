<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_host_ports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('network_host_id')->constrained('network_hosts')->cascadeOnDelete();
            $table->string('label', 80);
            $table->string('protocol', 10)->default('http');
            $table->unsignedInteger('port');
            $table->string('path', 255)->nullable();
            $table->boolean('open_in_new_tab')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['network_host_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_host_ports');
    }
};
