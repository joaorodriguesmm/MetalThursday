<?php

declare(strict_types=1);

namespace Tests\Unit\Regras\Autenticacao;

use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Testa os requisitos centrais das palavras-passe.
 *
 * Este teste utiliza a aplicação Laravel porque a regra Password depende dos
 * serviços de validação e tradução do framework.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class RequisitosPalavraPasseTest extends TestCase
{
    /**
     * Confirma os limites de comprimento publicados pela classe.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_disponibiliza_os_limites_de_comprimento(): void
    {
        self::assertSame(
            12,
            RequisitosPalavraPasse::comprimentoMinimo(),
        );

        self::assertSame(
            4096,
            RequisitosPalavraPasse::comprimentoMaximo(),
        );
    }

    /**
     * Confirma que a fábrica devolve uma regra Password.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function test_cria_uma_regra_de_palavra_passe(): void
    {
        self::assertInstanceOf(
            Password::class,
            RequisitosPalavraPasse::regra(),
        );
    }

    /**
     * Confirma as regras utilizadas para palavras-passe obrigatórias.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_disponibiliza_as_regras_obrigatorias(): void
    {
        $regras =
            RequisitosPalavraPasse::regrasObrigatorias();

        self::assertCount(
            5,
            $regras,
        );

        self::assertSame(
            'bail',
            $regras[0],
        );

        self::assertSame(
            'required',
            $regras[1],
        );

        self::assertSame(
            'string',
            $regras[2],
        );

        self::assertSame(
            'max:4096',
            $regras[3],
        );

        self::assertInstanceOf(
            Password::class,
            $regras[4],
        );
    }

    /**
     * Confirma que uma palavra-passe forte é aceite.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function test_aceita_uma_palavra_passe_forte(): void
    {
        RequisitosPalavraPasse::validar(
            'MetalThursday#2026',
        );

        $this->addToAssertionCount(
            1,
        );
    }

    /**
     * Confirma que palavras-passe inseguras são rejeitadas.
     *
     * @param  string  $palavraPasse  Palavra-passe insegura.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[DataProvider('fornecerPalavrasPasseInvalidas')]
    public function test_rejeita_palavras_passe_invalidas(
        string $palavraPasse,
    ): void {
        $this->expectException(
            InvalidArgumentException::class,
        );

        RequisitosPalavraPasse::validar(
            $palavraPasse,
        );
    }

    /**
     * Fornece palavras-passe que não cumprem os requisitos.
     *
     * @return iterable<string, array{0: string}> Palavras-passe inválidas.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public static function fornecerPalavrasPasseInvalidas(): iterable
    {
        yield 'vazia' => [
            '',
        ];

        yield 'demasiado curta' => [
            'Mt#2026',
        ];

        yield 'sem maiúsculas' => [
            'metalthursday#2026',
        ];

        yield 'sem minúsculas' => [
            'METALTHURSDAY#2026',
        ];

        yield 'sem números' => [
            'MetalThursday#Abc',
        ];

        yield 'sem símbolos' => [
            'MetalThursday2026',
        ];

        yield 'demasiado longa' => [
            'Aa1#'.str_repeat(
                'a',
                4093,
            ),
        ];
    }
}
