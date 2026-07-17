<div class="space-y-4 overflow-x-hidden">

    {{-- Flash / Validation --}}
    @if (session()->has('error'))
        <div
            class="flex items-center gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300">
            <flux:icon name="exclamation-circle" class="w-4 h-4 shrink-0" />
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
            <p class="text-xs font-semibold text-red-700 dark:text-red-300 mb-1">Terdapat kesalahan:</p>
            <ul class="list-disc list-inside text-xs text-red-600 dark:text-red-400 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate
            class="w-8 h-8 flex items-center justify-center bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shrink-0 cursor-pointer">
            <flux:icon name="arrow-left" class="w-4 h-4" />
        </a>
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">Buat Aktivitas</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ $step === 1 ? 'Langkah 1: Informasi Aktivitas' : 'Langkah 2: Aktivitas Kelas' }}
            </p>
        </div>
    </div>

    {{-- Progress --}}
    <div class="flex gap-1.5">
        <div class="flex-1 h-1 rounded-full {{ $step >= 1 ? 'bg-blue-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
        <div class="flex-1 h-1 rounded-full {{ $step >= 2 ? 'bg-blue-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
    </div>

    @if ($step === 1)
        {{-- ============================================================ --}}
        {{-- STEP 1: Informasi Aktivitas                                  --}}
        {{-- ============================================================ --}}
        <x-ui.card title="Informasi Aktivitas" variant="teacher">
            <div class="space-y-6">

                {{-- Row 1: Tanggal + Tingkat Kelas --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6" style="column-gap: 1.5rem;">
                    <div>
                        <flux:input wire:model="tanggal" type="date" label="Tanggal *"
                            label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                            class:input="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:ring-0 focus-visible:ring-0 focus:outline-none focus-visible:outline-none cursor-pointer" />
                        @error('tanggal')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:select wire:model.live="tingkatKelas" label="Tingkat Kelas *"
                            label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                            class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:ring-0 focus-visible:ring-0 focus:outline-none focus-visible:outline-none cursor-pointer">
                            <option value="">Pilih tingkat kelas...</option>
                            @foreach ($this->tingkatKelasList as $tingkat)
                                <option value="{{ $tingkat }}">Kelas {{ $tingkat }}</option>
                            @endforeach
                        </flux:select>
                        @error('tingkatKelas')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Row 2: Mata Pelajaran + Grup Kelas --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6" style="column-gap: 1.5rem;">
                    <div>
                        @if ($tingkatKelas !== null)
                            <flux:select wire:model.live="grupKelas" label="Grup Kelas *"
                                label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                                class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:ring-0 focus-visible:ring-0 focus:outline-none focus-visible:outline-none cursor-pointer">
                                <option value="">Pilih grup kelas...</option>
                                @foreach ($this->grupKelasList as $grup)
                                    <option value="{{ $grup }}">{{ $grup }}</option>
                                @endforeach
                            </flux:select>
                            @error('grupKelas')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        @else
                            <div>
                                <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1">Grup
                                    Kelas *</label>
                                <div
                                    class="h-10 flex items-center px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs text-slate-400">
                                    Pilih tingkat kelas dulu</div>
                            </div>
                        @endif
                    </div>
                    <div>
                        <flux:select wire:model.live="mataPelajaranId" label="Mata Pelajaran *"
                            label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                            class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:ring-0 focus-visible:ring-0 focus:outline-none focus-visible:outline-none cursor-pointer">
                            <option value="">Pilih mata pelajaran...</option>
                            @foreach ($this->mataPelajaran as $mapel)
                                <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
                            @endforeach
                        </flux:select>
                        @error('mataPelajaranId')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Row 3: Topik + Catatan --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6" style="column-gap: 1.5rem;">
                    <div>
                        <flux:input wire:model="topik" type="text" label="Topik Pembelajaran *"
                            placeholder="Contoh: Persamaan Linear Satu Variabel"
                            label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                            class:input="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:ring-0 focus-visible:ring-0 focus:outline-none focus-visible:outline-none" />
                        @error('topik')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="catatan" type="text" label="Catatan Tambahan (Opsional)"
                            placeholder="Catatan tambahan aktivitas..."
                            label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                            class:input="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:ring-0 focus-visible:ring-0 focus:outline-none focus-visible:outline-none" />
                        @error('catatan')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if ($this->selectedMapel)
                    <div
                        class="flex items-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <flux:icon name="user-group" class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                        <span class="text-xs text-blue-700 dark:text-blue-300">
                            <strong>{{ $this->selectedMapel->kelas->nama_lengkap }}</strong> &bull;
                            {{ $this->siswaList->count() }} siswa terdaftar
                        </span>
                    </div>
                @endif

            </div>
        </x-ui.card>

        <button wire:click="nextStep" wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
            class="w-full flex items-center justify-center gap-2 py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            {{ !$mataPelajaranId ? 'disabled' : '' }}>
            <span wire:loading.remove wire:target="nextStep">&gt; Lanjut ke Aktivitas Kelas</span>
            <span wire:loading wire:target="nextStep">Memproses...</span>
            <flux:icon wire:loading wire:target="nextStep" name="arrow-path" class="w-4 h-4 animate-spin" />
        </button>
    @else
        {{-- ============================================================ --}}
        {{-- STEP 2: Aktivitas Kelas — Semua state di Alpine, 0 wire.set  --}}
        {{-- ============================================================ --}}
        @php $keaktifanOptions = \App\Enums\Keaktifan::cases(); @endphp

        <div x-data="{
            students: @js($detailAktivitas),
            saving: false,
            get unsetCount() {
                return Object.values(this.students).filter(d => !d.kehadiran).length;
            },
            setAllHadir() {
                Object.keys(this.students).forEach(id => {
                    this.students[id].kehadiran = 'Hadir';
                });
            },
            setKehadiran(id, val) {
                this.students[id].kehadiran = val;
                if (val !== 'Hadir') {
                    this.students[id].keaktifan = null;
                }
            },
            setKeaktifan(id, val) {
                const cur = this.students[id].keaktifan;
                this.students[id].keaktifan = (cur === val) ? null : val;
            },
            async doSave() {
                this.saving = true;
                try {
                    await $wire.saveWithDetail(this.students);
                } finally {
                    this.saving = false;
                }
            },
            activeNote: { siswaId: null, nama: '', catatan: '' },
            openNote(siswaId, nama) {
                this.activeNote = {
                    siswaId: siswaId,
                    nama: nama,
                    catatan: this.students[siswaId]?.catatan || ''
                };
                Flux.modal('note-modal').show();
            },
            saveNote() {
                if (this.activeNote.siswaId !== null) {
                    this.students[this.activeNote.siswaId].catatan = this.activeNote.catatan;
                }
                Flux.modal('note-modal').close();
            }
        }">
            {{-- Summary bar --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 lg:gap-3">
                <div
                    class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 shadow-sm min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Mata Pelajaran</p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white mt-0.5 truncate">
                        {{ $this->selectedMapel?->nama_mapel ?? '-' }}</p>
                </div>
                <div
                    class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 shadow-sm min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Tanggal</p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">
                        {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d M Y') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 shadow-sm min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Kelas</p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white mt-0.5 truncate">
                        {{ $this->selectedMapel?->kelas?->nama_lengkap ?? '-' }}</p>
                </div>
                <div
                    class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 shadow-sm min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Jml Siswa</p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">{{ $this->siswaList->count() }}
                        siswa</p>
                </div>
            </div>

            {{-- Section header + mass action --}}
            <div class="flex items-center justify-between mt-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Aktivitas Kelas</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Catat kehadiran dan tingkat keaktifan siswa
                    </p>
                </div>
                <button type="button" x-on:click="setAllHadir()"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">
                    <flux:icon name="check-circle" class="w-4 h-4" />
                    Tandai Semua Hadir
                </button>
            </div>

            {{-- Status indicator --}}
            <div class="flex items-center gap-1.5 px-1 mt-2">
                <template x-if="unsetCount > 0">
                    <div class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">
                        <flux:icon name="exclamation-triangle" class="w-3.5 h-3.5 shrink-0" />
                        <span x-text="unsetCount + ' siswa belum dicatat kehadirannya'"></span>
                    </div>
                </template>
                <template x-if="unsetCount === 0">
                    <div class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
                        <flux:icon name="check-circle" class="w-3.5 h-3.5 shrink-0" />
                        <span>Semua siswa sudah tercatat</span>
                    </div>
                </template>
            </div>

            {{-- Student cards --}}
            <div class="space-y-2 mt-2">
                @forelse($this->siswaList as $siswa)
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm relative"
                        x-bind:class="students['{{ $siswa->id }}']?.kehadiran ? 'border-slate-200 dark:border-slate-700' :
                            'border-amber-300 dark:border-amber-700'"
                        wire:key="cs-{{ $siswa->id }}">

                        {{-- Mobile catatan button (icon-only, top-right) --}}
                        <button type="button" data-siswa-id="{{ $siswa->id }}"
                            data-siswa-nama="{{ $siswa->user->nama ?? $siswa->user->name }}"
                            x-on:click="students['{{ $siswa->id }}']?.kehadiran === 'Hadir' && openNote($el.dataset.siswaId, $el.dataset.siswaNama)"
                            x-bind:disabled="students['{{ $siswa->id }}']?.kehadiran !== 'Hadir'"
                            x-bind:class="students['{{ $siswa->id }}']?.kehadiran !== 'Hadir' ?
                                'text-slate-300 dark:text-slate-600 cursor-not-allowed opacity-40' :
                                (students['{{ $siswa->id }}']?.catatan ? 'text-blue-600 dark:text-blue-400' :
                                    'text-slate-400 hover:text-blue-500')"
                            class="lg:hidden absolute top-2.5 right-2.5 p-1.5 rounded-lg transition-colors cursor-pointer">
                            <flux:icon name="pencil-square" class="w-4 h-4" />
                            <span x-show="students['{{ $siswa->id }}']?.catatan"
                                class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-blue-500"></span>
                        </button>

                        <div class="flex flex-col gap-2.5 px-3 py-3 lg:flex-row lg:items-center lg:gap-3 lg:px-4">

                            {{-- Avatar — desktop only --}}
                            <div
                                class="hidden lg:flex w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/40 items-center justify-center shrink-0">
                                <span
                                    class="text-xs font-bold text-blue-600 dark:text-blue-300">{{ mb_substr($siswa->user->nama ?? ($siswa->user->name ?? '?'), 0, 1) }}</span>
                            </div>

                            {{-- Nama + NIS --}}
                            <div class="lg:w-36 lg:shrink-0">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white leading-tight truncate">
                                    {{ $siswa->user->nama ?? $siswa->user->name }}</p>
                                <p class="text-[11px] text-slate-400 font-mono">{{ $siswa->nis }}</p>
                            </div>

                            {{-- Divider + label — desktop only --}}
                            <div class="hidden lg:block w-px h-8 bg-slate-100 dark:bg-slate-700 shrink-0"></div>
                            <span
                                class="hidden lg:block text-[10px] font-semibold uppercase tracking-wider text-slate-400 shrink-0 w-16">Kehadiran</span>

                            {{-- H / I / S / A — fill width on mobile --}}
                            <div class="flex gap-1.5 lg:gap-1 shrink-0">
                                @foreach (['Hadir' => ['H', 'bg-emerald-500'], 'Izin' => ['I', 'bg-blue-500'], 'Sakit' => ['S', 'bg-amber-500'], 'Alpa' => ['A', 'bg-rose-500']] as $status => [$char, $activeClass])
                                    <button type="button"
                                        x-on:click="setKehadiran('{{ $siswa->id }}', '{{ $status }}')"
                                        x-bind:class="students['{{ $siswa->id }}']?.kehadiran === '{{ $status }}' ?
                                            '{{ $activeClass }} text-white shadow-sm lg:scale-110' :
                                            'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                        class="flex-1 lg:flex-none lg:w-9 h-9 rounded-lg text-xs font-bold transition-all duration-100 cursor-pointer"
                                        title="{{ $status }}">
                                        {{ $char }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Divider + label — desktop only --}}
                            <div class="hidden lg:block w-px h-8 bg-slate-100 dark:bg-slate-700 shrink-0"></div>
                            <span
                                class="hidden lg:block text-[10px] font-semibold uppercase tracking-wider text-slate-400 shrink-0 w-20">Keaktifan</span>

                            {{-- Keaktifan buttons — fill width on mobile --}}
                            <div class="flex gap-1.5 lg:gap-1 lg:flex-1"
                                x-show="students['{{ $siswa->id }}']?.kehadiran === 'Hadir'"
                                x-transition:enter="transition-opacity duration-100"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                @foreach ($keaktifanOptions as $option)
                                    <button type="button"
                                        x-on:click="setKeaktifan('{{ $siswa->id }}', @js($option->value))"
                                        x-bind:class="students['{{ $siswa->id }}']?.keaktifan === @js($option->value) ?
                                            'bg-blue-600 text-white shadow-sm' :
                                            'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                                        class="flex-1 lg:flex-none lg:px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-100 whitespace-nowrap cursor-pointer">
                                        {{ $option->label() }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Placeholder tidak hadir --}}
                            <div class="lg:flex-1 flex items-center"
                                x-show="students['{{ $siswa->id }}']?.kehadiran !== 'Hadir'" x-cloak>
                                <span class="text-xs text-slate-300 dark:text-slate-600 italic">— tidak hadir —</span>
                            </div>

                            {{-- Catatan button — desktop only --}}
                            <div class="hidden lg:block w-px h-8 bg-slate-100 dark:bg-slate-700 shrink-0 ml-auto">
                            </div>
                            <button type="button" data-siswa-id="{{ $siswa->id }}"
                                data-siswa-nama="{{ $siswa->user->nama ?? $siswa->user->name }}"
                                x-on:click="students['{{ $siswa->id }}']?.kehadiran === 'Hadir' && openNote($el.dataset.siswaId, $el.dataset.siswaNama)"
                                x-bind:disabled="students['{{ $siswa->id }}']?.kehadiran !== 'Hadir'"
                                x-bind:class="students['{{ $siswa->id }}']?.kehadiran !== 'Hadir' ?
                                    'text-slate-300 dark:text-slate-600 border-slate-100 dark:border-slate-700 cursor-not-allowed opacity-50' :
                                    (students['{{ $siswa->id }}']?.catatan ?
                                        'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700 cursor-pointer' :
                                        'text-slate-400 border-slate-200 dark:border-slate-600 hover:text-blue-500 hover:border-blue-200 cursor-pointer'
                                        )"
                                class="hidden lg:flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-all duration-150 shrink-0">
                                <flux:icon name="pencil-square" class="w-3.5 h-3.5" />
                                <span>Catatan</span>
                                <span x-show="students['{{ $siswa->id }}']?.catatan"
                                    class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                            </button>

                        </div>
                    </div>
                @empty
                    <div
                        class="p-8 text-center bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <flux:icon name="exclamation-triangle" class="mx-auto w-8 h-8 text-amber-400 mb-2" />
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">Tidak Ada Siswa</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Kelas ini belum memiliki siswa
                            terdaftar.</p>
                    </div>
                @endforelse
            </div>

            {{-- Action buttons --}}
            <div
                class="sticky bottom-16 lg:static z-20 flex gap-3 mt-4 -mx-1 p-2 bg-slate-50/95 dark:bg-slate-950/95 backdrop-blur lg:mx-0 lg:p-0 lg:bg-transparent lg:dark:bg-transparent lg:backdrop-blur-none">
                <button wire:click="previousStep" wire:loading.attr="disabled"
                    class="flex-1 flex items-center justify-center gap-2 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition-colors cursor-pointer">
                    <flux:icon name="arrow-left" class="w-4 h-4" />
                    <span>Kembali</span>
                </button>
                <button type="button" x-on:click="doSave()" x-bind:disabled="saving"
                    x-bind:class="saving ? 'opacity-60 cursor-not-allowed' : 'hover:bg-blue-700 cursor-pointer'"
                    class="flex-1 flex items-center justify-center gap-2 py-3 bg-blue-600 text-white font-semibold text-sm rounded-xl transition-colors shadow-sm"
                    {{ $this->siswaList->isEmpty() ? 'disabled' : '' }}>
                    <flux:icon name="check" class="w-4 h-4" x-show="!saving" />
                    <svg class="w-4 h-4 animate-spin" x-show="saving" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                </button>
            </div>

            {{-- ======================================================== --}}
            {{-- Note Modal — Flux UI, state di Alpine                      --}}
            {{-- ======================================================== --}}
            <flux:modal name="note-modal" class="md:w-[460px]">
                <div class="space-y-5">

                    {{-- Header: student identity --}}
                    <div class="flex items-center gap-3">
                        <div
                            class="w-11 h-11 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shrink-0 shadow-sm">
                            <span class="text-base font-bold text-white"
                                x-text="activeNote.nama ? activeNote.nama.charAt(0).toUpperCase() : '?'"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Catatan
                                Observasi</p>
                            <p class="text-base font-bold text-slate-900 dark:text-white leading-tight truncate"
                                x-text="activeNote.nama"></p>
                        </div>
                    </div>

                    <div class="h-px bg-slate-100 dark:bg-slate-700"></div>

                    {{-- Textarea --}}
                    <div class="relative">
                        <textarea x-model="activeNote.catatan" x-on:keydown.ctrl.enter.prevent="saveNote()" rows="5" maxlength="500"
                            placeholder="Tuliskan catatan observasi untuk siswa ini..."
                            class="w-full px-4 py-3 text-sm text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-xl resize-none focus:outline-none focus:border-blue-400 dark:focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/30 transition-all leading-relaxed placeholder:text-slate-300 dark:placeholder:text-slate-600"></textarea>
                        <span
                            class="absolute bottom-2.5 right-3 text-[10px] font-medium text-slate-300 dark:text-slate-600"
                            x-text="(activeNote.catatan || '').length + '/500'"></span>
                    </div>

                    {{-- Clear link --}}
                    <div class="flex justify-between items-center -mt-2">
                        <button type="button" x-show="activeNote.catatan" x-on:click="activeNote.catatan = ''"
                            class="text-xs text-slate-400 hover:text-red-500 transition-colors cursor-pointer">
                            Hapus catatan
                        </button>
                        <p class="text-[10px] text-slate-300 dark:text-slate-600 ml-auto">Ctrl+Enter untuk simpan</p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2 justify-end pt-1">
                        <flux:modal.close>
                            <flux:button variant="ghost" size="sm" class="cursor-pointer">Batal</flux:button>
                        </flux:modal.close>
                        <flux:button variant="primary" size="sm" x-on:click="saveNote()"
                            class="cursor-pointer">
                            <flux:icon name="check" class="w-3.5 h-3.5" />
                            Simpan Catatan
                        </flux:button>
                    </div>

                </div>
            </flux:modal>

        </div>{{-- end x-data --}}

    @endif
</div>
