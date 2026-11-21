<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NetworkProtocol: string implements HasColor, HasLabel
{
    case Http = 'http';
    case Https = 'https';

    public function getLabel(): string
    {
        return match ($this) {
            self::Http => 'HTTP',
            self::Https => 'HTTPS',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Http => 'gray',
            self::Https => 'success',
        };
    }
}
