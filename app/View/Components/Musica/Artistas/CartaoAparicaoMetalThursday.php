<?php

declare(strict_types=1);

namespace App\View\Components\Musica\Artistas;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara uma aparição de um artista numa MetalThursday.
 *
 * O componente utiliza exclusivamente relações previamente carregadas,
 * evitando consultas adicionais durante a apresentação.
 *
 * @since 2.0.0
 */
final class CartaoAparicaoMetalThursday extends Component
{
    /**
     * Dados preparados para apresentação.
     *
     * @var array{
     *     identificador: int,
     *     titulo: string,
     *     ano: int|null,
     *     nomeTipoSeccao: string,
     *     enderecoMetalThursday: string|null,
     *     nomeAutor: string,
     *     dataIso: string,
     *     dataApresentacao: string,
     *     descricao: HtmlString|null,
     *     ligacao: string|null
     * }
     *
     * @since 2.0.0
     */
    public readonly array $dados;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção apresentada.
     * @param  string  $nomeArtista  Nome do artista usado como título alternativo.
     *
     * @throws LogicException Quando uma relação necessária não está carregada
     *                        ou possui um tipo inesperado.
     *
     * @since 2.0.0
     */
    public function __construct(
        SeccaoMetalThursday $seccao,
        string $nomeArtista,
    ) {
        $metalThursday =
            $this->obterMetalThursday(
                $seccao,
            );

        $tipoSeccao =
            $this->obterTipoSeccao(
                $seccao,
            );

        $data =
            $metalThursday->data;

        if (! $data instanceof DateTimeInterface) {
            throw new LogicException(
                'A data da MetalThursday possui um tipo inesperado.',
            );
        }

        $titulo =
            $this->normalizarTexto(
                $seccao->titulo,
            )
            ?? $this->normalizarTexto(
                $nomeArtista,
            )
            ?? 'Artista indisponível';

        $descricao =
            $this->normalizarTexto(
                $seccao->descricao,
            );

        $this->dados = [
            'identificador' => $this->obterIdentificador(
                $seccao,
            ),

            'titulo' => $titulo,

            'ano' => $this->normalizarAno(
                $seccao->ano,
            ),

            'nomeTipoSeccao' => $this->normalizarTexto(
                $tipoSeccao->nome,
            )
                ?? 'Tipo indisponível',

            'enderecoMetalThursday' => $metalThursday->trashed()
                ? null
                : route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),

            'nomeAutor' => $this->obterNomeAutor(
                $metalThursday,
            ),

            'dataIso' => $data->format(
                'Y-m-d',
            ),

            'dataApresentacao' => $data->format(
                'd/m/Y',
            ),

            'descricao' => $descricao !== null
                ? new HtmlString(
                    nl2br(
                        e(
                            $descricao,
                        ),
                    ),
                )
                : null,

            'ligacao' => $this->normalizarTexto(
                $seccao->ligacao,
            ),
        ];
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista da aparição.
     *
     * @since 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.musica.artistas.cartao-aparicao-metal-thursday',
        );
    }

    /**
     * Obtém a MetalThursday relacionada com a secção.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção consultada.
     * @return MetalThursday MetalThursday relacionada.
     *
     * @throws LogicException Quando a relação não está carregada ou possui
     *                        um tipo inesperado.
     *
     * @since 2.0.0
     */
    private function obterMetalThursday(
        SeccaoMetalThursday $seccao,
    ): MetalThursday {
        if (! $seccao->relationLoaded('metalThursday')) {
            throw new LogicException(
                'A relação "metalThursday" deve estar carregada.',
            );
        }

        $metalThursday =
            $seccao->getRelation(
                'metalThursday',
            );

        if (! $metalThursday instanceof MetalThursday) {
            throw new LogicException(
                'A relação "metalThursday" possui um tipo inesperado.',
            );
        }

        return $metalThursday;
    }

    /**
     * Obtém o tipo relacionado com a secção.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção consultada.
     * @return TipoSeccao Tipo da secção.
     *
     * @throws LogicException Quando a relação não está carregada, é nula ou
     *                        possui um tipo inesperado.
     *
     * @since 2.0.0
     */
    private function obterTipoSeccao(
        SeccaoMetalThursday $seccao,
    ): TipoSeccao {
        if (! $seccao->relationLoaded('tipoSeccao')) {
            throw new LogicException(
                'A relação "tipoSeccao" deve estar carregada.',
            );
        }

        $tipoSeccao =
            $seccao->getRelation(
                'tipoSeccao',
            );

        if (! $tipoSeccao instanceof TipoSeccao) {
            throw new LogicException(
                'A relação "tipoSeccao" possui um tipo inesperado.',
            );
        }

        return $tipoSeccao;
    }

    /**
     * Obtém o nome do autor da MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday consultada.
     * @return string Nome do autor.
     *
     * @throws LogicException Quando a relação não está carregada ou possui
     *                        um tipo inesperado.
     *
     * @since 2.0.0
     */
    private function obterNomeAutor(
        MetalThursday $metalThursday,
    ): string {
        if (! $metalThursday->relationLoaded('autor')) {
            throw new LogicException(
                'A relação "autor" deve estar carregada.',
            );
        }

        $autor =
            $metalThursday->getRelation(
                'autor',
            );

        if ($autor === null) {
            return 'Utilizador removido';
        }

        if (! $autor instanceof Utilizador) {
            throw new LogicException(
                'A relação "autor" possui um tipo inesperado.',
            );
        }

        return $this->normalizarTexto(
            $autor->nome,
        )
            ?? 'Utilizador removido';
    }

    /**
     * Obtém o identificador persistido da secção.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção consultada.
     * @return int Identificador da secção.
     *
     * @throws LogicException Quando a secção não está persistida.
     *
     * @since 2.0.0
     */
    private function obterIdentificador(
        SeccaoMetalThursday $seccao,
    ): int {
        $identificador =
            $seccao->getKey();

        if (
            ! $seccao->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new LogicException(
                'A secção deve estar persistida.',
            );
        }

        return (int) $identificador;
    }

    /**
     * Normaliza um texto opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Texto normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarTexto(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $texto =
            trim(
                $valor,
            );

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Normaliza o ano da secção.
     *
     * @param  mixed  $ano  Ano recebido.
     * @return int|null Ano normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarAno(
        mixed $ano,
    ): ?int {
        if (
            ! is_numeric($ano)
            || (int) $ano < 1
        ) {
            return null;
        }

        return (int) $ano;
    }
}
