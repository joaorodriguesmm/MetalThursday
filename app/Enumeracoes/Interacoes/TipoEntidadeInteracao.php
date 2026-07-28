<?php

declare(strict_types=1);

namespace App\Enumeracoes\Interacoes;

use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;

/**
 * Representa os tipos de entidades suportados pelas interações.
 *
 * O valor de cada caso corresponde ao slug público utilizado nas rotas,
 * atributos HTML e pedidos JavaScript.
 *
 * Os aliases polimórficos são tratados separadamente porque constituem um
 * contrato interno persistido na base de dados.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
enum TipoEntidadeInteracao: string
{
    /**
     * Representa um MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case METAL_THURSDAY =
        'metal-thursday';

    /**
     * Representa uma secção de um MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case SECCAO_METAL_THURSDAY =
        'seccao-metal-thursday';

    /**
     * Obtém a classe Eloquent correspondente ao tipo.
     *
     * @return class-string<MetalThursday|SeccaoMetalThursday> Classe do
     *                                                         modelo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterClasseModelo(): string
    {
        return match ($this) {
            self::METAL_THURSDAY => MetalThursday::class,

            self::SECCAO_METAL_THURSDAY => SeccaoMetalThursday::class,
        };
    }

    /**
     * Obtém o alias utilizado nas relações polimórficas.
     *
     * @return string Alias persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterAliasPolimorfico(): string
    {
        return match ($this) {
            self::METAL_THURSDAY => 'metal_thursday',

            self::SECCAO_METAL_THURSDAY => 'seccao_metal_thursday',
        };
    }

    /**
     * Resolve um slug público.
     *
     * @param  string  $slug  Slug recebido.
     * @return self|null Tipo correspondente ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function deSlug(
        string $slug,
    ): ?self {
        return self::tryFrom(
            mb_strtolower(
                trim(
                    $slug,
                ),
            ),
        );
    }

    /**
     * Resolve o tipo correspondente a um modelo.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $modelo  Modelo recebido.
     * @return self Tipo correspondente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function deModelo(
        MetalThursday|SeccaoMetalThursday $modelo,
    ): self {
        return match (true) {
            $modelo instanceof MetalThursday => self::METAL_THURSDAY,

            $modelo instanceof SeccaoMetalThursday => self::SECCAO_METAL_THURSDAY,
        };
    }

    /**
     * Resolve um alias polimórfico.
     *
     * @param  string  $alias  Alias recebido.
     * @return self|null Tipo correspondente ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function deAliasPolimorfico(
        string $alias,
    ): ?self {
        $aliasNormalizado =
            mb_strtolower(
                trim(
                    $alias,
                ),
            );

        foreach (self::cases() as $tipo) {
            if (
                $tipo->obterAliasPolimorfico()
                === $aliasNormalizado
            ) {
                return $tipo;
            }
        }

        return null;
    }

    /**
     * Obtém o mapa utilizado pelas relações polimórficas.
     *
     * @return array<
     *     string,
     *     class-string<MetalThursday|SeccaoMetalThursday>
     * > Mapa de aliases.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function obterMapaPolimorfico(): array
    {
        $mapa = [];

        foreach (self::cases() as $tipo) {
            $mapa[$tipo->obterAliasPolimorfico()] =
                $tipo->obterClasseModelo();
        }

        return $mapa;
    }

    /**
     * Obtém todos os slugs públicos permitidos.
     *
     * @return list<string> Slugs públicos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function obterSlugs(): array
    {
        return array_map(
            static fn (
                self $tipo,
            ): string => $tipo->value,
            self::cases(),
        );
    }
}
