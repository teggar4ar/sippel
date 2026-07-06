<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\Concerns;

use App\Models\Kelas;
use App\Models\Laporan as LaporanModel;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

trait HasLaporanComputed
{
    /**
     * Get the current teacher (logged in user)
     */
    #[Computed]
    public function teacher(): ?User
    {
        return Auth::user();
    }

    /**
     * Get classes where the teacher is the homeroom teacher (wali kelas)
     *
     * @return Collection<int, Kelas>
     */
    #[Computed]
    public function kelasWali(): Collection
    {
        $query = Kelas::where('wali_kelas_id', Auth::id())
            ->with('tahunAjaran')
            ->orderBy('tingkat_kelas')
            ->orderBy('grup_kelas');

        // Always scope to the context academic year
        $contextId = TahunAjaran::getContext()?->id;
        if ($contextId) {
            $query->where('tahun_ajaran_id', $contextId);
        }

        return $query->get();
    }

    /**
     * Check if teacher has any class as homeroom teacher
     */
    #[Computed]
    public function hasKelasWali(): bool
    {
        return $this->kelasWali->isNotEmpty();
    }

    /**
     * Get students in the selected class
     *
     * @return Collection<int, Siswa>
     */
    #[Computed]
    public function siswaList(): Collection
    {
        if ($this->kelasId === null || $this->kelasId === 0) {
            return new Collection();
        }

        // Each Kelas record belongs to exactly one academic year, so querying via
        // kelasHistory by kelas_id gives us only the students from that year.
        // withTrashed() catches graduated (soft-deleted) students in past years.
        // Fallback on current siswa.kelas_id for systems where kelasHistory has not
        // yet been fully backfilled.
        return Siswa::withTrashed()
            ->where(function ($query): void {
                $query
                    ->whereHas('kelasHistory', fn ($q) => $q->where('kelas_id', $this->kelasId))
                    ->orWhere('kelas_id', $this->kelasId);
            })
            ->with('user')
            ->get()
            ->sortBy(fn (Siswa $s): string => $s->user->name ?? '');
    }

    /**
     * Get subjects for the selected class
     *
     * @return Collection<int, MataPelajaran>
     */
    #[Computed]
    public function mataPelajaranList(): Collection
    {
        if ($this->kelasId === null || $this->kelasId === 0) {
            return new Collection();
        }

        return MataPelajaran::where('kelas_id', $this->kelasId)
            ->with('guru')
            ->orderBy('nama_mapel')
            ->get();
    }

    /**
     * Get selected kelas model (only if teacher is wali kelas)
     */
    #[Computed]
    public function selectedKelas(): ?Kelas
    {
        if ($this->kelasId === null || $this->kelasId === 0) {
            return null;
        }

        // Security: Only return if teacher is wali kelas of this class
        return Kelas::with('waliKelas')
            ->where('id', $this->kelasId)
            ->where('wali_kelas_id', Auth::id())
            ->first();
    }

    /**
     * Get selected siswa model (only if in teacher's class)
     */
    #[Computed]
    public function selectedSiswa(): ?Siswa
    {
        if ($this->siswaId === null || $this->siswaId === 0) {
            return null;
        }

        // Security: verify student was (or is currently) in teacher's wali kelas
        $kelasWaliIds = $this->kelasWali->pluck('id');

        $siswa = Siswa::withTrashed()
            ->with(['user', 'kelas.waliKelas'])
            ->find($this->siswaId);

        if (! $siswa) {
            return null;
        }

        // Check current class OR any historical class
        $inCurrentClass = $kelasWaliIds->contains($siswa->kelas_id);
        $inHistoricalClass = $siswa->kelasHistory()->whereIn('kelas_id', $kelasWaliIds)->exists();

        return ($inCurrentClass || $inHistoricalClass) ? $siswa : null;
    }

    /**
     * Get selected mata pelajaran model (only if in teacher's class)
     */
    #[Computed]
    public function selectedMataPelajaran(): ?MataPelajaran
    {
        if ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) {
            return null;
        }

        // Security: Only return if mata pelajaran is in a class where teacher is wali kelas
        $kelasWaliIds = $this->kelasWali->pluck('id');

        return MataPelajaran::with('guru')
            ->where('id', $this->mataPelajaranId)
            ->whereIn('kelas_id', $kelasWaliIds)
            ->first();
    }

    /**
     * Get the context academic year (from sidebar switcher)
     */
    #[Computed]
    public function contextTahunAjaran(): ?TahunAjaran
    {
        return TahunAjaran::getContext();
    }

    /**
     * Get preview data for student report
     *
     * @return Collection<int, LaporanModel>
     */
    #[Computed]
    public function studentReportData(): Collection
    {
        $contextId = $this->contextTahunAjaran?->id;
        if ($this->siswaId === null || $this->siswaId === 0 || ! $contextId) {
            return new Collection();
        }

        return LaporanModel::where('siswa_id', $this->siswaId)
            ->where('tahun_ajaran_id', $contextId)
            ->with('mataPelajaran')
            ->get();
    }

    /**
     * Get per-activity detail records for the student report "Riwayat Aktivitas" table.
     * Returns a paginated result for use with Flux UI table pagination.
     */
    #[Computed]
    public function studentActivityData(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $contextId = $this->contextTahunAjaran?->id;
        if ($this->siswaId === null || $this->siswaId === 0 || ! $contextId || $this->kelasId === null) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        return \App\Models\DetailAktivitas::where('siswa_id', $this->siswaId)
            ->whereHas('aktivitasPembelajaran', function ($q) use ($contextId): void {
                $q->where('kelas_id', $this->kelasId)
                    ->whereHas('kelas', fn ($kq) => $kq->where('tahun_ajaran_id', $contextId));
            })
            ->with(['aktivitasPembelajaran.mataPelajaran', 'aktivitasPembelajaran'])
            ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
            ->orderByDesc('aktivitas_pembelajaran.tanggal')
            ->orderByDesc('detail_aktivitas.id')
            ->select('detail_aktivitas.*')
            ->paginate(10);
    }

    /**
     * Get preview data for class report
     *
     * @return Collection<int, LaporanModel>
     */
    #[Computed]
    public function classReportData(): Collection
    {
        $contextId = $this->contextTahunAjaran?->id;
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ! $contextId) {
            return new Collection();
        }

        $laporanData = LaporanModel::where('tahun_ajaran_id', $contextId)
            ->where('mata_pelajaran_id', $this->mataPelajaranId)
            ->whereHas(
                'siswa.kelasHistory',
                fn ($q) => $q
                    ->where('tahun_ajaran_id', $contextId)
                    ->where('kelas_id', $this->kelasId)
            )
            ->with(['siswa.user', 'mataPelajaran'])
            ->get();

        // Apply sorting
        return match ($this->sortBy) {
            'keaktifan' => $laporanData->sortByDesc(fn (LaporanModel $laporan): int => $laporan->rata_keaktifan?->weight() ?? 0)->values(),
            'keaktifan_asc' => $laporanData->sortBy(fn (LaporanModel $laporan): int => $laporan->rata_keaktifan?->weight() ?? 0)->values(),
            'kehadiran' => $laporanData->sortByDesc('rata_kehadiran')->values(),
            'nama' => $laporanData->sortBy(fn (LaporanModel $l): string => $l->siswa->user->name ?? '')->values(),
            default => $laporanData->sortByDesc('rata_kehadiran')->values(),
        };
    }
}
