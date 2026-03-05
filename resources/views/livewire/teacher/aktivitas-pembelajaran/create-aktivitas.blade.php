<div class="space-y-3 overflow-x-hidden">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                <p class="text-sm font-medium text-emerald-900 dark:text-emerald-100">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400" />
                <p class="text-sm font-medium text-red-900 dark:text-red-100">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
            <div class="flex items-start gap-2">
                <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-900 dark:text-red-100 mb-1">Terdapat kesalahan:</p>
                    <ul class="list-disc list-inside text-xs text-red-800 dark:text-red-200 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Header with back button - Compact --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate
           class="w-8 h-8 flex items-center justify-center bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex-shrink-0">
            <flux:icon name="arrow-left" class="w-4 h-4" />
        </a>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Buat Aktivitas</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                @if($step === 1)
                    Langkah 1: Informasi Aktivitas
                @else
                    Langkah 2: Absensi & Penilaian
                @endif
            </p>
        </div>
    </div>

    {{-- Progress indicator - Compact --}}
    <div class="flex items-center gap-1.5">
        <div class="flex-1 h-1.5 rounded-full {{ $step >= 1 ? 'bg-blue-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
        <div class="flex-1 h-1.5 rounded-full {{ $step >= 2 ? 'bg-blue-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
    </div>

    @if($step === 1)
        {{-- Step 1: Activity Information --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700">
                <span class="text-sm font-semibold text-slate-900 dark:text-white">Informasi Aktivitas</span>
            </div>
            <div class="p-3 space-y-3">
                {{-- Date --}}
                <div>
                    <flux:input
                        wire:model="tanggal"
                        type="date"
                        label="Tanggal *"
                        label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                        class:input="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                    />
                    @error('tanggal')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tingkat Kelas --}}
                <div>
                    <flux:select
                        wire:model.live="tingkatKelas"
                        label="Tingkat Kelas *"
                        label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                        class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                    >
                        <option value="">Pilih tingkat kelas...</option>
                        @foreach($this->tingkatKelasList as $tingkat)
                            <option value="{{ $tingkat }}">Kelas {{ $tingkat }}</option>
                        @endforeach
                    </flux:select>
                    @error('tingkatKelas')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Grup Kelas --}}
                @if($tingkatKelas !== null)
                    <div>
                        <flux:select
                            wire:model.live="grupKelas"
                            label="Grup Kelas *"
                            label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                            class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                        >
                            <option value="">Pilih grup kelas...</option>
                            @foreach($this->grupKelasList as $grup)
                                <option value="{{ $grup }}">{{ $grup }}</option>
                            @endforeach
                        </flux:select>
                        @error('grupKelas')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Subject selection --}}
                @if($grupKelas !== null && $grupKelas !== '')
                    <div>
                        <flux:select
                            wire:model.live="mataPelajaranId"
                            label="Mata Pelajaran *"
                            label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                            class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                        >
                            <option value="">Pilih mata pelajaran...</option>
                            @foreach($this->mataPelajaran as $mapel)
                                <option value="{{ $mapel->id }}">
                                    {{ $mapel->nama_mapel }}
                                </option>
                            @endforeach
                        </flux:select>
                        @error('mataPelajaranId')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Show selected class info --}}
                @if($this->selectedMapel)
                    <div class="flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <flux:icon name="user-group" class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                        <span class="text-xs text-blue-700 dark:text-blue-300">
                            <strong>{{ $this->selectedMapel->kelas->nama_lengkap }}</strong> • {{ $this->siswaList->count() }} siswa
                        </span>
                    </div>
                @endif

                {{-- Topic --}}
                <div>
                    <flux:input
                        wire:model="topik"
                        type="text"
                        label="Topik Pembelajaran *"
                        placeholder="Contoh: Persamaan Linear Satu Variabel"
                        label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                        class:input="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                    />
                    @error('topik')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notes --}}
                <div>
                    <flux:textarea
                        wire:model="catatan"
                        label="Catatan (Opsional)"
                        rows="3"
                        placeholder="Catatan tambahan..."
                        label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                        class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 text-sm"
                    />
                    @error('catatan')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Step 1 action button --}}
        <div class="sticky bottom-0 -mx-4 px-4 py-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 lg:relative lg:mx-0 lg:px-0 lg:border-0 lg:bg-transparent">
            <button wire:click="nextStep"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="w-full flex items-center justify-center gap-2 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    {{ !$mataPelajaranId ? 'disabled' : '' }}>
                <span wire:loading.remove wire:target="nextStep">Lanjut ke Absensi</span>
                <span wire:loading wire:target="nextStep">Memproses...</span>
                <flux:icon wire:loading.remove wire:target="nextStep" name="arrow-right" class="w-4 h-4" />
                <flux:icon wire:loading wire:target="nextStep" name="arrow-path" class="w-4 h-4 animate-spin" />
            </button>
        </div>

    @else
        {{-- Step 2: Attendance Recording --}}
        <div class="space-y-3">
            {{-- Activity summary - Compact --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="flex divide-x divide-slate-100 dark:divide-slate-700">
                    <div class="flex-1 p-2 text-center">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Tanggal</p>
                        <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}</p>
                    </div>
                    <div class="flex-1 p-2 text-center">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Kelas</p>
                        <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $this->selectedMapel?->kelas->nama_lengkap }}</p>
                    </div>
                    <div class="flex-1 p-2 text-center">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Siswa</p>
                        <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $this->siswaList->count() }}</p>
                    </div>
                </div>
                <div class="px-3 py-2 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                    <p class="text-xs text-slate-600 dark:text-slate-400 truncate">
                        <span class="font-medium text-slate-900 dark:text-white">{{ $this->selectedMapel?->nama_mapel }}</span>
                        • {{ $topik }}
                    </p>
                </div>
            </div>

            {{-- Section header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Absensi & Penilaian</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Catat kehadiran dan nilai siswa</p>
                </div>
            </div>

            {{-- Quick attendance button --}}
            @php
                $unsetCount = collect($detailAktivitas)->filter(fn($d) => empty($d['kehadiran']))->count();
            @endphp
            <div class="flex items-center justify-between gap-2 p-2 bg-slate-100 dark:bg-slate-800 rounded-lg">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-600 dark:text-slate-400">Tandai:</span>
                    <button
                        type="button"
                        wire:click="setAllAttendance('Hadir')"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded transition-colors"
                    >
                        <flux:icon name="check" class="w-3 h-3" />
                        <span>Semua Hadir</span>
                    </button>
                </div>
                @if($unsetCount > 0)
                    <span class="text-[10px] text-amber-600 dark:text-amber-400 flex items-center gap-1">
                        <flux:icon name="exclamation-circle" class="w-3 h-3" /> {{ $unsetCount }} belum
                    </span>
                @else
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                        <flux:icon name="check-circle" class="w-3 h-3" /> Lengkap
                    </span>
                @endif
            </div>

            {{-- Compact student list with scrollable container --}}
            <div class="max-h-[55vh] overflow-y-auto space-y-2 pr-1 -mr-1" x-data="{ expanded: null }">
                @foreach($this->siswaList as $siswa)
                    @php
                        $kehadiran = $detailAktivitas[$siswa->id]['kehadiran'] ?? null;
                        $isHadir = $kehadiran === 'Hadir';
                        $isUnset = empty($kehadiran);
                        $nilai = $detailAktivitas[$siswa->id]['nilai'] ?? null;
                        $partisipasi = $detailAktivitas[$siswa->id]['partisipasi'] ?? null;
                    @endphp
                    <div
                        class="bg-zinc-50 dark:bg-zinc-900 rounded-lg border {{ $isUnset ? 'border-amber-300 dark:border-amber-700' : 'border-zinc-200 dark:border-zinc-700' }} overflow-hidden"
                        wire:key="siswa-{{ $siswa->id }}"
                    >
                        {{-- Compact header (always visible) --}}
                        <div class="flex items-center gap-2 p-3">
                            {{-- Student info --}}
                            <div
                                class="flex items-center gap-2 flex-1 min-w-0 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 -m-2 p-2 rounded-lg transition-colors"
                                x-on:click="expanded = expanded === {{ $siswa->id }} ? null : {{ $siswa->id }}"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm text-zinc-900 dark:text-white truncate">{{ $siswa->user->nama }}</div>
                                    <div class="text-xs text-zinc-500">{{ $siswa->nis }}</div>
                                </div>

                                {{-- Quick status display --}}
                                <div class="flex items-center gap-1.5 text-xs shrink-0">
                                    @if($isHadir && $nilai !== null)
                                        <span class="px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-medium">{{ $nilai }}</span>
                                    @endif
                                    @if($isHadir && $partisipasi)
                                        <span class="text-yellow-500">{{ str_repeat('⭐', (int)$partisipasi) }}</span>
                                    @endif
                                </div>

                                {{-- Expand indicator --}}
                                <flux:icon
                                    name="chevron-down"
                                    class="size-4 text-zinc-400 transition-transform shrink-0"
                                    x-bind:class="{ 'rotate-180': expanded === {{ $siswa->id }} }"
                                />
                            </div>

                            {{-- Attendance buttons (always visible) --}}
                            <div class="flex gap-1 shrink-0">
                                @foreach(['Hadir', 'Izin', 'Sakit', 'Alpa'] as $status)
                                    @php
                                        $btnColor = match($status) {
                                            'Hadir' => 'bg-green-500 text-white',
                                            'Izin' => 'bg-blue-500 text-white',
                                            'Sakit' => 'bg-yellow-500 text-white',
                                            'Alpa' => 'bg-red-500 text-white',
                                            default => 'bg-zinc-200 text-zinc-600',
                                        };
                                        $isActive = $kehadiran === $status;
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="$set('detailAktivitas.{{ $siswa->id }}.kehadiran', '{{ $status }}')"
                                        class="w-8 h-8 rounded-md text-xs font-bold transition-all {{ $isActive ? $btnColor : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}"
                                        title="{{ $status }}"
                                    >
                                        {{ substr($status, 0, 1) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Expandable content --}}
                        <div
                            x-show="expanded === {{ $siswa->id }}"
                            x-collapse
                            class="border-t border-zinc-200 dark:border-zinc-700"
                        >
                            <div class="p-3 space-y-3">
                                {{-- Grade and participation (inline) --}}
                                <div class="flex gap-2 {{ !$isHadir ? 'opacity-40 pointer-events-none' : '' }}">
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Nilai</label>
                                    <flux:input
                                        type="number"
                                        wire:model="detailAktivitas.{{ $siswa->id }}.nilai"
                                        min="0"
                                        max="100"
                                        placeholder="0-100"
                                        :disabled="!$isHadir"
                                        class:input="h-9 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                                    />
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Partisipasi</label>
                                    <flux:select
                                        wire:model="detailAktivitas.{{ $siswa->id }}.partisipasi"
                                        :disabled="!$isHadir"
                                        class="h-9 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                                    >
                                            <option value="">-</option>
                                            @for($i = 1; $i <= 5; $i++)
                                                <option value="{{ $i }}">{{ $i }} ⭐</option>
                                            @endfor
                                        </flux:select>
                                    </div>
                                </div>

                                {{-- Notes (collapsible) --}}
                                <div>
                                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Catatan</label>
                                    <flux:input
                                        type="text"
                                        wire:model="detailAktivitas.{{ $siswa->id }}.catatan"
                                        placeholder="Catatan siswa (opsional)"
                                        class:input="h-9 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- No students warning --}}
            @if($this->siswaList->isEmpty())
                <div class="p-4 text-center bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                    <flux:icon name="exclamation-triangle" class="mx-auto w-8 h-8 text-amber-500" />
                    <p class="mt-2 text-sm font-medium text-amber-800 dark:text-amber-200">Tidak Ada Siswa</p>
                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Kelas ini belum memiliki siswa.</p>
                </div>
            @endif
        </div>

        {{-- Step 2 action buttons --}}
        <div class="sticky bottom-0 -mx-4 px-4 py-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 lg:relative lg:mx-0 lg:px-0 lg:border-0 lg:bg-transparent">
            <div class="flex gap-2">
                <button wire:click="previousStep"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="flex-1 flex items-center justify-center gap-2 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-sm rounded-xl transition-colors">
                    <flux:icon name="arrow-left" class="w-4 h-4" />
                    <span>Kembali</span>
                </button>
                <button wire:click="save"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="flex-1 flex items-center justify-center gap-2 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        {{ $this->siswaList->isEmpty() ? 'disabled' : '' }}>
                    <flux:icon wire:loading.remove wire:target="save" name="check" class="w-4 h-4" />
                    <flux:icon wire:loading wire:target="save" name="arrow-path" class="w-4 h-4 animate-spin" />
                    <span wire:loading.remove wire:target="save">Simpan</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </div>
    @endif
</div>
