<?php

declare(strict_types=1);

namespace Database\Factories\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para utilizadores.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<Utilizador>
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class UtilizadorFactory extends Factory
{
    /**
     * Palavra-passe utilizada por predefinição nos utilizadores de teste.
     *
     * A constante é pública para permitir que os testes autentiquem os
     * utilizadores criados por esta factory.
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
    protected $model =
        Utilizador::class;

    /**
     * Hash reutilizado da palavra-passe predefinida.
     *
     * Evita calcular repetidamente o mesmo hash durante a execução do
     * processo de testes.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static ?string $hashPalavraPasse =
        null;

    /**
     * Define os atributos predefinidos de um utilizador.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
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

            'remember_token' => Str::random(
                10,
            ),
        ];
    }

    /**
     * Cria um utilizador cujo endereço de correio eletrónico ainda não foi
     * verificado.
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
            static fn (): array => [
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
            static fn (): array => [
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
     * @throws InvalidArgumentException Quando o caminho está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comFotografia(
        string $caminho,
    ): static {
        $caminhoNormalizado =
            trim(
                $caminho,
            );

        if ($caminhoNormalizado === '') {
            throw new InvalidArgumentException(
                'O caminho da fotografia não pode estar vazio.',
            );
        }

        return $this->state(
            static fn (): array => [
                'fotografia' => $caminhoNormalizado,
            ],
        );
    }
}
