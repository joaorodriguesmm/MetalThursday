<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Notifications\NotificacaoInteracaoUtilizador;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os retratos escalares das notificações de interações.
 *
 * @since 2.0.0
 */
final class NotificacaoInteracaoUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que uma interação numa secção conserva o contexto original e
     * não consulta a base de dados durante a construção das mensagens.
     *
     * @since 2.0.0
     */
    #[Test]
    public function seccao_preserva_retrato_sem_consultas_posteriores(): void
    {
        $autorMetalThursday = Utilizador::factory()
            ->create();

        $causador = Utilizador::factory()
            ->create([
                'nome' => 'Causador original',
            ]);

        $destinatario = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comAutor(
                $autorMetalThursday,
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->comDados(
                'album',
                'Álbum',
                'Apresentação de um álbum.',
            )
            ->create();

        $artista = Artista::factory()
            ->comNome(
                'Artista original',
            )
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->comArtista(
                $artista,
            )
            ->comConteudo(
                'Descrição original.',
                'Título original',
            )
            ->create();

        $identificadorMetalThursday =
            (int) $metalThursday->getKey();

        $notificacao = unserialize(
            serialize(
                new NotificacaoInteracaoUtilizador(
                    $seccao,
                    $causador,
                    'avaliou',
                ),
            ),
            [
                'allowed_classes' => true,
            ],
        );

        self::assertInstanceOf(
            NotificacaoInteracaoUtilizador::class,
            $notificacao,
        );

        $causador->updateOrFail([
            'nome' => 'Causador alterado',
        ]);

        $tipoSeccao->updateOrFail([
            'nome' => 'Tipo alterado',
        ]);

        $artista->updateOrFail([
            'nome' => 'Artista alterado',
        ]);

        $seccao->updateOrFail([
            'titulo' => 'Título alterado',
        ]);

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] = $consulta->sql;
            },
        );

        $dados = $notificacao->toArray(
            $destinatario,
        );

        $notificacao->toMail(
            $destinatario,
        );

        self::assertSame(
            'Causador original avaliou a secção Álbum de Artista original — «Título original».',
            $dados['mensagem'],
        );

        self::assertSame(
            route(
                'metal-thursday.detalhes',
                [
                    'metalThursday' => $identificadorMetalThursday,
                ],
            ),
            $dados['ligacao'],
        );

        self::assertSame(
            [],
            $consultas,
        );
    }

    /**
     * Confirma que uma interação num comentário conserva o autor e o contexto,
     * mantendo a mensagem específica para o autor do comentário.
     *
     * @since 2.0.0
     */
    #[Test]
    public function comentario_preserva_autor_e_contexto_sem_consultas(): void
    {
        $autorComentario = Utilizador::factory()
            ->create([
                'nome' => 'Autor original',
            ]);

        $causador = Utilizador::factory()
            ->create([
                'nome' => 'Causador original',
            ]);

        $outroDestinatario = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comNome(
                'Contexto original',
            )
            ->create();

        $comentario = $metalThursday
            ->comentarios()
            ->create([
                'utilizador_id' => $autorComentario->getKey(),

                'conteudo' => 'Comentário original.',

                'comentario_pai_id' => null,
            ]);

        self::assertInstanceOf(
            Comentario::class,
            $comentario,
        );

        $notificacao = unserialize(
            serialize(
                new NotificacaoInteracaoUtilizador(
                    $comentario,
                    $causador,
                    'gostou',
                ),
            ),
            [
                'allowed_classes' => true,
            ],
        );

        self::assertInstanceOf(
            NotificacaoInteracaoUtilizador::class,
            $notificacao,
        );

        $autorComentario->updateOrFail([
            'nome' => 'Autor alterado',
        ]);

        $causador->updateOrFail([
            'nome' => 'Causador alterado',
        ]);

        $metalThursday->updateOrFail([
            'nome' => 'Contexto alterado',
        ]);

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] = $consulta->sql;
            },
        );

        $dadosAutor = $notificacao->toArray(
            $autorComentario,
        );

        $dadosOutroDestinatario = $notificacao->toArray(
            $outroDestinatario,
        );

        $notificacao->toMail(
            $autorComentario,
        );

        self::assertSame(
            'Causador original gostou do teu comentário em MetalThursday «Contexto original».',
            $dadosAutor['mensagem'],
        );

        self::assertSame(
            'Causador original gostou de um comentário de Autor original em MetalThursday «Contexto original».',
            $dadosOutroDestinatario['mensagem'],
        );

        self::assertSame(
            [],
            $consultas,
        );
    }
}
