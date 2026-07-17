        <x-ui.card variant="teacher" title="Jenis Laporan">
            <div class="flex flex-col sm:flex-row gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" wire:model.live="reportType" value="student" class="peer hidden" />
                    <div
                        class="p-3 rounded-lg border-2 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600">
                        <div class="flex items-center gap-3">
                            <flux:icon name="user" class="w-5 h-5 text-blue-500" />
                            <div>
                                <div class="font-medium text-sm text-slate-900 dark:text-white">Laporan Siswa</div>
                                <div class="text-xs text-slate-500">Laporan individu per siswa</div>
                            </div>
                        </div>
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" wire:model.live="reportType" value="class" class="peer hidden" />
                    <div
                        class="p-3 rounded-lg border-2 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600">
                        <div class="flex items-center gap-3">
                            <flux:icon name="user-group" class="w-5 h-5 text-emerald-500" />
                            <div>
                                <div class="font-medium text-sm text-slate-900 dark:text-white">Laporan Kelas</div>
                                <div class="text-xs text-slate-500">Rekap seluruh siswa per mata pelajaran</div>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </x-ui.card>
