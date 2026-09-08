<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        $comptes = [
            ['email' => 'admin@ecole.tg', 'name' => 'Administrateur', 'role' => 'admin'],
            ['email' => 'directeur@ecole.tg', 'name' => 'Directeur', 'role' => 'direction'],
            ['email' => 'secretaire@ecole.tg', 'name' => 'Secrétaire', 'role' => 'secretaire'],
            ['email' => 'comptable@ecole.tg', 'name' => 'Comptable', 'role' => 'comptable'],
            ['email' => 'enseignant@ecole.tg', 'name' => 'Enseignant', 'role' => 'enseignant'],
            ['email' => 'surveillant@ecole.tg', 'name' => 'Surveillant', 'role' => 'surveillant'],
            ['email' => 'eleve@ecole.tg', 'name' => 'Élève', 'role' => 'eleve'],
            ['email' => 'parent@ecole.tg', 'name' => 'Tuteur', 'role' => 'parent'],
        ];

        foreach ($comptes as $compte) {
            $user = User::firstOrCreate(
                ['email' => $compte['email']],
                [
                    'name' => $compte['name'],
                    'password' => bcrypt('password'),
                ]
            );

            $user->syncRoles([$compte['role']]);
        }
    }
}