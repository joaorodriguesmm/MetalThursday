<?php

declare(strict_types=1);

namespace Database\Factories\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\RegistoPapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * Cria dados de teste para registos dos papéis dos utilizadores.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<RegistoPapelUtilizador>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class RegistoPapelUtilizadorFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<RegistoPapelUtilizador>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model =
        RegistoPapelUtilizador::class;

    /**
     * Define os atributos predefinidos de uma alteração do papel.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do registo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        return [
            'utilizador_id' => Utilizador::factory()->comPapel(
                PapelUtilizador::Administrador,
            ),

            'papel_anterior' => PapelUtilizador::Utilizador,

            'papel_novo' => PapelUtilizador::Administrador,

            'responsavel_id' => Utilizador::factory()->comPapel(
                PapelUtilizador::SuperAdministrador,
            ),

            'registado_em' => now(),
        ];
    }

    /**
     * Configura os papéis anterior e novo do registo.
     *
     * @param  PapelUtilizador  $papelAnterior  Papel anterior.
     * @param  PapelUtilizador  $papelNovo  Novo papel.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando os papéis coincidem.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function alteracao(
        PapelUtilizador $papelAnterior,
        PapelUtilizador $papelNovo,
    ): static {
        if ($papelAnterior === $papelNovo) {
            throw new InvalidArgumentException(
                'Os papéis anterior e novo devem ser diferentes.',
            );
        }

        return $this->state([
            'papel_anterior' => $papelAnterior,

            'papel_novo' => $papelNovo,
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
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
     * Define o momento do registo.
     *
     * @param  CarbonInterface  $momento  Momento pretendido.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function registadoEm(
        CarbonInterface $momento,
    ): static {
        return $this->state([
            'registado_em' => CarbonImmutable::instance(
                $momento,
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
     *
     * @version 1.0.0
     */
    private static function obterIdentificadorUtilizadorPersistido(
        Utilizador $utilizador,
    ): int {
        if (! $utilizador->exists) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido.',
            );
        }

        $identificador =
            $utilizador->getKey();

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
