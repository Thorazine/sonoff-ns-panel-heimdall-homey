<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\LogLevel;
use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['clear_pin']);

        if (empty($data['pin'])) {
            unset($data['pin']);
        }

        if (! filled($data['email'] ?? null)) {
            $data['email'] = null;
            $data['password'] = null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        ActivityLog::record(LogLevel::Info, "Gebruiker aangemaakt: {$this->record->name}");
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
