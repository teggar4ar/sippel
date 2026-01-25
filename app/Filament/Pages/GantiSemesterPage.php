<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\TahunAjarans\TahunAjaranResource;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

final class GantiSemesterPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Ganti Semester';

    protected static ?string $title = 'Ganti Semester';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.ganti-semester';

    // Form data
    public ?array $data = [];

    // Context data
    public ?TahunAjaran $activeTahunAjaran = null;

    /** @var Collection<int, Kelas> */
    public Collection $currentClasses;

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

        if ($this->activeTahunAjaran) {
             // Load classes first as they are needed for schema generation
            $this->currentClasses = Kelas::where('tahun_ajaran_id', $this->activeTahunAjaran->id)
                ->with(['waliKelas', 'siswa'])
                ->orderBy('tingkat_kelas')
                ->orderBy('grup_kelas')
                ->get();

            $defaults['namaTahun'] = $this->activeTahunAjaran->nama_tahun;
            $defaults['semester'] = $this->activeTahunAjaran->isGanjil() ? 'Genap' : 'Ganjil';
            
            // Initialize wali kelas assignments
            $assignments = [];
            foreach ($this->currentClasses as $kelas) {
                $assignments[$kelas->id] = $kelas->wali_kelas_id;
            }
            $defaults['waliKelasAssignments'] = $assignments;
        }

        $this->form->fill($defaults);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Semester Baru')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('namaTahun')
                                        ->label('Nama Tahun Ajaran')
                                        ->required()
                                        ->maxLength(20)
                                        ->placeholder('2025/2026'),
                                    Select::make('semester')
                                        ->options([
                                            'Ganjil' => 'Ganjil',
                                            'Genap' => 'Genap',
                                        ])
                                        ->required()
                                        ->disabled()
                                        ->dehydrated(),
                                    DatePicker::make('tanggalMulai')
                                        ->label('Tanggal Mulai')
                                        ->required()
                                        // Standard format Y-m-d handled by Filament
                                        ->native(false),
                                    DatePicker::make('tanggalSelesai')
                                        ->label('Tanggal Selesai')
                                        ->required()
                                        ->after('tanggalMulai')
                                        ->native(false),
                                ])->columns(2),
                        ]),
                    Wizard\Step::make('Wali Kelas')
                        ->schema(function () {
                            $fields = [];
                            foreach ($this->currentClasses as $kelas) {
                                $fields[] = Select::make("waliKelasAssignments.{$kelas->id}")
                                    ->label("Wali Kelas {$kelas->tingkat_kelas} {$kelas->grup_kelas}")
                                    ->options(User::role('teacher')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required();
                            }
                            return [Section::make('Daftar Kelas')->schema($fields)->columns(2)];
                        }),
                    Wizard\Step::make('Konfirmasi')
                        ->schema([
                           Section::make('Ringkasan')
                                ->schema([
                                    Placeholder::make('summary_tahun')
                                        ->label('Tahun Ajaran Baru')
                                        ->content(fn ($get) => "{$get('namaTahun')} - Semester {$get('semester')}"),
                                    Placeholder::make('summary_dates')
                                        ->label('Periode')
                                        ->content(fn ($get) => "{$get('tanggalMulai')} s/d {$get('tanggalSelesai')}"),
                                    Placeholder::make('summary_classes')
                                        ->label('Jumlah Kelas')
                                        ->content($this->currentClasses->count()),
                                    Placeholder::make('summary_students')
                                        ->label('Total Siswa')
                                        ->content($this->getTotalStudentsProperty()),
                                    Placeholder::make('info')
                                        ->content(new HtmlString('<span class="text-warning-600 font-medium">Perhatian: Tahun ajaran lama akan dinonaktifkan. Pastikan semua data sudah benar.</span>')),
                                ])
                        ]),
                ])
                ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary relative grid-flow-col items-center justify-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition duration-75 focus:ring-2 focus:ring-primary-500/50 cursor-pointer">Simpan & Proses</button>'))
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

        // Manual validation for duplicate/existing year
        $exists = TahunAjaran::where('nama_tahun', $namaTahun)
            ->where('semester', $semester)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Tahun Ajaran Sudah Ada')
                ->body("Tahun ajaran {$namaTahun} semester {$semester} sudah ada.")
                ->danger()
                ->send();
            return;
        }

        try {
            DB::transaction(function () use ($namaTahun, $semester, $tanggalMulai, $tanggalSelesai, $waliKelasAssignments) {
                // 1. Deactivate current tahun ajaran
                if ($this->activeTahunAjaran) {
                    $this->activeTahunAjaran->update(['status' => false]);
                }

                // 2. Create new tahun ajaran
                $newTahunAjaran = TahunAjaran::create([
                    'nama_tahun' => $namaTahun,
                    'semester' => $semester,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                    'status' => true,
                ]);

                // 3. Create new classes and migrate students
                $kelasMapping = []; // old_kelas_id => new_kelas_id

                foreach ($this->currentClasses as $oldKelas) {
                    $newKelas = Kelas::create([
                        'tingkat_kelas' => $oldKelas->tingkat_kelas,
                        'grup_kelas' => $oldKelas->grup_kelas,
                        'wali_kelas_id' => $waliKelasAssignments[$oldKelas->id] ?? null,
                        'tahun_ajaran_id' => $newTahunAjaran->id,
                    ]);

                    $kelasMapping[$oldKelas->id] = $newKelas->id;
                }

                // 4. Migrate students to new classes
                foreach ($this->currentClasses as $oldKelas) {
                    $newKelasId = $kelasMapping[$oldKelas->id];
                    Siswa::where('kelas_id', $oldKelas->id)
                        ->update(['kelas_id' => $newKelasId]);
                }
            });

            Notification::make()
                ->title('Ganti Semester Berhasil')
                ->body("Berhasil beralih ke semester {$semester} tahun {$namaTahun}.")
                ->success()
                ->send();

            $this->redirect(TahunAjaranResource::getUrl());
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Ganti Semester')
                ->body('Terjadi kesalahan: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getTotalStudentsProperty(): int
    {
        return $this->currentClasses->sum(fn ($kelas) => $kelas->siswa->count());
    }
}
