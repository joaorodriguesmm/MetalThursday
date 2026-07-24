<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Database\Seeder;

/**
 * Regista as permissões de envio de email disponíveis na aplicação.
 *
 * O nome `Seeder` permanece em inglês por corresponder à convenção utilizada
 * pelo Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class PermissaoEmailSeeder extends Seeder
{
    /**
     * Permissões de email disponibilizadas aos utilizadores.
     *
     * @var list<array{
     *     nome: string,
     *     identificador: string,
     *     descricao: string
     * }>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSOES = [
        [
            'nome' => 'Todas as notificações',

            'identificador' => 'todas_notificacoes',

            'descricao' => 'Receber emails relativos a todas as atividades da MetalThursday.',
        ],
        [
            'nome' => 'Novas publicações',

            'identificador' => 'novas_publicacoes',

            'descricao' => 'Receber um email sempre que for publicada uma nova MetalThursday.',
        ],
        [
            'nome' => 'Todas as interações',

            'identificador' => 'todas_interacoes',

            'descricao' => 'Receber emails sobre comentários, avaliações, gostos e outras interações realizadas em qualquer publicação.',
        ],
        [
            'nome' => 'Interações nas minhas publicações',

            'identificador' => 'interacoes_nas_minhas_publicacoes',

            'descricao' => 'Receber emails sobre comentários, avaliações, gostos e outras interações realizadas nas minhas publicações.',
        ],
        [
            'nome' => 'Lembrete diário de tarefas',

            'identificador' => 'lembrete_diario_tarefas',

            'descricao' => 'Receber um email no dia em que exista uma tarefa por concluir, como a submissão de uma MetalThursday.',
        ],
        [
            'nome' => 'Lembrete diário de atrasos',

            'identificador' => 'lembrete_diario_atrasos',

            'descricao' => 'Receber diariamente um email quando existir uma tarefa em atraso, como uma MetalThursday ainda não submetida.',
        ],
        [
            'nome' => 'Nomeação para uma MetalThursday',

            'identificador' => 'alerta_nomeacao',

            'descricao' => 'Receber um email quando for nomeado para apresentar a próxima MetalThursday.',
        ],
    ];

    /**
     * Regista ou atualiza as permissões de email.
     *
     * O nome `run` permanece em inglês por corresponder ao método
     * convencional dos seeders do Laravel.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function run(): void
    {
        foreach (
            self::PERMISSOES as $permissao
        ) {
            PermissaoEmail::query()->updateOrCreate(
                [
                    'identificador' => $permissao['identificador'],
                ],
                [
                    'nome' => $permissao['nome'],

                    'descricao' => $permissao['descricao'],
                ],
            );
        }
    }
}
