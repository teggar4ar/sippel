<?php

declare(strict_types=1);

use App\Enums\Keaktifan;

it('exposes the database values in domain order', function (): void {
    expect(Keaktifan::values())->toBe([
        'pasif',
        'cukup',
        'aktif',
        'sangat_aktif',
    ]);
});

it('maps each category to its label and weight', function (Keaktifan $keaktifan, string $label, int $weight): void {
    expect($keaktifan->label())->toBe($label)
        ->and($keaktifan->weight())->toBe($weight);
})->with([
    [Keaktifan::Pasif, 'Pasif', 1],
    [Keaktifan::Cukup, 'Cukup', 2],
    [Keaktifan::Aktif, 'Aktif', 3],
    [Keaktifan::SangatAktif, 'Sangat Aktif', 4],
]);

it('maps numeric averages to the expected category', function (float $average, Keaktifan $expected): void {
    expect(Keaktifan::fromAverage($average))->toBe($expected);
})->with([
    [1.0, Keaktifan::Pasif],
    [1.49, Keaktifan::Pasif],
    [1.5, Keaktifan::Cukup],
    [2.5, Keaktifan::Aktif],
    [3.5, Keaktifan::SangatAktif],
    [4.0, Keaktifan::SangatAktif],
]);
