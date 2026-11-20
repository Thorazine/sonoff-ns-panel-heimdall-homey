<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AlarmMode: string implements HasColor, HasLabel
{
    case Armed = 'armed';
    case PartiallyArmed = 'partially_armed';
    case Disarmed = 'disarmed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Armed => 'Volledig ingeschakeld',
            self::PartiallyArmed => 'Gedeeltelijk ingeschakeld',
            self::Disarmed => 'Uitgeschakeld',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Armed => 'danger',
            self::PartiallyArmed => 'warning',
            self::Disarmed => 'success',
        };
    }
}
