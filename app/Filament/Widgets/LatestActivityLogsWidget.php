<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestActivityLogsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recente Kiosk & Alarm Activiteit';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ActivityLog::query()->latest('id')->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('type')
                    ->label('Niveau')
                    ->badge(),

                TextColumn::make('message')
                    ->label('Gebeurtenis')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Tijdstip')
                    ->dateTime('d-m-Y H:i:s'),
            ]);
    }
}
