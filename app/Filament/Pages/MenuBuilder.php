<?php

namespace App\Filament\Pages;

use App\Enums\KioskModule;
use App\Enums\LogLevel;
use App\Enums\MenuBlockColor;
use App\Enums\MenuBlockIcon;
use App\Enums\MenuBlockType;
use App\Models\ActivityLog;
use App\Models\KioskMenu;
use App\Models\KioskMenuBlock;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MenuBuilder extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Menu Builder';

    protected static ?string $title = 'Kiosk Menu Builder (4 Blocks)';

    protected static ?string $slug = 'menu-builder';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.menu-builder';

    public ?int $currentMenuId = null;

    public string $menuTitle = 'Hoofdmenu';

    public int $selectedPosition = 1;

    public string $blockType = 'empty';

    public ?string $blockModuleKey = null;

    public string $blockLabel = '';

    public string $blockIcon = 'grid';

    public string $blockColor = 'sky';

    public function mount(): void
    {
        $root = KioskMenu::ensureRootExists();
        $this->currentMenuId = $root->id;
        $this->menuTitle = $root->title;
        $this->selectedPosition = 1;
        $this->loadSelectedBlock();
    }

    public function getCurrentMenuProperty(): KioskMenu
    {
        $root = KioskMenu::ensureRootExists();

        if ($this->currentMenuId === null) {
            $this->currentMenuId = $root->id;

            return $root;
        }

        $found = KioskMenu::query()
            ->whereNull('wall_panel_id')
            ->with(['blocks.submenu.blocks', 'parentBlock.menu'])
            ->find($this->currentMenuId);

        if ($found === null) {
            $this->currentMenuId = $root->id;

            return $root;
        }

        $found->ensureFourBlocks();

        return $found->load(['blocks.submenu.blocks', 'parentBlock.menu']);
    }

    public function openMenu(int $menuId): void
    {
        $target = KioskMenu::query()
            ->whereNull('wall_panel_id')
            ->find($menuId);
        if ($target === null) {
            return;
        }

        $target->ensureFourBlocks();
        $this->currentMenuId = $target->id;
        $this->menuTitle = $target->title;
        $this->selectedPosition = 1;
        $this->loadSelectedBlock();
    }

    public function goUp(): void
    {
        $parent = $this->currentMenu->parentMenu();
        if ($parent !== null) {
            $this->openMenu($parent->id);
        }
    }

    public function selectBlock(int $position): void
    {
        if (! in_array($position, KioskMenu::POSITIONS, true)) {
            return;
        }

        $this->selectedPosition = $position;
        $this->resetValidation();
        $this->loadSelectedBlock();
    }

    public function loadSelectedBlock(): void
    {
        $menu = $this->currentMenu;
        $this->menuTitle = $menu->title;

        /** @var KioskMenuBlock|null $block */
        $block = $menu->blocks->firstWhere('position', $this->selectedPosition);

        if ($block === null) {
            $this->blockType = MenuBlockType::Empty->value;
            $this->blockModuleKey = null;
            $this->blockLabel = '';
            $this->blockIcon = MenuBlockIcon::Grid->value;
            $this->blockColor = MenuBlockColor::Sky->value;

            return;
        }

        $this->blockType = $block->type->value;
        $this->blockModuleKey = $block->module_key?->value;
        $this->blockLabel = (string) ($block->label ?? '');
        $this->blockIcon = $block->resolvedIcon()->value;
        $this->blockColor = $block->resolvedColor()->value;
    }

    public function updatedBlockType(string $value): void
    {
        if ($value === MenuBlockType::Module->value) {
            if ($this->blockModuleKey === null || KioskModule::tryFrom($this->blockModuleKey) === null) {
                $this->blockModuleKey = KioskModule::Alarm->value;
            }
            $this->applyModuleDefaultsIfAppropriate(KioskModule::from($this->blockModuleKey));
        } elseif ($value === MenuBlockType::Submenu->value) {
            if ($this->blockIcon === '' || $this->isModuleDefaultIcon($this->blockIcon)) {
                $this->blockIcon = MenuBlockIcon::Grid->value;
            }
            if ($this->blockColor === '' || $this->isModuleDefaultColor($this->blockColor)) {
                $this->blockColor = MenuBlockColor::Sky->value;
            }
            if ($this->isModuleDefaultLabel($this->blockLabel)) {
                $this->blockLabel = '';
            }
        }
    }

    public function updatedBlockModuleKey(?string $value): void
    {
        if ($value === null) {
            return;
        }

        $module = KioskModule::tryFrom($value);
        if ($module !== null) {
            $this->applyModuleDefaultsIfAppropriate($module);
        }
    }

    protected function applyModuleDefaultsIfAppropriate(KioskModule $module): void
    {
        if (trim($this->blockLabel) === '' || $this->isModuleDefaultLabel($this->blockLabel)) {
            $this->blockLabel = $module->defaultButtonLabel();
        }
        $this->blockIcon = $module->defaultIcon()->value;
        $this->blockColor = $module->defaultColor()->value;
    }

    protected function isModuleDefaultLabel(string $label): bool
    {
        $upper = strtoupper(trim($label));
        foreach (KioskModule::cases() as $case) {
            if ($upper === $case->defaultButtonLabel()) {
                return true;
            }
        }

        return false;
    }

    protected function isModuleDefaultIcon(string $icon): bool
    {
        foreach (KioskModule::cases() as $case) {
            if ($icon === $case->defaultIcon()->value) {
                return true;
            }
        }

        return false;
    }

    protected function isModuleDefaultColor(string $color): bool
    {
        foreach (KioskModule::cases() as $case) {
            if ($color === $case->defaultColor()->value) {
                return true;
            }
        }

        return false;
    }

    public function saveBlock(): ?KioskMenuBlock
    {
        $this->validate([
            'selectedPosition' => ['required', 'integer', Rule::in(KioskMenu::POSITIONS)],
            'blockType' => ['required', Rule::in(array_map(fn (MenuBlockType $t): string => $t->value, MenuBlockType::cases()))],
            'blockModuleKey' => [
                Rule::requiredIf(fn (): bool => $this->blockType === MenuBlockType::Module->value),
                'nullable',
                Rule::in(array_map(fn (KioskModule $m): string => $m->value, KioskModule::cases())),
            ],
            'blockLabel' => [
                Rule::requiredIf(fn (): bool => $this->blockType === MenuBlockType::Submenu->value),
                'nullable',
                'string',
                'max:40',
            ],
            'blockIcon' => ['required', Rule::in(array_map(fn (MenuBlockIcon $i): string => $i->value, MenuBlockIcon::cases()))],
            'blockColor' => ['required', Rule::in(array_map(fn (MenuBlockColor $c): string => $c->value, MenuBlockColor::cases()))],
        ], [
            'blockLabel.required' => 'Geef een naam op voor deze submenu-knop.',
            'blockModuleKey.required' => 'Kies welke module onder deze knop zit.',
        ]);

        $menu = $this->currentMenu;

        /** @var KioskMenuBlock|null $block */
        $block = $menu->blocks()->where('position', $this->selectedPosition)->first();
        if ($block === null) {
            return null;
        }

        $newType = MenuBlockType::from($this->blockType);
        $oldSubmenu = $block->submenu;

        if ($newType !== MenuBlockType::Submenu && $oldSubmenu !== null) {
            $block->update(['submenu_id' => null]);
            $oldSubmenu->deleteSubtree();
        }

        if ($newType === MenuBlockType::Empty) {
            $block->update([
                'type' => MenuBlockType::Empty,
                'label' => null,
                'icon' => null,
                'color' => null,
                'module_key' => null,
                'submenu_id' => null,
            ]);
        } elseif ($newType === MenuBlockType::Module) {
            $module = KioskModule::from((string) $this->blockModuleKey);
            $cleanLabel = trim($this->blockLabel) !== '' ? trim($this->blockLabel) : $module->defaultButtonLabel();

            $block->update([
                'type' => MenuBlockType::Module,
                'module_key' => $module,
                'label' => $cleanLabel,
                'icon' => MenuBlockIcon::from($this->blockIcon),
                'color' => MenuBlockColor::from($this->blockColor),
                'submenu_id' => null,
            ]);
        } elseif ($newType === MenuBlockType::Submenu) {
            $cleanLabel = trim($this->blockLabel) !== '' ? trim($this->blockLabel) : 'Submenu';

            if ($oldSubmenu !== null) {
                $oldSubmenu->update(['title' => $cleanLabel]);
                $submenu = $oldSubmenu;
            } else {
                $submenu = KioskMenu::createEmptySubmenu($cleanLabel);
            }

            $block->update([
                'type' => MenuBlockType::Submenu,
                'submenu_id' => $submenu->id,
                'module_key' => null,
                'label' => $cleanLabel,
                'icon' => MenuBlockIcon::from($this->blockIcon),
                'color' => MenuBlockColor::from($this->blockColor),
            ]);
        }

        unset($this->currentMenu);
        $this->loadSelectedBlock();

        ActivityLog::record(LogLevel::Info, "Menu Builder: {$menu->title} vak {$this->selectedPosition} opgeslagen ({$newType->getLabel()})");

        Notification::make()
            ->title("Vak {$this->selectedPosition} opgeslagen")
            ->success()
            ->send();

        return $block->fresh();
    }

    public function saveAndOpenSubmenu(): void
    {
        $this->blockType = MenuBlockType::Submenu->value;
        $saved = $this->saveBlock();

        if ($saved !== null && $saved->submenu_id !== null) {
            $this->openMenu($saved->submenu_id);
        }
    }

    public function clearSelectedBlock(): void
    {
        $this->blockType = MenuBlockType::Empty->value;
        $this->blockModuleKey = null;
        $this->blockLabel = '';
        $this->saveBlock();
    }

    public function swapWithPosition(int $targetPosition): void
    {
        if (! in_array($targetPosition, KioskMenu::POSITIONS, true) || $targetPosition === $this->selectedPosition) {
            return;
        }

        $menu = $this->currentMenu;

        /** @var KioskMenuBlock|null $source */
        $source = $menu->blocks()->where('position', $this->selectedPosition)->first();
        /** @var KioskMenuBlock|null $target */
        $target = $menu->blocks()->where('position', $targetPosition)->first();

        if ($source === null || $target === null) {
            return;
        }

        DB::transaction(function () use ($source, $target): void {
            $sourceAttrs = [
                'type' => $source->type,
                'label' => $source->label,
                'icon' => $source->icon,
                'color' => $source->color,
                'module_key' => $source->module_key,
                'submenu_id' => $source->submenu_id,
            ];

            $source->update([
                'type' => $target->type,
                'label' => $target->label,
                'icon' => $target->icon,
                'color' => $target->color,
                'module_key' => $target->module_key,
                'submenu_id' => $target->submenu_id,
            ]);

            $target->update($sourceAttrs);
        });

        unset($this->currentMenu);
        $this->selectedPosition = $targetPosition;
        $this->loadSelectedBlock();

        Notification::make()
            ->title("Vak gewisseld naar positie {$targetPosition}")
            ->success()
            ->send();
    }

    public function saveMenuTitle(): void
    {
        $menu = $this->currentMenu;
        if ($menu->is_root) {
            return;
        }

        $clean = trim($this->menuTitle);
        if ($clean === '') {
            return;
        }

        $menu->update(['title' => $clean]);
        if ($menu->parentBlock !== null) {
            $menu->parentBlock->update(['label' => $clean]);
        }

        unset($this->currentMenu);

        Notification::make()
            ->title('Submenu-naam bijgewerkt')
            ->success()
            ->send();
    }

    /**
     * @return array{id: int, title: string, is_root: bool, blocks: array<int, array<string, mixed>>}
     */
    public function getMenuTreeProperty(): array
    {
        $root = KioskMenu::ensureRootExists();
        $visited = [];

        return $this->buildTreeNode($root, $visited);
    }

    /**
     * @param  array<int, bool>  $visited
     * @return array{id: int, title: string, is_root: bool, blocks: array<int, array<string, mixed>>}
     */
    protected function buildTreeNode(KioskMenu $menu, array &$visited): array
    {
        $visited[$menu->id] = true;
        $menu->loadMissing('blocks.submenu');

        $blocks = [];
        foreach ($menu->blocks as $block) {
            $childTree = null;
            if ($block->type === MenuBlockType::Submenu && $block->submenu !== null && ! isset($visited[$block->submenu->id])) {
                $childTree = $this->buildTreeNode($block->submenu, $visited);
            }

            $blocks[] = [
                'position' => $block->position,
                'type' => $block->type->value,
                'label' => $block->resolvedLabel(),
                'module_label' => $block->module_key?->getLabel(),
                'submenu' => $childTree,
            ];
        }

        return [
            'id' => $menu->id,
            'title' => $menu->title,
            'is_root' => (bool) $menu->is_root,
            'blocks' => $blocks,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openModules')
                ->label('Modules & Opties')
                ->color('gray')
                ->icon(Heroicon::OutlinedCube)
                ->url(fn (): string => ModulesOverview::getUrl()),

            Action::make('resetMenu')
                ->label('Standaardmenu herstellen')
                ->color('danger')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalHeading('Standaardmenu herstellen?')
                ->modalDescription('Hiermee worden alle aangepaste submenu\'s verwijderd en wordt het hoofdmenu teruggezet naar Alarm, Gym en Alles Uit.')
                ->action(function (): void {
                    $root = KioskMenu::resetToDefault();
                    $this->currentMenuId = $root->id;
                    $this->selectedPosition = 1;
                    unset($this->currentMenu);
                    $this->loadSelectedBlock();

                    ActivityLog::record(LogLevel::Warning, 'Menu Builder teruggezet naar standaardmenu');

                    Notification::make()
                        ->title('Standaardmenu hersteld')
                        ->success()
                        ->send();
                }),
        ];
    }
}
