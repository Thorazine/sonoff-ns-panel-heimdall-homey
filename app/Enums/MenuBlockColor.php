<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuBlockColor: string implements HasLabel
{
    case Flashy = 'flashy';
    case Amber = 'amber';
    case Rose = 'rose';
    case Emerald = 'emerald';
    case Sky = 'sky';
    case Violet = 'violet';
    case Gray = 'gray';

    public function getLabel(): string
    {
        return match ($this) {
            self::Flashy => 'Turquoise (Heimdall)',
            self::Amber => 'Amber / Goud',
            self::Rose => 'Rood / Roze',
            self::Emerald => 'Groen',
            self::Sky => 'Blauw',
            self::Violet => 'Paars',
            self::Gray => 'Neutraal Grijs',
        };
    }

    public function kioskCardClasses(): string
    {
        return match ($this) {
            self::Flashy => 'border-flashy/60 shadow-[0_0_22px_rgba(0,255,204,0.15)] text-flashy',
            self::Amber => 'border-amber-400/60 shadow-[0_0_22px_rgba(245,158,11,0.15)] text-amber-300',
            self::Rose => 'border-rose-400/60 shadow-[0_0_22px_rgba(244,63,94,0.15)] text-rose-300',
            self::Emerald => 'border-emerald-400/60 shadow-[0_0_22px_rgba(16,185,129,0.15)] text-emerald-300',
            self::Sky => 'border-sky-400/60 shadow-[0_0_22px_rgba(14,165,233,0.15)] text-sky-300',
            self::Violet => 'border-violet-400/60 shadow-[0_0_22px_rgba(139,92,246,0.15)] text-violet-300',
            self::Gray => 'border-gray-500/70 text-gray-100',
        };
    }

    public function adminPreviewClasses(): string
    {
        return match ($this) {
            self::Flashy => 'border-teal-500/60 bg-gray-900 text-teal-300',
            self::Amber => 'border-amber-500/60 bg-gray-900 text-amber-400',
            self::Rose => 'border-rose-500/60 bg-gray-900 text-rose-400',
            self::Emerald => 'border-emerald-500/60 bg-gray-900 text-emerald-400',
            self::Sky => 'border-sky-500/60 bg-gray-900 text-sky-400',
            self::Violet => 'border-violet-500/60 bg-gray-900 text-violet-400',
            self::Gray => 'border-gray-600 bg-gray-900 text-gray-200',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
