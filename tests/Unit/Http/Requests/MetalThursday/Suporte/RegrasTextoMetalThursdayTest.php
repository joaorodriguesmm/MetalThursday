<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MetalThursday\Suporte;

use App\Http\Requests\MetalThursday\Suporte\RegrasTextoMetalThursday;
use Closure;
use PHPUnit\Framework\TestCase;

/**
 * Testa as regras textuais partilhadas pelos pedidos de MetalThursday.
 *
 * @since 2.0.0
 */
final class RegrasTextoMetalThursdayTest extends TestCase
{
    /**
     * Confirma que texto de uma linha válido não produz erros.
     *
     * @since 2.0.0
     */
    public function test_regra_linha_aceita_texto_valido(): void
    {
        self::assertSame(
            [],
            $this->executarRegra(
                RegrasTextoMetalThursday::criarRegraTextoLinha(
                    'texto inválido',
                    'caracteres inválidos',
                ),
                'Metal Thursday',
            ),
        );
    }

    /**
     * Confirma que a regra de uma linha rejeita UTF-8 inválido.
     *
     * @since 2.0.0
     */
    public function test_regra_linha_rejeita_utf8_invalido(): void
    {
        self::assertSame(
            [
                'texto inválido',
            ],
            $this->executarRegra(
                RegrasTextoMetalThursday::criarRegraTextoLinha(
                    'texto inválido',
                    'caracteres inválidos',
                ),
                "\xC3\x28",
            ),
        );
    }

    /**
     * Confirma que a regra de uma linha rejeita caracteres de controlo.
     *
     * @since 2.0.0
     */
    public function test_regra_linha_rejeita_caracteres_controlo(): void
    {
        self::assertSame(
            [
                'caracteres inválidos',
            ],
            $this->executarRegra(
                RegrasTextoMetalThursday::criarRegraTextoLinha(
                    'texto inválido',
                    'caracteres inválidos',
                ),
                "Metal\x00Thursday",
            ),
        );
    }

    /**
     * Confirma que tabulações e quebras de linha são válidas em multilinha.
     *
     * @since 2.0.0
     */
    public function test_regra_multilinha_aceita_tab_e_quebras_linha(): void
    {
        self::assertSame(
            [],
            $this->executarRegra(
                RegrasTextoMetalThursday::criarRegraTextoMultilinha(
                    'texto inválido',
                    'caracteres inválidos',
                ),
                "Linha 1\tvalor\nLinha 2\r\nLinha 3",
            ),
        );
    }

    /**
     * Confirma que outros caracteres de controlo são rejeitados em multilinha.
     *
     * @since 2.0.0
     */
    public function test_regra_multilinha_rejeita_caracteres_controlo(): void
    {
        self::assertSame(
            [
                'caracteres inválidos',
            ],
            $this->executarRegra(
                RegrasTextoMetalThursday::criarRegraTextoMultilinha(
                    'texto inválido',
                    'caracteres inválidos',
                ),
                "Linha 1\x00Linha 2",
            ),
        );
    }

    /**
     * Confirma que a regra multilinha rejeita UTF-8 inválido.
     *
     * @since 2.0.0
     */
    public function test_regra_multilinha_rejeita_utf8_invalido(): void
    {
        self::assertSame(
            [
                'texto inválido',
            ],
            $this->executarRegra(
                RegrasTextoMetalThursday::criarRegraTextoMultilinha(
                    'texto inválido',
                    'caracteres inválidos',
                ),
                "\xC3\x28",
            ),
        );
    }

    /**
     * Confirma que valores nulos são deixados para outras regras de validação.
     *
     * @since 2.0.0
     */
    public function test_regras_ignoram_valor_nulo(): void
    {
        foreach ([
            RegrasTextoMetalThursday::criarRegraTextoLinha(
                'texto inválido',
                'caracteres inválidos',
            ),
            RegrasTextoMetalThursday::criarRegraTextoMultilinha(
                'texto inválido',
                'caracteres inválidos',
            ),
        ] as $regra) {
            self::assertSame(
                [],
                $this->executarRegra(
                    $regra,
                    null,
                ),
            );
        }
    }

    /**
     * Confirma que valores não textuais são deixados para a regra de tipo.
     *
     * @since 2.0.0
     */
    public function test_regras_ignoram_valores_nao_textuais(): void
    {
        foreach ([
            123,
            true,
            [],
            new \stdClass,
        ] as $valor) {
            foreach ([
                RegrasTextoMetalThursday::criarRegraTextoLinha(
                    'texto inválido',
                    'caracteres inválidos',
                ),
                RegrasTextoMetalThursday::criarRegraTextoMultilinha(
                    'texto inválido',
                    'caracteres inválidos',
                ),
            ] as $regra) {
                self::assertSame(
                    [],
                    $this->executarRegra(
                        $regra,
                        $valor,
                    ),
                );
            }
        }
    }

    /**
     * Executa uma regra e recolhe as mensagens emitidas.
     *
     * @param  Closure(string, mixed, Closure(string): void): void  $regra
     * @param  mixed  $valor  Valor testado.
     * @return list<string> Mensagens emitidas pela regra.
     *
     * @since 2.0.0
     */
    private function executarRegra(
        Closure $regra,
        mixed $valor,
    ): array {
        $mensagens = [];

        $regra(
            'campo',
            $valor,
            static function (
                string $mensagem,
            ) use (
                &$mensagens,
            ): void {
                $mensagens[] =
                    $mensagem;
            },
        );

        return $mensagens;
    }
}
