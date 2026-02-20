<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\TahunAjaran;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class TahunAjaranSelector extends Component
{
    public ?int $selectedTahunAjaranId = null;

    /**
     * Theme variant: 'slate' for teacher, 'teal' for student.
     * Passed from the layout to match panel styling.
     */
    public string $variant = 'slate';

    public function mount(): void
    {
        $context = TahunAjaran::getContext();
        $this->selectedTahunAjaranId = $context?->id;
    }

    #[Computed]
    public function tahunAjaranList()
    {
        return TahunAjaran::orderByDesc('nama_tahun')
            ->orderByDesc('semester')
            ->get();
    }

    public function updatedSelectedTahunAjaranId(?int $value): void
    {
        TahunAjaran::setContext($value);

        // Dispatch browser event to reload page
        $this->dispatch('tahun-ajaran-changed');
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.components.tahun-ajaran-selector');
    }
}
