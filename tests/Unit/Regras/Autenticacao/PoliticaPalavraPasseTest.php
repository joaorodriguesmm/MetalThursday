<?php

declare(strict_types=1);

namespace Tests\Unit\Regras\Autenticacao;

use App\Regras\Autenticacao\PoliticaPalavraPasse;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Testa a política central de palavras-passe.
 *
 * Este teste utiliza a aplicação Laravel porque a regra Password depende do
 * serviço de validação e do tradutor do framework.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class PoliticaPalavraPasseTest extends TestCase
{
    /**
     * Confirma que a fábrica devolve uma regra Password.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_cria_uma_regra_de_palavra_passe(): void
    {
        self::assertInstanceOf(
            Password::class,
            PoliticaPalavraPasse::regra(),
        );
    }

    /**
     * Confirma que uma palavra-passe forte é aceite.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_aceita_palavra_passe_forte(): void
    {
        PoliticaPalavraPasse::validar(
            'MetalThursday#2026',
        );

        $this->addToAssertionCount(1);
    }

    /**
     * Confirma que palavras-passe inseguras são rejeitadas.
     *
     * @param  string  $palavraPasse  - Palavra-passe insegura.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[DataProvider('fornecerPalavrasPasseInvalidas')]
    public function test_rejeita_palavras_passe_invalidas(
        string $palavraPasse,
    ): void {
        $this->expectException(
            InvalidArgumentException::class,
        );

        PoliticaPalavraPasse::validar(
            $palavraPasse,
        );
    }

    /**
     * Fornece palavras-passe que não cumprem a política.
     *
     * @return iterable<string, array{0: string}> - Palavras-passe inválidas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function fornecerPalavrasPasseInvalidas(): iterable
    {
        yield 'vazia' => [''];

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
    }
}
