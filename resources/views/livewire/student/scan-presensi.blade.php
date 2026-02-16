<div class="space-y-3"
     x-data="qrScanner()"
     x-on:stopScanning.window="stopScanning()"
     x-on:resetScanner.window="init()"
     x-on:attendance-recorded.window="setTimeout(() => { window.location.href = '{{ route('student.dashboard') }}'; }, 3000)">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-teal-900 dark:text-white">Scan Presensi</h1>
            <p class="text-xs text-teal-600 dark:text-teal-300 mt-0.5">Arahkan kamera ke QR code Anda</p>
        </div>
        <a href="{{ route('student.dashboard') }}" wire:navigate
           class="p-2 text-teal-600 dark:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900/50 rounded-lg transition-colors">
            <flux:icon name="x-mark" class="w-5 h-5" />
        </a>
    </div>

    {{-- Scanner Container --}}
    @if($scanState !== 'error' || $errorType !== 'no_qr')
    <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-teal-200 dark:border-slate-700/90 overflow-hidden">
        {{-- Camera Preview --}}
        <div class="relative aspect-square max-w-md mx-auto bg-slate-900">
            <div id="qr-reader" class="w-full h-full"></div>

            {{-- Scanner Overlay --}}
            <div class="absolute inset-0 pointer-events-none">
                {{-- Corner Guides --}}
                <div class="absolute top-8 left-8 w-12 h-12 border-t-4 border-l-4 border-teal-400"></div>
                <div class="absolute top-8 right-8 w-12 h-12 border-t-4 border-r-4 border-teal-400"></div>
                <div class="absolute bottom-8 left-8 w-12 h-12 border-b-4 border-l-4 border-teal-400"></div>
                <div class="absolute bottom-8 right-8 w-12 h-12 border-b-4 border-r-4 border-teal-400"></div>

                {{-- Center Target Line --}}
                <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 flex items-center justify-center">
                    <div class="w-3/4 h-0.5 bg-teal-400/50"></div>
                </div>
            </div>

            {{-- Loading State --}}
            <div x-show="!cameraReady && '{{ $scanState }}' === 'idle'"
                 class="absolute inset-0 flex items-center justify-center bg-slate-900/80">
                <div class="text-center">
                    <svg class="animate-spin h-10 w-10 text-teal-400 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-white">Memuat kamera...</p>
                </div>
            </div>

            {{-- Processing State --}}
            <div wire:loading.flex wire:target="processScan"
                 class="absolute inset-0 items-center justify-center bg-slate-900/90">
                <div class="text-center">
                    <svg class="animate-spin h-10 w-10 text-teal-400 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-white font-medium">Memproses...</p>
                </div>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="p-4 bg-teal-50 dark:bg-teal-900/30 border-t border-teal-200 dark:border-teal-700/50">
            <div class="flex items-start gap-2">
                <flux:icon name="information-circle" class="w-5 h-5 text-teal-600 dark:text-teal-300 flex-shrink-0 mt-0.5" />
                <div class="text-xs text-teal-700 dark:text-teal-200">
                    <p class="font-medium mb-1">Cara scan:</p>
                    <ol class="list-decimal list-inside space-y-0.5 text-[11px]">
                        <li>Buka kartu QR pribadi Anda</li>
                        <li>Arahkan kamera ke QR code</li>
                        <li>Pastikan QR berada dalam kotak scan</li>
                        <li>Tunggu hingga terdeteksi otomatis</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Success Message --}}
    @if($scanState === 'success')
    <div class="bg-emerald-50 dark:bg-emerald-900/30 rounded-xl p-4 border border-emerald-200 dark:border-emerald-700/50">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center flex-shrink-0">
                <flux:icon name="check" class="w-6 h-6 text-white" />
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-bold text-emerald-900 dark:text-emerald-100 mb-1">Berhasil!</h3>
                <p class="text-xs text-emerald-700 dark:text-emerald-200 mb-2">{{ $message }}</p>
                <p class="text-[10px] text-emerald-600 dark:text-emerald-300">Mengarahkan ke dashboard...</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Error Message --}}
    @if($scanState === 'error')
    <div class="bg-red-50 dark:bg-red-900/30 rounded-xl p-4 border border-red-200 dark:border-red-700/50">
        <div class="flex items-start gap-3 mb-3">
            <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                <flux:icon name="exclamation-triangle" class="w-6 h-6 text-white" />
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-bold text-red-900 dark:text-red-100 mb-1">
                    {{ match($errorType) {
                        'no_session' => 'Tidak Ada Sesi Aktif',
                        'session_expired' => 'Sesi Berakhir',
                        'already_scanned' => 'Sudah Absen',
                        'wrong_qr' => 'QR Salah',
                        'wrong_class' => 'Kelas Salah',
                        'no_qr' => 'QR Tidak Tersedia',
                        'invalid_qr' => 'QR Tidak Valid',
                        'rate_limit' => 'Terlalu Banyak Percobaan',
                        default => 'Gagal'
                    } }}
                </h3>
                <p class="text-xs text-red-700 dark:text-red-200">{{ $message }}</p>
            </div>
        </div>

        {{-- Retry Button (except for no_qr error) --}}
        @if($errorType !== 'no_qr')
        <button wire:click="resetScan"
                class="w-full py-2.5 px-4 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition-colors">
            <div class="flex items-center justify-center gap-2">
                <flux:icon name="arrow-path" class="w-4 h-4" />
                Scan Ulang
            </div>
        </button>
        @else
        <a href="{{ route('student.dashboard') }}" wire:navigate
           class="block w-full py-2.5 px-4 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition-colors text-center">
            Kembali ke Dashboard
        </a>
        @endif
    </div>
    @endif

    {{-- Help Card --}}
    <div class="bg-blue-50 dark:bg-blue-900/30 rounded-xl p-3 border border-blue-200 dark:border-blue-700/50">
        <div class="flex items-start gap-2">
            <flux:icon name="question-mark-circle" class="w-5 h-5 text-blue-600 dark:text-blue-300 flex-shrink-0" />
            <div class="text-xs text-blue-700 dark:text-blue-200">
                <p class="font-medium mb-1">Mengalami masalah?</p>
                <ul class="space-y-0.5 text-[11px]">
                    <li>• Pastikan kartu QR dalam kondisi baik (tidak rusak/buram)</li>
                    <li>• Coba scan dengan pencahayaan yang lebih baik</li>
                    <li>• Pastikan guru sudah membuka sesi presensi</li>
                    <li>• Hubungi guru jika masih gagal</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('qrScanner', () => ({
    html5QrCode: null,
    cameraReady: false,

    init() {
        this.startScanning();
    },

    startScanning() {
        if (this.html5QrCode) {
            this.stopScanning();
        }

        // Check if Html5Qrcode is available
        if (typeof window.Html5Qrcode === 'undefined') {
            console.error('Html5Qrcode not loaded');
            @this.set('scanState', 'error');
            @this.set('errorType', 'library_error');
            @this.set('message', 'Library QR scanner tidak tersedia. Silakan refresh halaman.');
            return;
        }

        this.html5QrCode = new window.Html5Qrcode("qr-reader");

        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0,
        };

        this.html5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText) => {
                // QR detected - send to Livewire
                this.stopScanning();
                @this.call('processScan', decodedText);
            },
            (errorMessage) => {
                // Scanning errors (most are ignorable)
            }
        ).then(() => {
            this.cameraReady = true;
        }).catch((err) => {
            console.error("Camera error:", err);
            @this.set('scanState', 'error');
            @this.set('errorType', 'camera_error');
            @this.set('message', 'Tidak dapat mengakses kamera. Pastikan izin kamera diaktifkan.');
        });
    },

    stopScanning() {
        if (this.html5QrCode) {
            this.html5QrCode.stop().then(() => {
                this.html5QrCode = null;
                this.cameraReady = false;
            }).catch((err) => {
                console.error("Stop error:", err);
            });
        }
    },

    destroy() {
        this.stopScanning();
    }
}));
</script>
@endscript
