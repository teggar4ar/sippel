<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\AktivitasPembelajaran;

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
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
#[Title('Edit Aktivitas - SIPPEL Guru')]
final class EditAktivitas extends Component
{
    public AktivitasPembelajaran $aktivitas;

    // Activity information
    public string $tanggal = '';

    public ?int $mataPelajaranId = null;

    public string $topik = '';

    public string $catatan = '';

    public ?int $kelasId = null;

    // Student attendance details
    public array $detailAktivitas = [];

    public function mount(int $id): void
    {
        $this->aktivitas = AktivitasPembelajaran::query()
            ->where('guru_id', Auth::id())
            ->with(['kelas', 'mataPelajaran.kelas', 'detailAktivitas.siswa.user'])
            ->findOrFail($id);

        // Same guard as CreateAktivitas: context year must be set and active,
        // and this activity must belong to it.
        $contextTahunAjaran = TahunAjaran::getContext();

        if (! $contextTahunAjaran instanceof TahunAjaran || ! $contextTahunAjaran->status) {
            session()->flash('error', 'Tidak dapat mengubah aktivitas pada tahun ajaran yang tidak aktif. Silakan pilih tahun ajaran yang aktif.');
            $this->redirect(route('teacher.aktivitas.list'), navigate: true);

            return;
        }

        if ($this->aktivitas->kelas?->tahun_ajaran_id !== $contextTahunAjaran->id) {
            session()->flash('error', 'Aktivitas ini tidak termasuk dalam tahun ajaran yang sedang dipilih.');
            $this->redirect(route('teacher.aktivitas.list'), navigate: true);

            return;
        }

        // Load activity data
        $this->tanggal = $this->aktivitas->tanggal->format('Y-m-d');
        $this->mataPelajaranId = $this->aktivitas->mata_pelajaran_id;
        $this->kelasId = $this->aktivitas->kelas_id;
        $this->topik = $this->aktivitas->topik;
        $this->catatan = $this->aktivitas->catatan ?? '';

        // Load existing detail data
        $this->loadDetailAktivitas();
    }

