<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ClassReportExport;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

final class ClassReportExportController extends Controller
{
    public function export(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_pelajaran_id' => ['nullable', 'exists:mata_pelajaran,id'],
        ]);

        $kelas = Kelas::with('waliKelas')->findOrFail($validated['kelas_id']);

        // Authorization check
        Gate::authorize('export-class-report', $kelas);

        $mataPelajaran = isset($validated['mata_pelajaran_id'])
            ? MataPelajaran::with('guru')->find($validated['mata_pelajaran_id'])
            : null;

        $fileName = sprintf(
            'Jurnal_Observasi_%s-%s_%s.xlsx',
            $kelas->tingkat_kelas,
            $kelas->grup_kelas,
            now()->format('Y-m-d_His')
        );

        return Excel::download(
            new ClassReportExport($kelas, $mataPelajaran),
            $fileName
        );
    }
}
