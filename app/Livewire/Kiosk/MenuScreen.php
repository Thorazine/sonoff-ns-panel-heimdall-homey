<?php

namespace App\Livewire\Kiosk;

use App\Models\Setting;
use App\Services\HomeyService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.kiosk')]
#[Title('Modus Kiezen | Heimdall Kiosk')]
class MenuScreen extends Component
{
    #[Url]
    public ?int $from = null;

    /** @var array<int, string> */
    public array $openSensors = [];

    public ?string $pendingMode = null;

    public function selectMode(string $mode): void
    {
        if (! in_array($mode, ['armed', 'partially_armed'], true)) {
            return;
        }

        $alarmOptions = Setting::alarmModuleOptions();

        if ($alarmOptions['checkOpenSensors']) {
            $sensors = app(HomeyService::class)->getOpenSensors();

            if ($sensors !== []) {
                $this->openSensors = $sensors;
                $this->pendingMode = $mode;

                return;
            }
        }

        $this->redirect('/entry?mode='.$mode, navigate: true);
    }

    public function acceptAndProceed(): void
    {
        if ($this->pendingMode !== null) {
            $this->redirect('/entry?mode='.$this->pendingMode, navigate: true);
        }
    }

    public function goBack(): void
    {
        if ($this->from !== null) {
            $this->redirect('/hub?menu='.$this->from, navigate: true);

            return;
        }

        $this->redirect('/', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.kiosk.menu-screen');
    }
}
