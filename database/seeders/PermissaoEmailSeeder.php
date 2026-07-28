<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Regista as permissões de comunicação por correio eletrónico.
 *
 * O nome `Seeder` permanece em inglês por corresponder à convenção utilizada
 * pelo Laravel.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class PermissaoEmailSeeder extends Seeder
{
    /**
     * Permissões disponibilizadas aos utilizadores.
     *
     * @var list<array{
     *     identificador: string,
     *     nome: string,
     *     descricao: string,
     *     ordem: int
     * }>
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const PERMISSOES = [
        [
            'identificador' => 'todas_notificacoes',

            'nome' => 'Todas as notificações',

            'descricao' => 'Receber e-mails relativos a todas as atividades da MetalThursday.',

            'ordem' => 1,
        ],
        [
            'identificador' => 'novas_publicacoes',

            'nome' => 'Novas publicações',

            'descricao' => 'Receber um e-mail sempre que for publicada uma nova MetalThursday.',

            'ordem' => 2,
        ],
        [
            'identificador' => 'todas_interacoes',

            'nome' => 'Todas as interações',

            'descricao' => 'Receber e-mails sobre comentários, avaliações, gostos e outras interações realizadas em qualquer publicação.',

            'ordem' => 3,
        ],
        [
            'identificador' => 'interacoes_nas_minhas_publicacoes',

            'nome' => 'Interações nas minhas publicações',

            'descricao' => 'Receber e-mails sobre comentários, avaliações, gostos e outras interações realizadas nas minhas publicações.',

            'ordem' => 4,
        ],
        [
            'identificador' => 'lembrete_diario_tarefas',

            'nome' => 'Lembrete diário de tarefas',

            'descricao' => 'Receber um e-mail no dia em que exista uma tarefa por concluir, como a submissão de uma MetalThursday.',

            'ordem' => 5,
        ],
        [
            'identificador' => 'lembrete_diario_atrasos',

            'nome' => 'Lembrete diário de atrasos',

            'descricao' => 'Receber diariamente um e-mail quando existir uma tarefa em atraso, como uma MetalThursday ainda não submetida.',

            'ordem' => 6,
        ],
        [
            'identificador' => 'alerta_nomeacao',

            'nome' => 'Nomeação para uma MetalThursday',

            'descricao' => 'Receber um e-mail quando for nomeado para apresentar a próxima MetalThursday.',

            'ordem' => 7,
        ],
    ];

    /**
     * Regista ou atualiza as permissões.
     *
     * O nome `run` permanece em inglês por corresponder ao método
     * convencional dos seeders do Laravel.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function run(): void
    {
        $agora = now();

        /** @var list<array{
         *     identificador: string,
         *     nome: string,
         *     descricao: string,
         *     ordem: int,
         *     created_at: mixed,
         *     updated_at: mixed
         * }> $registos
         */
        $registos = array_map(
            static fn (
                array $permissao,
            ): array => [
                ...$permissao,

                'created_at' => $agora,

                'updated_at' => $agora,
            ],
            self::PERMISSOES,
        );

        DB::table(
            'permissoes_email',
        )->upsert(
            $registos,
            [
                'identificador',
            ],
            [
                'nome',
                'descricao',
                'ordem',
                'updated_at',
            ],
        );
    }
}
