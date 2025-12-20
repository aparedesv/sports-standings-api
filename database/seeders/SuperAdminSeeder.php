<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Crear el rol super_admin si no existeix
        $role = Role::firstOrCreate(['name' => 'super_admin']);

        // Crear l'usuari super_admin
        $user = User::firstOrCreate(
            ['email' => 'info@osonaweb.cat'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin1234'),
                'email_verified_at' => now(),
            ]
        );

        // Assignar el rol super_admin
        if (!$user->hasRole('super_admin')) {
            $user->assignRole($role);
        }

        $this->command->info('Super Admin creat correctament: info@osonaweb.cat');
    }
}
