<?php

namespace App\Filament\Pages\Auth;

use App\Enums\LogLevel;
use App\Http\Middleware\EnforceAdminIdleTimeout;
use App\Models\ActivityLog;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InitialAdminSetup extends BaseRegister
{
    #[Unexposed]
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $this->redirect(Filament::getUrl());

            return;
        }

        if (! User::needsInitialAdminSetup()) {
            $this->redirect(Filament::getLoginUrl());

            return;
        }

        $this->callHook('beforeFill');
        $this->form->fill();
        $this->callHook('afterFill');
    }

    #[Unexposed]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Naam')
                    ->placeholder('Bijv. Hoofdbewoner')
                    ->required()
                    ->maxLength(80)
                    ->autofocus(),

                TextInput::make('email')
                    ->label('E-mailadres')
                    ->placeholder('naam@voorbeeld.nl')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class),

                TextInput::make('password')
                    ->label('Wachtwoord')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->same('passwordConfirmation')
                    ->validationAttribute('wachtwoord')
                    ->autocomplete('new-password'),

                TextInput::make('passwordConfirmation')
                    ->label('Wachtwoord bevestigen')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false)
                    ->autocomplete('new-password'),

                TextInput::make('pin')
                    ->label('Paneel PIN-code (optioneel, 4 tot 6 cijfers)')
                    ->password()
                    ->revealable()
                    ->rules(['nullable', 'regex:/^\d{4,6}$/'])
                    ->validationMessages([
                        'regex' => 'De PIN-code moet uit 4 tot 6 cijfers bestaan.',
                    ])
                    ->extraInputAttributes([
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]*',
                        'maxlength' => 6,
                        'autocomplete' => 'new-password',
                    ])
                    ->helperText('Optioneel: stel direct een persoonlijke PIN-code in voor het fysieke alarmpaneel.'),
            ]);
    }

    #[Exposed]
    public function register(): ?RegistrationResponse
    {
        if (! User::needsInitialAdminSetup()) {
            $this->redirect(Filament::getLoginUrl());

            return null;
        }

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            throw ValidationException::withMessages([
                'data.email' => "Te veel pogingen. Probeer opnieuw over {$exception->secondsUntilAvailable} seconden.",
            ]);
        }

        $data = $this->form->getState();

        /** @var User|null $user */
        $user = DB::transaction(function () use ($data): ?User {
            User::query()->lockForUpdate()->get();

            if (! User::needsInitialAdminSetup()) {
                return null;
            }

            return User::query()->create([
                'name' => trim((string) $data['name']),
                'email' => strtolower(trim((string) $data['email'])),
                'password' => (string) $data['password'],
                'pin' => filled($data['pin'] ?? null) ? trim((string) $data['pin']) : null,
                'is_active' => true,
                'is_admin' => true,
            ]);
        });

        if ($user === null) {
            $this->redirect(Filament::getLoginUrl());

            return null;
        }

        $this->clearRateLimiter();

        Filament::auth()->login($user, remember: false);
        session()->regenerate();
        session()->put(EnforceAdminIdleTimeout::SESSION_KEY, now()->getTimestamp());

        ActivityLog::record(
            LogLevel::Info,
            "Eerste beheerder aangemaakt tijdens installatie: {$user->name}"
        );

        Notification::make()
            ->title('Welkom! Je beheerdersaccount is aangemaakt.')
            ->success()
            ->send();

        return app(RegistrationResponse::class);
    }

    #[Unexposed]
    public function getRegisterFormAction(): Action
    {
        return Action::make('register')
            ->label('Beheerder aanmaken & starten')
            ->submit('register');
    }

    #[Unexposed]
    public function getTitle(): string|Htmlable
    {
        return 'Eerste Beheerder Aanmaken';
    }

    #[Unexposed]
    public function getHeading(): string|Htmlable|null
    {
        return 'Welkom bij Heimdall Beheer';
    }

    #[Unexposed]
    public function getSubheading(): string|Htmlable|null
    {
        return 'Er is nog geen beheerder ingesteld. Maak het eerste account aan om te starten.';
    }
}
