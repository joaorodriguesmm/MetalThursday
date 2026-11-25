<?php

declare(strict_types=1);

namespace Tests\Unit\Enumeracoes;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use PHPUnit\Framework\TestCase;

/**
 * Testa o catálogo das permissões de comunicação por e-mail.
 *
 * @since 2.0.0
 */
final class IdentificadorPermissaoEmailTest extends TestCase
{
    /**
     * Confirma os identificadores técnicos públicos das permissões.
     *
     * @since 2.0.0
     */
    public function test_define_identificadores_publicos(): void
    {
        self::assertSame(
            [
                'todas_notificacoes',
                'novas_publicacoes',
                'interacoes_nas_minhas_publicacoes',
                'lembrete_diario_tarefas',
                'lembrete_diario_atrasos',
                'alerta_nomeacao',
                'novas_denuncias_comentarios',
                'decisoes_denuncias_comentarios',
            ],
            array_map(
                static fn (
                    IdentificadorPermissaoEmail $permissao,
                ): string => $permissao->value,
                IdentificadorPermissaoEmail::cases(),
            ),
        );
    }

    /**
     * Confirma que todas as permissões possuem metadados válidos.
     *
     * @since 2.0.0
     */
    public function test_define_metadados_validos_e_unicos(): void
    {
        $nomes = [];
        $ordens = [];

        foreach (
            IdentificadorPermissaoEmail::cases() as $permissao
        ) {
            self::assertNotSame(
                '',
                trim(
                    $permissao->nome(),
                ),
            );

            self::assertNotSame(
                '',
                trim(
                    $permissao->descricao(),
                ),
            );

            self::assertGreaterThanOrEqual(
                1,
                $permissao->ordem(),
            );

            self::assertLessThanOrEqual(
                255,
                $permissao->ordem(),
            );

            $nomes[] =
                $permissao->nome();

            $ordens[] =
                $permissao->ordem();
        }

        self::assertCount(
            count(
                $nomes,
            ),
            array_unique(
                $nomes,
            ),
        );

        self::assertCount(
            count(
                $ordens,
            ),
            array_unique(
                $ordens,
            ),
        );
    }

    /**
     * Confirma que apenas a permissão global é identificada como tal.
     *
     * @since 2.0.0
     */
    public function test_distingue_permissao_global(): void
    {
        foreach (
            IdentificadorPermissaoEmail::cases() as $permissao
        ) {
            self::assertSame(
                $permissao
                    === IdentificadorPermissaoEmail::TodasNotificacoes,
                $permissao->eGlobal(),
            );
        }
    }

    /**
     * Confirma os metadados públicos exatos do catálogo.
     *
     * @since 2.0.0
     */
    public function test_define_metadados_publicos_exatos(): void
    {
        $esperados = [
            IdentificadorPermissaoEmail::TodasNotificacoes->value => [
                'nome' => 'Todas as notificações',
                'descricao' => 'Receber e-mails relativos a todas as atividades da MetalThursday.',
                'ordem' => 1,
            ],

            IdentificadorPermissaoEmail::NovasPublicacoes->value => [
                'nome' => 'Novas publicações',
                'descricao' => 'Receber um e-mail sempre que for publicada uma nova MetalThursday.',
                'ordem' => 2,
            ],

            IdentificadorPermissaoEmail::InteracoesNasMinhasPublicacoes->value => [
                'nome' => 'Interações relacionadas comigo',
                'descricao' => 'Receber e-mails quando alguém comentar ou avaliar uma publicação minha, responder a um comentário meu ou lhe atribuir um gosto.',
                'ordem' => 4,
            ],

            IdentificadorPermissaoEmail::LembreteDiarioTarefas->value => [
                'nome' => 'Lembrete diário de tarefas',
                'descricao' => 'Receber um e-mail no dia em que exista uma tarefa por concluir, como a submissão de uma MetalThursday.',
                'ordem' => 5,
            ],

            IdentificadorPermissaoEmail::LembreteDiarioAtrasos->value => [
                'nome' => 'Lembrete diário de atrasos',
                'descricao' => 'Receber diariamente um e-mail quando existir uma tarefa em atraso, como uma MetalThursday ainda não submetida.',
                'ordem' => 6,
            ],

            IdentificadorPermissaoEmail::AlertaNomeacao->value => [
                'nome' => 'Nomeação para uma MetalThursday',
                'descricao' => 'Receber um e-mail quando for nomeado para apresentar a próxima MetalThursday.',
                'ordem' => 7,
            ],

            IdentificadorPermissaoEmail::NovasDenunciasComentarios->value => [
                'nome' => 'Novas denúncias de comentários',
                'descricao' => 'Receber um e-mail quando for apresentada uma denúncia de comentário, enquanto tiveres o papel de superadministrador.',
                'ordem' => 8,
            ],

            IdentificadorPermissaoEmail::DecisoesDenunciasComentarios->value => [
                'nome' => 'Decisões sobre as minhas denúncias',
                'descricao' => 'Receber um e-mail quando a moderação decidir sobre uma denúncia apresentada por mim.',
                'ordem' => 9,
            ],
        ];

        $atuais = [];

        foreach (
            IdentificadorPermissaoEmail::cases() as $permissao
        ) {
            $atuais[$permissao->value] = [
                'nome' => $permissao->nome(),

                'descricao' => $permissao->descricao(),

                'ordem' => $permissao->ordem(),
            ];
        }

        self::assertSame(
            $esperados,
            $atuais,
        );
    }
}
