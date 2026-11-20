<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gebruiker')
                    ->description('Met alleen een naam en PIN-code kan deze persoon het alarm in- en uitschakelen. Een e-mailadres met wachtwoord geeft toegang tot /admin.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Naam')
                            ->placeholder('Bijv. Bewoner of Gast')
                            ->required()
                            ->maxLength(80),

                        Toggle::make('is_active')
                            ->label('Actief')
                            ->helperText('Schakelt zowel een eventuele PIN-code als /admin toegang in of uit.')
                            ->default(true),
                    ]),

                Section::make('Alarmpaneel (PIN-code)')
                    ->description('Zorgt ervoor dat deze persoon met een 4- tot 6-cijferige code het alarm aan en uit kan zetten op het paneel.')
                    ->columns(2)
                    ->components([
                        TextInput::make('pin')
                            ->label('PIN-code (4 tot 6 cijfers)')
                            ->password()
                            ->revealable()
                            ->disabled(fn (Get $get): bool => (bool) $get('clear_pin'))
                            ->required(fn (Get $get, ?User $record): bool => blank($get('email')) && ($record === null || ! $record->hasPin() || (bool) $get('clear_pin')))
                            ->rules(['nullable', 'regex:/^\d{4,6}$/'])
                            ->validationMessages([
                                'required' => 'Vul een PIN-code in (of geef hieronder een e-mailadres op voor /admin toegang).',
                                'regex' => 'De PIN-code moet uit 4 tot 6 cijfers bestaan.',
                            ])
                            ->extraInputAttributes([
                                'inputmode' => 'numeric',
                                'pattern' => '[0-9]*',
                                'maxlength' => 6,
                                'autocomplete' => 'new-password',
                            ])
                            ->helperText(fn (?User $record): string => ($record !== null && $record->hasPin())
                                ? 'Laat leeg om de huidige gehashte PIN-code te behouden.'
                                : 'Verplicht voor gebruikers zonder e-mailadres; optioneel voor beheerders.'),

                        Toggle::make('clear_pin')
                            ->label('Bestaande PIN-code verwijderen')
                            ->helperText('Alleen mogelijk wanneer deze gebruiker een e-mailadres voor /admin heeft.')
                            ->live()
                            ->default(false)
                            ->dehydrated(true)
                            ->visible(fn (Get $get, ?User $record): bool => $record !== null && $record->hasPin() && filled($get('email'))),
                    ]),

                Section::make('Beheerpaneel toegang (/admin — optioneel)')
                    ->description('Niet verplicht. Laat het e-mailadres leeg voor een gebruiker die alleen het alarm bedient. Een ingevuld e-mailadres geeft toegang tot /admin.')
                    ->columns(2)
                    ->components([
                        TextInput::make('email')
                            ->label('E-mailadres (optioneel)')
                            ->placeholder('Laat leeg voor alleen alarmpaneel')
                            ->email()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->unique(table: User::class, column: 'email', ignoreRecord: true),

                        TextInput::make('password')
                            ->label('Wachtwoord (voor /admin)')
                            ->password()
                            ->revealable()
                            ->required(fn (Get $get, ?User $record): bool => filled($get('email')) && ($record === null || ! filled($record->password)))
                            ->minLength(8)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->autocomplete('new-password')
                            ->validationMessages([
                                'required' => 'Stel een wachtwoord in wanneer je een e-mailadres opgeeft voor /admin toegang.',
                            ])
                            ->helperText(fn (?User $record): string => ($record !== null && filled($record->password))
                                ? 'Laat leeg om het huidige wachtwoord te behouden (minimaal 8 tekens bij wijzigen).'
                                : 'Alleen nodig als je een e-mailadres invult (minimaal 8 tekens).'),
                    ]),
            ]);
    }
}
