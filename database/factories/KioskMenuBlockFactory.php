<?php

namespace Database\Factories;

use App\Enums\KioskModule;
use App\Enums\MenuBlockColor;
use App\Enums\MenuBlockIcon;
use App\Enums\MenuBlockType;
use App\Models\KioskMenu;
use App\Models\KioskMenuBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KioskMenuBlock>
 */
class KioskMenuBlockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kiosk_menu_id' => KioskMenu::factory(),
            'position' => 1,
            'type' => MenuBlockType::Empty,
            'label' => null,
            'icon' => null,
            'color' => null,
            'module_key' => null,
            'submenu_id' => null,
        ];
    }

    public function forModule(KioskModule $module, ?string $label = null): static
    {
        return $this->state(fn (): array => [
            'type' => MenuBlockType::Module,
            'module_key' => $module,
            'label' => $label ?? $module->defaultButtonLabel(),
            'icon' => $module->defaultIcon(),
            'color' => $module->defaultColor(),
        ]);
    }

    public function forSubmenu(KioskMenu $submenu, ?string $label = null): static
    {
        return $this->state(fn (): array => [
            'type' => MenuBlockType::Submenu,
            'submenu_id' => $submenu->id,
            'label' => $label ?? strtoupper($submenu->title),
            'icon' => MenuBlockIcon::Grid,
            'color' => MenuBlockColor::Sky,
        ]);
    }
}
