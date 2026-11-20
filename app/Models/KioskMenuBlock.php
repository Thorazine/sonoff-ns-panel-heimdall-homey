<?php

namespace App\Models;

use App\Enums\KioskModule;
use App\Enums\MenuBlockColor;
use App\Enums\MenuBlockIcon;
use App\Enums\MenuBlockType;
use Database\Factories\KioskMenuBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kiosk_menu_id', 'position', 'type', 'label', 'icon', 'color', 'module_key', 'submenu_id'])]
class KioskMenuBlock extends Model
{
    /** @use HasFactory<KioskMenuBlockFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<KioskMenu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(KioskMenu::class, 'kiosk_menu_id');
    }

    /**
     * @return BelongsTo<KioskMenu, $this>
     */
    public function submenu(): BelongsTo
    {
        return $this->belongsTo(KioskMenu::class, 'submenu_id');
    }

    public function positionLabel(): string
    {
        $gridSize = (int) ($this->menu?->grid_size ?? 4);

        if ($gridSize === 8) {
            $row = $this->position <= 4 ? 1 : 2;
            $col = (($this->position - 1) % 4) + 1;

            return "Vak {$this->position} (Rij {$row}, Kol {$col})";
        }

        return match ($this->position) {
            1 => 'Vak 1 (Linksboven)',
            2 => 'Vak 2 (Rechtsboven)',
            3 => 'Vak 3 (Linksonder)',
            4 => 'Vak 4 (Rechtsonder)',
            default => "Vak {$this->position}",
        };
    }

    public function resolvedLabel(): string
    {
        $custom = trim((string) ($this->label ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        if ($this->type === MenuBlockType::Module && $this->module_key instanceof KioskModule) {
            return $this->module_key->defaultButtonLabel();
        }

        if ($this->type === MenuBlockType::Submenu) {
            return $this->submenu?->title ?? 'SUBMENU';
        }

        return '';
    }

    public function resolvedIcon(): MenuBlockIcon
    {
        if ($this->icon instanceof MenuBlockIcon) {
            return $this->icon;
        }

        if ($this->type === MenuBlockType::Module && $this->module_key instanceof KioskModule) {
            return $this->module_key->defaultIcon();
        }

        return MenuBlockIcon::Grid;
    }

    public function resolvedColor(): MenuBlockColor
    {
        if ($this->color instanceof MenuBlockColor) {
            return $this->color;
        }

        if ($this->type === MenuBlockType::Module && $this->module_key instanceof KioskModule) {
            return $this->module_key->defaultColor();
        }

        return MenuBlockColor::Sky;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'type' => MenuBlockType::class,
            'icon' => MenuBlockIcon::class,
            'color' => MenuBlockColor::class,
            'module_key' => KioskModule::class,
        ];
    }
}
