<?php

namespace App\Livewire\Kiosk;

use App\Enums\KioskModule;
use App\Models\Setting;
use App\Services\HomeyService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.kiosk')]
class ModuleScreen extends Component
{
    public KioskModule $module;

    #[Url]
    public ?int $from = null;

    public ?int $confirmingSlot = null;

    public ?string $feedbackMsg = null;

    public string $feedbackType = 'info';

    public function mount(KioskModule $module): void
    {
        $this->module = $module;

        if (! $module->isPresetGridModule()) {
            $target = match ($module) {
                KioskModule::Gym => '/gym',
                KioskModule::AllOff => '/all-off',
                default => '/hub',
            };

            $this->redirect($target, navigate: true);
        }
    }

    public function triggerSlot(int $slot): void
    {
        if (! in_array($slot, [1, 2, 3, 4], true)) {
            return;
        }

        $options = Setting::presetModuleOptions($this->module);

        if ($slot === 1 && $options['confirmFirstSlot'] && $this->confirmingSlot !== 1) {
            $this->confirmingSlot = 1;

            return;
        }

        $this->executeSlot($slot, $options);
    }

    public function confirmPendingSlot(): void
    {
        if ($this->confirmingSlot === null) {
            return;
        }

        $slot = $this->confirmingSlot;
        $this->confirmingSlot = null;
        $this->executeSlot($slot);
    }

    public function cancelConfirmation(): void
    {
        $this->confirmingSlot = null;
    }

    /**
     * @param  array<string, mixed>|null  $options
     */
    protected function executeSlot(int $slot, ?array $options = null): void
    {
        $this->confirmingSlot = null;
        $resolvedOptions = $options ?? Setting::presetModuleOptions($this->module);
        $slotConfig = $resolvedOptions['slots'][$slot] ?? null;

        if (! is_array($slotConfig)) {
            return;
        }

        $label = (string) $slotConfig['label'];
        $flowId = (string) $slotConfig['flowId'];

        $res = app(HomeyService::class)->triggerModuleAction($this->module, $label, $flowId);

        if ($res['ok']) {
            $this->feedbackType = 'info';
            $this->feedbackMsg = strtoupper($label).' GEACTIVEERD';

            if (! empty($resolvedOptions['autoReturn'])) {
                $this->dispatch('navigate-after-delay', url: '/', delay: 1200);
            }
        } else {
            $this->feedbackType = 'error';
            $this->feedbackMsg = $res['message'] ?? 'FLOW NOG NIET GEKOPPELD';
        }
    }

    public function dismissFeedback(): void
    {
        $this->feedbackMsg = null;
    }

    public function goBack(): void
    {
        if ($this->confirmingSlot !== null) {
            $this->confirmingSlot = null;

            return;
        }

        if ($this->from !== null) {
            $this->redirect('/hub?menu='.$this->from, navigate: true);

            return;
        }

        $this->redirect('/', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.kiosk.module-screen', [
            'options' => Setting::presetModuleOptions($this->module),
        ])->title($this->module->getLabel().' | Heimdall Kiosk');
    }
}
