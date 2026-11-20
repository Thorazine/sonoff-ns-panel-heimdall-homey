<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuBlockIcon: string implements HasLabel
{
    case ShieldCheck = 'shield-check';
    case VideoCamera = 'video-camera';
    case Bell = 'bell';
    case Microphone = 'microphone';
    case Dumbbell = 'dumbbell';
    case Power = 'power';
    case Grid = 'grid';
    case Waves = 'waves';
    case Fire = 'fire';
    case Thermometer = 'thermometer';
    case Lightbulb = 'lightbulb';
    case Blinds = 'blinds';
    case Droplet = 'droplet';
    case Fan = 'fan';
    case Home = 'home';
    case Sun = 'sun';
    case Moon = 'moon';
    case Lock = 'lock';
    case LockOpen = 'lock-open';
    case Bolt = 'bolt';
    case Sparkles = 'sparkles';
    case ChevronUp = 'chevron-up';
    case ChevronDown = 'chevron-down';
    case Stop = 'stop';

    public function getLabel(): string
    {
        return match ($this) {
            self::ShieldCheck => 'Schild / Alarm',
            self::VideoCamera => 'Camera / Video',
            self::Bell => 'Deurbel / Melding',
            self::Microphone => 'Microfoon / Intercom',
            self::Dumbbell => 'Halter / Gym',
            self::Power => 'Aan/Uit Knop',
            self::Grid => 'Submenu / 4 Vakken',
            self::Waves => 'Golven / Jacuzzi & Spa',
            self::Fire => 'Warmte / Sauna & Vlam',
            self::Thermometer => 'Thermometer / Klimaat',
            self::Lightbulb => 'Verlichting / Lamp',
            self::Blinds => 'Zonwering / Rolluiken',
            self::Droplet => 'Water / Beregening',
            self::Fan => 'Ventilatie / Koeling',
            self::Home => 'Huis / Kamer',
            self::Sun => 'Zon / Dag',
            self::Moon => 'Maan / Nacht',
            self::Lock => 'Slot / Beveiliging',
            self::LockOpen => 'Poort / Ontgrendelen',
            self::Bolt => 'Bliksem / Energie',
            self::Sparkles => 'Scène / Sfeer',
            self::ChevronUp => 'Omhoog / Openen',
            self::ChevronDown => 'Omlaag / Sluiten',
            self::Stop => 'Stop',
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
