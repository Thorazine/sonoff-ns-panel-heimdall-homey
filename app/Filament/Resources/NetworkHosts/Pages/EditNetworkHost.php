<?php

namespace App\Filament\Resources\NetworkHosts\Pages;

use App\Enums\LogLevel;
use App\Filament\Resources\NetworkHosts\NetworkHostResource;
use App\Models\ActivityLog;
use App\Models\NetworkHost;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNetworkHost extends EditRecord
{
    protected static string $resource = NetworkHostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(fn (NetworkHost $record) => ActivityLog::record(
                    LogLevel::Warning,
                    "Netwerk DNS-naam verwijderd: {$record->name} ({$record->dns_name})"
                )),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['dns_name'] = NetworkHost::normalizeDnsName((string) ($data['dns_name'] ?? ''));
        $data['category'] = filled($data['category'] ?? null) ? trim((string) $data['category']) : null;
        $data['ip_address'] = filled($data['ip_address'] ?? null) ? trim((string) $data['ip_address']) : null;
        $data['description'] = filled($data['description'] ?? null) ? trim((string) $data['description']) : null;
        $data['port'] = filled($data['port'] ?? null) ? (int) $data['port'] : null;
        $data['path'] = ($cleanPath = NetworkHost::normalizePath((string) ($data['path'] ?? ''))) !== '' ? $cleanPath : null;

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var NetworkHost $record */
        $record = $this->record;
        ActivityLog::record(LogLevel::Info, "Netwerk DNS-naam bijgewerkt: {$record->name} ({$record->dns_name})");
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
