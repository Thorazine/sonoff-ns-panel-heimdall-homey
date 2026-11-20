<?php

namespace App\Filament\Resources\PinCodes\Pages;

use App\Enums\LogLevel;
use App\Filament\Resources\PinCodes\PinCodeResource;
use App\Models\ActivityLog;
use App\Models\PinCode;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPinCode extends EditRecord
{
    protected static string $resource = PinCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (PinCode $record): bool => $record->is_active && PinCode::query()->where('is_active', true)->count() <= 1)
                ->after(fn (PinCode $record) => ActivityLog::record(LogLevel::Warning, "PIN-code verwijderd: {$record->name}")),
        ];
    }

    protected function afterSave(): void
    {
        ActivityLog::record(LogLevel::Info, "PIN-code bijgewerkt: {$this->record->name}");
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
