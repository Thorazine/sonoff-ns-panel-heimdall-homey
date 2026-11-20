<?php

namespace App\Livewire\Kiosk;

use App\Models\Setting;
use App\Services\HomeyService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.kiosk')]
#[Title('Aftellen | Heimdall Kiosk')]
class CountdownScreen extends Component
{
    #[Url]
    public string $mode = 'armed';

    public int $timeLeft = 10;

    public bool $armed = false;

    public function mount(): void
    {
        if (! $this->hasValidArmAuthorization()) {
            $this->redirect('/menu', navigate: true);

            return;
        }

        $this->timeLeft = Setting::publicSettings()['countdownSeconds'];
    }

    protected function hasValidArmAuthorization(): bool
    {
        if (! in_array($this->mode, ['armed', 'partially_armed'], true)) {
            return false;
        }

        $auth = session('kiosk.verified_arm');
        if (! is_array($auth)) {
            return false;
        }

        return ($auth['mode'] ?? null) === $this->mode
            && isset($auth['expires_at'])
            && (int) $auth['expires_at'] >= now()->getTimestamp();
    }

    public function completeArming(): void
    {
        if (! $this->hasValidArmAuthorization()) {
            $this->redirect('/menu', navigate: true);

            return;
        }

        session()->forget('kiosk.verified_arm');

        app(HomeyService::class)->setAlarmMode($this->mode);
        $this->armed = true;

        $this->dispatch('kiosk-sound', type: 'armed');
        $this->dispatch('navigate-after-delay', url: '/', delay: 2000);
    }

    public function cancel(): void
    {
        session()->forget('kiosk.verified_arm');
        $this->redirect('/menu', navigate: true);
    }

    public function render()
    {
        return view('livewire.kiosk.countdown-screen');
    }
}
