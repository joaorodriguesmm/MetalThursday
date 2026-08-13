<?php

declare(strict_types=1);

namespace Database\Factories\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para convites.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<Convite>
 *
 * @since 2.0.0
 */
final class ConviteFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<Convite>
     *
     * @since 2.0.0
     */
    protected $model =
        Convite::class;

    /**
     * Define os atributos predefinidos de um convite.
     *
     * O código original não é persistido. É gerado um código aleatório e
     * guardado apenas o respetivo hash.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do convite.
     *
     * @since 2.0.0
     */
    public function definition(): array
    {
        $codigo = Str::random(
            64,
        );

        return [
            'nome_convidado' => $this
                ->faker
                ->name(),

            'email_destino' => $this
                ->faker
                ->unique()
                ->safeEmail(),

            'codigo_hash' => Convite::calcularHashCodigo(
                $codigo,
            ),

            'criado_por_id' => null,

            'utilizado_por_id' => null,

            'expira_em' => now()->addDays(
                7,
            ),

            'utilizado_em' => null,

            'revogado_em' => null,

            'revogado_por_id' => null,
        ];
    }

    /**
     * Define um código conhecido para o convite.
     *
     * @param  string  $codigo  Código original do convite.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     */
    public function comCodigo(
        string $codigo,
    ): static {
        return $this->state([
            'codigo_hash' => Convite::calcularHashCodigo(
                $codigo,
            ),
        ]);
    }

    /**
     * Cria um convite sem endereço de correio eletrónico de destino.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function semEmailDestino(): static
    {
        return $this->state([
            'email_destino' => null,
        ]);
    }

    /**
     * Cria um convite sem prazo de expiração.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function semExpiracao(): static
    {
        return $this->state([
            'expira_em' => null,
        ]);
    }

    /**
     * Cria um convite expirado e ainda não utilizado nem revogado.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function expirado(): static
    {
        return $this->state([
            'expira_em' => now()->subMinute(),

            'utilizado_por_id' => null,

            'utilizado_em' => null,

            'revogado_em' => null,

            'revogado_por_id' => null,
        ]);
    }

    /**
     * Cria um convite revogado por um utilizador conhecido.
     *
     * O estado garante que um convite revogado não permanece simultaneamente
     * utilizado.
     *
     * @param  Utilizador  $responsavel  Responsável pela revogação.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o responsável não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function revogadoPor(
        Utilizador $responsavel,
    ): static {
        return $this->state([
            'utilizado_por_id' => null,

            'utilizado_em' => null,

            'revogado_em' => now(),

            'revogado_por_id' => $this->obterIdentificadorUtilizadorPersistido(
                $responsavel,
            ),
        ]);
    }

    /**
     * Associa o convite ao respetivo criador.
     *
     * @param  Utilizador  $utilizador  Criador do convite.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     */
    public function criadoPor(
        Utilizador $utilizador,
    ): static {
        return $this->state([
            'criado_por_id' => $this->obterIdentificadorUtilizadorPersistido(
                $utilizador,
            ),
        ]);
    }

    /**
     * Cria um convite utilizado por um determinado utilizador.
     *
     * O estado garante que um convite utilizado não permanece simultaneamente
     * revogado.
     *
     * @param  Utilizador  $utilizador  Utilizador que utilizou o convite.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     */
    public function utilizadoPor(
        Utilizador $utilizador,
    ): static {
        return $this->state([
            'utilizado_por_id' => $this->obterIdentificadorUtilizadorPersistido(
                $utilizador,
            ),

            'utilizado_em' => now(),

            'revogado_em' => null,

            'revogado_por_id' => null,
        ]);
    }

    /**
     * Obtém o identificador inteiro de um utilizador persistido.
     *
     * @param  Utilizador  $utilizador  Utilizador a validar.
     * @return int Identificador do utilizador.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorUtilizadorPersistido(
        Utilizador $utilizador,
    ): int {
        if (! $utilizador->exists) {
            throw new InvalidArgumentException(
                'O utilizador associado ao convite deve estar persistido.',
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

        if (is_string($identificador)) {
            $identificadorNormalizado = trim(
                $identificador,
            );

            if (
                $identificadorNormalizado !== ''
                && ctype_digit(
                    $identificadorNormalizado,
                )
                && (int) $identificadorNormalizado > 0
            ) {
                return (int) $identificadorNormalizado;
            }
        }

        throw new InvalidArgumentException(
            'O utilizador associado ao convite deve possuir um identificador válido.',
        );
    }
}
