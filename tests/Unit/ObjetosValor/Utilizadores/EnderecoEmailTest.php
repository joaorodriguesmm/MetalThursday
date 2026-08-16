<?php

declare(strict_types=1);

namespace Tests\Unit\ObjetosValor\Utilizadores;

use App\ObjetosValor\Utilizadores\EnderecoEmail;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Testa o objeto de valor do endereço de e-mail.
 *
 * @since 2.0.0
 */
final class EnderecoEmailTest extends TestCase
{
    /**
     * Confirma que o endereço é normalizado.
     *
     * @since 2.0.0
     */
    public function test_normaliza_o_endereco_de_email(): void
    {
        $email = EnderecoEmail::deTexto(
            '  Utilizador@Exemplo.PT  ',
        );

        self::assertSame(
            'utilizador@exemplo.pt',
            $email->valor(),
        );

        self::assertSame(
            'utilizador@exemplo.pt',
            (string) $email,
        );

        self::assertSame(
            'utilizador@exemplo.pt',
            $email->jsonSerialize(),
        );
    }

    /**
     * Confirma que endereços equivalentes são considerados iguais.
     *
     * @since 2.0.0
     */
    public function test_compara_enderecos_normalizados(): void
    {
        $primeiroEmail = EnderecoEmail::deTexto(
            'UTILIZADOR@EXEMPLO.PT',
        );

        $segundoEmail = EnderecoEmail::deTexto(
            ' utilizador@exemplo.pt ',
        );

        self::assertTrue(
            $primeiroEmail->igualA($segundoEmail),
        );
    }

    /**
     * Confirma que endereços diferentes não são considerados iguais.
     *
     * @since 2.0.0
     */
    public function test_distingue_enderecos_diferentes(): void
    {
        $primeiroEmail = EnderecoEmail::deTexto(
            'primeiro@exemplo.pt',
        );

        $segundoEmail = EnderecoEmail::deTexto(
            'segundo@exemplo.pt',
        );

        self::assertFalse(
            $primeiroEmail->igualA($segundoEmail),
        );
    }

    /**
     * Confirma que endereços inválidos são rejeitados.
     *
     * @param  string  $email  Endereço inválido.
     *
     * @since 2.0.0
     */
    #[DataProvider('fornecerEnderecosInvalidos')]
    public function test_rejeita_enderecos_invalidos(
        string $email,
    ): void {
        $this->expectException(
            InvalidArgumentException::class,
        );

        EnderecoEmail::deTexto($email);
    }

    /**
     * Fornece endereços inválidos.
     *
     * @return iterable<string, array{0: string}> Endereços inválidos.
     *
     * @since 2.0.0
     */
    public static function fornecerEnderecosInvalidos(): iterable
    {
        yield 'vazio' => [''];

        yield 'apenas espaços' => ['   '];

        yield 'sem arroba' => [
            'utilizador.exemplo.pt',
        ];

        yield 'sem nome local' => [
            '@exemplo.pt',
        ];

        yield 'sem domínio' => [
            'utilizador@',
        ];

        yield 'com espaços interiores' => [
            'utilizador @exemplo.pt',
        ];

        yield 'demasiado longo' => [
            str_repeat('a', 250).'@exemplo.pt',
        ];
    }
}
