<?php

namespace App\Livewire\Kiosk;

use App\Enums\KioskModule;
use App\Enums\MenuBlockType;
use App\Models\KioskMenu;
use App\Models\WallPanel;
use App\Services\HomeyService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.kiosk')]
#[Title('Menu | Heimdall Kiosk')]
class HubScreen extends Component
{
    #[Url]
    public ?int $menu = null;

    public ?string $error = null;

    public function activateBlock(int $blockId): void
    {
        $this->error = null;
        $currentMenu = $this->resolveCurrentMenu();
        $block = $currentMenu->blocks->firstWhere('id', $blockId);

        if ($block === null) {
            return;
        }

        if ($block->type === MenuBlockType::Submenu && $block->submenu_id !== null) {
            $this->menu = $block->submenu_id;

            return;
        }

        if ($block->type === MenuBlockType::Module && $block->module_key instanceof KioskModule) {
            $this->openModule($block->module_key, $currentMenu);
        }
    }

    public function openModule(KioskModule $module, ?KioskMenu $fromMenu = null): void
    {
        match ($module) {
            KioskModule::Alarm => $this->openAlarm($fromMenu),
            KioskModule::Gym => $this->redirect($this->moduleUrl('/gym', $fromMenu), navigate: true),
            KioskModule::AllOff => $this->redirect($this->moduleUrl('/all-off', $fromMenu), navigate: true),
            KioskModule::Cameras => $this->openCameras(),
            default => $this->redirect($this->moduleUrl('/module/'.$module->value, $fromMenu), navigate: true),
        };
    }

    protected function openCameras(): void
    {
        $panel = WallPanel::ensureDefaultsExist()->firstWhere('is_active', true)
            ?? WallPanel::query()->first();

        if ($panel !== null) {
            $this->redirect('/panel/'.$panel->slug.'?module=cameras', navigate: true);

            return;
        }

        $this->error = 'GEEN GROOT SCHERM ACTIEF';
    }

    public function openAlarm(?KioskMenu $fromMenu = null): void
    {
        $this->error = null;
        $status = app(HomeyService::class)->getAlarmStatus();

        if ($status === 'armed' || $status === 'partially_armed') {
            $this->redirect('/entry', navigate: true);

            return;
        }

        if ($status === 'disarmed') {
            $this->redirect($this->moduleUrl('/menu', $fromMenu), navigate: true);

            return;
        }

        $this->error = 'FOUT BIJ VERBINDEN';
    }

    public function dismissFeedback(): void
    {
        $this->error = null;
    }

    public function goBack(): void
    {
        $currentMenu = $this->resolveCurrentMenu();

        if (! $currentMenu->is_root) {
            $parent = $currentMenu->parentMenu();
            $this->menu = ($parent !== null && ! $parent->is_root) ? $parent->id : null;

            return;
        }

        $this->redirect('/', navigate: true);
    }

    protected function resolveCurrentMenu(): KioskMenu
    {
        $root = KioskMenu::ensureRootExists();

        if ($this->menu === null) {
            return $root;
        }

        $found = KioskMenu::query()
            ->whereNull('wall_panel_id')
            ->with(['blocks.submenu', 'parentBlock.menu'])
            ->find($this->menu);

        if ($found === null) {
            $this->menu = null;

            return $root;
        }

        $found->ensureFourBlocks();

        return $found->load(['blocks.submenu', 'parentBlock.menu']);
    }

    protected function moduleUrl(string $basePath, ?KioskMenu $fromMenu): string
    {
        if ($fromMenu !== null && ! $fromMenu->is_root) {
            return $basePath.'?from='.$fromMenu->id;
        }

        return $basePath;
    }

    public function render(): View
    {
        return view('livewire.kiosk.hub-screen', [
            'currentMenu' => $this->resolveCurrentMenu(),
        ]);
    }
}
