<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_hosts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('dns_name', 255);
            $table->string('ip_address', 45)->nullable();
            $table->string('category', 60)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('protocol', 10)->default('http');
            $table->unsignedInteger('port')->nullable();
            $table->string('path', 255)->nullable();
            $table->boolean('has_root_link')->default(true);
            $table->boolean('open_in_new_tab')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_hosts');
    }
};
