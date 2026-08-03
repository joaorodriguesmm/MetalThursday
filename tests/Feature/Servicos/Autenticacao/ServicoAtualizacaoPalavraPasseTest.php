<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Excecoes\Autenticacao\NovaPalavraPasseIgualAAtual;
use App\Excecoes\Autenticacao\PalavraPasseAtualIncorreta;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Autenticacao\ServicoAtualizacaoPalavraPasse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o serviço responsável pela alteração da palavra-passe.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class ServicoAtualizacaoPalavraPasseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Palavra-passe inicial utilizada nos testes.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PALAVRA_PASSE_ATUAL =
        'PalavraPasse#Atual2026';

    /**
     * Nova palavra-passe válida utilizada nos testes.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const NOVA_PALAVRA_PASSE =
        'NovaPalavraPasse#2026';

    /**
     * Token persistente inicial utilizado nos testes.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TOKEN_PERSISTENTE_INICIAL =
        'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    /**
     * Serviço testado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private ServicoAtualizacaoPalavraPasse $servicoPalavraPasse;

    /**
     * Prepara cada teste.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->servicoPalavraPasse =
            new ServicoAtualizacaoPalavraPasse;
    }

    /**
     * Altera a palavra-passe, persiste apenas a respetiva hash e renova o
     * token de autenticação persistente.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function altera_palavra_passe_com_sucesso(): void
    {
        $utilizador =
            $this->criarUtilizador(
                self::PALAVRA_PASSE_ATUAL,
            );

        $hashAnterior =
            $utilizador->getAuthPassword();

        $tokenPersistenteAnterior =
            $utilizador->getRememberToken();

        self::assertIsString(
            $hashAnterior,
        );

        self::assertTrue(
            Hash::check(
                self::PALAVRA_PASSE_ATUAL,
                $hashAnterior,
            ),
        );

        self::assertSame(
            self::TOKEN_PERSISTENTE_INICIAL,
            $tokenPersistenteAnterior,
        );

        $utilizadorAtualizado =
            $this
                ->servicoPalavraPasse
                ->atualizar(
                    utilizador: $utilizador,
                    palavraPasseAtual: self::PALAVRA_PASSE_ATUAL,
                    novaPalavraPasse: self::NOVA_PALAVRA_PASSE,
                );

        self::assertSame(
            $utilizador->getKey(),
            $utilizadorAtualizado->getKey(),
        );

        $utilizadorAtualizado->refresh();

        $hashNovo =
            $utilizadorAtualizado->getAuthPassword();

        $tokenPersistenteNovo =
            $utilizadorAtualizado->getRememberToken();

        self::assertIsString(
            $hashNovo,
        );

        self::assertNotSame(
            $hashAnterior,
            $hashNovo,
        );

        self::assertNotSame(
            self::NOVA_PALAVRA_PASSE,
            $hashNovo,
        );

        self::assertTrue(
            Hash::check(
                self::NOVA_PALAVRA_PASSE,
                $hashNovo,
            ),
        );

        self::assertFalse(
            Hash::check(
                self::PALAVRA_PASSE_ATUAL,
                $hashNovo,
            ),
        );

        self::assertIsString(
            $tokenPersistenteNovo,
        );

        self::assertSame(
            60,
            strlen(
                $tokenPersistenteNovo,
            ),
        );

        self::assertNotSame(
            $tokenPersistenteAnterior,
            $tokenPersistenteNovo,
        );

        $this->assertDatabaseHas(
            'utilizadores',
            [
                'id' => $utilizador->getKey(),

                'email' => 'utilizador@exemplo.pt',

                'remember_token' => $tokenPersistenteNovo,
            ],
        );
    }

    /**
     * Rejeita uma palavra-passe atual incorreta.
     *
     * A hash e o token persistidos devem permanecer inalterados.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_palavra_passe_atual_incorreta(): void
    {
        $utilizador =
            $this->criarUtilizador(
                self::PALAVRA_PASSE_ATUAL,
            );

        $hashAnterior =
            $utilizador->getAuthPassword();

        $tokenPersistenteAnterior =
            $utilizador->getRememberToken();

        try {
            $this
                ->servicoPalavraPasse
                ->atualizar(
                    utilizador: $utilizador,
                    palavraPasseAtual: 'PalavraPasse#Incorreta2026',
                    novaPalavraPasse: self::NOVA_PALAVRA_PASSE,
                );

            self::fail(
                'Era esperada uma exceção para a palavra-passe atual incorreta.',
            );
        } catch (PalavraPasseAtualIncorreta $excecao) {
            self::assertSame(
                'A palavra-passe atual não está correta.',
                $excecao->getMessage(),
            );
        }

        $utilizador->refresh();

        self::assertSame(
            $hashAnterior,
            $utilizador->getAuthPassword(),
        );

        self::assertSame(
            $tokenPersistenteAnterior,
            $utilizador->getRememberToken(),
        );

        self::assertTrue(
            Hash::check(
                self::PALAVRA_PASSE_ATUAL,
                $utilizador->getAuthPassword(),
            ),
        );
    }

    /**
     * Impede a reutilização da palavra-passe atual.
     *
     * A hash e o token persistidos devem permanecer inalterados.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_nova_palavra_passe_igual_a_atual(): void
    {
        $utilizador =
            $this->criarUtilizador(
                self::PALAVRA_PASSE_ATUAL,
            );

        $hashAnterior =
            $utilizador->getAuthPassword();

        $tokenPersistenteAnterior =
            $utilizador->getRememberToken();

        try {
            $this
                ->servicoPalavraPasse
                ->atualizar(
                    utilizador: $utilizador,
                    palavraPasseAtual: self::PALAVRA_PASSE_ATUAL,
                    novaPalavraPasse: self::PALAVRA_PASSE_ATUAL,
                );

            self::fail(
                'Era esperada uma exceção para a reutilização da palavra-passe.',
            );
        } catch (
            NovaPalavraPasseIgualAAtual $excecao
        ) {
            self::assertSame(
                'A nova palavra-passe deve ser diferente da atual.',
                $excecao->getMessage(),
            );
        }

        $utilizador->refresh();

        self::assertSame(
            $hashAnterior,
            $utilizador->getAuthPassword(),
        );

        self::assertSame(
            $tokenPersistenteAnterior,
            $utilizador->getRememberToken(),
        );

        self::assertTrue(
            Hash::check(
                self::PALAVRA_PASSE_ATUAL,
                $utilizador->getAuthPassword(),
            ),
        );
    }

    /**
     * Rejeita uma nova palavra-passe que não cumpra os requisitos.
     *
     * A validação deve ocorrer antes da transação e não deve alterar o
     * utilizador.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_nova_palavra_passe_insegura(): void
    {
        $utilizador =
            $this->criarUtilizador(
                self::PALAVRA_PASSE_ATUAL,
            );

        $hashAnterior =
            $utilizador->getAuthPassword();

        $tokenPersistenteAnterior =
            $utilizador->getRememberToken();

        try {
            $this
                ->servicoPalavraPasse
                ->atualizar(
                    utilizador: $utilizador,
                    palavraPasseAtual: self::PALAVRA_PASSE_ATUAL,
                    novaPalavraPasse: 'fraca',
                );

            self::fail(
                'Era esperada uma exceção para a palavra-passe insegura.',
            );
        } catch (InvalidArgumentException) {
            self::assertTrue(
                true,
            );
        }

        $utilizador->refresh();

        self::assertSame(
            $hashAnterior,
            $utilizador->getAuthPassword(),
        );

        self::assertSame(
            $tokenPersistenteAnterior,
            $utilizador->getRememberToken(),
        );

        self::assertTrue(
            Hash::check(
                self::PALAVRA_PASSE_ATUAL,
                $utilizador->getAuthPassword(),
            ),
        );
    }

    /**
     * Rejeita um utilizador que ainda não esteja persistido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_utilizador_nao_persistido(): void
    {
        $utilizador = new Utilizador;

        $utilizador->nome =
            'Utilizador Teste';

        $utilizador->email =
            'utilizador@exemplo.pt';

        $utilizador->password =
            self::PALAVRA_PASSE_ATUAL;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O utilizador deve estar persistido para alterar a palavra-passe.',
        );

        $this
            ->servicoPalavraPasse
            ->atualizar(
                utilizador: $utilizador,
                palavraPasseAtual: self::PALAVRA_PASSE_ATUAL,
                novaPalavraPasse: self::NOVA_PALAVRA_PASSE,
            );
    }

    /**
     * Cria um utilizador persistido.
     *
     * @param  string  $palavraPasse  Palavra-passe inicial.
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function criarUtilizador(
        string $palavraPasse,
    ): Utilizador {
        $utilizador = new Utilizador;

        $utilizador->nome =
            'Utilizador Teste';

        $utilizador->email =
            'utilizador@exemplo.pt';

        $utilizador->password =
            $palavraPasse;

        $utilizador->papel =
            PapelUtilizador::Utilizador;

        $utilizador->email_verified_at =
            now()
                ->subDay()
                ->startOfSecond();

        $utilizador->setRememberToken(
            self::TOKEN_PERSISTENTE_INICIAL,
        );

        $utilizador->saveOrFail();

        return $utilizador->refresh();
    }
}
