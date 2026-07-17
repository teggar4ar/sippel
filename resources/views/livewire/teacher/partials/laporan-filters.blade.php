<x-ui.card variant="teacher" title="Filter Laporan">
    @if ($reportType === 'student')
        {{-- Student filter: single horizontal row --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
            <div class="flex-1 min-w-0">
                <flux:select wire:model.live="kelasId" label="Kelas Perwalian"
                    label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                    class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer">
                    <option value="">Pilih Kelas</option>
                    @foreach ($this->kelasWali as $kelas)
                        <option value="{{ $kelas->id }}">
                            {{ $kelas->tingkat_kelas }}-{{ $kelas->grup_kelas }}
                            ({{ $kelas->tahunAjaran?->nama_tahun }})
                        </option>
                    @endforeach
                </flux:select>
            </div>
            <div class="flex-1 min-w-0">
                <flux:select wire:model.live="siswaId" label="Siswa"
                    label:class="text-xs font-medium text-slate-600 dark:text-slate-400" :disabled="!$kelasId"
                    class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer">
                    <option value="">{{ $kelasId ? 'Pilih Siswa' : 'Pilih kelas dulu' }}</option>
                    @foreach ($this->siswaList as $siswa)
                        <option value="{{ $siswa->id }}">
                            {{ $siswa->user?->name }} ({{ $siswa->nis }})
                        </option>
                    @endforeach
                </flux:select>
            </div>
            <button wire:click="generatePreview" wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="flex items-center justify-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl transition-colors shrink-0 cursor-pointer">
                <flux:icon wire:loading.remove wire:target="generatePreview" name="user" class="w-4 h-4" />
                <flux:icon wire:loading wire:target="generatePreview" name="arrow-path" class="w-4 h-4 animate-spin" />
                <span>Lihat Pratinjau</span>
            </button>
        </div>
    @else
        {{-- Class filter: single horizontal row --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
            <div class="flex-1 min-w-0">
                <flux:select wire:model.live="kelasId" label="Kelas Perwalian"
                    label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                    class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer">
                    <option value="">Pilih Kelas</option>
                    @foreach ($this->kelasWali as $kelas)
                        <option value="{{ $kelas->id }}">
                            {{ $kelas->tingkat_kelas }}-{{ $kelas->grup_kelas }}
                            ({{ $kelas->tahunAjaran?->nama_tahun }})
                        </option>
                    @endforeach
                </flux:select>
            </div>
            <div class="flex-1 min-w-0">
                <flux:select wire:model.live="mataPelajaranId" label="Mata Pelajaran"
                    label:class="text-xs font-medium text-slate-600 dark:text-slate-400" :disabled="!$kelasId"
                    class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer">
                    <option value="">{{ $kelasId ? 'Pilih Mata Pelajaran' : 'Pilih kelas dulu' }}</option>
                    @foreach ($this->mataPelajaranList as $mapel)
                        <option value="{{ $mapel->id }}">
                            {{ $mapel->nama_mapel }}
                        </option>
                    @endforeach
                </flux:select>
            </div>
            <div class="flex-1 min-w-0">
                <flux:select wire:model.live="sortBy" label="Urut Berdasarkan"
                    label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                    class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer">
                    <option value="kehadiran">Kehadiran (Tertinggi)</option>
                    <option value="keaktifan">Keaktifan (Tertinggi)</option>
                    <option value="keaktifan_asc">Keaktifan (Terendah)</option>
                    <option value="nama">Nama (A-Z)</option>
                </flux:select>
            </div>
            <button wire:click="generatePreview" wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="flex items-center justify-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl transition-colors shrink-0 cursor-pointer">
                <flux:icon wire:loading.remove wire:target="generatePreview" name="user-group" class="w-4 h-4" />
                <flux:icon wire:loading wire:target="generatePreview" name="arrow-path" class="w-4 h-4 animate-spin" />
                <span>Lihat Pratinjau</span>
            </button>
        </div>
    @endif
</x-ui.card>
