<?php

namespace App\Filament\Resources\NetworkHosts;

use App\Filament\Resources\NetworkHosts\Pages\CreateNetworkHost;
use App\Filament\Resources\NetworkHosts\Pages\EditNetworkHost;
use App\Filament\Resources\NetworkHosts\Pages\ListNetworkHosts;
use App\Filament\Resources\NetworkHosts\Schemas\NetworkHostForm;
use App\Filament\Resources\NetworkHosts\Tables\NetworkHostsTable;
use App\Models\NetworkHost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NetworkHostResource extends Resource
{
    protected static ?string $model = NetworkHost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Netwerk & DNS';

    protected static ?string $modelLabel = 'DNS-naam';

    protected static ?string $pluralModelLabel = 'Netwerk & DNS (/menu)';

    protected static ?string $slug = 'network-hosts';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 5;

    /**
     * @return Builder<NetworkHost>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('ports');
    }

    public static function form(Schema $schema): Schema
    {
        return NetworkHostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NetworkHostsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNetworkHosts::route('/'),
            'create' => CreateNetworkHost::route('/create'),
            'edit' => EditNetworkHost::route('/{record}/edit'),
        ];
    }
}
