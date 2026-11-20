<?php

namespace App\Models;

use App\Enums\LogLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'message', 'ip_address', 'context'])]
class ActivityLog extends Model
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public static function record(LogLevel|string $type, string $message, ?array $context = null): self
    {
        $level = $type instanceof LogLevel ? $type : (LogLevel::tryFrom($type) ?? LogLevel::Info);

        return static::query()->create([
            'type' => $level,
            'message' => $message,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'context' => $context,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LogLevel::class,
            'context' => 'array',
        ];
    }
}
