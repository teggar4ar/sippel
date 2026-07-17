<div class="space-y-3 overflow-x-hidden">
    <x-ui.section-heading variant="teacher" title="Laporan"
        subtitle="Lihat dan cetak laporan siswa kelas perwalian Anda" />

    @if (!$this->hasKelasWali)
        {{-- No homeroom class assigned --}}
        <div
            class="p-4 text-center bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
            <flux:icon name="exclamation-triangle" class="mx-auto w-10 h-10 text-amber-500" />
            <p class="mt-2 text-sm font-medium text-amber-800 dark:text-amber-200">Belum Ada Kelas Perwalian</p>
            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                Anda belum ditugaskan sebagai wali kelas. Fitur laporan hanya tersedia untuk wali kelas.
            </p>
        </div>
    @else
        {{-- Report Type Selection --}}
        @include('livewire.teacher.partials.laporan-jenis')

        {{-- Filter Form --}}
        @include('livewire.teacher.partials.laporan-filters')

        {{-- Preview Section --}}
        @if ($showPreview)
            @if ($reportType === 'student')
                @include('livewire.teacher.partials.laporan-preview-siswa')
            @else
                @include('livewire.teacher.partials.laporan-preview-kelas')
            @endif
        @endif
    @endif
</div>
