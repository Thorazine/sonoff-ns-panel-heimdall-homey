<?php

namespace App\Models;

use App\Enums\NetworkProtocol;
use Database\Factories\NetworkHostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'dns_name',
    'ip_address',
    'category',
    'description',
    'protocol',
    'port',
    'path',
    'has_root_link',
    'open_in_new_tab',
    'sort_order',
    'is_active',
])]
class NetworkHost extends Model
{
    /** @use HasFactory<NetworkHostFactory> */
    use HasFactory;

    /**
     * @return HasMany<NetworkHostPort, $this>
     */
    public function ports(): HasMany
    {
        return $this->hasMany(NetworkHostPort::class)
            ->orderBy('sort_order')
            ->orderBy('port')
            ->orderBy('id');
    }

    /**
     * @return HasMany<NetworkHostPort, $this>
     */
    public function activePorts(): HasMany
    {
        return $this->hasMany(NetworkHostPort::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('port')
            ->orderBy('id');
    }

    public static function normalizeDnsName(string $input): string
    {
        $clean = strtolower(trim($input));

        if (str_starts_with($clean, 'http://')) {
            $clean = substr($clean, 7);
        } elseif (str_starts_with($clean, 'https://')) {
            $clean = substr($clean, 8);
        }

        $clean = rtrim($clean, './');

        if (filter_var($clean, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return '['.$clean.']';
        }

        return $clean;
    }

    public static function isValidHost(string $host): bool
    {
        if ($host === '' || strlen($host) > 253) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return true;
        }

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $ipv6 = substr($host, 1, -1);

            return filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }

        return preg_match('/^[a-z0-9](?:[a-z0-9_-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9_-]{0,61}[a-z0-9])?)*$/', $host) === 1;
    }

    public static function normalizePath(?string $path): string
    {
        $clean = preg_replace('/[\x00-\x1F\x7F\s]+/', '', trim((string) $path)) ?? '';

        if ($clean === '' || $clean === '/') {
            return '';
        }

        return '/'.ltrim($clean, '/');
    }

    public function resolvedProtocol(): NetworkProtocol
    {
        return $this->protocol instanceof NetworkProtocol
            ? $this->protocol
            : (NetworkProtocol::tryFrom((string) $this->protocol) ?? NetworkProtocol::Http);
    }

    public function resolvedUrl(): string
    {
        $protocol = $this->resolvedProtocol()->value;
        $normalizedHost = static::normalizeDnsName((string) $this->dns_name);
        $safeHost = static::isValidHost($normalizedHost) ? $normalizedHost : 'localhost';
        $portSegment = ($this->port !== null && $this->port >= 1 && $this->port <= 65535)
            ? ':'.$this->port
            : '';
        $pathSegment = static::normalizePath($this->path);

        return "{$protocol}://{$safeHost}{$portSegment}{$pathSegment}";
    }

    public function displayAddress(): string
    {
        $normalizedHost = static::normalizeDnsName((string) $this->dns_name);
        $portSegment = ($this->port !== null && $this->port >= 1 && $this->port <= 65535)
            ? ':'.$this->port
            : '';
        $pathSegment = static::normalizePath($this->path);

        return "{$normalizedHost}{$portSegment}{$pathSegment}";
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'protocol' => NetworkProtocol::class,
            'port' => 'integer',
            'has_root_link' => 'boolean',
            'open_in_new_tab' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
