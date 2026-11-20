<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum KioskModule: string implements HasColor, HasIcon, HasLabel
{
    case Alarm = 'alarm';
    case Cameras = 'cameras';
    case Jacuzzi = 'jacuzzi';
    case Sauna = 'sauna';
    case Gym = 'gym';
    case Lighting = 'lighting';
    case Sunscreens = 'sunscreens';
    case Climate = 'climate';
    case Garden = 'garden';
    case AllOff = 'all_off';

    public function getLabel(): string
    {
        return match ($this) {
            self::Alarm => 'Alarm (Heimdall)',
            self::Cameras => 'Reolink Camera\'s & Intercom',
            self::Jacuzzi => 'Jacuzzi & Spa',
            self::Sauna => 'Sauna & Wellness',
            self::Gym => 'Gym (Aan/Uit Schakelaar)',
            self::Lighting => 'Verlichting & Scènes',
            self::Sunscreens => 'Zonwering & Rolluiken',
            self::Climate => 'Klimaat & Verwarming',
            self::Garden => 'Tuin, Poort & Beregening',
            self::AllOff => 'Alles Uit / Flow Actie',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Alarm => 'Beslaat het volledige beveiligingsmenu: modus kiezen (Volledig / Gedeeltelijk), controle op open ramen & deuren, PIN-invoer en aftelscherm.',
            self::Cameras => 'Live weergave van de Reolink deurbel en 4 tuincamera\'s (WebRTC via go2rtc of stream/snapshot) inclusief twee-weg audio om terug te spreken.',
            self::Jacuzzi => 'Volledig 2x2 wellness-menu voor verwarmen, jets & bubbels, sfeerverlichting en eco/uit-stand met optionele temperatuur-badge.',
            self::Sauna => 'Volledig 2x2 saunamenu met voorverwarmen (incl. optionele veiligheidsbevestiging), relax/infrarood-stand, saunalicht en uitschakelen.',
            self::Gym => 'Volledig 2x2 keuzemenu met eigen AAN- en UIT-tegels gekoppeld aan Homey Flows.',
            self::Lighting => 'Volledig 2x2 lichtscène-menu om direct te schakelen tussen helder werklicht, avondsfeer, gedimd licht en uitschakelen.',
            self::Sunscreens => 'Volledig 2x2 bedieningspaneel voor screens, rolluiken en gordijnen: omhoog, omlaag, 50% zonstand en stop.',
            self::Climate => 'Volledig 2x2 klimaatmenu voor comforttemperatuur, eco/nachtstand, snelle warmte-boost en ventilatie.',
            self::Garden => 'Volledig 2x2 buitenmenu voor het openen van de poort/garage, tuinverlichting, beregening en alles buiten uit.',
            self::AllOff => 'Volledig actiescherm om alle verlichting of een gekoppelde Homey Flow (met optionele bevestiging) uit te schakelen.',
        };
    }

    public function defaultButtonLabel(): string
    {
        return match ($this) {
            self::Alarm => 'ALARM',
            self::Cameras => 'CAMERA\'S',
            self::Jacuzzi => 'JACUZZI',
            self::Sauna => 'SAUNA',
            self::Gym => 'GYM',
            self::Lighting => 'LICHTSCÈNES',
            self::Sunscreens => 'ZONWERING',
            self::Climate => 'KLIMAAT',
            self::Garden => 'TUIN & POORT',
            self::AllOff => 'ALLES UIT',
        };
    }

    public function defaultIcon(): MenuBlockIcon
    {
        return match ($this) {
            self::Alarm => MenuBlockIcon::ShieldCheck,
            self::Cameras => MenuBlockIcon::VideoCamera,
            self::Jacuzzi => MenuBlockIcon::Waves,
            self::Sauna => MenuBlockIcon::Fire,
            self::Gym => MenuBlockIcon::Dumbbell,
            self::Lighting => MenuBlockIcon::Lightbulb,
            self::Sunscreens => MenuBlockIcon::Blinds,
            self::Climate => MenuBlockIcon::Thermometer,
            self::Garden => MenuBlockIcon::Droplet,
            self::AllOff => MenuBlockIcon::Power,
        };
    }

    public function defaultColor(): MenuBlockColor
    {
        return match ($this) {
            self::Alarm => MenuBlockColor::Flashy,
            self::Cameras => MenuBlockColor::Sky,
            self::Jacuzzi => MenuBlockColor::Sky,
            self::Sauna => MenuBlockColor::Amber,
            self::Gym => MenuBlockColor::Amber,
            self::Lighting => MenuBlockColor::Violet,
            self::Sunscreens => MenuBlockColor::Sky,
            self::Climate => MenuBlockColor::Emerald,
            self::Garden => MenuBlockColor::Emerald,
            self::AllOff => MenuBlockColor::Rose,
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Alarm => 'primary',
            self::Cameras, self::Jacuzzi, self::Sunscreens => 'info',
            self::Sauna, self::Gym => 'warning',
            self::Lighting => 'primary',
            self::Climate, self::Garden => 'success',
            self::AllOff => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Alarm => Heroicon::OutlinedShieldCheck,
            self::Cameras => Heroicon::OutlinedVideoCamera,
            self::Jacuzzi => Heroicon::OutlinedSparkles,
            self::Sauna => Heroicon::OutlinedFire,
            self::Gym => Heroicon::OutlinedBolt,
            self::Lighting => Heroicon::OutlinedLightBulb,
            self::Sunscreens => Heroicon::OutlinedSun,
            self::Climate => Heroicon::OutlinedHomeModern,
            self::Garden => Heroicon::OutlinedKey,
            self::AllOff => Heroicon::OutlinedPower,
        };
    }

    public function isPresetGridModule(): bool
    {
        return in_array($this, self::presetGridModules(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function presetGridModules(): array
    {
        return [
            self::Jacuzzi,
            self::Sauna,
            self::Lighting,
            self::Sunscreens,
            self::Climate,
            self::Garden,
        ];
    }

    /**
     * @return array{
     *     screenTitle: string,
     *     badge: string,
     *     badgeHint: string,
     *     confirmFirstSlot: bool,
     *     confirmFirstSlotLabel: string,
     *     autoReturn: bool,
     *     slots: array<int, array{slotLabel: string, defaultLabel: string, icon: MenuBlockIcon, color: MenuBlockColor, allowLightsOff?: bool}>
     * }
     */
    public function defaultPresetConfig(): array
    {
        return match ($this) {
            self::Jacuzzi => [
                'screenTitle' => 'JACUZZI & SPA',
                'badge' => '38°C',
                'badgeHint' => 'Optionele streeftemperatuur-indicatie rechtsboven op het Kiosk-scherm (laat leeg om te verbergen).',
                'confirmFirstSlot' => false,
                'confirmFirstSlotLabel' => 'Vraag bevestiging vóór verwarmen (tegel 1)',
                'autoReturn' => true,
                'slots' => [
                    1 => ['slotLabel' => 'Tegel 1 — Verwarmen / Aan', 'defaultLabel' => 'VERWARMEN', 'icon' => MenuBlockIcon::Fire, 'color' => MenuBlockColor::Emerald],
                    2 => ['slotLabel' => 'Tegel 2 — Jets & Bubbels', 'defaultLabel' => 'JETS & BUBBELS', 'icon' => MenuBlockIcon::Waves, 'color' => MenuBlockColor::Sky],
                    3 => ['slotLabel' => 'Tegel 3 — Sfeerverlichting', 'defaultLabel' => 'SFEERLICHT', 'icon' => MenuBlockIcon::Sparkles, 'color' => MenuBlockColor::Violet],
                    4 => ['slotLabel' => 'Tegel 4 — Eco / Uit', 'defaultLabel' => 'ECO / UIT', 'icon' => MenuBlockIcon::Power, 'color' => MenuBlockColor::Rose],
                ],
            ],
            self::Sauna => [
                'screenTitle' => 'SAUNA & WELLNESS',
                'badge' => '85°C',
                'badgeHint' => 'Optionele doeltemperatuur-indicatie rechtsboven op het Kiosk-scherm (laat leeg om te verbergen).',
                'confirmFirstSlot' => true,
                'confirmFirstSlotLabel' => 'Veiligheidsbevestiging vragen vóór voorverwarmen (tegel 1)',
                'autoReturn' => true,
                'slots' => [
                    1 => ['slotLabel' => 'Tegel 1 — Voorverwarmen', 'defaultLabel' => 'VOORVERWARMEN', 'icon' => MenuBlockIcon::Fire, 'color' => MenuBlockColor::Amber],
                    2 => ['slotLabel' => 'Tegel 2 — Infrarood / Relax', 'defaultLabel' => 'INFRAROOD', 'icon' => MenuBlockIcon::Sun, 'color' => MenuBlockColor::Flashy],
                    3 => ['slotLabel' => 'Tegel 3 — Saunalicht', 'defaultLabel' => 'SAUNALICHT', 'icon' => MenuBlockIcon::Lightbulb, 'color' => MenuBlockColor::Violet],
                    4 => ['slotLabel' => 'Tegel 4 — Sauna Uit', 'defaultLabel' => 'SAUNA UIT', 'icon' => MenuBlockIcon::Power, 'color' => MenuBlockColor::Rose],
                ],
            ],
            self::Lighting => [
                'screenTitle' => 'VERLICHTING & SCÈNES',
                'badge' => '',
                'badgeHint' => 'Optionele ruimte- of zone-indicatie rechtsboven (bijv. Woonkamer).',
                'confirmFirstSlot' => false,
                'confirmFirstSlotLabel' => 'Vraag bevestiging bij tegel 1',
                'autoReturn' => true,
                'slots' => [
                    1 => ['slotLabel' => 'Tegel 1 — Helder / Daglicht', 'defaultLabel' => 'HELDER', 'icon' => MenuBlockIcon::Sun, 'color' => MenuBlockColor::Amber],
                    2 => ['slotLabel' => 'Tegel 2 — Avondsfeer', 'defaultLabel' => 'AVONDSFEER', 'icon' => MenuBlockIcon::Sparkles, 'color' => MenuBlockColor::Flashy],
                    3 => ['slotLabel' => 'Tegel 3 — Gedimd / Film', 'defaultLabel' => 'GEDIMD', 'icon' => MenuBlockIcon::Moon, 'color' => MenuBlockColor::Violet],
                    4 => ['slotLabel' => 'Tegel 4 — Verlichting Uit', 'defaultLabel' => 'LICHTEN UIT', 'icon' => MenuBlockIcon::Power, 'color' => MenuBlockColor::Rose, 'allowLightsOff' => true],
                ],
            ],
            self::Sunscreens => [
                'screenTitle' => 'ZONWERING & ROLLUIKEN',
                'badge' => '',
                'badgeHint' => 'Optionele zone-indicatie rechtsboven (bijv. Zuidzijde).',
                'confirmFirstSlot' => false,
                'confirmFirstSlotLabel' => 'Vraag bevestiging bij tegel 1',
                'autoReturn' => false,
                'slots' => [
                    1 => ['slotLabel' => 'Tegel 1 — Alles Omhoog / Open', 'defaultLabel' => 'OMHOOG', 'icon' => MenuBlockIcon::ChevronUp, 'color' => MenuBlockColor::Emerald],
                    2 => ['slotLabel' => 'Tegel 2 — Alles Omlaag / Dicht', 'defaultLabel' => 'OMLAAG', 'icon' => MenuBlockIcon::ChevronDown, 'color' => MenuBlockColor::Sky],
                    3 => ['slotLabel' => 'Tegel 3 — Zonstand (50%)', 'defaultLabel' => 'ZONSTAND', 'icon' => MenuBlockIcon::Sun, 'color' => MenuBlockColor::Amber],
                    4 => ['slotLabel' => 'Tegel 4 — Stop beweging', 'defaultLabel' => 'STOP', 'icon' => MenuBlockIcon::Stop, 'color' => MenuBlockColor::Rose],
                ],
            ],
            self::Climate => [
                'screenTitle' => 'KLIMAAT & COMFORT',
                'badge' => '21°C',
                'badgeHint' => 'Optionele comforttemperatuur-badge rechtsboven op het scherm.',
                'confirmFirstSlot' => false,
                'confirmFirstSlotLabel' => 'Vraag bevestiging bij tegel 1',
                'autoReturn' => true,
                'slots' => [
                    1 => ['slotLabel' => 'Tegel 1 — Comfort / Thuis', 'defaultLabel' => 'COMFORT', 'icon' => MenuBlockIcon::Thermometer, 'color' => MenuBlockColor::Emerald],
                    2 => ['slotLabel' => 'Tegel 2 — Eco / Nachtstand', 'defaultLabel' => 'ECO / NACHT', 'icon' => MenuBlockIcon::Moon, 'color' => MenuBlockColor::Sky],
                    3 => ['slotLabel' => 'Tegel 3 — Snel Verwarmen (Boost)', 'defaultLabel' => 'BOOST', 'icon' => MenuBlockIcon::Fire, 'color' => MenuBlockColor::Amber],
                    4 => ['slotLabel' => 'Tegel 4 — Ventilatie / Koeling', 'defaultLabel' => 'VENTILATIE', 'icon' => MenuBlockIcon::Fan, 'color' => MenuBlockColor::Flashy],
                ],
            ],
            default => [
                'screenTitle' => 'TUIN & POORT',
                'badge' => '',
                'badgeHint' => 'Optionele buiten-indicatie rechtsboven (bijv. Achtertuin).',
                'confirmFirstSlot' => true,
                'confirmFirstSlotLabel' => 'Vraag bevestiging vóór poort/garage openen (tegel 1)',
                'autoReturn' => true,
                'slots' => [
                    1 => ['slotLabel' => 'Tegel 1 — Poort / Garage Openen', 'defaultLabel' => 'POORT OPEN', 'icon' => MenuBlockIcon::LockOpen, 'color' => MenuBlockColor::Flashy],
                    2 => ['slotLabel' => 'Tegel 2 — Tuinverlichting Aan', 'defaultLabel' => 'TUINLICHT', 'icon' => MenuBlockIcon::Lightbulb, 'color' => MenuBlockColor::Amber],
                    3 => ['slotLabel' => 'Tegel 3 — Beregening Starten', 'defaultLabel' => 'BEREGENING', 'icon' => MenuBlockIcon::Droplet, 'color' => MenuBlockColor::Sky],
                    4 => ['slotLabel' => 'Tegel 4 — Tuin Alles Uit', 'defaultLabel' => 'TUIN UIT', 'icon' => MenuBlockIcon::Power, 'color' => MenuBlockColor::Rose],
                ],
            ],
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
