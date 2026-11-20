<?php

namespace App\Filament\Pages\Auth;

use App\Enums\LogLevel;
use App\Http\Middleware\EnforceAdminIdleTimeout;
use App\Models\ActivityLog;
use App\Models\PinCode;
use App\Models\Setting;
use App\Services\PinLockoutService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class PinLogin extends BaseLogin
{
    #[Unexposed]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('pin')
                    ->label('Beheer PIN-code')
                    ->password()
                    ->revealable()
                    ->required()
                    ->autofocus()
                    ->extraInputAttributes([
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]*',
                        'maxlength' => 6,
                        'autocomplete' => 'one-time-code',
                    ])
                    ->helperText('Voer een actieve 4- tot 6-cijferige PIN-code met beheerdersrechten in.'),
            ]);
    }

    #[Exposed]
    public function authenticate(): ?LoginResponse
    {
        $settings = Setting::publicSettings();
        $lockout = app(PinLockoutService::class);

        try {
            $this->rateLimit($settings['maxFailedAttempts'], $settings['lockoutSeconds']);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            throw ValidationException::withMessages([
                'data.pin' => "Te veel pogingen. Geblokkeerd voor {$exception->secondsUntilAvailable} seconden.",
            ]);
        }

        if ($lockout->isLockedOut()) {
            $seconds = $lockout->remainingSeconds();

            Notification::make()
                ->title("Te veel foutieve pogingen ({$seconds}s geblokkeerd)")
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'data.pin' => "Geblokkeerd. Probeer opnieuw over {$seconds} seconden.",
            ]);
        }

        $state = $this->form->getState();
        $pin = trim((string) ($state['pin'] ?? ''));

        $verified = PinCode::verify($pin);

        if ($verified === null) {
            $failure = $lockout->recordFailure(contextLabel: 'beheer login');

            throw ValidationException::withMessages([
                'data.pin' => $failure['lockedOut']
                    ? "Te veel pogingen. Geblokkeerd voor {$failure['remainingSeconds']} seconden."
                    : 'Ongeldige PIN-code.',
            ]);
        }

        if (! $verified->is_admin) {
            ActivityLog::record(
                LogLevel::Warning,
                "Geweigerde beheer-login met niet-admin PIN ({$verified->name})"
            );
            $lockout->recordFailure(contextLabel: 'beheer login (geen admin)');

            throw ValidationException::withMessages([
                'data.pin' => 'Deze PIN-code heeft geen beheerdersrechten.',
            ]);
        }

        $this->clearRateLimiter();
        $lockout->clear();

        $user = $verified->resolveAdminUser();
        Filament::auth()->login($user, remember: false);
        session()->regenerate();
        session()->put(EnforceAdminIdleTimeout::SESSION_KEY, now()->getTimestamp());

        ActivityLog::record(LogLevel::Info, "Beheerpaneel geopend door {$verified->name}");

        return app(LoginResponse::class);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Ontgrendelen')
            ->submit('authenticate');
    }

    #[Unexposed]
    public function getTitle(): string|Htmlable
    {
        return 'Heimdall Beheer';
    }

    #[Unexposed]
    public function getHeading(): string|Htmlable|null
    {
        return 'Heimdall Beheer';
    }

    #[Unexposed]
    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }
}
