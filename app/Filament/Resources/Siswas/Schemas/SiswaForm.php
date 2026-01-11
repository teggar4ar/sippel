<?php

declare(strict_types=1);

namespace App\Filament\Resources\Siswas\Schemas;

use App\Models\Kelas;
use App\Models\Siswa;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class SiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    // Step 1: Student Data
                    Step::make('Data Siswa')
                        ->description('Masukkan NIS siswa')
                        ->schema([
                            Checkbox::make('use_temporary_nis')
                                ->label('Gunakan NIS Sementara')
                                ->helperText('Centang jika siswa belum memiliki NIS')
                                ->live()
                                ->afterStateUpdated(function (bool $state, callable $set): void {
                                    if ($state) {
                                        $set('nis', self::generateTemporaryNis());
                                    } else {
                                        $set('nis', '');
                                    }
                                })
                                ->dehydrated(false)
                                ->visible(fn (string $operation): bool => $operation === 'create'),

                            TextInput::make('nis')
                                ->label('NIS (Nomor Induk Siswa)')
                                ->required()
                                ->tel()
                                ->length(10)
                                ->unique(ignoreRecord: true)
                                ->placeholder('Contoh: 0012345678')
                                ->helperText(
                                    fn ($get): string => $get('use_temporary_nis')
                                        ? 'NIS sementara telah digenerate. Dapat diubah nanti.'
                                        : 'NIS harus 10 digit angka dan unik'
                                )
                                ->mask('9999999999')
                                ->rules(['regex:/^\d{10}$/'])
                                ->disabled(fn ($get): bool => (bool) $get('use_temporary_nis'))
                                ->dehydrated(),
                        ]),

                    // Step 2: User Account Data
                    Step::make('Akun Pengguna')
                        ->description('Data akun untuk login siswa')
                        ->schema([
                            TextInput::make('user.name')
                                ->label('Nama Lengkap')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('Contoh: Ahmad Rizki'),

                            TextInput::make('user.email')
                                ->label('Email')
                                ->required()
                                ->email()
                                ->unique('users', 'email', modifyRuleUsing: function ($rule, $get, $record) {
                                    // When editing, ignore the current user's email
                                    if ($record && $record->user) {
                                        return $rule->ignore($record->user->id);
                                    }

                                    return $rule;
                                })
                                ->placeholder('Contoh: ahmad.rizki@email.com'),

                            TextInput::make('user.password')
                                ->label('Password')
                                ->password()
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->minLength(8)
                                ->dehydrated(fn ($state): bool => filled($state))
                                ->placeholder('Minimal 8 karakter')
                                ->helperText('Kosongkan jika tidak ingin mengubah password'),

                            Select::make('user.jenis_kelamin')
                                ->label('Jenis Kelamin')
                                ->required()
                                ->options([
                                    'L' => 'Laki-laki',
                                    'P' => 'Perempuan',
                                ])
                                ->placeholder('Pilih jenis kelamin'),
                        ]),

                    // Step 3: Class Assignment
                    Step::make('Penempatan Kelas')
                        ->description('Pilih kelas untuk siswa')
                        ->schema([
                            Select::make('kelas_id')
                                ->label('Kelas')
                                ->required()
                                ->relationship(
                                    'kelas',
                                    'id',
                                    fn ($query) => $query->whereHas('tahunAjaran', function ($q): void {
                                        $q->where('status', true);
                                    })
                                )
                                ->getOptionLabelFromRecordUsing(
                                    fn (Kelas $record): string => "{$record->tingkat_kelas}{$record->grup_kelas} - {$record->tahunAjaran->nama_tahun} {$record->tahunAjaran->semester}"
                                )
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih kelas')
                                ->helperText('Menampilkan kelas dari tahun ajaran aktif')
                                ->dehydrated(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    /**
     * Generate a unique temporary NIS with format: 9XXXXXXXXX (starts with 9)
     * The prefix '9' indicates it's a temporary NIS
     */
    public static function generateTemporaryNis(): string
    {
        do {
            // Format: 9 + timestamp last 5 digits + 4 random digits
            $timestamp = mb_substr((string) time(), -5);
            $random = mb_str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $nis = '9'.$timestamp.$random;
        } while (Siswa::where('nis', $nis)->exists());

        return $nis;
    }
}
