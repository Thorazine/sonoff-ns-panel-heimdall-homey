<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Enums\LogLevel;
use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Logboek';

    protected static ?string $modelLabel = 'Logboekregel';

    protected static ?string $pluralModelLabel = 'Logboek';

    protected static ?int $navigationSort = 8;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('Niveau')
                    ->badge()
                    ->sortable(),

                TextColumn::make('message')
                    ->label('Gebeurtenis')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('ip_address')
                    ->label('IP-adres')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Tijdstip')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Niveau')
                    ->options(LogLevel::class),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivityLogs::route('/'),
        ];
    }
}
