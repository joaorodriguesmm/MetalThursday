<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara o modal utilizado para criar um artista.
 *
 * O componente valida e prepara as opções de origens geográficas e géneros
 * recebidas do controlador, bem como o endereço e os limites utilizados pelo
 * formulário.
 *
 * @since 1.0.0
 */
final class ModalCriarArtista extends Component
{
    /**
     * Origens geográficas disponíveis para seleção.
     *
     * @var array<int, array{
     *     identificador: int,
     *     nome: string
     * }>
     *
     * @since 2.0.0
     */
    public readonly array $origensGeograficas;

    /**
     * Géneros disponíveis para seleção.
     *
     * @var array<int, array{
     *     identificador: int,
     *     nome: string
     * }>
     *
     * @since 2.0.0
     */
    public readonly array $generos;

    /**
     * Endereço utilizado para guardar um artista.
     *
     * @since 2.0.0
     */
    public readonly string $enderecoGuardarArtista;

    /**
     * Comprimento máximo permitido para o nome do artista.
     *
     * @since 2.0.0
     */
    public readonly int $comprimentoMaximoNome;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  Collection<int, OrigemGeografica>  $origensGeograficas  Origens disponíveis.
     * @param  Collection<int, Genero>  $generos  Géneros disponíveis.
     *
     * @throws LogicException Quando uma coleção contém modelos ou dados
     *                        inválidos.
     *
     * @since 1.0.0
     */
    public function __construct(
        Collection $origensGeograficas,
        Collection $generos,
    ) {
        $this->origensGeograficas = $this->prepararOpcoes(
            $origensGeograficas,
            OrigemGeografica::class,
            'origens geográficas',
        );

        $this->generos = $this->prepararOpcoes(
            $generos,
            Genero::class,
            'géneros',
        );

        $this->enderecoGuardarArtista = route(
            'artistas.guardar',
        );

        $this->comprimentoMaximoNome =
            Artista::COMPRIMENTO_MAXIMO_NOME;
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista do modal.
     *
     * @since 1.0.0
     */
    public function render(): View
    {
        return view(
            'components.metal-thursday.modal-criar-artista',
        );
    }

    /**
     * Prepara opções de seleção a partir de modelos persistidos.
     *
     * @template TModelo of Model
     *
     * @param  Collection<int, TModelo>  $modelos  Modelos recebidos.
     * @param  class-string<TModelo>  $classeEsperada  Classe permitida.
     * @param  string  $descricaoColecao  Descrição utilizada nos erros.
     * @return array<int, array{
     *     identificador: int,
     *     nome: string
     * }> Opções preparadas.
     *
     * @throws LogicException Quando existe um modelo, identificador ou nome
     *                        inválido.
     *
     * @since 2.0.0
     */
    private function prepararOpcoes(
        Collection $modelos,
        string $classeEsperada,
        string $descricaoColecao,
    ): array {
        $opcoes = [];
        $identificadores = [];

        foreach ($modelos as $modelo) {
            if (! $modelo instanceof $classeEsperada) {
                throw new LogicException(
                    sprintf(
                        'A coleção de %s contém um modelo inesperado.',
                        $descricaoColecao,
                    ),
                );
            }

            $identificador = $this->obterIdentificadorModelo(
                $modelo,
                $descricaoColecao,
            );

            if (isset($identificadores[$identificador])) {
                throw new LogicException(
                    sprintf(
                        'A coleção de %s contém identificadores repetidos.',
                        $descricaoColecao,
                    ),
                );
            }

            $nome = $this->obterNomeModelo(
                $modelo,
                $descricaoColecao,
            );

            $identificadores[$identificador] = true;

            $opcoes[] = [
                'identificador' => $identificador,

                'nome' => $nome,
            ];
        }

        return $opcoes;
    }

    /**
     * Obtém o identificador positivo de um modelo persistido.
     *
     * @param  Model  $modelo  Modelo recebido.
     * @param  string  $descricaoColecao  Descrição utilizada no erro.
     * @return int Identificador do modelo.
     *
     * @throws LogicException Quando o modelo não está persistido ou possui
     *                        um identificador inválido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorModelo(
        Model $modelo,
        string $descricaoColecao,
    ): int {
        $identificador = filter_var(
            $modelo->getKey(),
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        if (
            ! $modelo->exists
            || $identificador === false
        ) {
            throw new LogicException(
                sprintf(
                    'A coleção de %s contém um modelo não persistido ou sem identificador válido.',
                    $descricaoColecao,
                ),
            );
        }

        return $identificador;
    }

    /**
     * Obtém o nome normalizado de um modelo de seleção.
     *
     * @param  Model  $modelo  Modelo recebido.
     * @param  string  $descricaoColecao  Descrição utilizada no erro.
     * @return string Nome normalizado.
     *
     * @throws LogicException Quando o modelo não possui um nome válido.
     *
     * @since 2.0.0
     */
    private function obterNomeModelo(
        Model $modelo,
        string $descricaoColecao,
    ): string {
        $nome = $modelo->getAttribute(
            'nome',
        );

        if (! is_string($nome)) {
            throw new LogicException(
                sprintf(
                    'A coleção de %s contém um modelo sem nome válido.',
                    $descricaoColecao,
                ),
            );
        }

        $nomeNormalizado = Str::squish(
            $nome,
        );

        if ($nomeNormalizado === '') {
            throw new LogicException(
                sprintf(
                    'A coleção de %s contém um modelo sem nome válido.',
                    $descricaoColecao,
                ),
            );
        }

        return $nomeNormalizado;
    }
}
