<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\AktivitasPembelajaran;

use App\Models\AktivitasPembelajaran;
use App\Models\MataPelajaran;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.teacher')]
#[Title('Daftar Aktivitas - SIPPEL Guru')]
final class ListAktivitas extends Component
{
    use WithPagination;

    #[Url]
    public string $filterMapel = '';

    #[Url]
    public string $filterTanggal = '';

    #[Url]
    public string $filterPeriode = ''; // Quick date filter: today, week, month

    #[Url]
    public string $search = '';

    #[Url]
    public int $perPage = 10;

    // Delete confirmation modal
    public bool $showDeleteModal = false;

    public ?int $deleteId = null;

    public ?string $deleteTopik = null;

    public function updatingFilterMapel(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTanggal(): void
    {
        $this->filterPeriode = ''; // Clear quick filter when specific date is selected
        $this->resetPage();
    }

    public function updatingFilterPeriode(): void
    {
        $this->filterTanggal = ''; // Clear specific date when quick filter is selected
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function setQuickFilter(string $periode): void
    {
        $this->filterPeriode = $this->filterPeriode === $periode ? '' : $periode;
        $this->filterTanggal = '';
        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function clearFilters(): void
    {
        $this->filterMapel = '';
        $this->filterTanggal = '';
        $this->filterPeriode = '';
        $this->search = '';
        $this->perPage = 10;
        $this->resetPage();
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

    /**
     * Summary stats: Total activities this month
     */
    #[Computed]
    public function totalAktivitasBulanIni(): int
    {
        return AktivitasPembelajaran::query()
            ->where('guru_id', Auth::id())
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->count();
    }

    /**
     * Summary stats: Average attendance percentage this month
     */
    #[Computed]
    public function rataKehadiran(): float
    {
        $stats = DB::table('aktivitas_pembelajaran')
            ->join('detail_aktivitas', 'aktivitas_pembelajaran.id', '=', 'detail_aktivitas.aktivitas_pembelajaran_id')
            ->where('aktivitas_pembelajaran.guru_id', Auth::id())
            ->whereMonth('aktivitas_pembelajaran.tanggal', Carbon::now()->month)
            ->whereYear('aktivitas_pembelajaran.tanggal', Carbon::now()->year)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN LOWER(detail_aktivitas.kehadiran) = ? THEN 1 ELSE 0 END) as hadir
            ', ['hadir'])
            ->first();

        if (! $stats || $stats->total === 0) {
            return 0;
        }

        return round(($stats->hadir / $stats->total) * 100, 1);
    }

    /**
     * Summary stats: Most active subject this month
     */
    #[Computed]
    public function mapelTeraktif(): ?object
    {
        $result = AktivitasPembelajaran::query()
            ->where('guru_id', Auth::id())
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->select('mata_pelajaran_id', DB::raw('COUNT(*) as total'))
            ->groupBy('mata_pelajaran_id')
            ->orderByDesc('total')
            ->first();

        if (! $result) {
            return null;
        }

        $mapel = MataPelajaran::with('kelas')->find($result->mata_pelajaran_id);

        return (object) [
            'nama' => $mapel?->nama_mapel ?? '-',
            'kelas' => $mapel?->kelas?->nama_lengkap ?? '',
            'total' => $result->total,
        ];
    }

    #[Computed]
    public function aktivitas()
    {
        return AktivitasPembelajaran::query()
            ->where('guru_id', Auth::id())
            ->when($this->filterMapel, fn ($q) => $q->where('mata_pelajaran_id', $this->filterMapel))
            ->when($this->filterTanggal, fn ($q) => $q->whereDate('tanggal', $this->filterTanggal))
            ->when($this->filterPeriode === 'today', fn ($q) => $q->whereDate('tanggal', Carbon::today()))
            ->when($this->filterPeriode === 'week', fn ($q) => $q->whereBetween('tanggal', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]))
            ->when($this->filterPeriode === 'month', fn ($q) => $q->whereMonth('tanggal', Carbon::now()->month)->whereYear('tanggal', Carbon::now()->year))
            ->when($this->search, fn ($q) => $q->where('topik', 'like', "%{$this->search}%"))
            ->with(['mataPelajaran', 'kelas', 'detailAktivitas'])
            ->latest('tanggal')
            ->latest('created_at')
            ->paginate($this->perPage);
    }

    /**
     * Group activities by date for display
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection>
     */
    #[Computed]
    public function aktivitasGrouped()
    {
        return $this->aktivitas->getCollection()->groupBy(fn ($item) => $item->tanggal->format('Y-m-d'));
    }

    /**
     * Check if there are more items to load
     */
    #[Computed]
    public function hasMorePages(): bool
    {
        return $this->aktivitas->hasMorePages();
    }

    public function confirmDelete(int $id, string $topik): void
    {
        $this->deleteId = $id;
        $this->deleteTopik = $topik;
        $this->showDeleteModal = true;
    }

    public function deleteAktivitas(): void
    {
        if ($this->deleteId === null || $this->deleteId === 0) {
            return;
        }

        $aktivitas = AktivitasPembelajaran::where('guru_id', Auth::id())->find($this->deleteId);

        if ($aktivitas) {
            try {
                $aktivitas->delete();
                Cache::forget('teacher_dashboard_stats_'.Auth::id());
                session()->flash('success', 'Aktivitas berhasil dihapus.');
            } catch (Exception) {
                session()->flash('error', 'Gagal menghapus aktivitas. Silakan coba lagi.');
            }
        }

        $this->closeDeleteModal();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
        $this->deleteTopik = null;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.aktivitas-pembelajaran.list-aktivitas');
    }
}
