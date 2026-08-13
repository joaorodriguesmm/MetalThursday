<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para músicas favoritas de uma edição.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<MusicaFavoritaEdicao>
 *
 * @since 2.0.0
 */
final class MusicaFavoritaEdicaoFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<MusicaFavoritaEdicao>
     *
     * @since 2.0.0
     */
    protected $model = MusicaFavoritaEdicao::class;

    /**
     * Define os atributos predefinidos de uma música favorita.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da música favorita.
     *
     * @since 2.0.0
     */
    public function definition(): array
    {
        return [
            'edicao_id' => Edicao::factory(),

            'utilizador_id' => Utilizador::factory(),

            'posicao' => $this
                ->faker
                ->numberBetween(
                    MusicaFavoritaEdicao::POSICAO_MINIMA,
                    MusicaFavoritaEdicao::POSICAO_MAXIMA,
                ),

            'musica' => Str::limit(
                sprintf(
                    '%s — %s',
                    Str::ucfirst(
                        $this
                            ->faker
                            ->words(
                                2,
                                true,
                            ),
                    ),
                    Str::ucfirst(
                        $this
                            ->faker
                            ->words(
                                3,
                                true,
                            ),
                    ),
                ),
                MusicaFavoritaEdicao::COMPRIMENTO_MAXIMO_MUSICA,
                '',
            ),

            'registado_por_id' => null,
        ];
    }

    /**
     * Associa a música favorita a uma edição.
     *
     * @param  Edicao  $edicao  Edição pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a edição não está persistida.
     *
     * @since 2.0.0
     */
    public function paraEdicao(
        Edicao $edicao,
    ): static {
        $this->validarModeloPersistido(
            $edicao,
            'A edição associada à música favorita deve estar persistida.',
        );

        return $this->for(
            $edicao,
            'edicao',
        );
    }

    /**
     * Associa a escolha ao respetivo utilizador.
     *
     * @param  Utilizador  $utilizador  Proprietário da escolha.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function pertencenteA(
        Utilizador $utilizador,
    ): static {
        $this->validarModeloPersistido(
            $utilizador,
            'O proprietário da música favorita deve estar persistido.',
        );

        return $this->for(
            $utilizador,
            'utilizador',
        );
    }

    /**
     * Define o utilizador que registou a escolha.
     *
     * @param  Utilizador  $utilizador  Utilizador responsável pelo registo.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function registadaPor(
        Utilizador $utilizador,
    ): static {
        $this->validarModeloPersistido(
            $utilizador,
            'O utilizador responsável pelo registo deve estar persistido.',
        );

        return $this->for(
            $utilizador,
            'registadoPor',
        );
    }

    /**
     * Define a posição da música favorita.
     *
     * A validação é delegada ao contrato definitivo do modelo.
     *
     * @param  int  $posicao  Posição pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a posição não é válida.
     *
     * @since 2.0.0
     */
    public function comPosicao(
        int $posicao,
    ): static {
        $musicaFavorita = new MusicaFavoritaEdicao;

        $musicaFavorita->posicao =
            $posicao;

        return $this->state([
            'posicao' => $musicaFavorita->posicao,
        ]);
    }

    /**
     * Define a identificação da música favorita.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo.
     *
     * @param  string  $musica  Identificação da música.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a identificação não é válida.
     *
     * @since 2.0.0
     */
    public function comMusica(
        string $musica,
    ): static {
        $musicaFavorita = new MusicaFavoritaEdicao;

        $musicaFavorita->musica =
            $musica;

        return $this->state([
            'musica' => $musicaFavorita->musica,
        ]);
    }

    /**
     * Cria uma escolha sem o utilizador responsável pelo registo identificado.
     *
     * Este estado é útil para representar a eliminação posterior do
     * utilizador que registou a escolha.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function semRegistador(): static
    {
        return $this->state([
            'registado_por_id' => null,
        ]);
    }

    /**
     * Valida que um modelo relacionado já se encontra persistido.
     *
     * @param  Model  $modelo  Modelo a validar.
     * @param  string  $mensagem  Mensagem utilizada em caso de erro.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido.
     *
     * @since 2.0.0
     */
    private function validarModeloPersistido(
        Model $modelo,
        string $mensagem,
    ): void {
        if (
            ! $modelo->exists
            || $modelo->getKey() === null
        ) {
            throw new InvalidArgumentException(
                $mensagem,
            );
        }
    }
}
