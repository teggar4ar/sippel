<div class="space-y-3 overflow-x-hidden">
    {{-- Header --}}
    <div class="flex items-center gap-2">
        <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate
           class="p-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg flex-shrink-0">
            <flux:icon name="arrow-left" class="size-5" />
        </a>
        <div class="flex-1 min-w-0 overflow-hidden">
            <h1 class="text-base font-bold text-slate-900 dark:text-white leading-tight truncate">{{ $aktivitas->topik }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $aktivitas->tanggal->format('d M Y') }} · {{ $aktivitas->mataPelajaran->nama_mapel }}</p>
        </div>
        <a href="{{ route('teacher.aktivitas.edit', $aktivitas->id) }}" wire:navigate
           class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg flex-shrink-0">
            <flux:icon name="pencil" class="size-5" />
        </a>
    </div>

    {{-- Info + Stats Combined Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        {{-- Class & Student count --}}
        <div class="p-3 text-xs">
            <div class="flex justify-between gap-2">
                <span class="text-slate-500 dark:text-slate-400 truncate">{{ $aktivitas->kelas->nama_lengkap }}</span>
                <span class="text-slate-900 dark:text-white font-medium flex-shrink-0">{{ $this->stats['total'] }} siswa</span>
            </div>
        </div>

        {{-- Attendance row --}}
        <div class="flex border-t border-slate-100 dark:border-slate-700">
            <div class="flex-1 py-2 text-center border-r border-slate-100 dark:border-slate-700">
                <div class="text-sm font-bold text-emerald-600">{{ $this->stats['hadir'] }}</div>
                <div class="text-[10px] text-slate-500">Hadir</div>
            </div>
            <div class="flex-1 py-2 text-center border-r border-slate-100 dark:border-slate-700">
                <div class="text-sm font-bold text-blue-600">{{ $this->stats['izin'] }}</div>
                <div class="text-[10px] text-slate-500">Izin</div>
            </div>
            <div class="flex-1 py-2 text-center border-r border-slate-100 dark:border-slate-700">
                <div class="text-sm font-bold text-amber-600">{{ $this->stats['sakit'] }}</div>
                <div class="text-[10px] text-slate-500">Sakit</div>
            </div>
            <div class="flex-1 py-2 text-center">
                <div class="text-sm font-bold text-red-600">{{ $this->stats['alpa'] }}</div>
                <div class="text-[10px] text-slate-500">Alpa</div>
            </div>
        </div>

        {{-- Average row --}}
        <div class="flex border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/30">
            <div class="flex-1 py-2 text-center border-r border-slate-100 dark:border-slate-700">
                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $this->stats['percentage'] }}%</div>
                <div class="text-[10px] text-slate-500">Kehadiran</div>
            </div>
            <div class="flex-1 py-2 text-center border-r border-slate-100 dark:border-slate-700">
                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $this->stats['avg_nilai'] ? number_format($this->stats['avg_nilai'], 1) : '-' }}</div>
                <div class="text-[10px] text-slate-500">Nilai</div>
            </div>
            <div class="flex-1 py-2 text-center">
                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $this->stats['avg_partisipasi'] ? number_format($this->stats['avg_partisipasi'], 1) : '-' }}</div>
                <div class="text-[10px] text-slate-500">Partisipasi</div>
            </div>
        </div>

        @if($aktivitas->catatan)
            <div class="p-3 border-t border-slate-100 dark:border-slate-700">
                <p class="text-xs text-slate-600 dark:text-slate-300 break-words"><span class="text-slate-400">Catatan:</span> {{ $aktivitas->catatan }}</p>
            </div>
        @endif
    </div>

    {{-- Student List --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-900 dark:text-white">Detail Siswa</span>
            <span class="text-[10px] text-slate-500">{{ $aktivitas->detailAktivitas->count() }} siswa</span>
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700">
            @foreach($aktivitas->detailAktivitas as $detail)
                <div class="p-3" wire:key="detail-{{ $detail->id }}">
                    {{-- Row 1: Name + Badge --}}
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1 overflow-hidden">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $detail->siswa->user->nama }}</p>
                            <p class="text-[10px] text-slate-400">{{ $detail->siswa->nis }}</p>
                        </div>
                        @php
                            $badgeColors = [
                                'Hadir' => 'bg-emerald-500',
                                'Izin' => 'bg-blue-500',
                                'Sakit' => 'bg-amber-500',
                                'Alpa' => 'bg-red-500',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-[10px] font-medium text-white rounded flex-shrink-0 {{ $badgeColors[$detail->kehadiran] ?? 'bg-slate-500' }}">
                            {{ $detail->kehadiran }}
                        </span>
                    </div>
                    {{-- Row 2: Scores --}}
                    <div class="flex gap-3 mt-1.5 text-xs text-slate-500">
                        <span>Nilai: <b class="text-slate-700 dark:text-slate-200">{{ $detail->nilai ?? '-' }}</b></span>
                        <span>Part: <b class="text-slate-700 dark:text-slate-200">{{ $detail->partisipasi ? (int) $detail->partisipasi : '-' }}</b></span>
                    </div>
                    @if($detail->catatan)
                        <p class="mt-1 text-[10px] text-slate-400 truncate">{{ $detail->catatan }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Action buttons --}}
    <div class="flex gap-2 pb-4">
        <a href="{{ route('teacher.aktivitas.list') }}" wire:navigate
           class="flex-1 py-2.5 text-center text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg">
            ← Kembali
        </a>
        <a href="{{ route('teacher.aktivitas.edit', $aktivitas->id) }}" wire:navigate
           class="flex-1 py-2.5 text-center text-sm font-medium text-white bg-blue-600 rounded-lg">
            Edit
        </a>
    </div>
</div>
