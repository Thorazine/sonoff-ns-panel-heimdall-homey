<?php

use App\Services\TrashService;
use Carbon\CarbonImmutable;

function sampleTrashCollections(): array
{
    return [
        ['type' => 'REST', 'localText' => 'Restafval', 'dates' => ['2026-11-09T00:00:00.000Z']],
        ['type' => 'PAPIER', 'localText' => 'Papier en karton', 'dates' => ['2026-11-23T00:00:00.000Z']],
        ['type' => 'PMD', 'localText' => 'PMD', 'dates' => ['2026-11-09T00:00:00.000Z']],
        ['type' => 'GFT', 'localText' => 'GFT', 'dates' => ['2026-11-16T00:00:00.000Z']],
        ['type' => 'SNOEI', 'localText' => 'Snoeiafval', 'dates' => ['2026-11-16T00:00:00.000Z']],
    ];
}

test('T10: returns empty trash list before 17:00 on the day before collection', function (): void {
    $trash = app(TrashService::class);
    $afternoonBefore = CarbonImmutable::create(2026, 11, 8, 16, 30, 0);

    expect($trash->getActiveTrashContainers(sampleTrashCollections(), $afternoonBefore))->toBe([]);
});

test('T11: returns matching styled containers after 17:00 on the evening before collection', function (): void {
    $trash = app(TrashService::class);
    $eveningBefore = CarbonImmutable::create(2026, 11, 8, 17, 15, 0);

    $res = $trash->getActiveTrashContainers(sampleTrashCollections(), $eveningBefore);

    expect(array_column($res, 'type'))->toBe(['REST', 'PMD'])
        ->and($res[0]['colorHex'])->toBe('#9ca3af')
        ->and($res[0]['dayLabel'])->toBe('Ma 9/11')
        ->and($res[1]['colorHex'])->toBe('#f97316');
});

test('T12: keeps showing containers until 10:00 on collection morning and deduplicates GFT + SNOEI', function (): void {
    $trash = app(TrashService::class);
    $morningOfGft = CarbonImmutable::create(2026, 11, 16, 8, 30, 0);

    $res = $trash->getActiveTrashContainers(sampleTrashCollections(), $morningOfGft);

    expect($res)->toHaveCount(1)
        ->and($res[0]['type'])->toBe('GFT')
        ->and($res[0]['colorHex'])->toBe('#22c55e');
});

test('T13: hides containers after 10:00 on collection day if nothing is scheduled for tomorrow', function (): void {
    $trash = app(TrashService::class);
    $lateMorning = CarbonImmutable::create(2026, 11, 16, 10, 5, 0);

    expect($trash->getActiveTrashContainers(sampleTrashCollections(), $lateMorning))->toBe([]);
});
