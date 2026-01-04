<?php

declare(strict_types=1);

namespace App\Filament\Resources\Kelas\Schemas;

use App\Models\TahunAjaran;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

final class KelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('tingkat_kelas')
                    ->label('Tingkat Kelas')
                    ->options([
                        7 => '7 (Kelas 7)',
                        8 => '8 (Kelas 8)',
                        9 => '9 (Kelas 9)',
                    ])
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->live()
                    ->columnSpan(1),

                Select::make('grup_kelas')
                    ->label('Grup Kelas')
                    ->options(fn () => collect(range('A', 'Z'))->mapWithKeys(fn ($letter): array => [$letter => $letter])->toArray())
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->live()
                    ->columnSpan(1),

                Select::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->options(fn () => TahunAjaran::query()
                        ->get()
                        ->mapWithKeys(fn ($ta): array => [$ta->id => $ta->nama_tahun.' - '.$ta->semester])
                        ->toArray())
                    ->default(fn () => TahunAjaran::where('status', true)->first()?->id)
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->live()
                    ->helperText('Tahun ajaran yang aktif dipilih secara default')
                    ->columnSpan(2),

                Select::make('wali_kelas_id')
                    ->label('Wali Kelas')
                    ->relationship(
                        name: 'waliKelas',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->role('teacher')
                    )
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->required()
                    ->native(false)
                    ->helperText('Pilih guru yang akan menjadi wali kelas')
                    ->columnSpan(2),
            ]);
    }
}
