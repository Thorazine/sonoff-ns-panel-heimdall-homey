<?php

namespace App\Filament\Pages\Auth;

use App\Enums\LogLevel;
use App\Http\Middleware\EnforceAdminIdleTimeout;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminLogin extends BaseLogin
{
    #[Unexposed]
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        if (User::needsInitialAdminSetup()) {
            $this->redirect(route('filament.admin.auth.setup'));

            return;
        }

        $this->form->fill();
    }

    #[Unexposed]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent()
                    ->label('E-mailadres'),
                $this->getPasswordFormComponent()
                    ->label('Wachtwoord')
                    ->revealable(),
            ]);
    }

    #[Exposed]
    public function authenticate(): ?LoginResponse
    {
        $settings = Setting::publicSettings();

        try {
            $this->rateLimit($settings['maxFailedAttempts'], $settings['lockoutSeconds']);
        } catch (TooManyRequestsException $exception) {
            ActivityLog::record(
                LogLevel::Danger,
                "Beheer-login tijdelijk geblokkeerd na te veel foutieve pogingen ({$exception->secondsUntilAvailable}s)"
            );

            $this->getRateLimitedNotification($exception)?->send();

            throw ValidationException::withMessages([
                'data.email' => "Te veel inlogpogingen. Probeer opnieuw over {$exception->secondsUntilAvailable} seconden.",
            ]);
        }

        $data = $this->form->getState();
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! filled($user->password) || ! Hash::check($password, $user->password)) {
            ActivityLog::record(
                LogLevel::Warning,
                "Mislukte beheer-login voor {$email}"
            );

            throw ValidationException::withMessages([
                'data.email' => 'Onjuiste inloggegevens.',
            ]);
        }

        if (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            ActivityLog::record(
                LogLevel::Warning,
                "Geweigerde beheer-login zonder rechten of inactief account ({$user->name})"
            );

            throw ValidationException::withMessages([
                'data.email' => 'Dit account heeft geen toegang tot het beheerpaneel.',
            ]);
        }

        $this->clearRateLimiter();

        Filament::auth()->login($user, remember: false);
        session()->regenerate();
        session()->put(EnforceAdminIdleTimeout::SESSION_KEY, now()->getTimestamp());

        ActivityLog::record(LogLevel::Info, "Beheerpaneel geopend door {$user->name}");

        return app(LoginResponse::class);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Inloggen')
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
        return 'Log in met je beheerdersaccount';
    }
}
