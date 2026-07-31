<?php

declare(strict_types=1);

namespace Database\Factories\Comunicacao;

use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Cria dados de teste para permissões de correio eletrónico.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<PermissaoEmail>
 *
 * @since 2.0.0
 *
 * @version 2.1.0
 */
final class PermissaoEmailFactory extends Factory
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
     * Define os atributos predefinidos de uma permissão.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da permissão.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
     */
    public function definition(): array
    {
        $nome = Str::ucfirst(
            $this
                ->faker
                ->unique()
                ->words(
                    3,
                    true,
                ),
        );

        return [
            'identificador' => sprintf(
                'permissao_%s',
                Str::lower(
                    Str::random(
                        16,
                    ),
                ),
            ),

            'nome' => Str::limit(
                $nome,
                PermissaoEmail::COMPRIMENTO_MAXIMO_NOME,
                '',
            ),

            'descricao' => Str::limit(
                $this
                    ->faker
                    ->sentence(),
                PermissaoEmail::COMPRIMENTO_MAXIMO_DESCRICAO,
                '',
            ),

            'ordem' => $this
                ->faker
                ->unique()
                ->numberBetween(
                    PermissaoEmail::ORDEM_MINIMA,
                    PermissaoEmail::ORDEM_MAXIMA,
                ),
        ];
    }

    /**
     * Define o identificador da permissão.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo.
     *
     * @param  string  $identificador  Identificador pretendido.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
     */
    public function comIdentificador(
        string $identificador,
    ): static {
        $permissao = new PermissaoEmail;

        $permissao->identificador =
            $identificador;

        return $this->state([
            'identificador' => $permissao->identificador,
        ]);
    }

    /**
     * Define os dados apresentados ao utilizador.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo.
     *
     * @param  string  $nome  Nome da permissão.
     * @param  string  $descricao  Explicação da permissão.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
     */
    public function comDados(
        string $nome,
        string $descricao,
    ): static {
        $permissao = new PermissaoEmail;

        $permissao->nome =
            $nome;

        $permissao->descricao =
            $descricao;

        return $this->state([
            'nome' => $permissao->nome,

            'descricao' => $permissao->descricao,
        ]);
    }

    /**
     * Define a ordem de apresentação da permissão.
     *
     * A validação é delegada ao contrato definitivo do modelo.
     *
     * @param  int  $ordem  Ordem pretendida.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
     */
    public function naOrdem(
        int $ordem,
    ): static {
        $permissao = new PermissaoEmail;

        $permissao->ordem =
            $ordem;

        return $this->state([
            'ordem' => $permissao->ordem,
        ]);
    }
}
