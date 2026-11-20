<?php

namespace App\Models;

use App\Enums\KioskModule;
use App\Enums\MenuBlockType;
use Database\Factories\KioskMenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['title', 'is_root', 'wall_panel_id', 'grid_size'])]
class KioskMenu extends Model
{
    /** @use HasFactory<KioskMenuFactory> */
    use HasFactory;

    public const POSITIONS = [1, 2, 3, 4];

    /**
     * @return BelongsTo<WallPanel, $this>
     */
    public function wallPanel(): BelongsTo
    {
        return $this->belongsTo(WallPanel::class);
    }

    /**
     * @return array<int, int>
     */
    public function positions(): array
    {
        $size = max(4, (int) ($this->grid_size ?: 4));

        return range(1, $size);
    }

    /**
     * @return HasMany<KioskMenuBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(KioskMenuBlock::class)->orderBy('position');
    }

    /**
     * @return HasOne<KioskMenuBlock, $this>
     */
    public function parentBlock(): HasOne
    {
        return $this->hasOne(KioskMenuBlock::class, 'submenu_id');
    }

    public function parentMenu(): ?self
    {
        return $this->parentBlock?->menu;
    }

    public static function ensureRootExists(): self
    {
        $root = static::query()
            ->whereNull('wall_panel_id')
            ->where('is_root', true)
            ->first();

        if ($root === null) {
            $root = static::query()->create([
                'title' => 'Hoofdmenu',
                'is_root' => true,
                'wall_panel_id' => null,
                'grid_size' => 4,
            ]);

            $root->blocks()->createMany([
                [
                    'position' => 1,
                    'type' => MenuBlockType::Module,
                    'module_key' => KioskModule::Alarm,
                    'label' => KioskModule::Alarm->defaultButtonLabel(),
                    'icon' => KioskModule::Alarm->defaultIcon(),
                    'color' => KioskModule::Alarm->defaultColor(),
                ],
                [
                    'position' => 2,
                    'type' => MenuBlockType::Module,
                    'module_key' => KioskModule::Gym,
                    'label' => KioskModule::Gym->defaultButtonLabel(),
                    'icon' => KioskModule::Gym->defaultIcon(),
                    'color' => KioskModule::Gym->defaultColor(),
                ],
                [
                    'position' => 3,
                    'type' => MenuBlockType::Module,
                    'module_key' => KioskModule::AllOff,
                    'label' => KioskModule::AllOff->defaultButtonLabel(),
                    'icon' => KioskModule::AllOff->defaultIcon(),
                    'color' => KioskModule::AllOff->defaultColor(),
                ],
                [
                    'position' => 4,
                    'type' => MenuBlockType::Empty,
                ],
            ]);
        } else {
            $root->ensureBlocks();
        }

        return $root->load('blocks.submenu.blocks');
    }

    public static function resetToDefault(): self
    {
        static::query()->whereNull('wall_panel_id')->delete();

        return static::ensureRootExists();
    }

    public function ensureBlocks(): void
    {
        $existingPositions = $this->blocks()->pluck('position')->all();

        foreach ($this->positions() as $position) {
            if (! in_array($position, $existingPositions, true)) {
                $this->blocks()->create([
                    'position' => $position,
                    'type' => MenuBlockType::Empty,
                ]);
            }
        }
    }

    public function ensureFourBlocks(): void
    {
        $this->ensureBlocks();
    }

    public static function createEmptySubmenu(string $title = 'Submenu', ?int $wallPanelId = null, int $gridSize = 4): self
    {
        $resolvedGridSize = max(4, $gridSize);

        $submenu = static::query()->create([
            'title' => $title !== '' ? $title : 'Submenu',
            'is_root' => false,
            'wall_panel_id' => $wallPanelId,
            'grid_size' => $resolvedGridSize,
        ]);

        foreach (range(1, $resolvedGridSize) as $position) {
            $submenu->blocks()->create([
                'position' => $position,
                'type' => MenuBlockType::Empty,
            ]);
        }

        return $submenu;
    }

    public function deleteSubtree(): void
    {
        $visited = [];
        $this->deleteSubtreeRecursive($visited);
    }

    /**
     * @param  array<int, bool>  $visited
     */
    protected function deleteSubtreeRecursive(array &$visited): void
    {
        if (isset($visited[$this->id]) || $this->is_root) {
            return;
        }

        $visited[$this->id] = true;

        foreach ($this->blocks()->with('submenu')->get() as $block) {
            if ($block->submenu !== null) {
                $block->submenu->deleteSubtreeRecursive($visited);
            }
        }

        $this->delete();
    }

    /**
     * @return array<int, array{id: int, title: string, is_root: bool}>
     */
    public function breadcrumbs(): array
    {
        $trail = [];
        $current = $this;
        $visited = [];

        while ($current !== null && ! isset($visited[$current->id])) {
            $visited[$current->id] = true;
            array_unshift($trail, [
                'id' => $current->id,
                'title' => $current->title,
                'is_root' => (bool) $current->is_root,
            ]);
            $current = $current->parentMenu();
        }

        return $trail;
    }

    public function filledBlocksCount(): int
    {
        return $this->blocks->filter(fn (KioskMenuBlock $b): bool => $b->type !== MenuBlockType::Empty)->count();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_root' => 'boolean',
            'wall_panel_id' => 'integer',
            'grid_size' => 'integer',
        ];
    }
}
