<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\MetalThursday\LigacaoSeccaoMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Cria dados de teste para ligações de secções MetalThursday.
 *
 * @extends Factory<LigacaoSeccaoMetalThursday>
 *
 * @since 2.0.0
 */
final class LigacaoSeccaoMetalThursdayFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<LigacaoSeccaoMetalThursday>
     *
     * @since 2.0.0
     */
    protected $model = LigacaoSeccaoMetalThursday::class;

    /**
     * Define os atributos predefinidos de uma ligação.
     *
     * @return array<string, mixed> Atributos da ligação.
     *
     * @since 2.0.0
     */
    public function definition(): array
    {
        return [
            'seccao_metal_thursday_id' => SeccaoMetalThursday::factory(),
            'etiqueta' => 'Mais informações',
            'url' => sprintf(
                'https://example.com/%s',
                Str::lower(
                    Str::random(
                        20,
                    ),
                ),
            ),
            'incorporar' => false,
            'ordem' => LigacaoSeccaoMetalThursday::ORDEM_MINIMA,
        ];
    }
}
