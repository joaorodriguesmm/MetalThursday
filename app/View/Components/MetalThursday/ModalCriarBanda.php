<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Prepara o modal utilizado para criar uma banda.
 *
 * O componente normaliza os valores antigos do formulário e recebe as
 * coleções de países e géneros preparadas pelo controlador da página.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class ModalCriarBanda extends Component
{
    /**
     * Países disponíveis para seleção.
     *
     * @var Collection<int, mixed>
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly Collection $paises;

    /**
     * Géneros disponíveis para seleção.
     *
     * @var Collection<int, mixed>
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly Collection $generos;

    /**
     * Nome anteriormente introduzido.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $nomeBanda;

    /**
     * Identificador do país anteriormente selecionado.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $identificadorPaisSelecionado;

    /**
     * Identificadores dos géneros anteriormente selecionados.
     *
     * @var array<int, string>
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly array $identificadoresGenerosSelecionados;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Collection<int, mixed>  $paises  Países disponíveis.
     * @param  Collection<int, mixed>  $generos  Géneros disponíveis.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function __construct(
        Request $pedido,
        Collection $paises,
        Collection $generos,
    ) {
        $this->paises = $paises;
        $this->generos = $generos;

        $this->nomeBanda =
            $this->normalizarTexto(
                $pedido->old(
                    'nome',
                ),
            );

        $this->identificadorPaisSelecionado =
            $this->normalizarIdentificador(
                $pedido->old(
                    'pais_id',
                ),
            );

        $this->identificadoresGenerosSelecionados =
            $this->normalizarIdentificadores(
                $pedido->old(
                    'generos',
                    [],
                ),
            );
    }

    /**
     * Obtém a view do componente.
     *
     * @return View View do modal.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.metal-thursday.modal-criar-banda',
        );
    }

    /**
     * Normaliza um texto utilizado num campo do formulário.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string Texto normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarTexto(
        mixed $valor,
    ): string {
        if (
            ! is_string($valor)
            && ! is_int($valor)
            && ! is_float($valor)
        ) {
            return '';
        }

        return trim(
            (string) $valor,
        );
    }

    /**
     * Normaliza um identificador selecionado.
     *
     * @param  mixed  $valor  Identificador recebido.
     * @return string Identificador normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificador(
        mixed $valor,
    ): string {
        if (
            ! is_int($valor)
            && ! is_string($valor)
        ) {
            return '';
        }

        $identificador =
            trim(
                (string) $valor,
            );

        if (
            $identificador === ''
            || ! ctype_digit($identificador)
            || (int) $identificador < 1
        ) {
            return '';
        }

        return (string) (int) $identificador;
    }

    /**
     * Normaliza uma lista de identificadores selecionados.
     *
     * @param  mixed  $valores  Valores recebidos.
     * @return array<int, string> Identificadores normalizados e únicos.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificadores(
        mixed $valores,
    ): array {
        if (! is_array($valores)) {
            return [];
        }

        $identificadores = [];

        foreach ($valores as $valor) {
            $identificador =
                $this->normalizarIdentificador(
                    $valor,
                );

            if ($identificador === '') {
                continue;
            }

            $identificadores[$identificador] =
                $identificador;
        }

        return array_values(
            $identificadores,
        );
    }
}
