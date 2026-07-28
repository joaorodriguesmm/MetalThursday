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
 * @version 2.0.0
 */
enum TipoEntidadeInteracao: string
{
    /**
     * Representa uma MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case MetalThursday = 'metal-thursday';

    /**
     * Representa uma secção de uma MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case SeccaoMetalThursday =
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
            self::MetalThursday => MetalThursday::class,

            self::SeccaoMetalThursday => SeccaoMetalThursday::class,
        };
    }

    /**
     * Obtém o alias utilizado nas relações polimórficas.
     *
     * @return 'metal_thursday'|'seccao_metal_thursday' Alias persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterAliasPolimorfico(): string
    {
        return match ($this) {
            self::MetalThursday => 'metal_thursday',

            self::SeccaoMetalThursday => 'seccao_metal_thursday',
        };
    }

    /**
     * Resolve um slug público.
     *
     * A normalização limita-se à remoção de espaços exteriores e à conversão
     * para minúsculas.
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
            $modelo instanceof MetalThursday => self::MetalThursday,

            $modelo instanceof SeccaoMetalThursday => self::SeccaoMetalThursday,
        };
    }

    /**
     * Obtém o mapa utilizado pelas relações polimórficas das interações.
     *
     * O alias `utilizador`, necessário para as notificações persistidas, será
     * acrescentado pelo provider geral porque não representa uma entidade de
     * interação.
     *
     * @return array<
     *     'metal_thursday'|'seccao_metal_thursday',
     *     class-string<MetalThursday|SeccaoMetalThursday>
     * > Mapa de aliases.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public static function obterMapaPolimorfico(): array
    {
        return [
            self::MetalThursday
                ->obterAliasPolimorfico() => MetalThursday::class,

            self::SeccaoMetalThursday
                ->obterAliasPolimorfico() => SeccaoMetalThursday::class,
        ];
    }
}
