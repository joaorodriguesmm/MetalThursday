<?php

namespace Database\Seeders;

use App\Models\EmailPermission;
use Illuminate\Database\Seeder;

/**
 * Seeder para a tabela email_permissions.
 *
 * @since 1.0
 * @version 1.0
 */
class EmailPermissionSeeder extends Seeder
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
        $permissions = [
            [
                'name' => 'Todas as notificações',
                'slug' => 'all',
                'description' => 'Receber e-mail de todas as atividades da MetalThursday.'
            ],
            [
                'name' => 'Novas publicações',
                'slug' => 'new-posts',
                'description' => 'Receber e-mail quando houver novas publicações.'
            ],
            [
                'name' => 'Todas as interações',
                'slug' => 'new-interactions',
                'description' => 'Receber e-mail quando houver interações (comentários, avaliações, etc.) em qualquer publicação.'
            ],
            [
                'name' => 'Interações nas tuas publicações',
                'slug' => 'interactions-on-my-posts',
                'description' => 'Receber e-mail quando houver interações (comentários, avaliações, etc.) nas tuas publicações.'
            ],
            [
                'name' => 'Lembrete se tens alguma coisa para fazer nesse dia',
                'slug' => 'daily-reminder',
                'description' => 'Receber e-mail de lembrete no dia em que tenho alguma coisa para fazer (Ex: Dia de submeteres a tua MetalThursday)'
            ],
            [
                'name' => 'Lembrete diário de atrasos',
                'slug' => 'overdue-reminder',
                'description' => 'Receber e-mail de lembrete diário se estiveres atrasado em alguma coisa para fazer (Ex: Já passou do dia de submeteres a tua MetalThursday).'
            ],
            [
                'name' => 'Quando fores nomeado para uma MetalThursday',
                'slug' => 'nomination-alert',
                'description' => 'Receber um e-mail quando fores nomeado para apresentar a próxima MetalThursday.'
            ],
        ];

        foreach ($permissions as $permission) {
            EmailPermission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
