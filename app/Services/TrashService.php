<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class TrashService
{
    /** @var array<string, array{key: string, label: string, colorHex: string, textHex: string, bgClass: string}> */
    public const STYLES = [
        'REST' => [
            'key' => 'REST',
            'label' => 'Rest',
            'colorHex' => '#9ca3af',
            'textHex' => '#d1d5db',
            'bgClass' => 'bg-gray-800/40 border border-gray-500/40 shadow-[0_0_15px_rgba(156,163,175,0.15)]',
        ],
        'PAPIER' => [
            'key' => 'PAPIER',
            'label' => 'Papier',
            'colorHex' => '#3b82f6',
            'textHex' => '#93c5fd',
            'bgClass' => 'bg-blue-950/40 border border-blue-500/40 shadow-[0_0_15px_rgba(59,130,246,0.2)]',
        ],
        'PMD' => [
            'key' => 'PMD',
            'label' => 'PMD',
            'colorHex' => '#f97316',
            'textHex' => '#fdba74',
            'bgClass' => 'bg-orange-950/40 border border-orange-500/40 shadow-[0_0_15px_rgba(249,115,22,0.2)]',
        ],
        'GFT' => [
            'key' => 'GFT',
            'label' => 'GFT',
            'colorHex' => '#22c55e',
            'textHex' => '#86efac',
            'bgClass' => 'bg-green-950/40 border border-green-500/40 shadow-[0_0_15px_rgba(34,197,94,0.2)]',
        ],
    ];

    /** @var array<int, string> */
    protected const DUTCH_DAYS_SHORT = ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za'];

    public function normalizeTrashType(?string $type): ?string
    {
        $upper = strtoupper(trim((string) $type));

        return match ($upper) {
            'REST', 'GREYBAGS', 'NONRECYCLABLE' => 'REST',
            'PAPIER', 'PAPER' => 'PAPIER',
            'PMD', 'PLASTIC', 'PBD' => 'PMD',
            'GFT', 'SNOEI', 'GREEN' => 'GFT',
            default => null,
        };
    }

    public function formatDutchDayLabel(string $dateKey): string
    {
        $slice = substr(trim($dateKey), 0, 10);
        $parts = explode('-', $slice);
        if (count($parts) !== 3 || ! is_numeric($parts[0]) || ! is_numeric($parts[1]) || ! is_numeric($parts[2])) {
            return '';
        }

        [$year, $month, $day] = array_map('intval', $parts);
        $dt = CarbonImmutable::createFromDate($year, $month, $day);
        if ($dt === null) {
            return '';
        }

        $dayName = self::DUTCH_DAYS_SHORT[$dt->dayOfWeek];

        return "{$dayName} {$day}/{$month}";
    }

    /**
     * @param  array<int, array<string, mixed>>  $collections
     * @param  array{previewDays?: int}  $options
     * @return array<int, array<string, mixed>>
     */
    public function getActiveTrashContainers(array $collections, ?CarbonInterface $now = null, array $options = []): array
    {
        if ($collections === []) {
            return [];
        }

        $nowDt = CarbonImmutable::instance($now ?? now());
        $previewDays = (int) ($options['previewDays'] ?? 0);
        $validDateKeys = [];

        if ($previewDays > 0) {
            for ($i = 0; $i <= $previewDays; $i++) {
                $validDateKeys[$nowDt->addDays($i)->format('Y-m-d')] = true;
            }
        } else {
            $hour = $nowDt->hour;
            if ($hour >= 17) {
                $validDateKeys[$nowDt->addDay()->format('Y-m-d')] = true;
            } elseif ($hour < 10) {
                $validDateKeys[$nowDt->format('Y-m-d')] = true;
            } else {
                return [];
            }
        }

        $seenKeys = [];
        $activeItems = [];

        foreach ($collections as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normKey = $this->normalizeTrashType(isset($item['type']) ? (string) $item['type'] : null);
            if ($normKey === null || isset($seenKeys[$normKey])) {
                continue;
            }

            $dates = isset($item['dates']) && is_array($item['dates']) ? $item['dates'] : [];
            $matchedDates = [];
            foreach ($dates as $dStr) {
                if (is_string($dStr)) {
                    $dayKey = substr($dStr, 0, 10);
                    if (isset($validDateKeys[$dayKey])) {
                        $matchedDates[] = $dayKey;
                    }
                }
            }

            if ($matchedDates !== []) {
                sort($matchedDates);
                $dateStr = $matchedDates[0];
                $seenKeys[$normKey] = true;
                $style = self::STYLES[$normKey];

                $activeItems[] = [
                    'kind' => 'trash',
                    'type' => $normKey,
                    'date' => $dateStr,
                    'dayLabel' => $this->formatDutchDayLabel($dateStr),
                    'label' => $style['label'],
                    'localText' => (string) ($item['localText'] ?? $style['label']),
                    'colorHex' => $style['colorHex'],
                    'textHex' => $style['textHex'],
                    'bgClass' => $style['bgClass'],
                ];
            }
        }

        usort($activeItems, fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        return $activeItems;
    }
}
