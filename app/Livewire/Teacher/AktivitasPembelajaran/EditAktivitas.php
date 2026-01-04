<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\AktivitasPembelajaran;

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use Illuminate\Support\Facades\Auth;
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

    public ?int $mata_pelajaran_id = null;

    public string $topik = '';

    public string $catatan = '';

    public ?int $kelas_id = null;

    // Student attendance details
    public array $detailAktivitas = [];

    public function mount(int $id): void
    {
        $this->aktivitas = AktivitasPembelajaran::query()
            ->where('guru_id', Auth::id())
            ->with(['mataPelajaran.kelas', 'detailAktivitas.siswa.user'])
            ->findOrFail($id);

        // Load activity data
        $this->tanggal = $this->aktivitas->tanggal->format('Y-m-d');
        $this->mata_pelajaran_id = $this->aktivitas->mata_pelajaran_id;
        $this->kelas_id = $this->aktivitas->kelas_id;
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

    public function updatedMataPelajaranId($value): void
    {
        if ($value) {
            $mapel = MataPelajaran::with('kelas')->find($value);
            if ($mapel) {
                $this->kelas_id = $mapel->kelas_id;
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

    public function save(): void
    {
        $this->validate();

        DB::transaction(function (): void {
            // Update activity
            $this->aktivitas->update([
                'tanggal' => $this->tanggal,
                'topik' => $this->topik,
                'catatan' => $this->catatan !== '' && $this->catatan !== '0' ? $this->catatan : null,
                'mata_pelajaran_id' => $this->mata_pelajaran_id,
                'kelas_id' => $this->kelas_id,
            ]);

            // Update or create detail records
            foreach ($this->detailAktivitas as $siswaId => $detail) {
                // Clear nilai and partisipasi if student is not present
                $isHadir = mb_strtolower((string) $detail['kehadiran']) === 'hadir';

                DetailAktivitas::updateOrCreate(
                    [
                        'aktivitas_pembelajaran_id' => $this->aktivitas->id,
                        'siswa_id' => $siswaId,
                    ],
                    [
                        'kehadiran' => $detail['kehadiran'],
                        'nilai' => $isHadir ? ($detail['nilai'] ?: null) : null,
                        'partisipasi' => $isHadir ? ($detail['partisipasi'] ?: null) : null,
                        'catatan' => $detail['catatan'] ?: null,
                    ]
                );
            }

            // Remove records for students no longer in the list
            DetailAktivitas::where('aktivitas_pembelajaran_id', $this->aktivitas->id)
                ->whereNotIn('siswa_id', array_keys($this->detailAktivitas))
                ->delete();
        });

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
    private function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
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
    private function messages(): array
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

    private function loadDetailAktivitas(): void
    {
        // Get all students in the class
        $siswaInClass = Siswa::where('kelas_id', $this->kelas_id)
            ->with('user')
            ->orderBy('nis')
            ->get();

        // Get existing detail records
        $existingDetails = $this->aktivitas->detailAktivitas->keyBy('siswa_id');

        foreach ($siswaInClass as $siswa) {
            $existing = $existingDetails->get($siswa->id);

            $this->detailAktivitas[$siswa->id] = [
                'siswa_id' => $siswa->id,
                'kehadiran' => $existing?->kehadiran ?? 'Hadir',
                'nilai' => $existing?->nilai,
                'partisipasi' => $existing?->partisipasi ? (int) $existing->partisipasi : null,
                'catatan' => $existing?->catatan ?? '',
            ];
        }
    }
}
