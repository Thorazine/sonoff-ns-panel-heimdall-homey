<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum LogLevel: string implements HasColor, HasIcon, HasLabel
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';

    public function getLabel(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Success => 'Succes',
            self::Warning => 'Waarschuwing',
            self::Danger => 'Kritiek',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Info => 'info',
            self::Success => 'success',
            self::Warning => 'warning',
            self::Danger => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Info => Heroicon::OutlinedInformationCircle,
            self::Success => Heroicon::OutlinedCheckCircle,
            self::Warning => Heroicon::OutlinedExclamationTriangle,
            self::Danger => Heroicon::OutlinedShieldExclamation,
        };
    }
}
