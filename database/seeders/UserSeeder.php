<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates an admin user with a randomly generated password.
     */
    public function run(): void
    {
        // Generate a secure random password
        $password = Str::random(12);

        // Create Admin user
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@sippel.sch.id',
            'password' => Hash::make($password),
            'jenis_kelamin' => 'L',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Display credentials to console
        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║           ADMIN CREDENTIALS (SAVE THIS!)                 ║');
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->info('║  Email    : admin@sippel.sch.id                          ║');
        $this->command->info('║  Password : '.mb_str_pad($password, 44).'║');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');
        $this->command->newLine();
        $this->command->warn('⚠️  Please save this password securely. It cannot be recovered!');
        $this->command->newLine();
    }
}
