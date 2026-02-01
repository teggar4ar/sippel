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
    public ?int $tingkat_kelas = null;

    public ?string $grup_kelas = null;

    public ?int $mata_pelajaran_id = null;

    public string $topik = '';

    public string $catatan = '';

    // Auto-filled from selected subject
    public ?int $kelas_id = null;

    // Step 2: Student attendance details
    public array $detailAktivitas = [];

    public function mount(): void
    {
        // Default to today's date
        $this->tanggal = now()->format('Y-m-d');
    }

    #[Computed]
    public function tingkatKelasList()
    {
        $activeTahunAjaran = TahunAjaran::getActive();

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
        if ($this->tingkat_kelas === null) {
            return collect();
        }

        $activeTahunAjaran = TahunAjaran::getActive();

        if (! $activeTahunAjaran instanceof TahunAjaran) {
            return collect();
        }

        // Get distinct grup kelas untuk tingkat yang dipilih
        return Kelas::where('tahun_ajaran_id', $activeTahunAjaran->id)
            ->where('tingkat_kelas', $this->tingkat_kelas)
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
        $activeTahunAjaran = TahunAjaran::getActive();

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
        if ($this->tingkat_kelas !== null) {
            $query->whereHas('kelas', function ($q): void {
                $q->where('tingkat_kelas', $this->tingkat_kelas);
            });
        }

        if ($this->grup_kelas !== null && $this->grup_kelas !== '') {
            $query->whereHas('kelas', function ($q): void {
                $q->where('grup_kelas', $this->grup_kelas);
            });
        }

        return $query->orderBy('nama_mapel')->get();
    }

    #[Computed]
    public function siswaList()
    {
        if ($this->kelas_id === null || $this->kelas_id === 0) {
            return collect();
        }

        return Siswa::query()
            ->where('kelas_id', $this->kelas_id)
            ->with('user')
            ->orderBy('nis')
            ->get();
    }

    #[Computed]
    public function selectedMapel()
    {
        if ($this->mata_pelajaran_id === null || $this->mata_pelajaran_id === 0) {
            return null;
        }

        return MataPelajaran::with('kelas')->find($this->mata_pelajaran_id);
    }

    public function updatedTingkatKelas(): void
    {
        // Reset grup kelas dan mata pelajaran ketika tingkat kelas berubah
        $this->grup_kelas = null;
        $this->mata_pelajaran_id = null;
        $this->kelas_id = null;
        $this->detailAktivitas = [];

        // Clear computed property cache
        unset($this->grupKelasList);
        unset($this->mataPelajaran);
        unset($this->siswaList);
    }

    public function updatedGrupKelas(): void
    {
        // Reset mata pelajaran ketika grup kelas berubah
        $this->mata_pelajaran_id = null;
        $this->kelas_id = null;
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
                $this->kelas_id = $mapel->kelas_id;
            }
        } else {
            $this->kelas_id = null;
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
                    'catatan' => $this->catatan !== '' && $this->catatan !== '0' ? $this->catatan : null,
                    'mata_pelajaran_id' => $this->mata_pelajaran_id,
                    'kelas_id' => $this->kelas_id,
                    'guru_id' => $userId,
                ]);

                // Create detail records for each student
                foreach ($this->detailAktivitas as $siswaId => $detail) {
                    // Clear nilai and partisipasi if student is not present
                    $isHadir = mb_strtolower((string) $detail['kehadiran']) === 'hadir';

                    DetailAktivitas::create([
                        'aktivitas_pembelajaran_id' => $aktivitas->id,
                        'siswa_id' => $siswaId,
                        'kehadiran' => $detail['kehadiran'],
                        'nilai' => $isHadir ? ($detail['nilai'] ?: null) : null,
                        'partisipasi' => $isHadir ? ($detail['partisipasi'] ?: null) : null,
                        'catatan' => $detail['catatan'] ?: null,
                    ]);
                }
            });
        } catch (Exception) {
            session()->flash('error', 'Gagal menyimpan data. Silakan coba lagi.');

            return;
        }

        Cache::forget('teacher_dashboard_stats_'.$userId);

        session()->flash('success', 'Aktivitas pembelajaran berhasil disimpan!');

        $this->redirect(route('teacher.aktivitas.list'), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.aktivitas-pembelajaran.create-aktivitas');
    }

    private function rulesForStep1(): array
    {
        return [
            'tanggal' => 'required|date',
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
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
            'mata_pelajaran_id.required' => 'Mata pelajaran harus dipilih.',
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
