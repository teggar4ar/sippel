<div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
    {{-- Class List Sidebar --}}
    <div class="lg:col-span-1">
        <x-filament::section>
            <x-slot name="heading">Pilih Kelas</x-slot>

            <div class="space-y-2">
                @foreach($this->currentClasses as $kelas)
                    <button
                        type="button"
                        wire:click="selectKelas({{ $kelas->id }})"
                        class="w-full text-left px-3 py-2 rounded-lg transition
                            {{ $this->selectedKelasId === $kelas->id
                                ? 'bg-primary-500 text-white'
                                : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white' }}"
                    >
                        <div class="font-medium">{{ $kelas->nama_lengkap }}</div>
                        <div class="text-xs {{ $this->selectedKelasId === $kelas->id ? 'text-primary-100' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $kelas->siswa->count() }} siswa
                            @if($kelas->isGraduating())
                                <span class="ml-1 text-warning-500">(Lulus)</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </x-filament::section>
    </div>

    {{-- Student List --}}
    <div class="lg:col-span-3">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-users class="h-5 w-5 text-primary-500" />
                        Siswa Kelas {{ $this->selectedKelas?->nama_lengkap ?? '' }}
                    </div>
                    @if($this->selectedKelas)
                        <div class="flex gap-2">
                            @if(!$this->selectedKelas->isGraduating())
                                <button
                                    type="button"
                                    wire:click="selectAllNaik"
                                    class="text-xs px-2 py-1 rounded bg-success-100 text-success-700 hover:bg-success-200 dark:bg-success-500/20 dark:text-success-400"
                                >
                                    Semua Naik
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="selectAllLulus"
                                    class="text-xs px-2 py-1 rounded bg-primary-100 text-primary-700 hover:bg-primary-200 dark:bg-primary-500/20 dark:text-primary-400"
                                >
                                    Semua Lulus
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="selectAllTinggal"
                                class="text-xs px-2 py-1 rounded bg-warning-100 text-warning-700 hover:bg-warning-200 dark:bg-warning-500/20 dark:text-warning-400"
                            >
                                Semua Tinggal
                            </button>
                        </div>
                    @endif
                </div>
            </x-slot>

            @if($this->selectedKelas)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="pb-2 text-left font-medium text-gray-500 dark:text-gray-400">NIS</th>
                                <th class="pb-2 text-left font-medium text-gray-500 dark:text-gray-400">Nama</th>
                                <th class="pb-2 text-left font-medium text-gray-500 dark:text-gray-400">Keputusan</th>
                                <th class="pb-2 text-left font-medium text-gray-500 dark:text-gray-400">Kelas Baru</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @forelse($this->studentsInSelectedKelas as $siswa)
                                <tr>
                                    <td class="py-3 text-gray-600 dark:text-gray-300">
                                        {{ $siswa->nis }}
                                    </td>
                                    <td class="py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $siswa->user?->name ?? '-' }}
                                    </td>
                                    <td class="py-3">
                                        <x-filament::input.wrapper>
                                            <x-filament::input.select wire:model.live="data.studentDecisions.{{ $siswa->id }}">
                                                @if($this->selectedKelas->isGraduating())
                                                    <option value="lulus">🎓 Lulus</option>
                                                    <option value="tinggal">🔄 Tinggal Kelas</option>
                                                @else
                                                    <option value="naik">⬆️ Naik Kelas</option>
                                                    <option value="tinggal">🔄 Tinggal Kelas</option>
                                                @endif
                                            </x-filament::input.select>
                                        </x-filament::input.wrapper>
                                    </td>
                                    <td class="py-3 text-gray-600 dark:text-gray-300">
                                        @php
                                            $decision = $this->data['studentDecisions'][$siswa->id] ?? 'naik';
                                            $currentKelas = $this->selectedKelas;
                                        @endphp
                                        @if($decision === 'lulus')
                                            <span class="text-danger-600 dark:text-danger-400">Dihapus (Lulus)</span>
                                        @elseif($decision === 'naik')
                                            @php $nextTingkat = $currentKelas->getNextTingkatKelas(); @endphp
                                            {{ $nextTingkat }}{{ $currentKelas->grup_kelas }}
                                        @elseif($decision === 'tinggal' && $currentKelas->tingkat_kelas == 7)
                                            <span class="text-warning-600 dark:text-warning-400">Perlu assign manual</span>
                                        @else
                                            {{ $currentKelas->tingkat_kelas }}{{ $currentKelas->grup_kelas }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-500 dark:text-gray-400">
                                        Tidak ada siswa di kelas ini
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                    Pilih kelas dari daftar di samping
                </p>
            @endif
        </x-filament::section>
    </div>
</div>
