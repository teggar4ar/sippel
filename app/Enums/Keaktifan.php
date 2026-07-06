<?php

declare(strict_types=1);

namespace App\Enums;

enum Keaktifan: string
{
    case Pasif = 'pasif';
    case Cukup = 'cukup';
    case Aktif = 'aktif';
    case SangatAktif = 'sangat_aktif';

    public static function fromAverage(float $average): self
    {
        return match (true) {
            $average < 1.5 => self::Pasif,
            $average < 2.5 => self::Cukup,
            $average < 3.5 => self::Aktif,
            default => self::SangatAktif,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pasif => 'Pasif',
            self::Cukup => 'Cukup',
            self::Aktif => 'Aktif',
            self::SangatAktif => 'Sangat Aktif',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Pasif => 1,
            self::Cukup => 2,
            self::Aktif => 3,
            self::SangatAktif => 4,
        };
    }
}
