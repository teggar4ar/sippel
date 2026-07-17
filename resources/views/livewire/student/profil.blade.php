<div class="space-y-5 overflow-x-hidden">

    {{-- Success toast (shown via Livewire dispatch event) --}}
    <div x-data="{ show: false }" x-on:student-profile-saved.window="show = true; setTimeout(() => show = false, 3000)"
        x-show="show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="flex items-center gap-3 px-4 py-3 bg-emerald-50 dark:bg-emerald-900/40 border border-emerald-200 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-200 rounded-xl text-sm"
        style="display: none;">
        <flux:icon name="check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />
        <span class="font-medium">Profil berhasil diperbarui.</span>
        <button x-on:click="show = false" class="ml-auto text-emerald-500 hover:text-emerald-700 cursor-pointer">
            <flux:icon name="x-mark" class="w-4 h-4" />
        </button>
    </div>
    {{-- Header --}}
    <x-ui.section-heading variant="student" title="Profil Saya"
        subtitle="Kelola informasi data diri dan pengaturan keamanan akun Anda" />

    {{-- Main Card --}}
    <x-ui.card variant="student" title="Data Siswa dan Informasi Akun" icon="user-circle" flush>
        {{-- Form --}}
        <form wire:submit="updateProfile" class="p-4 sm:p-6 space-y-6">
            {{-- Identity Header --}}
            <div
                class="flex flex-col sm:flex-row sm:items-center gap-4 rounded-2xl bg-teal-50/80 dark:bg-slate-800/60 border border-teal-100 dark:border-slate-700 p-4">
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-600 text-lg font-bold text-white shadow-sm shrink-0">
                    {{ mb_substr($nama ?: 'S', 0, 1) }}
                </div>
                <div class="min-w-0">
                    <p class="text-base font-semibold text-teal-950 dark:text-white truncate">{{ $nama }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-teal-700 dark:text-teal-300">
                        <span
                            class="inline-flex items-center rounded-full bg-white/80 dark:bg-slate-900/70 px-2 py-1 font-medium">NIS
                            {{ $nis }}</span>
                        <span
                            class="inline-flex items-center rounded-full bg-white/80 dark:bg-slate-900/70 px-2 py-1 font-medium">{{ $kelas }}</span>
                    </div>
                </div>
            </div>

            {{-- Profile Info Section --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input label="Nama" wire:model="nama" readonly disabled
                    class="bg-teal-50 dark:bg-slate-900/50 cursor-not-allowed" />
                <flux:input label="NIS" wire:model="nis" readonly disabled
                    class="bg-teal-50 dark:bg-slate-900/50 cursor-not-allowed" />
                <flux:input label="Kelas" wire:model="kelas" readonly disabled
                    class="bg-teal-50 dark:bg-slate-900/50 cursor-not-allowed" />
                <flux:input label="Email" type="email" wire:model="email" placeholder="email@contoh.com"
                    class:input="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 placeholder-gray-400 focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 transition" />
            </div>

            {{-- Divider --}}
            <div class="border-t border-teal-100 dark:border-slate-700/90"></div>

            {{-- Password Section --}}
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-teal-900 dark:text-white">Ubah Password</h3>
                    <p class="text-xs text-teal-500 dark:text-teal-400 mt-0.5">(Kosongkan jika tidak ingin mengubah
                        password)</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input label="Password Lama" type="password" viewable wire:model="current_password"
                        placeholder="Masukkan password lama" autocomplete="current-password"
                        class:input="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 placeholder-gray-400 focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 transition" />
                    <flux:input label="Password Baru" type="password" viewable wire:model="new_password"
                        placeholder="Minimal 8 karakter" autocomplete="new-password"
                        class:input="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 placeholder-gray-400 focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 transition" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input label="Konfirmasi Password Baru" type="password" viewable
                        wire:model="new_password_confirmation" placeholder="Ulangi password baru"
                        autocomplete="new-password"
                        class:input="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 placeholder-gray-400 focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 transition" />
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary" color="teal" class="cursor-pointer"
                    wire:loading.attr="disabled">
                    <flux:icon wire:loading wire:target="updateProfile" name="arrow-path"
                        class="w-4 h-4 animate-spin" />
                    <span wire:loading.remove wire:target="updateProfile">Simpan Perubahan</span>
                    <span wire:loading wire:target="updateProfile">Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </x-ui.card>
</div>
