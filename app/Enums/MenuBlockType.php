<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum MenuBlockType: string implements HasColor, HasIcon, HasLabel
{
    case Empty = 'empty';
    case Submenu = 'submenu';
    case Module = 'module';

    public function getLabel(): string
    {
        return match ($this) {
            self::Empty => 'Leeg vak',
            self::Submenu => 'Submenu (4 blocks)',
            self::Module => 'Module',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Empty => 'gray',
            self::Submenu => 'info',
            self::Module => 'primary',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Empty => Heroicon::OutlinedStop,
            self::Submenu => Heroicon::OutlinedSquares2x2,
            self::Module => Heroicon::OutlinedCube,
        };
    }
}
