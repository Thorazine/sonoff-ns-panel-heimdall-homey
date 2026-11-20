<?php

namespace App\Filament\Resources\PinCodes\Pages;

use App\Filament\Resources\PinCodes\PinCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPinCodes extends ListRecords
{
    protected static string $resource = PinCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
