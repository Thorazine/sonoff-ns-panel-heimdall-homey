<?php

namespace App\Filament\Resources\PinCodes;

use App\Filament\Resources\PinCodes\Pages\CreatePinCode;
use App\Filament\Resources\PinCodes\Pages\EditPinCode;
use App\Filament\Resources\PinCodes\Pages\ListPinCodes;
use App\Filament\Resources\PinCodes\Schemas\PinCodeForm;
use App\Filament\Resources\PinCodes\Tables\PinCodesTable;
use App\Models\PinCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PinCodeResource extends Resource
{
    protected static ?string $model = PinCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'PIN-codes';

    protected static ?string $modelLabel = 'PIN-code';

    protected static ?string $pluralModelLabel = 'PIN-codes';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return PinCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PinCodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPinCodes::route('/'),
            'create' => CreatePinCode::route('/create'),
            'edit' => EditPinCode::route('/{record}/edit'),
        ];
    }
}
