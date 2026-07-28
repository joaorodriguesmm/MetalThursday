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
 *
 * @version 2.0.0
 */
final class ConviteFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<Convite>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model =
        Convite::class;

    /**
     * Define os atributos predefinidos de um convite.
     *
     * O código original não é persistido. É gerado um código aleatório e
     * guardado apenas o respetivo hash.
     *
     * @return array<string, mixed> Atributos do convite.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
        ];
    }

    /**
     * Define um código conhecido para o convite.
     *
     * O código é validado e normalizado pelo próprio contrato do modelo antes
     * de o respetivo hash ser colocado no estado da factory.
     *
     * @param  string  $codigo  Código original do convite.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function comCodigo(
        string $codigo,
    ): static {
        $codigoHash = Convite::calcularHashCodigo(
            $codigo,
        );

        return $this->state(
            static fn (): array => [
                'codigo_hash' => $codigoHash,
            ],
        );
    }

    /**
     * Cria um convite sem endereço de correio eletrónico de destino.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function semEmailDestino(): static
    {
        return $this->state(
            static fn (): array => [
                'email_destino' => null,
            ],
        );
    }

    /**
     * Cria um convite sem prazo de expiração.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function semExpiracao(): static
    {
        return $this->state(
            static fn (): array => [
                'expira_em' => null,
            ],
        );
    }

    /**
     * Cria um convite expirado e ainda não utilizado nem revogado.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function expirado(): static
    {
        return $this->state(
            static fn (): array => [
                'expira_em' => now()->subMinute(),

                'utilizado_por_id' => null,

                'utilizado_em' => null,

                'revogado_em' => null,
            ],
        );
    }

    /**
     * Cria um convite revogado e ainda não utilizado.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function revogado(): static
    {
        return $this->state(
            static fn (): array => [
                'utilizado_por_id' => null,

                'utilizado_em' => null,

                'revogado_em' => now(),
            ],
        );
    }

    /**
     * Associa o convite ao respetivo criador.
     *
     * @param  Utilizador  $utilizador  Criador do convite.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function criadoPor(
        Utilizador $utilizador,
    ): static {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizadorPersistido(
                $utilizador,
            );

        return $this->state(
            static fn (): array => [
                'criado_por_id' => $identificadorUtilizador,
            ],
        );
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
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function utilizadoPor(
        Utilizador $utilizador,
    ): static {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizadorPersistido(
                $utilizador,
            );

        return $this->state(
            static fn (): array => [
                'utilizado_por_id' => $identificadorUtilizador,

                'utilizado_em' => now(),

                'revogado_em' => null,
            ],
        );
    }

    /**
     * Obtém o identificador de um utilizador persistido.
     *
     * @param  Utilizador  $utilizador  Utilizador a validar.
     * @return int|string Identificador do utilizador.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterIdentificadorUtilizadorPersistido(
        Utilizador $utilizador,
    ): int|string {
        $identificador = $utilizador->getKey();

        if (
            ! $utilizador->exists
            || $identificador === null
        ) {
            throw new InvalidArgumentException(
                'O utilizador associado ao convite deve estar persistido.',
            );
        }

        return $identificador;
    }
}
