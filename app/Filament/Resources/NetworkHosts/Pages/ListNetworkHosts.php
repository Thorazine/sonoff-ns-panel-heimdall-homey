<?php

namespace App\Filament\Resources\NetworkHosts\Pages;

use App\Filament\Resources\NetworkHosts\NetworkHostResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListNetworkHosts extends ListRecords
{
    protected static string $resource = NetworkHostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openNetworkMenu')
                ->label('Bekijk /menu')
                ->color('gray')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (): string => route('network.menu'), shouldOpenInNewTab: true),

            CreateAction::make()
                ->label('DNS-naam toevoegen'),
        ];
    }
}
