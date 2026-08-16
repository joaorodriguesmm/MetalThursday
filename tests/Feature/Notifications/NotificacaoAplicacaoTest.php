<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Notifications\NotificacaoAplicacao;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o comportamento comum das notificações da aplicação.
 *
 * @since 2.0.0
 */
final class NotificacaoAplicacaoTest extends TestCase
{
    /**
     * Confirma que um destinatário sem endereço de e-mail não provoca a
     * consulta das respetivas permissões.
     *
     * @since 2.0.0
     */
    #[Test]
    public function destinatario_sem_email_nao_consulta_permissoes(): void
    {
        $utilizador =
            new Utilizador;

        $utilizador->setRawAttributes(
            [
                'id' => 1,

                'nome' => 'Utilizador sem e-mail',

                'email' => null,
            ],
            true,
        );

        $utilizador->exists =
            true;

        $notificacao =
            new class extends NotificacaoAplicacao
            {
                /**
                 * Número de verificações das preferências de e-mail.
                 *
                 * @since 2.0.0
                 */
                public int $numeroVerificacoesPermissao =
                    0;

                /**
                 * Determina se o destinatário autorizou o envio.
                 *
                 * @param  Utilizador  $utilizador  Utilizador destinatário.
                 * @return bool Verdadeiro quando possui a permissão.
                 *
                 * @since 2.0.0
                 */
                protected function deveEnviarPorEmail(
                    Utilizador $utilizador,
                ): bool {
                    $this->numeroVerificacoesPermissao++;

                    return $utilizador->temPermissaoEmail(
                        'todas',
                    );
                }

                /**
                 * Obtém o assunto da mensagem.
                 *
                 * @param  Utilizador  $utilizador  Utilizador destinatário.
                 * @return string Assunto da mensagem.
                 *
                 * @since 2.0.0
                 */
                protected function obterAssunto(
                    Utilizador $utilizador,
                ): string {
                    return 'Assunto de teste';
                }

                /**
                 * Obtém a linha principal da mensagem.
                 *
                 * @param  Utilizador  $utilizador  Utilizador destinatário.
                 * @return string Conteúdo principal.
                 *
                 * @since 2.0.0
                 */
                protected function obterLinhaMensagem(
                    Utilizador $utilizador,
                ): string {
                    return 'Mensagem de teste.';
                }

                /**
                 * Obtém o texto da ação.
                 *
                 * @param  Utilizador  $utilizador  Utilizador destinatário.
                 * @return string|null Texto da ação.
                 *
                 * @since 2.0.0
                 */
                protected function obterTextoAcao(
                    Utilizador $utilizador,
                ): ?string {
                    return null;
                }

                /**
                 * Obtém o endereço da ação.
                 *
                 * @param  Utilizador  $utilizador  Utilizador destinatário.
                 * @return string|null Endereço da ação.
                 *
                 * @since 2.0.0
                 */
                protected function obterUrlAcao(
                    Utilizador $utilizador,
                ): ?string {
                    return null;
                }
            };

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] =
                    mb_strtolower(
                        $consulta->sql,
                    );
            },
        );

        self::assertSame(
            [
                'database',
            ],
            $notificacao->via(
                $utilizador,
            ),
        );

        self::assertSame(
            0,
            $notificacao->numeroVerificacoesPermissao,
        );

        self::assertSame(
            [],
            array_values(
                array_filter(
                    $consultas,
                    static fn (
                        string $consulta,
                    ): bool => str_contains(
                        $consulta,
                        'permissoes_email',
                    ),
                ),
            ),
        );
    }
}
