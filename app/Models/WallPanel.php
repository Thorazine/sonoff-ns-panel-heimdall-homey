<?php

namespace App\Models;

use App\Enums\KioskModule;
use App\Enums\MenuBlockType;
use Database\Factories\WallPanelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'location', 'show_doorbell_popup', 'is_active'])]
class WallPanel extends Model
{
    /** @use HasFactory<WallPanelFactory> */
    use HasFactory;

    public const GRID_SIZE = 8;

    /**
     * @return HasMany<KioskMenu, $this>
     */
    public function menus(): HasMany
    {
        return $this->hasMany(KioskMenu::class);
    }

    /**
     * @return HasOne<KioskMenu, $this>
     */
    public function rootMenu(): HasOne
    {
        return $this->hasOne(KioskMenu::class)->where('is_root', true);
    }

    public function ensureRootMenuExists(): KioskMenu
    {
        $root = $this->menus()->where('is_root', true)->first();

        if ($root === null) {
            $root = $this->menus()->create([
                'title' => "Hoofdmenu {$this->name}",
                'is_root' => true,
                'grid_size' => self::GRID_SIZE,
            ]);

            $defaultModules = $this->slug === 'zolder'
                ? [
                    1 => KioskModule::Cameras,
                    2 => KioskModule::Alarm,
                    3 => KioskModule::Gym,
                    4 => KioskModule::Climate,
                    5 => KioskModule::Lighting,
                    6 => KioskModule::Sunscreens,
                    7 => KioskModule::AllOff,
                ]
                : [
                    1 => KioskModule::Cameras,
                    2 => KioskModule::Alarm,
                    3 => KioskModule::Lighting,
                    4 => KioskModule::Climate,
                    5 => KioskModule::Sunscreens,
                    6 => KioskModule::Jacuzzi,
                    7 => KioskModule::Sauna,
                    8 => KioskModule::AllOff,
                ];

            for ($pos = 1; $pos <= self::GRID_SIZE; $pos++) {
                $module = $defaultModules[$pos] ?? null;
                if ($module instanceof KioskModule) {
                    $root->blocks()->create([
                        'position' => $pos,
                        'type' => MenuBlockType::Module,
                        'module_key' => $module,
                        'label' => $module->defaultButtonLabel(),
                        'icon' => $module->defaultIcon(),
                        'color' => $module->defaultColor(),
                    ]);
                } else {
                    $root->blocks()->create([
                        'position' => $pos,
                        'type' => MenuBlockType::Empty,
                    ]);
                }
            }
        } else {
            if ((int) $root->grid_size !== self::GRID_SIZE) {
                $root->update(['grid_size' => self::GRID_SIZE]);
            }
            $root->ensureFourBlocks();
        }

        return $root->load('blocks.submenu.blocks');
    }

    /**
     * @return Collection<int, self>
     */
    public static function ensureDefaultsExist(): Collection
    {
        if (! static::query()->exists()) {
            $firstFloor = static::query()->create([
                'name' => 'Eerste Verdieping',
                'slug' => 'eerste-verdieping',
                'location' => 'Overloop 1e verdieping',
                'show_doorbell_popup' => true,
                'is_active' => true,
            ]);
            $firstFloor->ensureRootMenuExists();

            $attic = static::query()->create([
                'name' => 'Zolder',
                'slug' => 'zolder',
                'location' => 'Zolderverdieping',
                'show_doorbell_popup' => true,
                'is_active' => true,
            ]);
            $attic->ensureRootMenuExists();
        }

        return static::query()->orderBy('id')->get();
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'paneel';
        }

        $candidate = $base;
        $counter = 2;

        while (
            static::query()
                ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_doorbell_popup' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
