<?php

namespace App\Filament\Resources\NetworkHosts\Tables;

use App\Enums\LogLevel;
use App\Models\ActivityLog;
use App\Models\NetworkHost;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NetworkHostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('dns_name')
                    ->label('DNS-naam')
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (NetworkHost $record): ?string => $record->ip_address)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Categorie')
                    ->badge()
                    ->color('info')
                    ->placeholder('Algemeen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('endpoints')
                    ->label('Poorten & Koppelingen')
                    ->badge()
                    ->color('gray')
                    ->state(function (NetworkHost $record): array {
                        $items = [];

                        if ($record->has_root_link) {
                            $proto = strtoupper($record->resolvedProtocol()->value);
                            $portPart = $record->port !== null ? ':'.$record->port : '';
                            $items[] = "{$proto}{$portPart}".NetworkHost::normalizePath($record->path);
                        }

                        foreach ($record->ports as $portItem) {
                            $suffix = $portItem->is_active ? '' : ' (uit)';
                            $items[] = "{$portItem->displayPortTarget()} ({$portItem->label}){$suffix}";
                        }

                        return $items !== [] ? $items : ['Alleen DNS'];
                    }),

                IconColumn::make('is_active')
                    ->label('Actief')
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('openHost')
                    ->label('Openen')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->hidden(fn (NetworkHost $record): bool => ! $record->has_root_link && $record->ports->where('is_active', true)->isEmpty())
                    ->url(function (NetworkHost $record): string {
                        if ($record->has_root_link) {
                            return $record->resolvedUrl();
                        }

                        $firstPort = $record->ports->firstWhere('is_active', true);

                        return $firstPort !== null ? $firstPort->resolvedUrl($record) : $record->resolvedUrl();
                    }, shouldOpenInNewTab: true),

                EditAction::make(),

                DeleteAction::make()
                    ->after(fn (NetworkHost $record) => ActivityLog::record(
                        LogLevel::Warning,
                        "Netwerk DNS-naam verwijderd: {$record->name} ({$record->dns_name})"
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
