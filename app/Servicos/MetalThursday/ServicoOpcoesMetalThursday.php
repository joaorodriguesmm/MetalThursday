<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Obtém e serializa opções estáveis utilizadas pelo MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoOpcoesMetalThursday
{
    /**
     * Obtém as edições disponíveis para seleção.
     *
     * @return Collection<int, Edicao>
     *
     * @since 2.0.0
     */
    public function obterEdicoesParaSelecao(): Collection
    {
        return Edicao::query()
            ->select([
                'id',
                'nome',
                'data_inicio',
                'data_fim',
            ])
            ->orderByDesc(
                'data_inicio',
            )
            ->orderByDesc(
                'id',
            )
            ->get();
    }

    /**
     * Obtém os utilizadores disponíveis para seleção geral.
     *
     * @return Collection<int, Utilizador>
     *
     * @since 2.0.0
     */
    public function obterUtilizadoresParaSelecao(): Collection
    {
        return Utilizador::query()
            ->comAcessoAtivo()
            ->selecionaveis()
            ->select([
                'id',
                'nome',
            ])
            ->reorder(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Obtém os utilizadores elegíveis para uma nova nomeação.
     *
     * Durante a edição, o nomeado atualmente persistido continua disponível
     * para permitir conservar uma nomeação anteriormente válida, mesmo que
     * tenha entretanto deixado de ser elegível para novas nomeações.
     *
     * @param  MetalThursday|null  $metalThursday  Registo atualmente editado.
     * @return Collection<int, Utilizador>
     *
     * @since 2.0.0
     */
    public function obterUtilizadoresElegiveisNomeacao(
        ?MetalThursday $metalThursday = null,
    ): Collection {
        $construtorElegiveis = Utilizador::query()
            ->elegiveisParaNomeacao()
            ->select([
                'id',
            ])
            ->reorder();

        $construtor = Utilizador::query()
            ->whereIn(
                'id',
                $construtorElegiveis,
            );

        $identificadorNomeadoAtual =
            $metalThursday?->proximo_nomeado_id;

        if (
            is_numeric(
                $identificadorNomeadoAtual,
            )
            && (int) $identificadorNomeadoAtual > 0
        ) {
            $construtor->orWhere(
                'utilizadores.id',
                (int) $identificadorNomeadoAtual,
            );
        }

        return $construtor
            ->select([
                'id',
                'nome',
            ])
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Obtém os artistas disponíveis para seleção.
     *
     * Durante a edição, os artistas já associados às secções permanecem
     * disponíveis mesmo que tenham sido entretanto eliminados logicamente. Os
     * restantes artistas eliminados continuam excluídos.
     *
     * A origem geográfica, o ano de início e os géneros são carregados
     * antecipadamente porque integram o rótulo contextual apresentado nas
     * opções.
     *
     * @param  MetalThursday|null  $metalThursday  Registo atualmente editado.
     * @return Collection<int, Artista>
     *
     * @throws LogicException Quando as secções esperadas não estão carregadas ou
     *                        possuem um tipo inesperado.
     *
     * @since 2.0.0
     */
    public function obterArtistasParaSelecao(
        ?MetalThursday $metalThursday = null,
    ): Collection {
        $identificadoresArtistasAtuais = [];

        if ($metalThursday instanceof MetalThursday) {
            if (! $metalThursday->relationLoaded('seccoes')) {
                throw new LogicException(
                    'A relação "seccoes" deve estar carregada para preparar os artistas da edição.',
                );
            }

            foreach ($metalThursday->getRelation('seccoes') as $seccao) {
                if (! $seccao instanceof SeccaoMetalThursday) {
                    throw new LogicException(
                        'A relação "seccoes" contém um modelo inesperado.',
                    );
                }

                if (
                    is_numeric(
                        $seccao->artista_id,
                    )
                    && (int) $seccao->artista_id > 0
                ) {
                    $identificadoresArtistasAtuais[] =
                        (int) $seccao->artista_id;
                }
            }

            $identificadoresArtistasAtuais = array_values(
                array_unique(
                    $identificadoresArtistasAtuais,
                ),
            );
        }

        $construtor = Artista::query();

        if ($identificadoresArtistasAtuais !== []) {
            $construtor
                ->withTrashed()
                ->where(
                    static function (
                        Builder $construtorArtistas,
                    ) use (
                        $identificadoresArtistasAtuais,
                    ): void {
                        $construtorArtistas
                            ->whereNull(
                                'artistas.deleted_at',
                            )
                            ->orWhereIn(
                                'artistas.id',
                                $identificadoresArtistasAtuais,
                            );
                    },
                );
        }

        return $construtor
            ->select([
                'id',
                'nome',
                'origem_geografica_id',
                'ano_inicio_atividade',
            ])
            ->with([
                'origemGeografica:id,nome',
                'generos:id,nome',
            ])
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Obtém os tipos de secção apresentados no formulário.
     *
     * @return Collection<int, TipoSeccao>
     *
     * @since 2.0.0
     */
    public function obterTiposSeccao(): Collection
    {
        return TipoSeccao::query()
            ->select([
                'id',
                'identificador',
                'nome',
                'descricao',
                'exige_detalhes',
            ])
            ->orderBy(
                'ordem',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Obtém as origens geográficas disponíveis.
     *
     * @return Collection<int, OrigemGeografica>
     *
     * @since 2.0.0
     */
    public function obterOrigensGeograficas(): Collection
    {
        return OrigemGeografica::query()
            ->select([
                'id',
                'nome',
            ])
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Obtém os géneros disponíveis para seleção.
     *
     * @return Collection<int, Genero>
     *
     * @since 2.0.0
     */
    public function obterGenerosParaSelecao(): Collection
    {
        return Genero::query()
            ->select([
                'id',
                'nome',
            ])
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Converte modelos nomeados em opções simples para JavaScript.
     *
     * @param  Collection<int, covariant Model>  $modelos
     * @return array<int, array{identificador: int, nome: string}>
     *
     * @throws LogicException Quando um modelo não possui identificador ou nome
     *                        válidos.
     *
     * @since 2.0.0
     */
    public function serializarOpcoesSelecao(
        Collection $modelos,
    ): array {
        $opcoes = [];

        foreach ($modelos as $modelo) {
            $identificador =
                $modelo->getKey();

            $nome =
                $modelo->getAttribute(
                    'nome',
                );

            if (
                ! is_numeric($identificador)
                || (int) $identificador < 1
            ) {
                throw new LogicException(
                    sprintf(
                        'O modelo %s não possui um identificador válido.',
                        $modelo::class,
                    ),
                );
            }

            if (
                ! is_string($nome)
                || trim($nome) === ''
            ) {
                throw new LogicException(
                    sprintf(
                        'O modelo %s não possui um nome válido.',
                        $modelo::class,
                    ),
                );
            }

            $opcoes[] = [
                'identificador' => (int) $identificador,

                'nome' => trim(
                    $nome,
                ),
            ];
        }

        return $opcoes;
    }
}
