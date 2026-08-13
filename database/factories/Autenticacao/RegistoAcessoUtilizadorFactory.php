<?php

declare(strict_types=1);

namespace Database\Factories\Autenticacao;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\RegistoAcessoUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\ObjetosValor\Utilizadores\MotivoSuspensaoUtilizador;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * Cria dados de teste para registos de acesso dos utilizadores.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<RegistoAcessoUtilizador>
 *
 * @since 2.0.0
 */
final class RegistoAcessoUtilizadorFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<RegistoAcessoUtilizador>
     *
     * @since 2.0.0
     */
    protected $model =
        RegistoAcessoUtilizador::class;

    /**
     * Define os atributos predefinidos de um registo de acesso.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do registo.
     *
     * @since 2.0.0
     */
    public function definition(): array
    {
        return [
            'utilizador_id' => Utilizador::factory(),

            'acao' => AcaoAcessoUtilizador::Suspensao,

            'motivo' => 'Suspensão criada para testes.',

            'responsavel_id' => Utilizador::factory()->comPapel(
                PapelUtilizador::SuperAdministrador,
            ),

            'registado_em' => now(),
        ];
    }

    /**
     * Configura um registo de suspensão.
     *
     * @param  string  $motivo  Motivo da suspensão.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o motivo não é válido.
     *
     * @since 2.0.0
     */
    public function suspensao(
        string $motivo = 'Suspensão criada para testes.',
    ): static {
        return $this->state([
            'acao' => AcaoAcessoUtilizador::Suspensao,

            'motivo' => MotivoSuspensaoUtilizador::deTexto(
                $motivo,
            )->valor(),
        ]);
    }

    /**
     * Configura um registo de reativação.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function reativacao(): static
    {
        return $this->state([
            'acao' => AcaoAcessoUtilizador::Reativacao,

            'motivo' => null,
        ]);
    }

    /**
     * Associa o registo ao utilizador afetado.
     *
     * @param  Utilizador  $utilizador  Utilizador afetado.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function paraUtilizador(
        Utilizador $utilizador,
    ): static {
        return $this->state([
            'utilizador_id' => self::obterIdentificadorUtilizadorPersistido(
                $utilizador,
            ),
        ]);
    }

    /**
     * Associa o registo ao utilizador responsável.
     *
     * @param  Utilizador  $responsavel  Utilizador responsável.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function registadoPor(
        Utilizador $responsavel,
    ): static {
        return $this->state([
            'responsavel_id' => self::obterIdentificadorUtilizadorPersistido(
                $responsavel,
            ),
        ]);
    }

    /**
     * Obtém o identificador de um utilizador persistido.
     *
     * @param  Utilizador  $utilizador  Utilizador recebido.
     * @return int Identificador válido.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     */
    private static function obterIdentificadorUtilizadorPersistido(
        Utilizador $utilizador,
    ): int {
        if (! $utilizador->exists) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido.',
            );
        }

        $identificador = $utilizador->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (
            is_string($identificador)
            && ctype_digit($identificador)
            && (int) $identificador > 0
        ) {
            return (int) $identificador;
        }

        throw new InvalidArgumentException(
            'O utilizador não possui um identificador válido.',
        );
    }
}
