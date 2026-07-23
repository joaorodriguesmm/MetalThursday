<?php

declare(strict_types=1);

namespace Database\Factories\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Cria dados de teste para convites.
 *
 * @extends Factory<Convite>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class ConviteFactory extends Factory
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
    protected $model = Convite::class;

    /**
     * Define os atributos por omissão de um convite.
     *
     * O código original não é persistido. A factory gera um código aleatório
     * e guarda apenas o respetivo hash.
     *
     * @return array<string, mixed> Atributos do convite.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        $codigo = Str::random(64);

        return [
            'nome_convidado' => fake()->name(),
            'email_destino' => fake()->unique()->safeEmail(),
            'codigo_hash' => Convite::calcularHashCodigo(
                $codigo,
            ),
            'criado_por_id' => null,
            'utilizado_por_id' => null,
            'expira_em' => now()->addDays(7),
            'utilizado_em' => null,
            'revogado_em' => null,
        ];
    }

    /**
     * Define um código conhecido para o convite.
     *
     * Este estado é útil em testes que precisam de validar a consulta ou a
     * comparação de um código específico.
     *
     * @param  string  $codigo  Código original do convite.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comCodigo(
        string $codigo,
    ): static {
        return $this->state(
            fn (): array => [
                'codigo_hash' => Convite::calcularHashCodigo(
                    $codigo,
                ),
            ],
        );
    }

    /**
     * Cria um convite sem endereço de e-mail de destino.
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
            fn (): array => [
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
            fn (): array => [
                'expira_em' => null,
            ],
        );
    }

    /**
     * Cria um convite expirado.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function expirado(): static
    {
        return $this->state(
            fn (): array => [
                'expira_em' => now()->subMinute(),
                'utilizado_por_id' => null,
                'utilizado_em' => null,
                'revogado_em' => null,
            ],
        );
    }

    /**
     * Cria um convite revogado.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function revogado(): static
    {
        return $this->state(
            fn (): array => [
                'utilizado_por_id' => null,
                'utilizado_em' => null,
                'revogado_em' => now(),
            ],
        );
    }

    /**
     * Associa o convite ao respetivo criador.
     *
     * O utilizador recebido deve estar persistido antes de a factory criar o
     * convite na base de dados.
     *
     * @param  Utilizador  $utilizador  Criador do convite.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function criadoPor(
        Utilizador $utilizador,
    ): static {
        return $this->state(
            fn (): array => [
                'criado_por_id' => $utilizador->getKey(),
            ],
        );
    }

    /**
     * Cria um convite utilizado por um utilizador.
     *
     * O utilizador recebido deve estar persistido antes de a factory criar o
     * convite na base de dados.
     *
     * @param  Utilizador  $utilizador  Utilizador associado ao convite.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function utilizadoPor(
        Utilizador $utilizador,
    ): static {
        return $this->state(
            fn (): array => [
                'utilizado_por_id' => $utilizador->getKey(),
                'utilizado_em' => now(),
                'revogado_em' => null,
            ],
        );
    }
}
