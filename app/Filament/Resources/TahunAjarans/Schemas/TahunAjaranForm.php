<?php

declare(strict_types=1);

namespace App\Filament\Resources\TahunAjarans\Schemas;

use App\Models\TahunAjaran;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

final class TahunAjaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_tahun')
                    ->label('Nama Tahun Ajaran')
                    ->required()
                    ->maxLength(50)
                    ->placeholder('Contoh: 2025/2026')
                    ->helperText('Format: YYYY/YYYY'),

                Select::make('semester')
                    ->label('Semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap' => 'Genap',
                    ])
                    ->required()
                    ->default('Ganjil')
                    ->native(false),

                DatePicker::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),

                DatePicker::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->after('tanggal_mulai'),

                Toggle::make('status')
                    ->label('Status Aktif')
                    ->default(false)
                    ->helperText('Hanya satu tahun ajaran yang dapat aktif pada satu waktu')
                    ->inline(false)
                    ->live()
                    ->afterStateUpdated(function ($state, $record): void {
                        if ($state) {
                            // Check if another academic year is already active
                            $activeExists = TahunAjaran::where('status', true)
                                ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                ->exists();

                            if ($activeExists) {
                                throw ValidationException::withMessages([
                                    'status' => 'Tidak dapat mengaktifkan tahun ajaran ini karena ada tahun ajaran lain yang masih aktif. Harap nonaktifkan tahun ajaran yang aktif terlebih dahulu.',
                                ]);
                            }
                        }
                    }),
            ]);
    }
}