    #[Computed]
    public function mataPelajaran()
    {
        return MataPelajaran::query()
            ->where('guru_id', Auth::id())
            ->with('kelas')
            ->orderBy('nama_mapel')
            ->get();
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

    public function updatedMataPelajaranId($value): void
    {
        if ($value) {
            $mapel = MataPelajaran::with('kelas')->find($value);
            // Only reload if the class actually changed
            if ($mapel && $this->kelasId !== $mapel->kelas_id) {
                $this->kelasId = $mapel->kelas_id;
                // Reload student list for new class
                $this->detailAktivitas = [];
                $this->loadDetailAktivitas();
            }
        }
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

    /**
     * Accept the full detailAktivitas payload from Alpine (client-side state)
     * and immediately save — eliminates multiple $wire.set() round-trips.
     *
     * @param  array<int|string, array{kehadiran: string|null, partisipasi: int|null, catatan: string}>  $detailAktivitas
     */
    public function saveWithDetail(array $detailAktivitas): void
    {
        $this->detailAktivitas = $detailAktivitas;
        $this->save();
    }

    public function save(): void
    {
        // Re-verify the context guard (mirrors the mount() check).
        $contextTahunAjaran = TahunAjaran::getContext();

        if (
            ! $contextTahunAjaran instanceof TahunAjaran
            || ! $contextTahunAjaran->status
            || $this->aktivitas->kelas?->tahun_ajaran_id !== $contextTahunAjaran->id
        ) {
            session()->flash('error', 'Tidak dapat mengubah aktivitas pada tahun ajaran yang tidak aktif.');
            $this->redirect(route('teacher.aktivitas.list'), navigate: true);

            return;
        }

        $this->validate();

        try {
            DB::transaction(function (): void {
                // Update activity
                $this->aktivitas->update([
                    'tanggal' => $this->tanggal,
                    'topik' => $this->topik,
                    'catatan' => $this->catatan !== '' ? $this->catatan : null,
                    'mata_pelajaran_id' => $this->mataPelajaranId,
                    'kelas_id' => $this->kelasId,
                ]);

                $this->updateDetailRecords();
            });
        } catch (Exception $e) {
            report($e);
            session()->flash('error', 'Gagal memperbarui data. Silakan coba lagi.');

            return;
        }

        $this->clearTeacherDashboardCache();

        session()->flash('success', 'Aktivitas pembelajaran berhasil diperbarui!');

        $this->redirect(route('teacher.aktivitas.list'), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.aktivitas-pembelajaran.edit-aktivitas');
    }

    /**
     * Get validation rules.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'mataPelajaranId' => 'required|exists:mata_pelajaran,id',
            'topik' => 'required|string|max:200',
            'catatan' => 'nullable|string|max:500',
            'detailAktivitas' => 'required|array|min:1',
            'detailAktivitas.*.kehadiran' => 'required|in:Hadir,Izin,Sakit,Alpa',
            'detailAktivitas.*.nilai' => 'nullable|numeric|min:0|max:100',
            'detailAktivitas.*.partisipasi' => 'nullable|integer|min:1|max:5',
            'detailAktivitas.*.catatan' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
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

    /**
     * Update or create DetailAktivitas records; remove records for removed students.
     */
    private function updateDetailRecords(): void
    {
        foreach ($this->detailAktivitas as $siswaId => $detail) {
            // Convert kehadiran to lowercase for database enum
            $kehadiran = mb_strtolower((string) $detail['kehadiran']);
            $isHadir = $kehadiran === 'hadir';

            $partisipasi = null;
            $nilai = null;
            if ($isHadir && $detail['partisipasi']) {
                $partisipasi = (int) $detail['partisipasi'];
                $nilai = $this->resolveNilaiFromPartisipasi($partisipasi);
            }

            DetailAktivitas::updateOrCreate(
                [
                    'aktivitas_pembelajaran_id' => $this->aktivitas->id,
                    'siswa_id' => $siswaId,
                ],
                [
                    'kehadiran' => $kehadiran,
                    'nilai' => $nilai,
                    'partisipasi' => $partisipasi,
                    'catatan' => ($detail['catatan'] !== '') ? $detail['catatan'] : null,
                ]
            );
        }

        // Remove records for students no longer in the list
        DetailAktivitas::where('aktivitas_pembelajaran_id', $this->aktivitas->id)
            ->whereNotIn('siswa_id', array_keys($this->detailAktivitas))
            ->delete();

        $this->clearStudentStreakCache(array_keys($this->detailAktivitas), $this->aktivitas->kelas?->tahun_ajaran_id);
        $this->clearStudentTopMapelCache(array_keys($this->detailAktivitas), $this->aktivitas->kelas?->tahun_ajaran_id);
    }

    /**
     * Bust the cached teacher dashboard stats for the current user.
     */
    private function clearTeacherDashboardCache(): void
    {
        $contextYear = TahunAjaran::getContext();
        Cache::forget('teacher_dashboard_stats_'.Auth::id().'_'.($contextYear?->id ?? 'none'));
    }

    /**
     * Bust cached student streaks for the given siswa IDs.
     *
     * @param  array<int|string>  $siswaIds
     */
    private function clearStudentStreakCache(array $siswaIds, ?int $tahunAjaranId): void
    {
        foreach ($siswaIds as $siswaId) {
            $id = (int) $siswaId;
            Cache::forget('student_streak_'.$id.'_'.($tahunAjaranId ?? 'none'));
            Cache::forget('student_streak_'.$id.'_none');
        }
    }

    /**
     * Bust cached student top-performa mapel data for the given siswa IDs.
     *
     * @param  array<int|string>  $siswaIds
     */
    private function clearStudentTopMapelCache(array $siswaIds, ?int $tahunAjaranId): void
    {
        foreach ($siswaIds as $siswaId) {
            $id = (int) $siswaId;
            Cache::forget('student_top_mapel_'.$id.'_'.($tahunAjaranId ?? 'none'));
            Cache::forget('student_top_mapel_'.$id.'_none');
        }
    }

    /**
     * Map a participation level (1–4) to its corresponding fixed observation score.
     * Pasif=60, Cukup=75, Aktif=85, Sangat Aktif=95.
     */
    private function resolveNilaiFromPartisipasi(int $partisipasi): ?int
    {
        return match ($partisipasi) {
            1 => 60,
            2 => 75,
            3 => 85,
            4 => 95,
            default => null,
        };
    }

    private function loadDetailAktivitas(): void
    {
        // Get all students in the class
        $siswaInClass = Siswa::where('kelas_id', $this->kelasId)
            ->with('user')
            ->orderBy('nis')
            ->get();

        // Get existing detail records
        $existingDetails = $this->aktivitas->detailAktivitas->keyBy('siswa_id');

        foreach ($siswaInClass as $siswa) {
            $existing = $existingDetails->get($siswa->id);

            // Normalize kehadiran enum to capitalized string (UI expects 'Hadir', 'Izin', etc.)
            $kehadiran = $existing?->kehadiran instanceof \App\Enums\KehadiranStatus
                ? ucfirst($existing->kehadiran->value)
                : 'Hadir';

            $this->detailAktivitas[$siswa->id] = [
                'siswa_id' => $siswa->id,
                'kehadiran' => $kehadiran,
                'nilai' => $existing?->nilai,
                'partisipasi' => $existing?->partisipasi ? (int) $existing->partisipasi : null,
                'catatan' => $existing?->catatan ?? '',
            ];
        }
    }
}
