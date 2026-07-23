<?php

declare(strict_types=1);

namespace Database\Factories\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cria dados de teste para utilizadores.
 *
 * @extends Factory<Utilizador>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class UtilizadorFactory extends Factory
{
    /**
     * Palavra-passe utilizada por omissão nos utilizadores de teste.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const PALAVRA_PASSE_PADRAO =
        'PalavraPasse#2026';

    /**
     * Modelo associado à factory.
     *
     * @var class-string<Utilizador>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = Utilizador::class;

    /**
     * Hash reutilizado da palavra-passe por omissão.
     *
     * Evita calcular repetidamente o mesmo hash durante a execução dos
     * testes.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static ?string $hashPalavraPasse = null;

    /**
     * Define os atributos por omissão de um utilizador.
     *
     * @return array<string, mixed> Atributos do utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'email' => $this
                ->faker
                ->unique()
                ->safeEmail(),

            'email_verified_at' => now(),

            'password' => self::$hashPalavraPasse ??=
                Hash::make(
                    self::PALAVRA_PASSE_PADRAO,
                ),

            'fotografia' => null,
            'papel' => PapelUtilizador::Utilizador,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Cria um utilizador cujo endereço de e-mail ainda não foi verificado.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function naoVerificado(): static
    {
        return $this->state(
            fn (): array => [
                'email_verified_at' => null,
            ],
        );
    }

    /**
     * Define o papel do utilizador.
     *
     * @param  PapelUtilizador  $papel  Papel pretendido.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comPapel(
        PapelUtilizador $papel,
    ): static {
        return $this->state(
            fn (): array => [
                'papel' => $papel,
            ],
        );
    }

    /**
     * Define uma fotografia para o utilizador.
     *
     * @param  string  $caminho  Caminho relativo da fotografia.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comFotografia(
        string $caminho,
    ): static {
        return $this->state(
            fn (): array => [
                'fotografia' => $caminho,
            ],
        );
    }
}
