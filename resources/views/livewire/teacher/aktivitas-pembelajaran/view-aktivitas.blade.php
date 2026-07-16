<div class="space-y-4">

    {{-- ═══ Header ═══ --}}
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate
               class="p-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg shrink-0">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <h1 class="text-lg lg:text-xl font-bold text-slate-900 dark:text-white leading-tight truncate">Detail Aktivitas Pembelajaran</h1>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('teacher.aktivitas.edit', $aktivitas->id) }}" wire:navigate
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-lg transition-colors">
                <flux:icon name="pencil" class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">Edit</span>
            </a>
            <button
                x-on:click="$wire.showDeleteModal = true"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-red-200 dark:border-red-700 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition-colors cursor-pointer"
            >
                <flux:icon name="trash" class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">Hapus</span>
            </button>
        </div>
    </div>

    {{-- ═══ Metadata Card ═══ --}}
    <x-ui.card>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 text-sm">
            {{-- Left column --}}
            <div class="space-y-2">
                <div class="flex">
                    <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Topik</span>
                    <span class="text-slate-400 mr-2">:</span>
                    <span class="font-medium text-slate-900 dark:text-white break-words min-w-0">{{ $aktivitas->topik }}</span>
                </div>
                <div class="flex">
                    <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Mata Pelajaran</span>
                    <span class="text-slate-400 mr-2">:</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $aktivitas->mataPelajaran->nama_mapel }}</span>
                </div>
            </div>
            {{-- Right column --}}
            <div class="space-y-2">
                <div class="flex">
                    <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Tanggal</span>
                    <span class="text-slate-400 mr-2">:</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $aktivitas->tanggal->translatedFormat('d F Y') }}</span>
                </div>
                <div class="flex">
                    <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Kelas</span>
                    <span class="text-slate-400 mr-2">:</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $aktivitas->kelas->tingkat_kelas }}-{{ $aktivitas->kelas->grup_kelas }}</span>
                </div>
            </div>
        </div>

        @if($aktivitas->catatan)
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
                <div class="flex">
                    <span class="text-sm text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Catatan</span>
                    <span class="text-sm text-slate-400 mr-2">:</span>
                    <span class="text-sm text-slate-700 dark:text-slate-300 break-words min-w-0">{{ $aktivitas->catatan }}</span>
                </div>
            </div>
        @endif
    </x-ui.card>

    {{-- ═══ Ringkasan Pertemuan ═══ --}}
    <x-ui.card title="Ringkasan Pertemuan">
        <div class="space-y-2">
        @php
            $avgKeaktifan = $this->stats['avg_keaktifan'];
            $keaktifanLabel = $avgKeaktifan === null ? '-' : \App\Enums\Keaktifan::fromAverage((float) $avgKeaktifan)->label();
        @endphp
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 rounded-full bg-emerald-50 dark:bg-emerald-900/20">
                <flux:icon name="user-group" class="w-3.5 h-3.5 text-emerald-500" />
                Total Hadir: {{ $this->stats['hadir'] }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 rounded-full bg-blue-50 dark:bg-blue-900/20">
                <flux:icon name="envelope" class="w-3.5 h-3.5 text-blue-500" />
                Izin: {{ $this->stats['izin'] }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 rounded-full bg-amber-50 dark:bg-amber-900/20">
                <flux:icon name="heart" class="w-3.5 h-3.5 text-amber-500" />
                Sakit: {{ $this->stats['sakit'] }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-full bg-red-50 dark:bg-red-900/20">
                <flux:icon name="x-circle" class="w-3.5 h-3.5 text-red-500" />
                Alpa: {{ $this->stats['alpa'] }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-violet-200 dark:border-violet-800 text-violet-700 dark:text-violet-300 rounded-full bg-violet-50 dark:bg-violet-900/20">
                <flux:icon name="star" class="w-3.5 h-3.5 text-violet-500" />
                Rata-rata Keaktifan: {{ $keaktifanLabel }}
            </span>
        </div>
        </div>
    </x-ui.card>

    {{-- ═══ Daftar Observasi Siswa ═══ --}}
    <x-ui.card title="Daftar Observasi Siswa" flush>
        <x-slot:actions>
            <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ $aktivitas->detailAktivitas->count() }} siswa</span>
        </x-slot:actions>

        {{-- Desktop Table (lg+) --}}
        <div class="hidden lg:block px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-12">No</flux:table.column>
                    <flux:table.column>Nama Siswa & NIS</flux:table.column>
                    <flux:table.column>Status Kehadiran</flux:table.column>
                    <flux:table.column>Tingkat Keaktifan</flux:table.column>
                    <flux:table.column>Catatan Observasi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($aktivitas->detailAktivitas as $index => $detail)
                        @php
                            $kehadiranBadge = match($detail->kehadiran) {
                                \App\Enums\KehadiranStatus::Hadir => ['Hadir', 'green'],
                                \App\Enums\KehadiranStatus::Izin  => ['Izin', 'amber'],
                                \App\Enums\KehadiranStatus::Sakit => ['Sakit', 'amber'],
                                \App\Enums\KehadiranStatus::Alpa  => ['Alpa', 'red'],
                                default => ['-', 'zinc'],
                            };
                            $keaktifanBadge = null;
                            if ($detail->kehadiran === \App\Enums\KehadiranStatus::Hadir && $detail->keaktifan) {
                                $keaktifanBadge = match($detail->keaktifan) {
                                    \App\Enums\Keaktifan::SangatAktif => ['S. Aktif', 'green'],
                                    \App\Enums\Keaktifan::Aktif => ['Aktif', 'blue'],
                                    \App\Enums\Keaktifan::Cukup => ['Cukup', 'amber'],
                                    \App\Enums\Keaktifan::Pasif => ['Pasif', 'red'],
                                };
                            }
                        @endphp
                        <flux:table.row :key="$detail->id">
                            <flux:table.cell class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">
                                {{ $index + 1 }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $detail->siswa->user->nama }}</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 tabular-nums">{{ $detail->siswa->nis }}</p>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $kehadiranBadge[1] }}" size="sm" inset="top bottom">{{ $kehadiranBadge[0] }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($keaktifanBadge)
                                    <flux:badge color="{{ $keaktifanBadge[1] }}" size="sm" inset="top bottom">{{ $keaktifanBadge[0] }}</flux:badge>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($detail->catatan)
                                    <span class="text-xs text-slate-600 dark:text-slate-300" title="{{ $detail->catatan }}">{{ Str::limit($detail->catatan, 50) }}</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Mobile Cards (< lg) --}}
        <div class="lg:hidden divide-y divide-slate-100 dark:divide-slate-700">
            @foreach($aktivitas->detailAktivitas as $index => $detail)
                @php
                    $kehadiranBadge = match($detail->kehadiran) {
                        \App\Enums\KehadiranStatus::Hadir => ['Hadir', 'green'],
                        \App\Enums\KehadiranStatus::Izin  => ['Izin', 'amber'],
                        \App\Enums\KehadiranStatus::Sakit => ['Sakit', 'amber'],
                        \App\Enums\KehadiranStatus::Alpa  => ['Alpa', 'red'],
                        default => ['-', 'zinc'],
                    };
                    $keaktifanBadge = null;
                    if ($detail->kehadiran === \App\Enums\KehadiranStatus::Hadir && $detail->keaktifan) {
                        $keaktifanBadge = match($detail->keaktifan) {
                            \App\Enums\Keaktifan::SangatAktif => ['S. Aktif', 'green'],
                            \App\Enums\Keaktifan::Aktif => ['Aktif', 'blue'],
                            \App\Enums\Keaktifan::Cukup => ['Cukup', 'amber'],
                            \App\Enums\Keaktifan::Pasif => ['Pasif', 'red'],
                        };
                    }
                @endphp
                <div class="p-3 flex gap-2.5" wire:key="detail-{{ $detail->id }}">
                    {{-- Number indicator --}}
                    <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 tabular-nums">{{ $index + 1 }}</span>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        {{-- Row 1: Name + NIS --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $detail->siswa->user->nama }}</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 tabular-nums">{{ $detail->siswa->nis }}</p>
                            </div>
                        </div>

                        {{-- Row 2: Badges --}}
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <flux:badge color="{{ $kehadiranBadge[1] }}" size="sm">{{ $kehadiranBadge[0] }}</flux:badge>
                            @if($keaktifanBadge)
                                <flux:badge color="{{ $keaktifanBadge[1] }}" size="sm">{{ $keaktifanBadge[0] }}</flux:badge>
                            @else
                                <span class="text-[10px] text-slate-400">-</span>
                            @endif
                        </div>

                        {{-- Row 3: Catatan --}}
                        @if($detail->catatan)
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                                <flux:icon name="chat-bubble-left" class="w-3 h-3 inline -mt-0.5 mr-0.5 text-slate-400" />
                                {{ $detail->catatan }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- ═══ Delete Confirmation Modal (Flux UI) ═══ --}}
    <flux:modal wire:model.self="showDeleteModal" class="min-w-[22rem] max-w-sm">
        <div class="space-y-5">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-50 dark:bg-red-950/50 rounded-full ring-4 ring-red-100 dark:ring-red-900/30">
                <flux:icon name="exclamation-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
            <div class="text-center space-y-2">
                <flux:heading size="lg">Hapus Aktivitas ini?</flux:heading>
                <flux:text class="font-medium line-clamp-2">"{{ $aktivitas->topik }}"</flux:text>
                <flux:text class="!text-xs !text-slate-500 dark:!text-slate-400">
                    Data kehadiran dan keaktifan akan terhapus permanen
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Batal</flux:button>
                </flux:modal.close>
                <flux:button
                    wire:click="deleteAktivitas"
                    wire:loading.attr="disabled"
                    variant="danger"
                    class="cursor-pointer"
                >
                    <flux:icon wire:loading wire:target="deleteAktivitas" name="arrow-path" class="w-4 h-4 animate-spin" />
                    Hapus
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>
