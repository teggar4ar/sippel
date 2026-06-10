<div class="space-y-3 overflow-x-hidden">

    {{-- Success toast (shown via Livewire dispatch event) --}}
    <div
        x-data="{ show: false }"
        x-on:profile-saved.window="show = true; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="flex items-center gap-3 px-4 py-3 bg-emerald-50 dark:bg-emerald-900/40 border border-emerald-200 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-200 rounded-xl text-sm"
        style="display: none;"
    >
        <flux:icon name="check-circle" class="w-5 h-5 text-emerald-500 shrink-0" />
        <span class="font-medium">Profil berhasil diperbarui.</span>
        <button x-on:click="show = false" class="ml-auto text-emerald-500 hover:text-emerald-700 cursor-pointer">
            <flux:icon name="x-mark" class="w-4 h-4" />
        </button>
    </div>
    {{-- Header --}}
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Profil Saya</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Kelola informasi data diri dan pengaturan keamanan akun Anda</p>
    </div>

    {{-- Main Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        {{-- Card header --}}
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700">
            <div class="flex items-center gap-2">
                <flux:icon name="user-circle" class="w-5 h-5 text-blue-500" />
                <span class="text-sm font-semibold text-slate-900 dark:text-white">Data Guru dan Informasi Akun</span>
            </div>
        </div>

        {{-- Form --}}
        <form wire:submit="updateProfile" class="p-4 sm:p-6 space-y-6">
            {{-- Profile Info Section --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input
                    label="Nama"
                    wire:model="nama"
                    readonly
                    disabled
                    class="bg-slate-50 dark:bg-slate-900/50 cursor-not-allowed"
                />
                <flux:input
                    label="Email"
                    type="email"
                    wire:model="email"
                    placeholder="email@contoh.com"
                />
            </div>

            {{-- Divider --}}
            <div class="border-t border-slate-100 dark:border-slate-700"></div>

            {{-- Password Section --}}
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Ubah Password</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">(Kosongkan jika tidak ingin mengubah password)</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        label="Password Lama"
                        type="password"
                        viewable
                        wire:model="current_password"
                        placeholder="Masukkan password lama"
                        autocomplete="current-password"
                    />
                    <flux:input
                        label="Password Baru"
                        type="password"
                        viewable
                        wire:model="new_password"
                        placeholder="Minimal 8 karakter"
                        autocomplete="new-password"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        label="Konfirmasi Password Baru"
                        type="password"
                        viewable
                        wire:model="new_password_confirmation"
                        placeholder="Ulangi password baru"
                        autocomplete="new-password"
                    />
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end pt-2">
                <flux:button
                    type="submit"
                    variant="primary"
                    color="blue"
                    class="cursor-pointer"
                    wire:loading.attr="disabled"
                >
                    <flux:icon wire:loading wire:target="updateProfile" name="arrow-path" class="w-4 h-4 animate-spin" />
                    <span wire:loading.remove wire:target="updateProfile">Simpan Perubahan</span>
                    <span wire:loading wire:target="updateProfile">Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
