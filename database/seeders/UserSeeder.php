<?php

namespace Database\Seeders;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder para a tabela users.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class UserSeeder extends Seeder
{
    /**
     * Executa o seeder.
     *
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function run(): void
    {
        Utilizador::firstOrCreate(
            ['email' => 'metal-thursday@joaorodrigues-multimedia.pt'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $inviteUsers = [
            'João Rodrigues',
            'Carolina Torres',
            'Fábio Gomes',
            'Paulo Barros',
            'Pedro Barros',
            'Samuel Gomes',
        ];

        foreach ($inviteUsers as $name) {
            Utilizador::firstOrCreate(
                ['name' => $name],
            );
        }
    }
}
