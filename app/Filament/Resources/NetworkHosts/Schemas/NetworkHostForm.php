<?php

namespace App\Filament\Resources\NetworkHosts\Schemas;

use App\Enums\NetworkProtocol;
use App\Models\NetworkHost;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NetworkHostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('DNS-naam & Apparaat')
                    ->description('Voeg een lokale DNS-naam of apparaat toe voor snelle navigatie op /menu.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Naam')
                            ->placeholder('Bijv. Synology NAS, Homey Pro of Router')
                            ->required()
                            ->maxLength(100)
                            ->validationMessages([
                                'required' => 'Geef een herkenbare naam op.',
                            ]),

                        TextInput::make('dns_name')
                            ->label('DNS-naam / Hostname')
                            ->placeholder('Bijv. nas.local, homey.lan of 192.168.1.10')
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                    $normalized = NetworkHost::normalizeDnsName((string) $value);
                                    if (! NetworkHost::isValidHost($normalized)) {
                                        $fail('Vul een geldige DNS-naam of IP-adres in zonder poort of pad (bijv. nas.local of 192.168.1.10).');
                                    }
                                },
                            ])
                            ->validationMessages([
                                'required' => 'Vul een DNS-naam of IP-adres in.',
                            ])
                            ->dehydrateStateUsing(fn (?string $state): string => NetworkHost::normalizeDnsName((string) $state)),

                        TextInput::make('category')
                            ->label('Categorie / Groep (optioneel)')
                            ->placeholder('Bijv. Servers & NAS, Smart Home, Netwerk, Media')
                            ->maxLength(60)
                            ->datalist(fn (): array => array_values(array_unique([
                                'Netwerk',
                                'Servers & NAS',
                                'Smart Home',
                                'Media',
                                'Beveiliging',
                                ...NetworkHost::query()
                                    ->whereNotNull('category')
                                    ->where('category', '!=', '')
                                    ->distinct()
                                    ->pluck('category')
                                    ->all(),
                            ]))),

                        TextInput::make('ip_address')
                            ->label('IP-adres ter referentie (optioneel)')
                            ->placeholder('Bijv. 192.168.1.10')
                            ->maxLength(45)
                            ->rules(['nullable', 'ip'])
                            ->validationMessages([
                                'ip' => 'Vul een geldig IPv4- of IPv6-adres in.',
                            ]),

                        TextInput::make('description')
                            ->label('Omschrijving (optioneel)')
                            ->placeholder('Korte toelichting die op /menu onder de naam getoond wordt')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Zichtbaar op /menu')
                            ->default(true),

                        Toggle::make('open_in_new_tab')
                            ->label('Hoofdlink openen in nieuw tabblad')
                            ->default(true),
                    ]),

                Section::make('Standaard Hoofdlink (optioneel)')
                    ->description('Standaardadres wanneer direct op de DNS-naam geklikt wordt. Laat de poort leeg voor standaard HTTP (80) / HTTPS (443), of zet de hoofdlink uit als je alleen specifieke poort-toevoegingen wilt tonen.')
                    ->columns(3)
                    ->components([
                        Toggle::make('has_root_link')
                            ->label('Hoofdlink op DNS-naam tonen')
                            ->helperText('Zet uit als dit apparaat uitsluitend via specifieke poorten hieronder bereikbaar is.')
                            ->default(true)
                            ->columnSpanFull(),

                        Select::make('protocol')
                            ->label('Protocol')
                            ->options(NetworkProtocol::class)
                            ->default(NetworkProtocol::Http->value)
                            ->required(),

                        TextInput::make('port')
                            ->label('Standaard poort (optioneel)')
                            ->placeholder('Bijv. 8080 of leeg laten')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(65535)
                            ->validationMessages([
                                'integer' => 'Vul een geldig poortnummer in.',
                                'min' => 'Poortnummer moet tussen 1 en 65535 liggen.',
                                'max' => 'Poortnummer moet tussen 1 en 65535 liggen.',
                            ]),

                        TextInput::make('path')
                            ->label('Pad (optioneel)')
                            ->placeholder('Bijv. /admin')
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): ?string => ($clean = NetworkHost::normalizePath($state)) !== '' ? $clean : null),
                    ]),

                Section::make('Poort-toevoegingen & Services')
                    ->description('Voeg één of meerdere poorten toe onder deze DNS-naam (bijv. :5000 voor DSM, :8000 voor Heimdall, :8123 voor Home Assistant, :9443 voor Portainer). Elke poort verschijnt als directe knop bij deze DNS-naam op /menu.')
                    ->components([
                        Repeater::make('ports')
                            ->label('Poort-toevoegingen')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->addActionLabel('Poort toevoegen')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['port'] ?? null)
                                ? trim(((string) ($state['label'] ?? 'Dienst')).' (:'.$state['port'].')')
                                : (((string) ($state['label'] ?? '')) !== '' ? (string) $state['label'] : null))
                            ->columns(4)
                            ->components([
                                TextInput::make('label')
                                    ->label('Naam / Dienst')
                                    ->placeholder('Bijv. DSM Beheer of Portainer')
                                    ->required()
                                    ->maxLength(80)
                                    ->validationMessages([
                                        'required' => 'Geef een naam op voor deze poort/dienst.',
                                    ]),

                                Select::make('protocol')
                                    ->label('Protocol')
                                    ->options(NetworkProtocol::class)
                                    ->default(NetworkProtocol::Http->value)
                                    ->required(),

                                TextInput::make('port')
                                    ->label('Poort')
                                    ->placeholder('5000')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(65535)
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Geef een poortnummer op (1–65535).',
                                        'integer' => 'Vul een geldig poortnummer in.',
                                        'min' => 'Poortnummer moet tussen 1 en 65535 liggen.',
                                        'max' => 'Poortnummer moet tussen 1 en 65535 liggen.',
                                    ]),

                                TextInput::make('path')
                                    ->label('Pad (optioneel)')
                                    ->placeholder('/admin')
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn (?string $state): ?string => ($clean = NetworkHost::normalizePath($state)) !== '' ? $clean : null),

                                Toggle::make('is_active')
                                    ->label('Zichtbaar op /menu')
                                    ->default(true)
                                    ->columnSpan(2),

                                Toggle::make('open_in_new_tab')
                                    ->label('Openen in nieuw tabblad')
                                    ->default(true)
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }
}
