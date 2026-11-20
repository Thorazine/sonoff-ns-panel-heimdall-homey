<?php

namespace App\Filament\Resources\PinCodes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PinCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('PIN-code gegevens')
                    ->description('Gebruikers-PIN voor het in- en uitschakelen van het Heimdall alarmsysteem en optioneel toegang tot het beheerpaneel.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Naam / Gebruiker')
                            ->placeholder('Bijv. Hoofdbewoner of Gast')
                            ->required()
                            ->maxLength(80),

                        TextInput::make('pin')
                            ->label('PIN-code (4 tot 6 cijfers)')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rules(['nullable', 'regex:/^\d{4,6}$/'])
                            ->validationMessages([
                                'regex' => 'De PIN-code moet uit 4 tot 6 cijfers bestaan.',
                            ])
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Laat leeg om de huidige gehashte PIN-code te behouden.'
                                : 'Wordt versleuteld (bcrypt) opgeslagen en is na opslaan niet meer leesbaar.'),

                        Toggle::make('is_active')
                            ->label('Actief')
                            ->helperText('Alleen actieve PIN-codes worden geaccepteerd.')
                            ->default(true),

                        Toggle::make('is_admin')
                            ->label('Beheerder (Admin)')
                            ->helperText('Geeft met deze PIN-code toegang tot de instellingen en /admin.')
                            ->default(false),
                    ]),
            ]);
    }
}
