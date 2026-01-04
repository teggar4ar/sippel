<div class="space-y-3 overflow-x-hidden">
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold text-teal-900 dark:text-white">Profil Saya</h1>
        <p class="text-sm text-teal-600 dark:text-teal-400 mt-0.5">Informasi akun dan data siswa</p>
    </div>

    {{-- User Information Card --}}
    <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden">
        <div class="p-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-lg font-bold text-white">{{ substr($user->nama, 0, 1) }}</span>
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-teal-900 dark:text-white truncate">{{ $user->nama }}</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-teal-100 text-teal-700 dark:bg-teal-800 dark:text-teal-300">
                        Siswa
                    </span>
                </div>
            </div>
        </div>
        <div class="border-t border-teal-100 dark:border-teal-800">
            <div class="flex divide-x divide-teal-100 dark:divide-teal-800">
                <div class="flex-1 p-3">
                    <p class="text-[10px] font-medium text-teal-500 dark:text-teal-400">Email</p>
                    <p class="text-xs text-teal-900 dark:text-white truncate">{{ $user->email }}</p>
                </div>
                <div class="flex-1 p-3">
                    <p class="text-[10px] font-medium text-teal-500 dark:text-teal-400">Jenis Kelamin</p>
                    <p class="text-xs text-teal-900 dark:text-white">{{ $user->jenis_kelamin ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Student Information Card --}}
    @if($siswa)
        <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden">
            <div class="px-3 py-2 border-b border-teal-100 dark:border-teal-800">
                <span class="text-sm font-semibold text-teal-900 dark:text-white">Informasi Siswa</span>
            </div>
            <div class="p-3 space-y-2">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <p class="text-[10px] font-medium text-teal-500 dark:text-teal-400">NIS</p>
                        <p class="text-sm font-mono font-bold text-teal-900 dark:text-white">{{ $siswa->nis }}</p>
                    </div>
                    <div class="flex-1">
                        <p class="text-[10px] font-medium text-teal-500 dark:text-teal-400">Kelas</p>
                        <p class="text-sm font-semibold text-teal-900 dark:text-white">
                            @if($siswa->kelas)
                                {{ $siswa->kelas->nama_lengkap ?? $siswa->kelas->tingkat . '-' . $siswa->kelas->grup }}
                            @else
                                <span class="text-teal-400 italic text-xs">Belum ditentukan</span>
                            @endif
                        </p>
                    </div>
                </div>
                @if($siswa->kelas && $siswa->kelas->waliKelas)
                    <div class="pt-2 border-t border-teal-50 dark:border-teal-800">
                        <p class="text-[10px] font-medium text-teal-500 dark:text-teal-400">Wali Kelas</p>
                        <p class="text-sm text-teal-900 dark:text-white">{{ $siswa->kelas->waliKelas->nama }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Stats - Inline compact --}}
        <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden">
            <div class="px-3 py-2 border-b border-teal-100 dark:border-teal-800">
                <span class="text-sm font-semibold text-teal-900 dark:text-white">Statistik</span>
            </div>
            <div class="flex">
                <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-teal-800">
                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($siswa->getAttendancePercentage(), 0) }}%</div>
                    <div class="text-[10px] text-teal-600 dark:text-teal-400">Kehadiran</div>
                </div>
                <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-teal-800">
                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($siswa->getAverageGrade(), 1) }}</div>
                    <div class="text-[10px] text-teal-600 dark:text-teal-400">Nilai</div>
                </div>
                <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-teal-800">
                    <div class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ number_format($siswa->getAverageParticipation() ?? 0, 1) }}</div>
                    <div class="text-[10px] text-teal-600 dark:text-teal-400">Partisipasi</div>
                </div>
                <div class="flex-1 py-3 text-center">
                    <div class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ $siswa->detailAktivitas()->count() }}</div>
                    <div class="text-[10px] text-teal-600 dark:text-teal-400">Aktivitas</div>
                </div>
            </div>
        </div>
    @else
        {{-- No student data --}}
        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Data Tidak Ditemukan</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400">Hubungi administrator untuk menghubungkan akun.</p>
                </div>
            </div>
        </div>
    @endif
</div>
