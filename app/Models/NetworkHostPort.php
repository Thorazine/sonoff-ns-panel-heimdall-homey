<?php

namespace App\Models;

use App\Enums\NetworkProtocol;
use Database\Factories\NetworkHostPortFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'network_host_id',
    'label',
    'protocol',
    'port',
    'path',
    'open_in_new_tab',
    'sort_order',
    'is_active',
])]
class NetworkHostPort extends Model
{
    /** @use HasFactory<NetworkHostPortFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<NetworkHost, $this>
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(NetworkHost::class, 'network_host_id');
    }

    public function resolvedProtocol(): NetworkProtocol
    {
        return $this->protocol instanceof NetworkProtocol
            ? $this->protocol
            : (NetworkProtocol::tryFrom((string) $this->protocol) ?? NetworkProtocol::Http);
    }

    public function resolvedUrl(?NetworkHost $host = null): string
    {
        $targetHost = $host ?? $this->host;
        $normalizedHost = $targetHost !== null
            ? NetworkHost::normalizeDnsName((string) $targetHost->dns_name)
            : 'localhost';
        $safeHost = NetworkHost::isValidHost($normalizedHost) ? $normalizedHost : 'localhost';
        $protocol = $this->resolvedProtocol()->value;
        $safePort = max(1, min(65535, (int) $this->port));
        $pathSegment = NetworkHost::normalizePath($this->path);

        return "{$protocol}://{$safeHost}:{$safePort}{$pathSegment}";
    }

    public function displayPortTarget(): string
    {
        $safePort = max(1, min(65535, (int) $this->port));

        return ':'.$safePort.NetworkHost::normalizePath($this->path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'protocol' => NetworkProtocol::class,
            'port' => 'integer',
            'open_in_new_tab' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
