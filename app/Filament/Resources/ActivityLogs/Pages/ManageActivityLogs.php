<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\ActivityLog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageActivityLogs extends ManageRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearLogs')
                ->label('Logboek Wissen')
                ->color('danger')
                ->icon(Heroicon::OutlinedTrash)
                ->requiresConfirmation()
                ->modalHeading('Volledig logboek wissen?')
                ->modalDescription('Alle vastgelegde activiteit wordt definitief verwijderd.')
                ->hidden(fn (): bool => ActivityLog::query()->count() === 0)
                ->action(function (): void {
                    ActivityLog::query()->delete();

                    Notification::make()
                        ->title('Logboek gewist')
                        ->success()
                        ->send();
                }),
        ];
    }
}
