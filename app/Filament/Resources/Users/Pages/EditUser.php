<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\LogLevel;
use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (User $record): bool => $record->is(auth()->user()) || $record->isLastActiveAdmin() || $record->isLastActivePin())
                ->after(fn (User $record) => ActivityLog::record(LogLevel::Warning, "Gebruiker verwijderd: {$record->name}")),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['clear_pin'] = false;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $clearPin = ! empty($data['clear_pin']);
        unset($data['clear_pin']);

        if ($clearPin && filled($data['email'] ?? null)) {
            unset($data['pin']);
            $data['pin_hash'] = null;
            $data['pin_last_used_at'] = null;
        } elseif (empty($data['pin'])) {
            unset($data['pin']);
        }

        if (! filled($data['email'] ?? null)) {
            $data['email'] = null;
            $data['password'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        ActivityLog::record(LogLevel::Info, "Gebruiker bijgewerkt: {$this->record->name}");
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
