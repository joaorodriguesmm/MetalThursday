<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use Illuminate\Database\Eloquent\Collection as ColecaoEloquent;
use Illuminate\Support\Collection;
use LogicException;

/**
 * Prepara os dados necessários à página de detalhes de uma edição.
 *
 * O serviço agrupa as músicas favoritas por utilizador, determina se os
 * resultados estão concluídos e prepara os campos utilizados pelo formulário.
 *
 * @since 3.0.0
 *
 * @version 1.0.0
 */
final class ServicoApresentacaoDetalhesEdicao
{
    /**
     * Prepara os dados da página de detalhes.
     *
     * @param  Edicao  $edicao  Edição apresentada.
     * @return array{
     *     gruposMusicasFavoritas: array<int, array{
     *         utilizador: Utilizador,
     *         escolhas: Collection<int, MusicaFavoritaEdicao>,
     *         campos: array<int, array{
     *             indice: int,
     *             posicao: int,
     *             nomeCampo: string,
     *             chaveCampo: string,
     *             identificadorCampo: string,
     *             identificadorErro: string,
     *             valorPredefinido: string
     *         }>
     *     }>,
     *     bloqueada: bool,
     *     ligacaoCompilacao: string|null
     * } Dados preparados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function preparar(
        Edicao $edicao,
    ): array {
        $utilizadores =
            $this->obterUtilizadores();

        $classificacoes =
            $this->obterClassificacoes(
                $edicao,
            );

        $gruposMusicasFavoritas = [];

        foreach ($utilizadores as $utilizador) {
            $identificadorUtilizador =
                $this->obterIdentificadorUtilizador(
                    $utilizador,
                );

            /** @var Collection<int, MusicaFavoritaEdicao> $escolhas */
            $escolhas = $classificacoes->get(
                $identificadorUtilizador,
                collect(),
            );

            $gruposMusicasFavoritas[] = [
                'utilizador' =>
                $utilizador,

                'escolhas' =>
                $escolhas,

                'campos' =>
                $this->prepararCampos(
                    $identificadorUtilizador,
                    $escolhas,
                ),
            ];
        }

        return [
            'gruposMusicasFavoritas' =>
            $gruposMusicasFavoritas,

            'bloqueada' =>
            $this->determinarSeEstaBloqueada(
                $gruposMusicasFavoritas,
            ),

            'ligacaoCompilacao' =>
            $this->normalizarLigacaoCompilacao(
                $edicao->ligacao_compilacao,
            ),
        ];
    }

    /**
     * Obtém os utilizadores disponíveis para classificação.
     *
     * @return ColecaoEloquent<int, Utilizador> Utilizadores selecionáveis.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadores(): ColecaoEloquent
    {
        return Utilizador::query()
            ->selecionaveis()
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
     * Obtém e agrupa as músicas favoritas da edição.
     *
     * @param  Edicao  $edicao  Edição consultada.
     * @return Collection<int, Collection<int, MusicaFavoritaEdicao>>
     *         Classificações agrupadas por utilizador.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterClassificacoes(
        Edicao $edicao,
    ): Collection {
        return MusicaFavoritaEdicao::query()
            ->select([
                'id',
                'edicao_id',
                'utilizador_id',
                'posicao',
                'musica',
            ])
            ->where(
                'edicao_id',
                $edicao->getKey(),
            )
            ->orderBy(
                'utilizador_id',
            )
            ->orderBy(
                'posicao',
            )
            ->orderBy(
                'id',
            )
            ->get()
            ->groupBy(
                'utilizador_id',
            );
    }

    /**
     * Prepara os campos de classificação de um utilizador.
     *
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  Collection<int, MusicaFavoritaEdicao>  $escolhas  Escolhas
     *                                                            existentes.
     * @return array<int, array{
     *     indice: int,
     *     posicao: int,
     *     nomeCampo: string,
     *     chaveCampo: string,
     *     identificadorCampo: string,
     *     identificadorErro: string,
     *     valorPredefinido: string
     * }> Campos preparados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function prepararCampos(
        int $identificadorUtilizador,
        Collection $escolhas,
    ): array {
        $escolhasPorPosicao =
            $escolhas->keyBy(
                'posicao',
            );

        $campos = [];

        for (
            $indice = 0;
            $indice < ServicoMusicasFavoritasEdicao::NUMERO_POSICOES;
            $indice++
        ) {
            $posicao =
                $indice + 1;

            $escolha =
                $escolhasPorPosicao->get(
                    $posicao,
                );

            $campos[] = [
                'indice' =>
                $indice,

                'posicao' =>
                $posicao,

                'nomeCampo' =>
                "classificacoes[{$identificadorUtilizador}][{$indice}]",

                'chaveCampo' =>
                "classificacoes.{$identificadorUtilizador}.{$indice}",

                'identificadorCampo' =>
                "musica-{$identificadorUtilizador}-{$posicao}",

                'identificadorErro' =>
                "erro-musica-{$identificadorUtilizador}-{$posicao}",

                'valorPredefinido' =>
                $escolha instanceof MusicaFavoritaEdicao
                    ? trim(
                        (string) $escolha->musica,
                    )
                    : '',
            ];
        }

        return $campos;
    }

    /**
     * Determina se todos os utilizadores concluíram as classificações.
     *
     * @param  array<int, array{
     *     utilizador: Utilizador,
     *     escolhas: Collection<int, MusicaFavoritaEdicao>,
     *     campos: array<int, array<string, int|string>>
     * }>  $grupos  Grupos preparados.
     * @return bool Verdadeiro quando os resultados estão concluídos.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function determinarSeEstaBloqueada(
        array $grupos,
    ): bool {
        if ($grupos === []) {
            return false;
        }

        foreach ($grupos as $grupo) {
            if (
                $grupo['escolhas']->count()
                < ServicoMusicasFavoritasEdicao::NUMERO_POSICOES
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtém o identificador persistido de um utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador consultado.
     * @return int Identificador do utilizador.
     *
     * @throws LogicException Quando o identificador não é válido.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $utilizador,
    ): int {
        $identificador =
            $utilizador->getKey();

        if (
            ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new LogicException(
                'O utilizador deve possuir um identificador válido.',
            );
        }

        return (int) $identificador;
    }

    /**
     * Normaliza a ligação da compilação.
     *
     * @param  mixed  $ligacao  Ligação recebida.
     * @return string|null Ligação normalizada ou nula.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarLigacaoCompilacao(
        mixed $ligacao,
    ): ?string {
        if (! is_string($ligacao)) {
            return null;
        }

        $ligacaoNormalizada =
            trim(
                $ligacao,
            );

        return $ligacaoNormalizada !== ''
            ? $ligacaoNormalizada
            : null;
    }
}
