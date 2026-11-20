<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('is_admin')->default(false)->after('is_active');
            $table->string('pin_hash')->nullable()->after('is_admin');
            $table->timestamp('pin_last_used_at')->nullable()->after('pin_hash');
        });

        DB::table('users')
            ->whereNotNull('email')
            ->whereNotNull('password')
            ->where('email', 'not like', 'pin-%@heimdall.local')
            ->update([
                'is_active' => true,
                'is_admin' => true,
            ]);

        if (Schema::hasTable('pin_codes')) {
            $pins = DB::table('pin_codes')->orderBy('id')->get();

            foreach ($pins as $pin) {
                $placeholderEmail = "pin-{$pin->id}@heimdall.local";
                $existingStub = DB::table('users')->where('email', $placeholderEmail)->first();

                if ($existingStub !== null) {
                    DB::table('users')
                        ->where('id', $existingStub->id)
                        ->update([
                            'name' => $pin->name,
                            'email' => null,
                            'password' => null,
                            'is_active' => (bool) $pin->is_active,
                            'is_admin' => false,
                            'pin_hash' => $pin->pin_hash,
                            'pin_last_used_at' => $pin->last_used_at,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('users')->insert([
                        'name' => $pin->name,
                        'email' => null,
                        'password' => null,
                        'is_active' => (bool) $pin->is_active,
                        'is_admin' => false,
                        'pin_hash' => $pin->pin_hash,
                        'pin_last_used_at' => $pin->last_used_at,
                        'created_at' => $pin->created_at ?? now(),
                        'updated_at' => $pin->updated_at ?? now(),
                    ]);
                }
            }

            DB::table('users')
                ->where('email', 'like', 'pin-%@heimdall.local')
                ->whereNull('pin_hash')
                ->delete();

            Schema::dropIfExists('pin_codes');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('pin_codes')) {
            Schema::create('pin_codes', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('pin_hash');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_admin')->default(false);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        $usersWithPin = DB::table('users')
            ->whereNotNull('pin_hash')
            ->where('pin_hash', '!=', '')
            ->get();

        foreach ($usersWithPin as $user) {
            DB::table('pin_codes')->insert([
                'name' => $user->name,
                'pin_hash' => $user->pin_hash,
                'is_active' => (bool) $user->is_active,
                'is_admin' => (bool) $user->is_admin,
                'last_used_at' => $user->pin_last_used_at,
                'created_at' => $user->created_at ?? now(),
                'updated_at' => $user->updated_at ?? now(),
            ]);
        }

        DB::table('users')
            ->where(function ($query): void {
                $query->whereNull('email')->orWhereNull('password');
            })
            ->delete();

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_active', 'is_admin', 'pin_hash', 'pin_last_used_at']);
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
