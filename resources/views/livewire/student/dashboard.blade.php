<div class="space-y-4 overflow-x-hidden">
    {{-- Greeting --}}
    <div>
        <h1 class="text-xl font-bold text-teal-900 dark:text-white">Hai, {{ explode(' ', auth()->user()->name ?? 'Siswa')[0] }}!</h1>
        @if($siswa && $contextKelas)
            <p class="text-sm text-teal-600 dark:text-teal-300">Kelas {{ $contextKelas->tingkat_kelas }}-{{ $contextKelas->grup_kelas }}</p>
        @endif
    </div>

    @if($siswa)
        {{-- Motivational Message --}}
        @php $message = $this->motivationalMessage; @endphp
        <div class="p-3 bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/50 dark:to-emerald-900/50 rounded-xl border border-teal-200 dark:border-teal-700/90">
            <div class="flex items-center gap-2">
                <flux:icon name="light-bulb" class="w-5 h-5 text-teal-500 flex-shrink-0" />
                <p class="text-sm text-teal-700 dark:text-teal-200">{{ $message['text'] }}</p>
            </div>
        </div>

        {{-- Widget Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            {{-- Kehadiran --}}
            <div class="bg-emerald-100/80 dark:bg-emerald-900/30 rounded-xl p-3 flex flex-col">
                <div class="flex items-start justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Kehadiran</p>
                    <flux:icon name="calendar-days" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 leading-none">{{ number_format($attendancePercentage, 0) }}%</p>
            </div>
            {{-- Partisipasi --}}
            <div class="bg-amber-100/80 dark:bg-amber-900/30 rounded-xl p-3 flex flex-col">
                <div class="flex items-start justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">Partisipasi</p>
                    <flux:icon name="star" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                </div>
                <p class="text-lg font-bold text-slate-900 dark:text-white mt-2 leading-none">{{ $averageParticipationLabel }}</p>
            </div>
            {{-- Total Mapel --}}
            <div class="bg-blue-100/80 dark:bg-blue-900/30 rounded-xl p-3 flex flex-col">
                <div class="flex items-start justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-400">Total Mapel</p>
                    <flux:icon name="book-open" class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 leading-none">{{ $totalMapel }}</p>
            </div>
            {{-- Streak --}}
            <div class="bg-orange-100/80 dark:bg-orange-900/30 rounded-xl p-3 flex flex-col">
                <div class="flex items-start justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-400">Streak</p>
                    <flux:icon name="fire" class="w-4 h-4 text-orange-600 dark:text-orange-400 shrink-0" />
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2 leading-none">{{ $this->attendanceStreak }} <span class="text-xs font-medium text-orange-600 dark:text-orange-400">hari</span></p>
            </div>
        </div>


        {{-- Main Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            {{-- Aktivitas Terkini --}}
            <div class="lg:col-span-3 bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-teal-200 dark:border-slate-700/90 overflow-hidden">
                <div class="px-3 py-2 border-b border-teal-100 dark:border-slate-700/90 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-semibold text-teal-900 dark:text-white">Aktivitas Terkini</span>
                        <p class="text-[10px] text-teal-500 dark:text-teal-300">7 Hari Terakhir</p>
                    </div>
                    <a href="{{ route('student.riwayat') }}" wire:navigate
                       class="inline-flex items-center gap-1 text-xs font-medium text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 transition-colors cursor-pointer">
                        Lihat Semua
                        <flux:icon name="arrow-right" class="w-3 h-3" />
                    </a>
                </div>

                @if($recentAktivitas->isNotEmpty())
                    {{-- Desktop Table --}}
                    <div class="hidden lg:block p-3 overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Tanggal</flux:table.column>
                                <flux:table.column>Mapel</flux:table.column>
                                <flux:table.column>Topik</flux:table.column>
                                <flux:table.column>Kehadiran</flux:table.column>
                                <flux:table.column>Partisipasi</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($recentAktivitas as $detail)
                                    @php
                                        $kehadiranColor = match($detail->kehadiran->value) {
                                            'hadir' => 'lime',
                                            'izin' => 'blue',
                                            'sakit' => 'amber',
                                            'alpa' => 'red',
                                            default => 'zinc',
                                        };
                                        $partisipasiColor = match($detail->label_partisipasi) {
                                            'Sangat Aktif' => 'lime',
                                            'Aktif' => 'teal',
                                            'Cukup' => 'amber',
                                            'Pasif' => 'red',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:table.row :key="$detail->id">
                                        <flux:table.cell class="whitespace-nowrap text-teal-700 dark:text-teal-200">
                                            <div class="text-xs tabular-nums">{{ $detail->aktivitasPembelajaran->tanggal->translatedFormat('d M Y') }}</div>
                                            <div class="text-[10px] text-teal-500 dark:text-teal-300 tabular-nums">{{ $detail->aktivitasPembelajaran->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</div>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <span class="text-xs text-teal-700 dark:text-teal-200">
                                                {{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }}
                                            </span>
                                        </flux:table.cell>
                                        <flux:table.cell class="max-w-[10rem]">
                                            <span class="block truncate text-xs text-teal-800 dark:text-teal-100" title="{{ $detail->aktivitasPembelajaran->topik }}">
                                                {{ $detail->aktivitasPembelajaran->topik }}
                                            </span>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge size="sm" color="{{ $kehadiranColor }}">{{ $detail->kehadiran->label() }}</flux:badge>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge size="sm" color="{{ $partisipasiColor }}">{{ $detail->label_partisipasi }}</flux:badge>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>

                    {{-- Mobile Cards --}}
                    <div class="lg:hidden divide-y divide-teal-50 dark:divide-slate-700/80">
                        @foreach($recentAktivitas as $detail)
                            @php
                                $kehadiranColor = match($detail->kehadiran->value) {
                                    'hadir' => 'lime',
                                    'izin' => 'blue',
                                    'sakit' => 'amber',
                                    'alpa' => 'red',
                                    default => 'zinc',
                                };
                                $partisipasiColor = match($detail->label_partisipasi) {
                                    'Sangat Aktif' => 'lime',
                                    'Aktif' => 'teal',
                                    'Cukup' => 'amber',
                                    'Pasif' => 'red',
                                    default => 'zinc',
                                };
                            @endphp
                            <div class="p-3" wire:key="recent-{{ $detail->id }}">
                                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                    <span class="text-xs font-bold tabular-nums text-teal-700 dark:text-teal-200">
                                        {{ $detail->aktivitasPembelajaran->tanggal->translatedFormat('d M') }}
                                    </span>
                                    <span class="text-[10px] tabular-nums text-teal-500 dark:text-teal-300">
                                        {{ $detail->aktivitasPembelajaran->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB
                                    </span>
                                    <span class="text-[10px] font-medium px-1.5 py-0.5 bg-teal-50 dark:bg-slate-700/80 text-teal-700 dark:text-teal-200 rounded">
                                        {{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-teal-900 dark:text-white leading-snug mb-2 truncate" title="{{ $detail->aktivitasPembelajaran->topik }}">
                                    {{ $detail->aktivitasPembelajaran->topik }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <flux:badge size="sm" color="{{ $kehadiranColor }}">{{ $detail->kehadiran->label() }}</flux:badge>
                                    <flux:badge size="sm" color="{{ $partisipasiColor }}">{{ $detail->label_partisipasi }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center">
                        <flux:icon name="inbox" class="w-8 h-8 mx-auto text-teal-300 dark:text-teal-500 mb-2" />
                        <p class="text-sm text-teal-500 dark:text-teal-300">Belum ada aktivitas dalam 7 hari terakhir</p>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-2 space-y-4">
                {{-- Top Performa Mapel --}}
                <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-teal-200 dark:border-slate-700/90 overflow-hidden">
                    <div class="px-3 py-2 border-b border-teal-100 dark:border-slate-700/90">
                        <span class="text-sm font-semibold text-teal-900 dark:text-white">Top 3 Mapel Paling Aktif</span>
                    </div>
                    @if($this->topPerformaMapel->isNotEmpty())
                        <div class="p-3 space-y-3">
                            @foreach($this->topPerformaMapel as $data)
                                @php
                                    $partisipasiColor = match($data['partisipasi_label']) {
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
                                            <span class="text-xs text-teal-700 dark:text-teal-200">Partisipasi</span>
                                            <flux:badge size="sm" color="{{ $partisipasiColor }}">{{ $data['partisipasi_label'] }}</flux:badge>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center">
                            <flux:icon name="chart-bar" class="w-8 h-8 mx-auto text-teal-300 dark:text-teal-500 mb-2" />
                            <p class="text-sm text-teal-500 dark:text-teal-300">Belum ada data performa mapel</p>
                        </div>
                    @endif
                </div>

                {{-- Panduan Indikator Partisipasi --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-teal-200 dark:border-slate-700/90 overflow-hidden">
                    <div class="px-4 pt-4 pb-2">
                        <p class="text-[11px] font-medium text-teal-400 dark:text-teal-300 uppercase tracking-widest">Panduan Indikator</p>
                        <h2 class="text-sm font-semibold text-teal-900 dark:text-white mt-0.5">Tingkat Partisipasi</h2>
                    </div>

                    <div class="px-4 pb-4 mt-2 space-y-0 divide-y divide-teal-100 dark:divide-slate-700/90">
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="w-2 h-2 rounded-full bg-lime-500 shrink-0 mt-0.5"></span>
                            <div>
                                <span class="text-[11px] font-semibold text-lime-700 dark:text-lime-300">Sangat Aktif</span>
                                <p class="text-xs text-teal-500 dark:text-teal-300 mt-0.5">Proaktif, memimpin diskusi, sering bertanya.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="w-2 h-2 rounded-full bg-teal-500 shrink-0 mt-0.5"></span>
                            <div>
                                <span class="text-[11px] font-semibold text-teal-700 dark:text-teal-300">Aktif</span>
                                <p class="text-xs text-teal-500 dark:text-teal-300 mt-0.5">Fokus, merespons pertanyaan dengan baik.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0 mt-0.5"></span>
                            <div>
                                <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-300">Cukup</span>
                                <p class="text-xs text-teal-500 dark:text-teal-300 mt-0.5">Mendengarkan, namun jarang mengambil inisiatif.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0 mt-0.5"></span>
                            <div>
                                <span class="text-[11px] font-semibold text-rose-600 dark:text-rose-300">Pasif</span>
                                <p class="text-xs text-teal-500 dark:text-teal-300 mt-0.5">Kurang fokus, pasif, atau tidak merespons.</p>
                            </div>
                        </div>
                    </div>
                </div>
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
