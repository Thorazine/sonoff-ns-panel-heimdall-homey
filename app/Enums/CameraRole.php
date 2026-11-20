<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CameraRole: string implements HasColor, HasLabel
{
    case Doorbell = 'doorbell';
    case Garden = 'garden';
    case Extra = 'extra';

    public function getLabel(): string
    {
        return match ($this) {
            self::Doorbell => 'Deurbelcamera (Hoofdbeeld)',
            self::Garden => 'Tuincamera (Zichtbaar bij aanbellen)',
            self::Extra => 'Extra camera',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Doorbell => 'primary',
            self::Garden => 'success',
            self::Extra => 'gray',
        };
    }
}
