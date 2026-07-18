<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder para a tabela users.
 *
 * @since 1.0
 * @version 1.0
 */
class UserSeeder extends Seeder
{
    /**
     * Executa o seeder.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'metal-thursday@joaorodrigues-multimedia.pt'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'invite_code' => 'ADMIN-CONVITE-001',
            ]
        );

        $inviteUsers = [
            'João Rodrigues',
            'Carolina Torres',
            'Fábio Gomes',
            'Paulo Barros',
            'Pedro Barros',
            'Samuel Gomes'
        ];

        foreach ($inviteUsers as $name) {
            User::firstOrCreate(
                ['name' => $name],
                [
                    'invite_code' => strtoupper('MT-' . Str::random(12)),
                ]
            );
        }
    }
}
