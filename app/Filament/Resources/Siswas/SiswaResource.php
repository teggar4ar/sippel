<?php

declare(strict_types=1);

namespace App\Filament\Resources\Siswas;

use App\Filament\Resources\Siswas\Pages\CreateSiswa;
use App\Filament\Resources\Siswas\Pages\EditSiswa;
use App\Filament\Resources\Siswas\Pages\ListSiswas;
use App\Filament\Resources\Siswas\Schemas\SiswaForm;
use App\Filament\Resources\Siswas\Tables\SiswasTable;
use App\Models\Siswa;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

final class SiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'nis';

    protected static ?string $modelLabel = 'Siswa';

    protected static ?string $pluralModelLabel = 'Siswa';

    /**
     * Hide Siswa navigation from non-admin users
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Restrict access to admin users only
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public static function getNavigationGroup(): string
    {
        return 'Manajemen';
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return SiswaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiswasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiswas::route('/'),
            'create' => CreateSiswa::route('/create'),
            'edit' => EditSiswa::route('/{record}/edit'),
        ];
    }

    /**
     * Eager load relationships to prevent N+1 queries
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'kelas.tahunAjaran']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
