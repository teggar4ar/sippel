<div class="space-y-6">
    {{-- Header with back button --}}
    <div class="flex items-center gap-4">
        <flux:button href="{{ route('teacher.aktivitas.list') }}" wire:navigate variant="ghost" size="sm">
            <flux:icon name="arrow-left" class="size-5" />
        </flux:button>
        <div>
            <flux:heading size="xl" level="1">Edit Aktivitas</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">Ubah data aktivitas pembelajaran</flux:text>
        </div>
    </div>

    <flux:separator variant="subtle" />

    {{-- Activity Information Form --}}
    <div class="p-6 lg:p-8 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <flux:heading size="lg" class="mb-6">Informasi Aktivitas</flux:heading>

        <div class="space-y-5">
            {{-- Date --}}
            <div>
                <flux:input
                    wire:model="tanggal"
                    type="date"
                    label="Tanggal"
                    required
                    class:input="focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0" />
                @error('tanggal')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Subject selection --}}
            <div>
                <flux:select
                    wire:model.live="mata_pelajaran_id"
                    label="Mata Pelajaran"
                    placeholder="Pilih mata pelajaran..."
                    required
                    class="focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0">
                    <option value="">Pilih mata pelajaran...</option>
                    @foreach($this->mataPelajaran as $mapel)
                        <option value="{{ $mapel->id }}">
                            {{ $mapel->nama_mapel }} - Kelas {{ $mapel->kelas->nama_lengkap }}
                        </option>
                    @endforeach
                </flux:select>
                @error('mata_pelajaran_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Show selected class info --}}
            @if($this->selectedMapel)
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Kelas:</strong> {{ $this->selectedMapel->kelas->nama_lengkap }}
                        ({{ $this->siswaList->count() }} siswa)
                    </flux:text>
                </div>
            @endif

            {{-- Topic --}}
            <div>
                <flux:input
                    wire:model="topik"
                    label="Topik Pembelajaran"
                    placeholder="Contoh: Persamaan Linear Satu Variabel"
                    required
                    class:input="focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0" />
                @error('topik')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes --}}
            <div>
                <flux:textarea
                    wire:model="catatan"
                    label="Catatan (Opsional)"
                    rows="3"
                    placeholder="Catatan tambahan tentang aktivitas pembelajaran..."
                    class="focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0" />
                @error('catatan')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Attendance Section --}}
    <div class="space-y-4">
        <flux:heading size="lg">Absensi & Penilaian</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            Perbarui kehadiran dan nilai untuk setiap siswa. Total: {{ $this->siswaList->count() }} siswa.
        </flux:text>

        {{-- Quick attendance button --}}
        @php
            $unsetCount = collect($detailAktivitas)->filter(fn($d) => empty($d['kehadiran']))->count();
        @endphp
        <div class="flex items-center justify-between gap-3 p-3 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
            <div class="flex items-center gap-2">
                <span class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-nowrap">Tandai semua:</span>
                <button
                    type="button"
                    wire:click="setAllAttendance('Hadir')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors"
                >
                    <flux:icon name="check" class="size-4" />
                    <span>Hadir</span>
                </button>
            </div>
            @if($unsetCount > 0)
                <span class="text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                    <flux:icon name="exclamation-circle" class="size-4" /> {{ $unsetCount }} belum diisi
                </span>
            @else
                <span class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                    <flux:icon name="check-circle" class="size-4" /> Semua terisi
                </span>
            @endif
        </div>

        {{-- Compact student list with scrollable container --}}
        <div class="max-h-[55vh] overflow-y-auto space-y-2 pr-1 -mr-1" x-data="{ expanded: null }">
            @foreach($this->siswaList as $siswa)
                @php
                    $kehadiran = $detailAktivitas[$siswa->id]['kehadiran'] ?? null;
                    $isHadir = strtolower($kehadiran ?? '') === 'hadir';
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
                                        class:input="h-9 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                                        :disabled="!$isHadir"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Partisipasi</label>
                                    <flux:select
                                        wire:model="detailAktivitas.{{ $siswa->id }}.partisipasi"
                                        class="h-9 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                                        :disabled="!$isHadir"
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
            <div class="p-8 text-center bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                <flux:icon name="exclamation-triangle" class="mx-auto size-12 text-yellow-500" />
                <flux:heading size="sm" class="mt-4 text-yellow-800 dark:text-yellow-200">Tidak Ada Siswa</flux:heading>
                <flux:text class="mt-2 text-yellow-600 dark:text-yellow-400">
                    Kelas ini belum memiliki siswa terdaftar.
                </flux:text>
            </div>
        @endif
    </div>

    {{-- Action buttons --}}
    <div class="sticky bottom-0 -mx-4 px-4 py-4 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 lg:relative lg:mx-0 lg:px-0 lg:border-0 lg:bg-transparent">
        <div class="flex gap-3">
            <flux:button
                href="{{ route('teacher.aktivitas.list') }}"
                wire:navigate
                variant="subtle"
                class="flex-1 h-12">
                Batal
            </flux:button>
            <flux:button
                wire:click="save"
                variant="primary"
                icon="check"
                class="flex-1 h-12 text-base"
                :disabled="$this->siswaList->isEmpty()">
                Simpan Perubahan
            </flux:button>
        </div>
    </div>
</div>
