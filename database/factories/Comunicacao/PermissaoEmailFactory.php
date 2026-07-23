<?php

declare(strict_types=1);

namespace Database\Factories\Comunicacao;

use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Cria dados de teste para permissões de e-mail.
 *
 * @extends Factory<PermissaoEmail>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class PermissaoEmailFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<PermissaoEmail>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = PermissaoEmail::class;

    /**
     * Define os atributos por omissão de uma permissão de e-mail.
     *
     * @return array<string, mixed> Atributos da permissão.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        $nome = $this
            ->faker
            ->unique()
            ->words(
                3,
                true,
            );

        return [
            'nome' => ucfirst($nome),

            'identificador' => sprintf(
                '%s_%s',
                Str::slug(
                    $nome,
                    '_',
                ),
                Str::lower(
                    Str::random(8),
                ),
            ),

            'descricao' => $this
                ->faker
                ->sentence(),
        ];
    }

    /**
     * Cria uma permissão sem descrição.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function semDescricao(): static
    {
        return $this->state(
            fn (): array => [
                'descricao' => null,
            ],
        );
    }

    /**
     * Define um identificador conhecido para a permissão.
     *
     * @param  string  $identificador  Identificador pretendido.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comIdentificador(
        string $identificador,
    ): static {
        return $this->state(
            fn (): array => [
                'identificador' => $identificador,
            ],
        );
    }
}
