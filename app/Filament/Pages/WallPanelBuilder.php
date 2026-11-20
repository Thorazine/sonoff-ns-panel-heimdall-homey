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
use App\Models\WallPanel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WallPanelBuilder extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Grote Schermen';

    protected static ?string $title = 'Grote Android Schermen — Menu Builder (8 Blocks)';

    protected static ?string $slug = 'wall-panels';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.wall-panel-builder';

    public const POSITIONS = [1, 2, 3, 4, 5, 6, 7, 8];

    public ?int $activePanelId = null;

    public string $panelName = '';

    public string $panelLocation = '';

    public bool $panelShowDoorbellPopup = true;

    public bool $panelIsActive = true;

    public bool $showCreatePanelForm = false;

    public string $newPanelName = '';

    public string $newPanelLocation = '';

    public ?int $currentMenuId = null;

    public string $menuTitle = '';

    public int $selectedPosition = 1;

    public string $blockType = 'empty';

    public ?string $blockModuleKey = null;

    public string $blockLabel = '';

    public string $blockIcon = 'grid';

    public string $blockColor = 'sky';

    public function mount(): void
    {
        $panels = WallPanel::ensureDefaultsExist();
        /** @var WallPanel $firstPanel */
        $firstPanel = $panels->first();

        $this->activePanelId = $firstPanel->id;
        $this->syncPanelForm($firstPanel);

        $root = $firstPanel->ensureRootMenuExists();
        $this->currentMenuId = $root->id;
        $this->menuTitle = $root->title;
        $this->selectedPosition = 1;
        $this->loadSelectedBlock();
    }

    /**
     * @return Collection<int, WallPanel>
     */
    public function getPanelsProperty(): Collection
    {
        return WallPanel::ensureDefaultsExist();
    }

    public function getActivePanelProperty(): WallPanel
    {
        $panels = $this->panels;

        if ($this->activePanelId !== null) {
            /** @var WallPanel|null $found */
            $found = $panels->firstWhere('id', $this->activePanelId);
            if ($found !== null) {
                return $found;
            }
        }

        /** @var WallPanel $fallback */
        $fallback = $panels->first();
        $this->activePanelId = $fallback->id;

        return $fallback;
    }

    protected function syncPanelForm(WallPanel $panel): void
    {
        $this->panelName = $panel->name;
        $this->panelLocation = (string) ($panel->location ?? '');
        $this->panelShowDoorbellPopup = (bool) $panel->show_doorbell_popup;
        $this->panelIsActive = (bool) $panel->is_active;
    }

    public function selectPanel(int $panelId): void
    {
        $panel = WallPanel::query()->find($panelId);
        if ($panel === null) {
            return;
        }

        $this->activePanelId = $panel->id;
        unset($this->activePanel, $this->panels, $this->currentMenu);
        $this->syncPanelForm($panel);

        $root = $panel->ensureRootMenuExists();
        $this->currentMenuId = $root->id;
        $this->menuTitle = $root->title;
        $this->selectedPosition = 1;
        $this->resetValidation();
        $this->loadSelectedBlock();
    }

    public function toggleCreatePanelForm(): void
    {
        $this->showCreatePanelForm = ! $this->showCreatePanelForm;
        $this->newPanelName = '';
        $this->newPanelLocation = '';
    }

    public function createPanel(): WallPanel
    {
        $this->validate([
            'newPanelName' => ['required', 'string', 'max:60'],
            'newPanelLocation' => ['nullable', 'string', 'max:80'],
        ], [
            'newPanelName.required' => 'Geef een naam op voor het nieuwe grote scherm (bijv. Zolder of Keuken).',
        ]);

        $cleanName = trim($this->newPanelName);
        $cleanLocation = trim($this->newPanelLocation);

        $panel = WallPanel::query()->create([
            'name' => $cleanName,
            'slug' => WallPanel::uniqueSlug($cleanName),
            'location' => $cleanLocation !== '' ? $cleanLocation : null,
            'show_doorbell_popup' => true,
            'is_active' => true,
        ]);

        $panel->ensureRootMenuExists();

        $this->showCreatePanelForm = false;
        $this->newPanelName = '';
        $this->newPanelLocation = '';

        ActivityLog::record(LogLevel::Info, "Groot scherm aangemaakt: {$panel->name} (/panel/{$panel->slug})");

        $this->selectPanel($panel->id);

        Notification::make()
            ->title("Scherm '{$panel->name}' aangemaakt")
            ->success()
            ->send();

        return $panel;
    }

    public function savePanelSettings(): void
    {
        $this->validate([
            'panelName' => ['required', 'string', 'max:60'],
            'panelLocation' => ['nullable', 'string', 'max:80'],
        ], [
            'panelName.required' => 'De schermnaam mag niet leeg zijn.',
        ]);

        $panel = $this->activePanel;
        $cleanName = trim($this->panelName);
        $cleanLocation = trim($this->panelLocation);

        $panel->update([
            'name' => $cleanName,
            'location' => $cleanLocation !== '' ? $cleanLocation : null,
            'show_doorbell_popup' => $this->panelShowDoorbellPopup,
            'is_active' => $this->panelIsActive,
        ]);

        $root = $panel->ensureRootMenuExists();
        $root->update(['title' => "Hoofdmenu {$cleanName}"]);

        unset($this->activePanel, $this->panels, $this->currentMenu);
        $this->loadSelectedBlock();

        ActivityLog::record(LogLevel::Info, "Instellingen groot scherm bijgewerkt: {$cleanName}");

        Notification::make()
            ->title("Scherminstellingen voor '{$cleanName}' opgeslagen")
            ->success()
            ->send();
    }

    public function deleteActivePanel(): void
    {
        if (WallPanel::query()->count() <= 1) {
            Notification::make()
                ->title('Er moet minimaal één groot scherm overblijven')
                ->warning()
                ->send();

            return;
        }

        $panel = $this->activePanel;
        $deletedName = $panel->name;
        $panel->delete();

        unset($this->activePanel, $this->panels, $this->currentMenu);
        $next = WallPanel::ensureDefaultsExist()->first();
        if ($next !== null) {
            $this->selectPanel($next->id);
        }

        ActivityLog::record(LogLevel::Warning, "Groot scherm verwijderd: {$deletedName}");

        Notification::make()
            ->title("Scherm '{$deletedName}' verwijderd")
            ->success()
            ->send();
    }

    public function getCurrentMenuProperty(): KioskMenu
    {
        $panel = $this->activePanel;
        $root = $panel->ensureRootMenuExists();

        if ($this->currentMenuId === null) {
            $this->currentMenuId = $root->id;

            return $root;
        }

        $found = KioskMenu::query()
            ->where('wall_panel_id', $panel->id)
            ->with(['blocks.submenu.blocks', 'parentBlock.menu'])
            ->find($this->currentMenuId);

        if ($found === null) {
            $this->currentMenuId = $root->id;

            return $root;
        }

        $found->ensureBlocks();

        return $found->load(['blocks.submenu.blocks', 'parentBlock.menu']);
    }

    public function openMenu(int $menuId): void
    {
        $target = KioskMenu::query()
            ->where('wall_panel_id', $this->activePanel->id)
            ->find($menuId);

        if ($target === null) {
            return;
        }

        $target->ensureBlocks();
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
        if (! in_array($position, self::POSITIONS, true)) {
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
                $this->blockModuleKey = KioskModule::Cameras->value;
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
            'selectedPosition' => ['required', 'integer', Rule::in(self::POSITIONS)],
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

        $panel = $this->activePanel;
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
                $submenu = KioskMenu::createEmptySubmenu(
                    title: $cleanLabel,
                    wallPanelId: $panel->id,
                    gridSize: WallPanel::GRID_SIZE,
                );
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

        ActivityLog::record(LogLevel::Info, "Grote Schermen Builder ({$panel->name}): {$menu->title} vak {$this->selectedPosition} opgeslagen ({$newType->getLabel()})");

        Notification::make()
            ->title("Vak {$this->selectedPosition} op '{$panel->name}' opgeslagen")
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
        if (! in_array($targetPosition, self::POSITIONS, true) || $targetPosition === $this->selectedPosition) {
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
        $root = $this->activePanel->ensureRootMenuExists();
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
            Action::make('openCameras')
                ->label('Camera\'s & Deurbel')
                ->color('info')
                ->icon(Heroicon::OutlinedVideoCamera)
                ->url(fn (): string => ManageCameras::getUrl()),

            Action::make('openModules')
                ->label('Modules & Opties')
                ->color('gray')
                ->icon(Heroicon::OutlinedCube)
                ->url(fn (): string => ModulesOverview::getUrl()),

            Action::make('resetPanelMenu')
                ->label('Standaardmenu dit scherm herstellen')
                ->color('danger')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalHeading('Standaard 8-blocks menu herstellen?')
                ->modalDescription('Hiermee worden alleen de aangepaste menu\'s van het momenteel geselecteerde grote scherm teruggezet naar de standaardindeling.')
                ->action(function (): void {
                    $panel = $this->activePanel;
                    $panel->menus()->delete();
                    $root = $panel->ensureRootMenuExists();

                    $this->currentMenuId = $root->id;
                    $this->selectedPosition = 1;
                    unset($this->currentMenu);
                    $this->loadSelectedBlock();

                    ActivityLog::record(LogLevel::Warning, "Standaardmenu hersteld voor groot scherm {$panel->name}");

                    Notification::make()
                        ->title("Standaardmenu voor '{$panel->name}' hersteld")
                        ->success()
                        ->send();
                }),
        ];
    }
}
