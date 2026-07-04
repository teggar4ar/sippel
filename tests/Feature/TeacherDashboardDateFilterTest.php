<?php

declare(strict_types=1);

use App\Livewire\Teacher\AktivitasPembelajaran\CreateAktivitas;
use App\Livewire\Teacher\AktivitasPembelajaran\EditAktivitas;
use App\Livewire\Teacher\Dashboard;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('limits the month dashboard filter to the active semester dates', function () {
    Carbon::setTestNow('2026-07-04 12:00:00');
    TahunAjaran::factory()->active()->create([
        'tanggal_mulai' => '2026-03-01',
        'tanggal_selesai' => '2026-07-25',
    ]);

    $dashboard = new Dashboard;
    $dashboard->rentangWaktu = 'bulan';

    $method = new ReflectionMethod($dashboard, 'getDateRange');
    $range = $method->invoke($dashboard);

    expect($range['start']->toDateString())->toBe('2026-07-01')
        ->and($range['end']->toDateString())->toBe('2026-07-25');
});

it('rejects create and edit dates outside the active semester', function (string $componentClass, string $rulesMethod) {
    TahunAjaran::factory()->active()->create([
        'tanggal_mulai' => '2026-03-01',
        'tanggal_selesai' => '2026-07-25',
    ]);

    $component = new $componentClass;
    $method = new ReflectionMethod($component, $rulesMethod);
    $rules = $method->invoke($component);
    $validator = Validator::make(['tanggal' => '2026-07-28'], [
        'tanggal' => $rules['tanggal'],
    ]);

    expect($validator->errors()->has('tanggal'))->toBeTrue();
})->with([
    'create activity' => [CreateAktivitas::class, 'rulesForStep1'],
    'edit activity' => [EditAktivitas::class, 'rules'],
]);
