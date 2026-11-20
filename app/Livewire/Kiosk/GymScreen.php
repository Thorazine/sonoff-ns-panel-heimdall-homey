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
#[Title('Gym | Heimdall Kiosk')]
class GymScreen extends Component
{
    #[Url]
    public ?int $from = null;

    public ?string $feedbackMsg = null;

    public string $feedbackType = 'info';

    public function triggerGym(string $state): void
    {
        if (! in_array($state, ['on', 'off'], true)) {
            return;
        }

        $options = Setting::gymModuleOptions();
        $res = app(HomeyService::class)->triggerGym($state);

        if ($res['ok']) {
            $this->feedbackType = 'info';
            $this->feedbackMsg = $state === 'on' ? 'GYM INGESCHAKELD' : 'GYM UITGESCHAKELD';

            if ($options['autoReturn']) {
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
        if ($this->from !== null) {
            $this->redirect('/hub?menu='.$this->from, navigate: true);

            return;
        }

        $this->redirect('/', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.kiosk.gym-screen', [
            'options' => Setting::gymModuleOptions(),
        ]);
    }
}
