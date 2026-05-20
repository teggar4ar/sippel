<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\AktivitasPembelajaran;

use App\Models\AktivitasPembelajaran;
use App\Models\MataPelajaran;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
    public string $filterPeriode = ''; // Quick date filter: today, week, month

    #[Url]
    public string $search = '';

    #[Url]
    public int $perPage = 10;

    // Delete confirmation modal
    public bool $showDeleteModal = false;

    public ?int $deleteId = null;

    public ?string $deleteTopik = null;

    // Inline feedback for same-page actions (shown in component view, not layout)
    public ?string $inlineError = null;

    public ?string $inlineSuccess = null;

    public function updatingFilterMapel(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPeriode(): void
    {
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
        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function clearFilters(): void
    {
        $this->filterMapel = '';
        $this->filterPeriode = '';
        $this->search = '';
        $this->perPage = 10;
        $this->resetPage();
    }

    #[Computed]
    public function mataPelajaran()
    {
        $contextTahunAjaran = \App\Models\TahunAjaran::getContext();

        return MataPelajaran::query()
            ->where('guru_id', Auth::id())
            ->when($contextTahunAjaran, fn ($q) => $q->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)))
            ->with('kelas')
            ->orderBy('nama_mapel')
            ->get();
    }

    #[Computed]
    public function aktivitas()
    {
        $contextTahunAjaran = \App\Models\TahunAjaran::getContext();

        return AktivitasPembelajaran::query()
            ->where('guru_id', Auth::id())
            ->when($contextTahunAjaran, fn ($q) => $q->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)))
            ->when($this->filterMapel, fn ($q) => $q->where('mata_pelajaran_id', $this->filterMapel))
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
        $this->inlineError = null;
        $this->inlineSuccess = null;
        $this->showDeleteModal = true;
    }

    public function deleteAktivitas(): void
    {
        if ($this->deleteId === null || $this->deleteId === 0) {
            return;
        }

        $aktivitas = AktivitasPembelajaran::where('guru_id', Auth::id())
            ->with('kelas')
            ->find($this->deleteId);

        if ($aktivitas) {
            // Same guard as CreateAktivitas: context year must be set, active,
            // and the activity must belong to it.
            $contextTahunAjaran = \App\Models\TahunAjaran::getContext();

            if (
                ! $contextTahunAjaran instanceof \App\Models\TahunAjaran
                || ! $contextTahunAjaran->status
                || $aktivitas->kelas?->tahun_ajaran_id !== $contextTahunAjaran->id
            ) {
                $this->inlineError = 'Tidak dapat menghapus aktivitas pada tahun ajaran yang tidak aktif.';
                $this->closeDeleteModal();

                return;
            }

            try {
                $aktivitas->delete();

                // Clear dashboard cache for current context
                $contextYear = \App\Models\TahunAjaran::getContext();
                Cache::forget('teacher_dashboard_stats_'.Auth::id().'_'.($contextYear?->id ?? 'none'));

                $this->inlineSuccess = 'Aktivitas berhasil dihapus.';
            } catch (Exception $e) {
                report($e);
                $this->inlineError = 'Gagal menghapus aktivitas. Silakan coba lagi.';
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
