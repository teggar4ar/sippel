<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin user
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@sippel.sch.id',
            'password' => Hash::make('admin123'),
            'jenis_kelamin' => 'L',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Create Teacher user
        $teacher = User::create([
            'name' => 'Guru Contoh',
            'email' => 'teacher@sippel.sch.id',
            'password' => Hash::make('teacher123'),
            'jenis_kelamin' => 'L',
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('teacher');

        // Create Student user
        $student = User::create([
            'name' => 'Siswa Contoh',
            'email' => 'student@sippel.sch.id',
            'password' => Hash::make('student123'),
            'jenis_kelamin' => 'L',
            'email_verified_at' => now(),
        ]);
        $student->assignRole('student');
    }
}
