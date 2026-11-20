<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\LogLevel;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('access_level')
                    ->label('Toegang')
                    ->badge()
                    ->state(function (User $record): string {
                        if ($record->hasAdminAccess() && $record->hasPin()) {
                            return 'Admin + PIN';
                        }

                        if ($record->hasAdminAccess()) {
                            return 'Admin';
                        }

                        if ($record->hasPin()) {
                            return 'Alleen PIN';
                        }

                        return 'Geen';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Admin + PIN' => 'success',
                        'Admin' => 'info',
                        'Alleen PIN' => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('email')
                    ->label('E-mailadres (/admin)')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('pin_last_used_at')
                    ->label('PIN laatst gebruikt')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('Nog nooit')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Bijgewerkt')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user()) || $record->isLastActiveAdmin() || $record->isLastActivePin())
                    ->after(fn (User $record) => ActivityLog::record(LogLevel::Warning, "Gebruiker verwijderd: {$record->name}")),
            ]);
    }
}
