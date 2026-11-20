<?php

namespace App\Filament\Resources\PinCodes\Tables;

use App\Models\PinCode;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PinCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('last_used_at')
                    ->label('Laatst gebruikt')
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
                    ->hidden(function (PinCode $record): bool {
                        if ($record->is_active && PinCode::query()->where('is_active', true)->count() <= 1) {
                            return true;
                        }

                        if ($record->is_active && $record->is_admin && PinCode::query()->where('is_active', true)->where('is_admin', true)->count() <= 1) {
                            return true;
                        }

                        return false;
                    }),
            ]);
    }
}
