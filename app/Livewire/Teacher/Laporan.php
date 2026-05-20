<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Livewire\Teacher\Concerns\HasLaporanComputed;
use App\Livewire\Teacher\Concerns\HasLaporanDownloads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.teacher')]
#[Title('Laporan - SIPPEL Guru')]
final class Laporan extends Component
{
    use HasLaporanComputed;
    use HasLaporanDownloads;
    use WithPagination;

    // Report type: 'student' or 'class'
    public string $reportType = 'student';

    // Form fields
    public ?int $kelasId = null;

    public ?int $siswaId = null;

    public ?int $mataPelajaranId = null;

    public string $sortBy = 'kehadiran';

    // Preview data
    public bool $showPreview = false;

    /**
     * Initialize component with defaults
     */
    public function mount(): void
    {
        // Auto-select first class if teacher has only one
        if ($this->kelasWali->count() === 1) {
            $this->kelasId = $this->kelasWali->first()?->id;
        }
    }

    /**
     * Reset dependent fields when report type changes
     */
    public function updatedReportType(): void
    {
        $this->resetDependentFields();
    }

    /**
     * Reset dependent fields when class changes
     */
    public function updatedKelasId(): void
    {
        $this->resetDependentFields();
        $this->resetPage();
    }

    /**
     * Reset preview when student changes
     */
    public function updatedSiswaId(): void
    {
        $this->showPreview = false;
        $this->resetPage();
    }

    /**
     * Reset preview when subject changes
     */
    public function updatedMataPelajaranId(): void
    {
        $this->showPreview = false;
    }

    /**
     * Reset preview when sort changes (for class report)
     */
    public function updatedSortBy(): void
    {
        // Preview will auto-refresh due to computed property
    }

    /**
     * Generate preview — delegates validation to a dedicated guard method.
     */
    public function generatePreview(): void
    {
        if (! $this->validatePreviewRequest()) {
            return;
        }

        $this->showPreview = true;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.laporan');
    }

    /**
     * Reset selection fields that depend on both report type and class.
     */
    private function resetDependentFields(): void
    {
        $this->siswaId = null;
        $this->mataPelajaranId = null;
        $this->showPreview = false;
    }
}
