<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\TahunAjarans\TahunAjaranResource;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelasHistory;
use App\Models\TahunAjaran;
use App\Models\User;
use BackedEnum;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

final class KenaikanKelasPage extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    // Context data
    public ?TahunAjaran $activeTahunAjaran = null;

    /** @var Collection<int, Kelas> */
    public Collection $currentClasses;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Kenaikan Kelas';

    protected static ?string $title = 'Kenaikan Kelas';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.kenaikan-kelas';

    public static function getNavigationGroup(): string
    {
        return 'Master Data';
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && $user->hasRole('admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->activeTahunAjaran = TahunAjaran::getActive();
        $this->currentClasses = collect();
        $defaults = [];

        if ($this->activeTahunAjaran instanceof TahunAjaran) {
            // Suggest next academic year name
            $defaults['namaTahun'] = $this->activeTahunAjaran->getNextNamaTahun();
            $defaults['semester'] = 'Ganjil';

            $this->currentClasses = Kelas::where('tahun_ajaran_id', $this->activeTahunAjaran->id)
                ->with(['waliKelas', 'siswa.user'])
                ->orderBy('tingkat_kelas')
                ->orderBy('grup_kelas')
                ->get();

            // Grade 8 classes in the new year receive students promoted from Grade 7,
            // so the group structure mirrors the current Grade 7 groups.
            // Grade 9 classes receive students promoted from Grade 8, same logic.
            $grupPerTingkat = $this->currentClasses
                ->groupBy('tingkat_kelas')
                ->map(fn ($classes) => $classes->pluck('grup_kelas')->unique()->sort()->values());

            $assignments = [];
            foreach (($grupPerTingkat->get('7') ?? collect(['A'])) as $grup) {
                $assignments['8_'.$grup] = null;
            }
            foreach (($grupPerTingkat->get('8') ?? collect(['A'])) as $grup) {
                $assignments['9_'.$grup] = null;
            }
            $defaults['waliKelasAssignments'] = $assignments;

            // Initialize student decisions
            $decisions = [];
            foreach ($this->currentClasses as $kelas) {
                foreach ($kelas->siswa as $siswa) {
                    $decisions[$siswa->id] = $kelas->isGraduating() ? 'lulus' : 'naik';
                }
            }
            $defaults['studentDecisions'] = $decisions;
        }

        $this->form->fill($defaults);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Tahun Baru')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('namaTahun')
                                        ->label('Nama Tahun Ajaran')
                                        ->required()
                                        ->maxLength(20)
                                        ->placeholder('2026/2027'),
                                    Select::make('semester')
                                        ->options([
                                            'Ganjil' => 'Ganjil',
                                            'Genap' => 'Genap',
                                        ])
                                        ->required()
                                        ->native(false)
                                        ->disabled()
                                        ->dehydrated(),
                                    DatePicker::make('tanggalMulai')
                                        ->label('Tanggal Mulai')
                                        ->required()
                                        ->native(false),
                                    DatePicker::make('tanggalSelesai')
                                        ->label('Tanggal Selesai')
                                        ->required()
                                        ->after('tanggalMulai')
                                        ->native(false),
                                ])->columns(2),
                        ]),
                    Wizard\Step::make('Wali Kelas')
                        ->schema(function (): array {
                            $fields = [];

                            // Grade 8 new classes mirror current Grade 7 groups (promotion source).
                            // Grade 9 new classes mirror current Grade 8 groups (promotion source).
                            $grupPerTingkat = $this->currentClasses
                                ->groupBy('tingkat_kelas')
                                ->map(fn ($classes) => $classes->pluck('grup_kelas')->unique()->sort()->values());

                            $newYearGroups = [
                                8 => $grupPerTingkat->get('7') ?? collect(['A']),
                                9 => $grupPerTingkat->get('8') ?? collect(['A']),
                            ];

                            foreach ([8, 9] as $tingkat) {
                                foreach ($newYearGroups[$tingkat] as $grup) {
                                    $key = "{$tingkat}_{$grup}";
                                    $fields[] = Select::make("waliKelasAssignments.{$key}")
                                        ->label("Wali Kelas {$tingkat}{$grup}")
                                        ->options(User::role('teacher')->pluck('name', 'id'))
                                        ->native(false)
                                        ->searchable()
                                        ->preload()
                                        ->required();
                                }
                            }

                            return [Section::make('Daftar Kelas Baru')->schema($fields)->columns(2)];
                        }),
                    Wizard\Step::make('Siswa')
                        ->schema(function (): array {
                            $fields = [];

                            // Group students by class
                            foreach ($this->currentClasses as $kelas) {

                                $students = $kelas->siswa->sortBy(fn ($siswa) => $siswa->user?->name ?? '');

                                if ($students->isEmpty()) {
                                    continue;
                                }

                                $studentFields = [];

                                foreach ($students as $siswa) {
                                    $studentFields[] = Select::make("studentDecisions.{$siswa->id}")
                                        ->label($siswa->user?->name ?? 'Unknown ('.$siswa->nis.')')
                                        ->options(function () use ($kelas): array {
                                            if ($kelas->isGraduating()) {
                                                return [
                                                    'lulus' => '🎓 Lulus',
                                                    'tinggal' => '🔄 Tinggal Kelas',
                                                ];
                                            }

                                            return [
                                                'naik' => '⬆️ Naik Kelas',
                                                'tinggal' => '🔄 Tinggal Kelas',
                                            ];
                                        })
                                        ->native(false)
                                        ->helperText(function ($get) use ($kelas, $siswa): ?string {
                                            $decision = $get("studentDecisions.{$siswa->id}");

                                            if ($decision === 'lulus') {
                                                return '➜ Dihapus (Lulus)';
                                            }
                                            if ($decision === 'naik') {
                                                $nextTingkat = $kelas->getNextTingkatKelas();

                                                return $nextTingkat !== null && $nextTingkat !== 0 ? "➜ Kelas {$nextTingkat}{$kelas->grup_kelas}" : '➜ -';
                                            }
                                            if ($decision === 'tinggal') {
                                                return "➜ Kelas {$kelas->tingkat_kelas}{$kelas->grup_kelas}";
                                            }

                                            return null;
                                        })
                                        ->required();
                                }

                                // Add section per class
                                $fields[] = Section::make("Kelas {$kelas->nama_lengkap}")
                                    ->schema($studentFields)
                                    ->columns(2)
                                    ->collapsible();
                            }

                            return $fields;
                        }),
                    Wizard\Step::make('Konfirmasi')
                        ->schema([
                            Section::make('Ringkasan')
                                ->schema([
                                    Placeholder::make('summary_tahun')
                                        ->label('Tahun Ajaran Baru')
                                        ->content(fn ($get): string => "{$get('namaTahun')} - Semester {$get('semester')}"),
                                    Placeholder::make('summary_dates')
                                        ->label('Periode')
                                        ->content(fn ($get): string => "{$get('tanggalMulai')} s/d {$get('tanggalSelesai')}"),
                                    Placeholder::make('summary_stats')
                                        ->label('Statistik Siswa')
                                        ->content(function ($get): HtmlString {
                                            $decisions = collect($get('studentDecisions') ?? []);
                                            $naik = $decisions->filter(fn ($d): bool => $d === 'naik')->count();
                                            $tinggal = $decisions->filter(fn ($d): bool => $d === 'tinggal')->count();
                                            $lulus = $decisions->filter(fn ($d): bool => $d === 'lulus')->count();

                                            // Safely escape generic HTML or use Blade rendering if complex
                                            return new HtmlString(
                                                "<div class='flex gap-4'>
                                                    <span class='text-success-600 font-bold'>Naik: {$naik}</span>
                                                    <span class='text-warning-600 font-bold'>Tinggal: {$tinggal}</span>
                                                    <span class='text-danger-600 font-bold'>Lulus: {$lulus}</span>
                                                </div>"
                                            );
                                        }),
                                    Placeholder::make('warning')
                                        ->content(new HtmlString('<span class="text-danger-600 font-medium">Perhatian: Proses ini akan menonaktifkan tahun ajaran lama dan menghapus data siswa yang lulus. Pastikan data sudah benar.</span>')),
                                ]),
                        ]),
                ])
                    ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary relative grid-flow-col items-center justify-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition duration-75 focus:ring-2 focus:ring-primary-500/50 cursor-pointer">Eksekusi Kenaikan Kelas</button>')),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $namaTahun = $data['namaTahun'];
        $semester = $data['semester'];
        $tanggalMulai = $data['tanggalMulai'];
        $tanggalSelesai = $data['tanggalSelesai'];
        $waliKelasAssignments = $data['waliKelasAssignments'] ?? [];
        $studentDecisions = $this->data['studentDecisions'] ?? [];

        // Manual validation for duplicate
        $exists = TahunAjaran::where('nama_tahun', $namaTahun)
            ->where('semester', $semester)
            ->exists();

        if ($exists) {
            Notification::make()->title('Tahun Ajaran Sudah Ada')->body("Tahun ajaran {$namaTahun} - {$semester} sudah ada.")->danger()->send();

            return;
        }

        try {
            DB::transaction(function () use ($namaTahun, $semester, $tanggalMulai, $tanggalSelesai, $waliKelasAssignments, $studentDecisions): void {
                // 1. Deactivate current
                if ($this->activeTahunAjaran instanceof TahunAjaran) {
                    $this->activeTahunAjaran->update(['status' => false]);
                }

                // 2. Create new
                $newTahunAjaran = TahunAjaran::create([
                    'nama_tahun' => $namaTahun,
                    'semester' => $semester,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                    'status' => true,
                ]);

                // 3. Create classes
                $newKelasMap = [];
                foreach ($waliKelasAssignments as $key => $waliKelasId) {
                    [$tingkat, $grup] = explode('_', $key);
                    $newKelas = Kelas::create([
                        'tingkat_kelas' => (int) $tingkat,
                        'grup_kelas' => $grup,
                        'wali_kelas_id' => $waliKelasId,
                        'tahun_ajaran_id' => $newTahunAjaran->id,
                    ]);
                    $newKelasMap[$key] = $newKelas->id;
                }

                // 4. Process students
                foreach ($studentDecisions as $siswaId => $decision) {
                    $siswa = Siswa::with('kelas')->find($siswaId);
                    if (! $siswa) {
                        continue;
                    }
                    if (! $siswa->kelas) {
                        continue;
                    }

                    /** @var Kelas $currentKelas */
                    $currentKelas = $siswa->kelas;

                    // Always record the OLD enrollment before any change
                    SiswaKelasHistory::firstOrCreate(
                        [
                            'siswa_id' => $siswa->id,
                            'tahun_ajaran_id' => $this->activeTahunAjaran->id,
                        ],
                        ['kelas_id' => $currentKelas->id]
                    );

                    if ($decision === 'lulus') {
                        // History already recorded above; now delete the student
                        $user = $siswa->user;
                        $siswa->delete();
                        if ($user) {
                            $user->delete();
                        }
                    } elseif ($decision === 'naik') {
                        $nextTingkat = $currentKelas->getNextTingkatKelas();
                        if ($nextTingkat !== null) {
                            $newKelasKey = $nextTingkat.'_'.$currentKelas->grup_kelas;
                            if (isset($newKelasMap[$newKelasKey])) {
                                $newKelasId = $newKelasMap[$newKelasKey];
                                $siswa->update(['kelas_id' => $newKelasId]);

                                // Record NEW enrollment in next academic year
                                SiswaKelasHistory::firstOrCreate(
                                    [
                                        'siswa_id' => $siswa->id,
                                        'tahun_ajaran_id' => $newTahunAjaran->id,
                                    ],
                                    ['kelas_id' => $newKelasId]
                                );
                            }
                        }
                    } elseif ($decision === 'tinggal') {
                        // Stay in same grade -> ensure there is a new class with same grade & group
                        $newKelasKey = $currentKelas->tingkat_kelas.'_'.$currentKelas->grup_kelas;
                        if (! isset($newKelasMap[$newKelasKey])) {
                            $newKelas = Kelas::create([
                                'tingkat_kelas' => $currentKelas->tingkat_kelas,
                                'grup_kelas' => $currentKelas->grup_kelas,
                                'wali_kelas_id' => $currentKelas->wali_kelas_id,
                                'tahun_ajaran_id' => $newTahunAjaran->id,
                            ]);
                            $newKelasMap[$newKelasKey] = $newKelas->id;
                        }
                        $newKelasId = $newKelasMap[$newKelasKey];
                        $siswa->update(['kelas_id' => $newKelasId]);

                        // Record NEW enrollment (repeating same grade in new year)
                        SiswaKelasHistory::firstOrCreate(
                            [
                                'siswa_id' => $siswa->id,
                                'tahun_ajaran_id' => $newTahunAjaran->id,
                            ],
                            ['kelas_id' => $newKelasId]
                        );
                    }
                }
            });

            Notification::make()->title('Kenaikan Kelas Berhasil')->success()->send();
            $this->redirect(TahunAjaranResource::getUrl());
        } catch (Exception $e) {
            Notification::make()->title('Gagal Kenaikan Kelas')->body($e->getMessage())->danger()->send();
        }
    }
}
