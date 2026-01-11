<div class="space-y-3 overflow-x-hidden">
    {{-- Greeting - Compact --}}
    <div>
        <h1 class="text-xl font-bold text-teal-900 dark:text-white">Hai, {{ explode(' ', auth()->user()->name ?? 'Siswa')[0] }}!</h1>
        @if($siswa && $siswa->kelas)
            <p class="text-sm text-teal-600 dark:text-teal-300">Kelas {{ $siswa->kelas->tingkat_kelas }}-{{ $siswa->kelas->grup_kelas }}</p>
        @endif
    </div>

    @if($siswa)
        {{-- Motivational Message - Compact --}}
        @php $message = $this->motivationalMessage; @endphp
        <div class="p-3 bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/50 dark:to-emerald-900/50 rounded-xl border border-teal-200 dark:border-teal-700/90">
            <div class="flex items-center gap-2">
                <flux:icon name="light-bulb" class="w-5 h-5 text-teal-500 flex-shrink-0" />
                <p class="text-sm text-teal-700 dark:text-teal-200">{{ $message['text'] }}</p>
            </div>
        </div>

        {{-- Stats - Inline compact card --}}
        <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-teal-200 dark:border-slate-700/90 overflow-hidden">
            <div class="flex">
                <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-slate-700/90">
                    <div class="text-lg font-bold text-teal-900 dark:text-white">{{ number_format($siswa->getAttendancePercentage(), 0) }}%</div>
                    <div class="text-[10px] text-teal-600 dark:text-teal-300">Kehadiran</div>
                </div>
                <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-slate-700/90">
                    <div class="text-lg font-bold text-teal-900 dark:text-white">{{ number_format($siswa->getAverageGrade() ?? 0, 1) }}</div>
                    <div class="text-[10px] text-teal-600 dark:text-teal-300">Nilai</div>
                </div>
                <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-slate-700/90">
                    <div class="text-lg font-bold text-teal-900 dark:text-white">{{ number_format($siswa->getAverageParticipation() ?? 0, 1) }}</div>
                    <div class="text-[10px] text-teal-600 dark:text-teal-300">Partisipasi</div>
                </div>
                <div class="flex-1 py-3 text-center">
                    <div class="text-lg font-bold text-orange-500">{{ $this->attendanceStreak }}</div>
                    <div class="text-[10px] text-teal-600 dark:text-teal-300">🔥 Streak</div>
                </div>
            </div>
        </div>

        {{-- Quick links - Compact buttons --}}
        <div class="flex gap-2">
            <a href="{{ route('student.kehadiran') }}" wire:navigate
               class="flex-1 py-2.5 text-center text-sm font-medium text-white bg-teal-600 rounded-lg">
                Kehadiran
            </a>
            <a href="{{ route('student.nilai') }}" wire:navigate
               class="flex-1 py-2.5 text-center text-sm font-medium text-teal-700 dark:text-teal-100 bg-teal-100 dark:bg-teal-900/80 rounded-lg">
                Nilai
            </a>
        </div>

        {{-- Subject Performance - Compact --}}
        @if($this->performancePerMapel->isNotEmpty())
        <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-teal-200 dark:border-slate-700/90 overflow-hidden">
            <div class="px-3 py-2 border-b border-teal-100 dark:border-slate-700/90">
                <span class="text-sm font-semibold text-teal-900 dark:text-white">Performa per Mapel</span>
            </div>
            <div class="p-3 space-y-3">
                @foreach($this->performancePerMapel as $data)
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-teal-700 dark:text-teal-200 truncate mr-2">{{ $data['nama_mapel'] }}</span>
                            <span class="font-bold text-teal-900 dark:text-white flex-shrink-0">{{ number_format($data['avg_nilai'], 1) }}</span>
                        </div>
                        <div class="w-full bg-teal-100 dark:bg-slate-700/80 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $data['avg_nilai'] >= 80 ? 'bg-emerald-500' : ($data['avg_nilai'] >= 60 ? 'bg-amber-500' : 'bg-red-500') }}"
                                 style="width: {{ min($data['avg_nilai'], 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recent activities - Compact list --}}
        <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-teal-200 dark:border-slate-700/90 overflow-hidden">
            <div class="px-3 py-2 border-b border-teal-100 dark:border-slate-700/90 flex items-center justify-between">
                <span class="text-sm font-semibold text-teal-900 dark:text-white">Aktivitas Terbaru</span>
                <span class="text-[10px] text-teal-500">{{ $recentAktivitas->count() }} item</span>
            </div>
            <div class="divide-y divide-teal-50 dark:divide-slate-700/80">
                @forelse($recentAktivitas as $detail)
                    <div class="p-3">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-xs text-teal-500 dark:text-teal-300">{{ $detail->aktivitasPembelajaran->tanggal->format('d M') }}</span>
                            @php
                                $badgeColors = [
                                    'Hadir' => 'bg-emerald-500',
                                    'Izin' => 'bg-blue-500',
                                    'Sakit' => 'bg-amber-500',
                                    'Alpa' => 'bg-red-500',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 text-[10px] font-medium text-white rounded {{ $badgeColors[$detail->kehadiran] ?? 'bg-teal-500' }}">
                                {{ $detail->kehadiran }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-teal-900 dark:text-white truncate">{{ $detail->aktivitasPembelajaran->topik }}</p>
                        <div class="flex items-center gap-2 mt-0.5 text-xs text-teal-600 dark:text-teal-300">
                            <span class="truncate">{{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }}</span>
                            @if($detail->nilai)
                                <span class="flex-shrink-0">· Nilai: <b class="text-teal-900 dark:text-white">{{ $detail->nilai }}</b></span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center">
                        <flux:icon name="inbox" class="w-8 h-8 mx-auto text-teal-300 dark:text-teal-500 mb-2" />
                        <p class="text-sm text-teal-500 dark:text-teal-300">Belum ada aktivitas</p>
                    </div>
                @endforelse
            </div>
            @if($recentAktivitas->isNotEmpty())
                <a href="{{ route('student.kehadiran') }}" wire:navigate
                   class="block p-2 text-center text-xs text-teal-600 dark:text-teal-300 hover:bg-teal-50 dark:hover:bg-slate-800/50 border-t border-teal-100 dark:border-slate-700/90">
                    Lihat Semua →
                </a>
            @endif
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
