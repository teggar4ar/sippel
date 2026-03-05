<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts a Filament resource so only admin users can see it in the
 * navigation and access it. Apply this trait to any Resource that must be
 * hidden from teacher/student roles.
 */
trait AdminOnlyResource
{
    public static function shouldRegisterNavigation(): bool
    {
        return self::isAdminUser();
    }

    public static function canAccess(): bool
    {
        return self::isAdminUser();
    }

    private static function isAdminUser(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('admin');
    }
}
