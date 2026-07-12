<div class="space-y-4 overflow-x-hidden">
    {{-- Greeting --}}
    <x-ui.section-heading
        variant="student"
        title="Hai, {{ explode(' ', auth()->user()->name ?? 'Siswa')[0] }}!"
        subtitle="{{ ($this->siswa && $this->contextKelas) ? 'Kelas '.$this->contextKelas->tingkat_kelas.'-'.$this->contextKelas->grup_kelas : null }}" />

    @if($this->siswa)
        {{-- Motivational Message --}}
        @php $message = $this->motivationalMessage; @endphp
        <div class="p-3 bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/50 dark:to-emerald-900/50 rounded-xl border border-teal-200 dark:border-teal-700/90">
            <div class="flex items-center gap-2">
                <flux:icon name="light-bulb" class="w-5 h-5 text-teal-500 flex-shrink-0" />
                <p class="text-sm text-teal-700 dark:text-teal-200">{{ $message['text'] }}</p>
            </div>
        </div>
        <x-ui.card variant="student">
            {{-- Filter Area --}}
            <div class="flex flex-col sm:flex-row sm:items-end gap-3 mb-4">
                <div class="flex items-center gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-teal-600 dark:text-teal-400 mb-1">Dari</label>
                        <input
                            type="date"
                            wire:model.live="tanggalMulai"
                            class="w-full sm:w-auto px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-teal-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 transition"
                        />
                    </div>
                    <span class="text-teal-400 dark:text-teal-500 mt-5">—</span>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-teal-600 dark:text-teal-400 mb-1">Sampai</label>
                        <input
                            type="date"
                            wire:model.live="tanggalSelesai"
                            class="w-full sm:w-auto px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-teal-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 transition"
                        />
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <button
                        wire:click="$set('filterCepat', 'semester')"
                        class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors cursor-pointer
                            {{ $filterCepat === 'semester' ? 'bg-teal-100 text-teal-800 dark:bg-teal-800/40 dark:text-teal-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600' }}">
                        Semester
                    </button>
                    <button
                        wire:click="$set('filterCepat', 'bulan')"
                        class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors cursor-pointer
                            {{ $filterCepat === 'bulan' ? 'bg-teal-100 text-teal-800 dark:bg-teal-800/40 dark:text-teal-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600' }}">
                        Bulan Ini
                    </button>
                    <button
                        wire:click="$set('filterCepat', 'minggu')"
                        class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors cursor-pointer
                            {{ $filterCepat === 'minggu' ? 'bg-teal-100 text-teal-800 dark:bg-teal-800/40 dark:text-teal-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600' }}">
                        Minggu Ini
                    </button>
                </div>
            </div>

            {{-- Summary Cards --}}
            @php $stats = $this->stats; @endphp
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <x-ui.metric-card accent="emerald" icon="check-circle" label="Hadir" :value="$stats['hadir']" />
                <x-ui.metric-card accent="blue" icon="clock" label="Izin" :value="$stats['izin']" />
                <x-ui.metric-card accent="amber" icon="exclamation-circle" label="Sakit" :value="$stats['sakit']" />
                <x-ui.metric-card accent="rose" icon="x-circle" label="Alpa" :value="$stats['alpa']" />
            </div>
        </x-ui.card>
        {{-- Main Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            {{-- Left Column: Heatmap + Widgets --}}
            <div class="lg:col-span-3 space-y-4">

            {{-- Attendance Heatmap --}}
            @php $heatmap = $this->heatmapData; @endphp
            <x-ui.card variant="student" title="Peta Kehadiran" flush class="self-start h-fit">

                @if(!$heatmap['enrolled'])
                    <div class="p-6 text-center">
                        <flux:icon name="exclamation-triangle" class="w-8 h-8 mx-auto text-amber-400 dark:text-amber-500 mb-2" />
                        <p class="text-sm text-teal-500 dark:text-teal-300">Kamu belum terdaftar di kelas untuk tahun ajaran ini</p>
                    </div>
                @else
                    {{-- Legend --}}
                    <div class="flex flex-wrap gap-x-3 gap-y-1.5 px-4 py-3 text-xs text-teal-600 dark:text-teal-400">
                        <span class="flex items-center gap-1 whitespace-nowrap">
                            <span class="size-3 rounded-sm bg-emerald-500 shrink-0"></span> Hadir
                        </span>
                        <span class="flex items-center gap-1 whitespace-nowrap">
                            <span class="size-3 rounded-sm bg-red-400 shrink-0"></span> Tidak Hadir
                        </span>
                        <span class="flex items-center gap-1 whitespace-nowrap">
                            <span class="size-3 rounded-sm bg-amber-400 shrink-0"></span> Campuran
                        </span>
                        <span class="flex items-center gap-1 whitespace-nowrap">
                            <span class="size-3 rounded-sm bg-gray-200 dark:bg-slate-700 shrink-0"></span> Kosong
                        </span>
                        <span class="flex items-center gap-1 whitespace-nowrap">
                            <span class="size-3 rounded-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-600 shrink-0"></span> Akan Datang
                        </span>
                    </div>

                    @php
                        $cellColors = [
                            'hadir' => 'bg-emerald-500',
                            'absent' => 'bg-red-400',
                            'incomplete' => 'bg-amber-400',
                            'no_activity' => 'bg-gray-200 dark:bg-slate-700',
                            'future' => 'bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-600',
                            'blank' => '',
                        ];
                        $statusLabels = [
                            'hadir' => 'Hadir',
                            'absent' => 'Tidak Hadir',
                            'incomplete' => 'Campuran',
                            'no_activity' => 'Tidak ada aktivitas',
                            'future' => 'Akan datang',
                            'blank' => '',
                        ];
                    @endphp
                {{-- WRAPPER SCROLLING HORIZONTAL --}}
                <div class="overflow-x-auto scroll-smooth w-full pb-4">

                    {{-- DITAMBAH w-max min-w-full agar ukuran grid tidak menyusut di layar HP dan memicu scroll --}}
                    <div class="flex px-4 w-max min-w-full h-fit self-start">

                        {{-- Day labels column --}}
                        <div class="shrink-0 mr-2">
                            {{-- Spacer ini SEKARANG SAMA PERSIS dengan tinggi Header Bulan + Margin bawahnya --}}
                            <div class="h-5 mb-1"></div>
                            <div class="grid grid-rows-5 gap-[3px]">
                                @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum'] as $dayLabel)
                                    <div class="h-5 w-7 text-xs leading-4 text-teal-500 dark:text-teal-400 flex items-center justify-end">{{ $dayLabel }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            {{-- Month header --}}
                            <div class="flex gap-x-[3px] h-5 mb-1">
                                @foreach($heatmap['weeks'] as $week)
                                    <div class="w-5 shrink-0 relative h-full">
                                        @if($week['is_first_week_of_month'])
                                            <span class="absolute bottom-0 left-0 z-10 whitespace-nowrap text-[10px] font-semibold text-teal-600 dark:text-teal-400">{{ $week['month_label'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Flat cells grid --}}
                            <div class="grid grid-flow-col grid-rows-5 gap-[3px]" style="grid-auto-columns: 20px">
                                @foreach($heatmap['weeks'] as $week)
                                    @foreach($week['days'] as $day)
                                        <div
                                            class="size-5 rounded-sm {{ $cellColors[$day['status']] }}"
                                            title="{{ $day['formatted_date'] ? $day['day_name'].', '.$day['formatted_date'].' — '.($statusLabels[$day['status']] ?? '') : '' }}"
                                        ></div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>
                @endif
            </x-ui.card>

            {{-- Bottom Widgets: 2-column row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- Widget 1: Streak Kehadiran & Pencapaian --}}
            @php
                $streak = $this->attendanceStreak;
                $milestones = [
                    ['target' => 5,  'icon' => '⚡', 'label' => '5 Hari'],
                    ['target' => 10, 'icon' => '🔥', 'label' => '10 Hari'],
                    ['target' => 20, 'icon' => '🌟', 'label' => '20 Hari'],
                    ['target' => 30, 'icon' => '💎', 'label' => '30 Hari'],
                    ['target' => 50, 'icon' => '🏆', 'label' => '50 Hari'],
                ];
            @endphp
            <x-ui.card variant="student" title="🔥 Streak Kehadiran" flush>
                <x-slot:actions>
                    <span class="text-[10px] font-medium uppercase tracking-wider text-teal-500 dark:text-teal-400">Berturut-turut</span>
                </x-slot:actions>
                <div class="p-4">
                    {{-- Streak Counter --}}
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-teal-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-sm">
                            <span class="text-2xl font-extrabold text-white leading-none">{{ $streak }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-teal-900 dark:text-white">
                                {{ $streak }} Hari Berturut-turut
                            </p>
                            <p class="text-xs text-teal-500 dark:text-teal-400 mt-0.5">
                                @if($streak >= 50)
                                    Legendaris! Kamu luar biasa konsisten! 🏆
                                @elseif($streak >= 30)
                                    Hebat! Kamu siswa teladan! 💎
                                @elseif($streak >= 20)
                                    Keren! Pertahankan terus ya! 🌟
                                @elseif($streak >= 10)
                                    Semangat! Terus hadir ya! 🔥
                                @elseif($streak >= 5)
                                    Bagus! Lanjutkan! ⚡
                                @elseif($streak > 0)
                                    Awal yang baik! Terus hadir ya!
                                @else
                                    Yuk mulai bangun streak kehadiranmu!
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Milestone Badges --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        @foreach($milestones as $ms)
                            <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all
                                {{ $streak >= $ms['target']
                                    ? 'bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 ring-1 ring-teal-200 dark:ring-teal-700'
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500' }}">
                                <span class="{{ $streak >= $ms['target'] ? '' : 'grayscale opacity-40' }}">{{ $ms['icon'] }}</span>
                                <span>{{ $ms['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Progress to next milestone --}}
                    @php
                        $nextMilestone = collect($milestones)->first(fn ($ms) => $ms['target'] > $streak);
                    @endphp
                    @if($nextMilestone)
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-teal-600 dark:text-teal-400">Menuju {{ $nextMilestone['icon'] }} {{ $nextMilestone['label'] }}</span>
                                <span class="font-semibold text-teal-800 dark:text-white">{{ $streak }}/{{ $nextMilestone['target'] }}</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-teal-400 to-emerald-500 rounded-full transition-all duration-500"
                                     x-data="{ w: @js(min(round(($streak / $nextMilestone['target']) * 100), 100)) }"
                                     x-bind:style="`width: ${w}%`"></div>
                            </div>
                        </div>
                    @else
                        <div class="mt-3 text-center">
                            <p class="text-xs text-teal-500 dark:text-teal-400 font-medium">🎉 Semua milestone tercapai! Luar biasa!</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            {{-- Widget 2: Panduan Indikator Keaktifan --}}
            <x-ui.card variant="student" title="Indikator Tingkat Keaktifan" subtitle="Panduan" flush>
                <div class="px-4 py-3 divide-y divide-teal-50 dark:divide-slate-800">
                    @foreach([
                        ['color' => 'bg-emerald-500', 'label' => 'Sangat Aktif', 'textColor' => 'text-emerald-600 dark:text-emerald-400', 'desc' => 'Proaktif, memimpin diskusi, sering bertanya.'],
                        ['color' => 'bg-blue-500',    'label' => 'Aktif',        'textColor' => 'text-blue-600 dark:text-blue-400',    'desc' => 'Fokus, merespons pertanyaan dengan baik.'],
                        ['color' => 'bg-amber-400',   'label' => 'Cukup',        'textColor' => 'text-amber-600 dark:text-amber-400',   'desc' => 'Mendengarkan, jarang mengambil inisiatif.'],
                        ['color' => 'bg-rose-500',    'label' => 'Pasif',        'textColor' => 'text-rose-600 dark:text-rose-400',     'desc' => 'Kurang fokus, pasif, atau tidak merespons.'],
                    ] as $item)
                        <div class="flex items-start gap-3 py-0.5">
                            <span class="w-2.5 h-2.5 rounded-full {{ $item['color'] }} shrink-0 mt-2"></span>
                            <div>
                                <span class="text-xs font-semibold {{ $item['textColor'] }}">{{ $item['label'] }}</span>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            </div> {{-- End Bottom Widgets --}}

            </div> {{-- End Left Column --}}

            {{-- Right Column --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- Subject List --}}
                <x-ui.card variant="student" title="Mata Pelajaran Saya" flush>
                    @if($this->mataPelajaranList->isNotEmpty())
                        <div class="p-3 space-y-3 overflow-y-auto max-h-[400px]">
                            @foreach($this->mataPelajaranList as $data)
                                @php
                                    $keaktifanColor = match($data['keaktifan_label']) {
                                        'Sangat Aktif' => 'lime',
                                        'Aktif' => 'teal',
                                        'Cukup' => 'amber',
                                        'Pasif' => 'red',
                                        default => 'zinc',
                                    };
                                @endphp
                                <div class="border border-teal-100 dark:border-slate-700/90 rounded-lg p-3">
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="book-open" class="w-4 h-4 text-teal-500" />
                                        <p class="text-sm font-semibold text-teal-900 dark:text-white truncate" title="{{ $data['nama_mapel'] }}">
                                            {{ $data['nama_mapel'] }}
                                        </p>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 mt-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 text-xs text-teal-700 dark:text-teal-200">
                                                <flux:icon name="users" class="w-3.5 h-3.5" />
                                                <span class="font-medium">Kehadiran</span>
                                                <span class="font-semibold text-teal-900 dark:text-white">{{ $data['attendance_pct'] }}%</span>
                                            </div>
                                            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mt-1.5">
                                                <div class="h-full bg-emerald-500 rounded-full" x-data="{ w: @js(min($data['attendance_pct'], 100)) }" x-bind:style="`width: ${w}%`"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon name="star" class="w-3.5 h-3.5 text-amber-500" />
                                            <span class="text-xs text-teal-700 dark:text-teal-200">Keaktifan</span>
                                            <flux:badge size="sm" color="{{ $keaktifanColor }}">{{ $data['keaktifan_label'] }}</flux:badge>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center">
                            <flux:icon name="chart-bar" class="w-8 h-8 mx-auto text-teal-300 dark:text-teal-500 mb-2" />
                            <p class="text-sm text-teal-500 dark:text-teal-300">Belum ada data Mata Pelajaran</p>
                        </div>
                    @endif
                </x-ui.card>
            </div>
        </div>
    @else
        {{-- No student data warning --}}
        <div class="p-4 bg-amber-50 dark:bg-amber-900/40 rounded-xl border border-amber-200 dark:border-amber-700/90">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                <p class="text-sm text-amber-800 dark:text-amber-200">Data siswa belum terhubung. Hubungi administrator.</p>
            </div>
        </div>
    @endif
</div>
