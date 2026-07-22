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
 * @version 1.0.0
 */
final class ServicoAtualizacaoPalavraPasseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Serviço testado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private ServicoAtualizacaoPalavraPasse $servicoPalavraPasse;

    /**
     * Prepara cada teste.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->servicoPalavraPasse =
            new ServicoAtualizacaoPalavraPasse;
    }

    /**
     * Altera a palavra-passe e persiste apenas a respetiva hash.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function altera_palavra_passe_com_sucesso(): void
    {
        $palavraPasseAtual =
            'PalavraPasse#Atual2026';

        $novaPalavraPasse =
            'NovaPalavraPasse#2026';

        $utilizador = $this->criarUtilizador(
            $palavraPasseAtual,
        );

        $hashAnterior =
            $utilizador->getAuthPassword();

        self::assertIsString($hashAnterior);

        self::assertTrue(
            Hash::check(
                $palavraPasseAtual,
                $hashAnterior,
            ),
        );

        $utilizadorAtualizado =
            $this->servicoPalavraPasse->atualizar(
                utilizador: $utilizador,
                palavraPasseAtual: $palavraPasseAtual,
                novaPalavraPasse: $novaPalavraPasse,
            );

        self::assertSame(
            $utilizador->getKey(),
            $utilizadorAtualizado->getKey(),
        );

        $utilizadorAtualizado->refresh();

        $hashNovo =
            $utilizadorAtualizado->getAuthPassword();

        self::assertIsString($hashNovo);

        self::assertNotSame(
            $hashAnterior,
            $hashNovo,
        );

        self::assertNotSame(
            $novaPalavraPasse,
            $hashNovo,
        );

        self::assertTrue(
            Hash::check(
                $novaPalavraPasse,
                $hashNovo,
            ),
        );

        self::assertFalse(
            Hash::check(
                $palavraPasseAtual,
                $hashNovo,
            ),
        );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $utilizador->getKey(),
                'email' => 'utilizador@exemplo.pt',
            ],
        );
    }

    /**
     * Rejeita uma palavra-passe atual incorreta.
     *
     * A hash persistida deve permanecer inalterada.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_palavra_passe_atual_incorreta(): void
    {
        $palavraPasseAtual =
            'PalavraPasse#Atual2026';

        $utilizador = $this->criarUtilizador(
            $palavraPasseAtual,
        );

        $hashAnterior =
            $utilizador->getAuthPassword();

        try {
            $this->servicoPalavraPasse->atualizar(
                utilizador: $utilizador,
                palavraPasseAtual: 'PalavraPasse#Incorreta2026',

                novaPalavraPasse: 'NovaPalavraPasse#2026',
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

        self::assertTrue(
            Hash::check(
                $palavraPasseAtual,
                $utilizador->getAuthPassword(),
            ),
        );
    }

    /**
     * Impede a reutilização da palavra-passe atual.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_nova_palavra_passe_igual_a_atual(): void
    {
        $palavraPasseAtual =
            'PalavraPasse#Atual2026';

        $utilizador = $this->criarUtilizador(
            $palavraPasseAtual,
        );

        $hashAnterior =
            $utilizador->getAuthPassword();

        try {
            $this->servicoPalavraPasse->atualizar(
                utilizador: $utilizador,
                palavraPasseAtual: $palavraPasseAtual,
                novaPalavraPasse: $palavraPasseAtual,
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

        self::assertTrue(
            Hash::check(
                $palavraPasseAtual,
                $utilizador->getAuthPassword(),
            ),
        );
    }

    /**
     * Rejeita uma nova palavra-passe que não cumpra a política.
     *
     * A validação deve ocorrer antes da transação e não deve alterar o
     * utilizador.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_nova_palavra_passe_insegura(): void
    {
        $palavraPasseAtual =
            'PalavraPasse#Atual2026';

        $utilizador = $this->criarUtilizador(
            $palavraPasseAtual,
        );

        $hashAnterior =
            $utilizador->getAuthPassword();

        try {
            $this->servicoPalavraPasse->atualizar(
                utilizador: $utilizador,
                palavraPasseAtual: $palavraPasseAtual,
                novaPalavraPasse: 'fraca',
            );

            self::fail(
                'Era esperada uma exceção para a palavra-passe insegura.',
            );
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $utilizador->refresh();

        self::assertSame(
            $hashAnterior,
            $utilizador->getAuthPassword(),
        );

        self::assertTrue(
            Hash::check(
                $palavraPasseAtual,
                $utilizador->getAuthPassword(),
            ),
        );
    }

    /**
     * Rejeita um utilizador que ainda não esteja persistido.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
            'PalavraPasse#Atual2026';

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O utilizador deve estar persistido para alterar a palavra-passe.',
        );

        $this->servicoPalavraPasse->atualizar(
            utilizador: $utilizador,
            palavraPasseAtual: 'PalavraPasse#Atual2026',

            novaPalavraPasse: 'NovaPalavraPasse#2026',
        );
    }

    /**
     * Cria um utilizador persistido.
     *
     * @param  string  $palavraPasse  - Palavra-passe inicial.
     * @return Utilizador - Utilizador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
            now()->subDay()->startOfSecond();

        $utilizador->saveOrFail();

        return $utilizador->refresh();
    }
}
