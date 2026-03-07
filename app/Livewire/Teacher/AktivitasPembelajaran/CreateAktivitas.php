<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\AktivitasPembelajaran;

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.teacher')]
#[Title('Buat Aktivitas - SIPPEL Guru')]
final class CreateAktivitas extends Component
{
    // Current step (1 = activity info, 2 = attendance)
    public int $step = 1;

    // Step 1: Activity information
    public string $tanggal = '';

    // Filter untuk memilih kelas
    public ?int $tingkatKelas = null;

    public ?string $grupKelas = null;

    public ?int $mataPelajaranId = null;

    public string $topik = '';

    public string $catatan = '';

    // Auto-filled from selected subject
    public ?int $kelasId = null;

    // Step 2: Student attendance details
    public array $detailAktivitas = [];

    public function mount(): void
    {
        // Default to today's date
        $this->tanggal = now()->format('Y-m-d');

        // Validation: Check if context year is active
        $contextTahunAjaran = TahunAjaran::getContext();

        if (! $contextTahunAjaran instanceof TahunAjaran) {
            session()->flash('error', 'Tidak ada tahun ajaran yang dipilih. Silakan pilih tahun ajaran terlebih dahulu.');
            $this->redirect(route('teacher.dashboard'), navigate: true);

            return;
        }

        if (! $contextTahunAjaran->status) {
            session()->flash('error', 'Tidak dapat membuat aktivitas pada tahun ajaran yang tidak aktif. Silakan pilih tahun ajaran yang aktif.');
            $this->redirect(route('teacher.dashboard'), navigate: true);

            return;
        }

        // Validation: Check if teacher has any assigned subjects in context year
        $hasSubjects = MataPelajaran::where('guru_id', Auth::id())
            ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $contextTahunAjaran->id))
            ->exists();

        if (! $hasSubjects) {
            session()->flash('error', 'Anda belum ditugaskan sebagai guru mata pelajaran pada tahun ajaran ini. Silakan hubungi admin.');
            $this->redirect(route('teacher.dashboard'), navigate: true);
        }
    }

    #[Computed]
    public function tingkatKelasList()
    {
        $activeTahunAjaran = TahunAjaran::getContext();

        if (! $activeTahunAjaran instanceof TahunAjaran) {
            return collect();
        }

        // Get distinct tingkat kelas yang ada di tahun ajaran aktif dan guru mengajar di kelas tersebut
        return Kelas::where('tahun_ajaran_id', $activeTahunAjaran->id)
            ->whereHas('mataPelajaran', function ($q): void {
                $q->where('guru_id', Auth::id());
            })
            ->orderBy('tingkat_kelas')
            ->pluck('tingkat_kelas')
            ->unique()
            ->values();
    }

    #[Computed]
    public function grupKelasList()
    {
        if ($this->tingkatKelas === null) {
            return collect();
        }

        $activeTahunAjaran = TahunAjaran::getContext();

        if (! $activeTahunAjaran instanceof TahunAjaran) {
            return collect();
        }

        // Get distinct grup kelas untuk tingkat yang dipilih
        return Kelas::where('tahun_ajaran_id', $activeTahunAjaran->id)
            ->where('tingkat_kelas', $this->tingkatKelas)
            ->whereHas('mataPelajaran', function ($q): void {
                $q->where('guru_id', Auth::id());
            })
            ->orderBy('grup_kelas')
            ->pluck('grup_kelas')
            ->unique()
            ->values();
    }

    #[Computed]
    public function mataPelajaran()
    {
        $activeTahunAjaran = TahunAjaran::getContext();

        if (! $activeTahunAjaran instanceof TahunAjaran) {
            return collect();
        }

        $query = MataPelajaran::query()
            ->where('guru_id', Auth::id())
            ->whereHas('kelas', function ($q) use ($activeTahunAjaran): void {
                $q->where('tahun_ajaran_id', $activeTahunAjaran->id);
            })
            ->with('kelas');

        // Filter berdasarkan tingkat kelas dan grup kelas jika dipilih
        if ($this->tingkatKelas !== null) {
            $query->whereHas('kelas', function ($q): void {
                $q->where('tingkat_kelas', $this->tingkatKelas);
            });
        }

        if ($this->grupKelas !== null && $this->grupKelas !== '') {
            $query->whereHas('kelas', function ($q): void {
                $q->where('grup_kelas', $this->grupKelas);
            });
        }

        return $query->orderBy('nama_mapel')->get();
    }

    #[Computed]
    public function siswaList()
    {
        if ($this->kelasId === null || $this->kelasId === 0) {
            return collect();
        }

        return Siswa::query()
            ->where('kelas_id', $this->kelasId)
            ->with('user')
            ->orderBy('nis')
            ->get();
    }

    #[Computed]
    public function selectedMapel()
    {
        if ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) {
            return null;
        }

        return MataPelajaran::with('kelas')->find($this->mataPelajaranId);
    }

    public function updatedTingkatKelas(): void
    {
        // Reset grup kelas dan mata pelajaran ketika tingkat kelas berubah
        $this->grupKelas = null;
        $this->mataPelajaranId = null;
        $this->kelasId = null;
        $this->detailAktivitas = [];

        // Clear computed property cache
        unset($this->grupKelasList);
        unset($this->mataPelajaran);
        unset($this->siswaList);
    }

    public function updatedGrupKelas(): void
    {
        // Reset mata pelajaran ketika grup kelas berubah
        $this->mataPelajaranId = null;
        $this->kelasId = null;
        $this->detailAktivitas = [];

        // Clear computed property cache
        unset($this->mataPelajaran);
        unset($this->siswaList);
    }

    public function updatedMataPelajaranId($value): void
    {
        if ($value) {
            $mapel = MataPelajaran::with('kelas')->find($value);
            if ($mapel) {
                $this->kelasId = $mapel->kelas_id;
            }
        } else {
            $this->kelasId = null;
        }

        // Reset attendance when subject changes
        $this->detailAktivitas = [];

        // Clear computed property cache
        unset($this->siswaList);
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep1(), $this->messagesForValidation());

        // Pre-populate attendance for all students
        $this->initializeDetailAktivitas();

        $this->step = 2;
    }

    public function previousStep(): void
    {
        $this->step = 1;
    }

    public function setAllAttendance(string $status): void
    {
        foreach (array_keys($this->detailAktivitas) as $siswaId) {
            $this->detailAktivitas[$siswaId]['kehadiran'] = $status;

            // Clear nilai and partisipasi if not present
            if ($status !== 'Hadir') {
                $this->detailAktivitas[$siswaId]['nilai'] = null;
                $this->detailAktivitas[$siswaId]['partisipasi'] = null;
            }
        }
    }

    public function save(): void
    {
        $contextTahunAjaran = $this->validateSaveContext();

        if (! $contextTahunAjaran instanceof TahunAjaran) {
            return;
        }

        // Validate both steps
        $this->validate(
            array_merge($this->rulesForStep1(), $this->rulesForStep2()),
            $this->messagesForValidation()
        );

        $userId = Auth::id();

        try {
            DB::transaction(function () use ($userId): void {
                // Create the activity
                $aktivitas = AktivitasPembelajaran::create([
                    'tanggal' => $this->tanggal,
                    'topik' => $this->topik,
                    'catatan' => $this->catatan !== '' ? $this->catatan : null,
                    'mata_pelajaran_id' => $this->mataPelajaranId,
                    'kelas_id' => $this->kelasId,
                    'guru_id' => $userId,
                ]);

                $this->createDetailRecords($aktivitas);
            });
        } catch (Exception $e) {
            report($e);
            session()->flash('error', 'Gagal menyimpan data. Silakan coba lagi.');

            return;
        }

        $this->clearTeacherDashboardCache((int) Auth::id());

        session()->flash('success', 'Aktivitas pembelajaran berhasil disimpan!');

        $this->redirect(route('teacher.aktivitas.list'), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.aktivitas-pembelajaran.create-aktivitas');
    }

    /**
     * Validate that the current context year is active and the teacher has subjects.
     * Flashes an error and returns null if any check fails.
     */
    private function validateSaveContext(): ?TahunAjaran
    {
        $contextTahunAjaran = TahunAjaran::getContext();

        if (! $contextTahunAjaran instanceof TahunAjaran) {
            session()->flash('error', 'Tidak ada tahun ajaran yang dipilih.');

            return null;
        }

        if (! $contextTahunAjaran->status) {
            session()->flash('error', 'Tidak dapat membuat aktivitas pada tahun ajaran yang tidak aktif.');

            return null;
        }

        $hasSubjects = MataPelajaran::where('guru_id', Auth::id())
            ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $contextTahunAjaran->id))
            ->exists();

        if (! $hasSubjects) {
            session()->flash('error', 'Anda belum ditugaskan sebagai guru mata pelajaran pada tahun ajaran ini.');

            return null;
        }

        return $contextTahunAjaran;
    }

    /**
     * Create DetailAktivitas records for every student in $this->detailAktivitas.
     */
    private function createDetailRecords(AktivitasPembelajaran $aktivitas): void
    {
        foreach ($this->detailAktivitas as $siswaId => $detail) {
            $kehadiran = mb_strtolower((string) $detail['kehadiran']);
            $isHadir = $kehadiran === 'hadir';

            $nilai = null;
            $partisipasi = null;
            if ($isHadir) {
                $nilai = $detail['nilai'] ?: null;
                $partisipasi = $detail['partisipasi'] ?: null;
            }

            DetailAktivitas::create([
                'aktivitas_pembelajaran_id' => $aktivitas->id,
                'siswa_id' => $siswaId,
                'kehadiran' => $kehadiran,
                'nilai' => $nilai,
                'partisipasi' => $partisipasi,
                'catatan' => ($detail['catatan'] !== '') ? $detail['catatan'] : null,
            ]);
        }
    }

    /**
     * Bust the cached teacher dashboard stats for the given user.
     */
    private function clearTeacherDashboardCache(int $userId): void
    {
        $contextYear = TahunAjaran::getContext();
        Cache::forget('teacher_dashboard_stats_'.$userId.'_'.($contextYear?->id ?? 'none'));
    }

    private function rulesForStep1(): array
    {
        return [
            'tanggal' => 'required|date',
            'mataPelajaranId' => 'required|exists:mata_pelajaran,id',
            'topik' => 'required|string|max:200',
            'catatan' => 'nullable|string|max:500',
        ];
    }

    private function rulesForStep2(): array
    {
        return [
            'detailAktivitas' => 'required|array|min:1',
            'detailAktivitas.*.kehadiran' => 'required|in:Hadir,Izin,Sakit,Alpa',
            'detailAktivitas.*.nilai' => 'nullable|numeric|min:0|max:100',
            'detailAktivitas.*.partisipasi' => 'nullable|integer|min:1|max:5',
            'detailAktivitas.*.catatan' => 'nullable|string|max:500',
        ];
    }

    private function messagesForValidation(): array
    {
        return [
            'tanggal.required' => 'Tanggal harus diisi.',
            'mataPelajaranId.required' => 'Mata pelajaran harus dipilih.',
            'topik.required' => 'Topik pembelajaran harus diisi.',
            'topik.max' => 'Topik maksimal 200 karakter.',
            'detailAktivitas.*.kehadiran.required' => 'Status kehadiran harus dipilih.',
            'detailAktivitas.*.nilai.numeric' => 'Nilai harus berupa angka.',
            'detailAktivitas.*.nilai.min' => 'Nilai minimal 0.',
            'detailAktivitas.*.nilai.max' => 'Nilai maksimal 100.',
        ];
    }

    private function initializeDetailAktivitas(): void
    {
        if ($this->detailAktivitas !== []) {
            return; // Already initialized
        }

        foreach ($this->siswaList as $siswa) {
            $this->detailAktivitas[$siswa->id] = [
                'siswa_id' => $siswa->id,
                'kehadiran' => null, // No default - teacher must select
                'nilai' => null,
                'partisipasi' => null,
                'catatan' => '',
            ];
        }
    }
}
