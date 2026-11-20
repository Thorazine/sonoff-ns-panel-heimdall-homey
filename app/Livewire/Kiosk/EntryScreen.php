<?php

namespace App\Livewire\Kiosk;

use App\Models\PinCode;
use App\Models\Setting;
use App\Services\HomeyService;
use App\Services\PinLockoutService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.kiosk')]
#[Title('PIN Invoer | Heimdall Kiosk')]
class EntryScreen extends Component
{
    use WithRateLimiting;

    #[Url]
    public bool $alarm = false;

    #[Url]
    public ?string $mode = null;

    public bool $isLockedOut = false;

    public int $lockoutSecondsLeft = 0;

    public int $alarmCountdownSeconds = 10;

    public bool $isFlashing = false;

    public bool $disarmed = false;

    public bool $showErrorScreen = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $settings = Setting::publicSettings();
        $this->alarmCountdownSeconds = $settings['alarmCountdownSeconds'];

        $this->refreshLockout();
    }

    public function refreshLockout(): void
    {
        $lockout = app(PinLockoutService::class);
        $this->isLockedOut = $lockout->isLockedOut();
        $this->lockoutSecondsLeft = $this->isLockedOut ? $lockout->remainingSeconds() : 0;
    }

    public function isArming(): bool
    {
        return in_array($this->mode, ['armed', 'partially_armed'], true);
    }

    public function triggerAlarmFlash(): void
    {
        if ($this->alarm && ! $this->disarmed) {
            $this->isFlashing = true;
        }
    }

    public function clearErrorScreen(): void
    {
        $this->showErrorScreen = false;
    }

    public function submitPin(string $pin): void
    {
        $this->errorMessage = null;
        $settings = Setting::publicSettings();
        $lockout = app(PinLockoutService::class);

        try {
            $this->rateLimit(max($settings['maxFailedAttempts'] * 2, 10), $settings['lockoutSeconds']);
        } catch (TooManyRequestsException $exception) {
            $this->isLockedOut = true;
            $this->lockoutSecondsLeft = max(1, $exception->secondsUntilAvailable);
            $this->dispatch('kiosk-sound', type: 'error');

            return;
        }

        if ($lockout->isLockedOut()) {
            $this->isLockedOut = true;
            $this->lockoutSecondsLeft = $lockout->remainingSeconds();
            $this->dispatch('kiosk-sound', type: 'error');

            return;
        }

        $verified = PinCode::verify($pin);

        if ($verified === null) {
            $contextLabel = $this->isArming() ? "inschakelen ({$this->mode})" : 'uitschakelen';
            $failure = $lockout->recordFailure(contextLabel: $contextLabel);
            $this->dispatch('kiosk-sound', type: 'error');

            if ($failure['lockedOut']) {
                $this->isLockedOut = true;
                $this->lockoutSecondsLeft = $failure['remainingSeconds'];
                $this->showErrorScreen = false;
            } else {
                $this->showErrorScreen = true;
                $this->errorMessage = 'FOUTIEVE CODE';
                $this->dispatch('pin-error-flash');
            }

            return;
        }

        $this->clearRateLimiter();
        $lockout->clear();
        $this->isLockedOut = false;
        $this->showErrorScreen = false;

        if ($this->isArming()) {
            session()->put('kiosk.verified_arm', [
                'mode' => $this->mode,
                'expires_at' => now()->addMinutes(5)->getTimestamp(),
            ]);

            $this->redirect('/countdown?mode='.$this->mode, navigate: true);

            return;
        }

        $disarmedOk = app(HomeyService::class)->setAlarmMode('disarmed');

        if ($disarmedOk) {
            $this->disarmed = true;
            $this->isFlashing = false;
            $this->dispatch('kiosk-sound', type: 'disarmed');
            $this->dispatch('navigate-after-delay', url: '/', delay: 2000);

            return;
        }

        $this->errorMessage = 'FOUT BIJ VERBINDEN';
        $this->dispatch('kiosk-sound', type: 'error');
    }

    public function cancelToStart(): void
    {
        $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.kiosk.entry-screen');
    }
}
