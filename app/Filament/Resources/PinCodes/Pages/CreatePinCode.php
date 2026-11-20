<?php

namespace App\Filament\Resources\PinCodes\Pages;

use App\Enums\LogLevel;
use App\Filament\Resources\PinCodes\PinCodeResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;

class CreatePinCode extends CreateRecord
{
    protected static string $resource = PinCodeResource::class;

    protected function afterCreate(): void
    {
        ActivityLog::record(LogLevel::Info, "PIN-code aangemaakt: {$this->record->name}");
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
