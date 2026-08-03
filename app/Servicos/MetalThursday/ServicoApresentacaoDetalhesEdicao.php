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
 * O serviço agrupa as músicas favoritas por utilizador, determina se todos os
 * utilizadores concluíram as respetivas escolhas e prepara os campos
 * utilizados pelo formulário.
 *
 * @since 3.0.0
 *
 * @version 2.0.0
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
     * @throws LogicException Quando a edição ou algum utilizador não possui
     *                        um identificador persistido válido.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public function preparar(
        Edicao $edicao,
    ): array {
        $this->obterIdentificadorEdicao(
            $edicao,
        );

        $utilizadores =
            $this->obterUtilizadores();

        $musicasFavoritasPorUtilizador =
            $this->obterMusicasFavoritasPorUtilizador(
                $edicao,
            );

        $gruposMusicasFavoritas = [];

        foreach ($utilizadores as $utilizador) {
            $identificadorUtilizador =
                $this->obterIdentificadorUtilizador(
                    $utilizador,
                );

            $escolhas =
                $musicasFavoritasPorUtilizador->get(
                    $identificadorUtilizador,
                );

            if (! $escolhas instanceof Collection) {
                $escolhas = collect();
            }

            /** @var Collection<int, MusicaFavoritaEdicao> $escolhas */
            $gruposMusicasFavoritas[] = [
                'utilizador' => $utilizador,

                'escolhas' => $escolhas,

                'campos' => $this->prepararCampos(
                    $identificadorUtilizador,
                    $escolhas,
                ),
            ];
        }

        return [
            'gruposMusicasFavoritas' => $gruposMusicasFavoritas,

            'bloqueada' => $this->determinarSeEstaBloqueada(
                $gruposMusicasFavoritas,
            ),

            'ligacaoCompilacao' => $this->obterLigacaoCompilacao(
                $edicao,
            ),
        ];
    }

    /**
     * Obtém os utilizadores disponíveis para escolha de músicas favoritas.
     *
     * O escopo `selecionaveis` exclui o superadministrador e aplica a
     * ordenação final por nome e identificador.
     *
     * @return ColecaoEloquent<int, Utilizador> Utilizadores selecionáveis.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterUtilizadores(): ColecaoEloquent
    {
        return Utilizador::query()
            ->selecionaveis()
            ->select([
                'id',
                'nome',
            ])
            ->get();
    }

    /**
     * Obtém e agrupa as músicas favoritas da edição por utilizador.
     *
     * A consulta utiliza a relação definitiva {@see Edicao::musicasFavoritas}
     * e conserva a ordenação por utilizador, posição e identificador definida
     * nessa relação.
     *
     * @param  Edicao  $edicao  Edição consultada.
     * @return Collection<int, Collection<int, MusicaFavoritaEdicao>>
     *                                                                Músicas favoritas agrupadas por utilizador.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterMusicasFavoritasPorUtilizador(
        Edicao $edicao,
    ): Collection {
        /** @var Collection<
         *     int,
         *     Collection<int, MusicaFavoritaEdicao>
         * > $musicasFavoritasPorUtilizador
         */
        $musicasFavoritasPorUtilizador =
            $edicao
                ->musicasFavoritas()
                ->select([
                    'id',
                    'edicao_id',
                    'utilizador_id',
                    'posicao',
                    'musica',
                ])
                ->get()
                ->groupBy(
                    static fn (
                        MusicaFavoritaEdicao $musicaFavorita,
                    ): int => $musicaFavorita->utilizador_id,
                );

        return $musicasFavoritasPorUtilizador;
    }

    /**
     * Prepara os campos das músicas favoritas de um utilizador.
     *
     * Os nomes enviados pelo formulário utilizam o contrato final
     * `musicas_favoritas`, deixando de utilizar o nome conceptual antigo
     * `classificacoes`.
     *
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  Collection<int, MusicaFavoritaEdicao>  $escolhas  Escolhas
     *                                                           existentes.
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
     * @version 2.0.0
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
            $posicao = MusicaFavoritaEdicao::POSICAO_MINIMA;
            $posicao <= MusicaFavoritaEdicao::POSICAO_MAXIMA;
            $posicao++
        ) {
            $indice =
                $posicao
                - MusicaFavoritaEdicao::POSICAO_MINIMA;

            $escolha =
                $escolhasPorPosicao->get(
                    $posicao,
                );

            $campos[] = [
                'indice' => $indice,

                'posicao' => $posicao,

                'nomeCampo' => "musicas_favoritas[{$identificadorUtilizador}][{$indice}]",

                'chaveCampo' => "musicas_favoritas.{$identificadorUtilizador}.{$indice}",

                'identificadorCampo' => "musica-{$identificadorUtilizador}-{$posicao}",

                'identificadorErro' => "erro-musica-{$identificadorUtilizador}-{$posicao}",

                'valorPredefinido' => $escolha instanceof MusicaFavoritaEdicao
                    ? $escolha->musica
                    : '',
            ];
        }

        return $campos;
    }

    /**
     * Determina se todos os utilizadores concluíram as escolhas.
     *
     * Cada utilizador deve possuir exatamente as posições permitidas pelo
     * modelo. A verificação das posições protege a apresentação mesmo perante
     * dados importados ou alterados fora dos fluxos normais da aplicação.
     *
     * Uma edição sem utilizadores selecionáveis não é considerada bloqueada.
     *
     * @param  array<int, array{
     *     utilizador: Utilizador,
     *     escolhas: Collection<int, MusicaFavoritaEdicao>,
     *     campos: array<int, array<string, int|string>>
     * }>  $grupos  Grupos preparados.
     * @return bool Verdadeiro quando todas as escolhas estão concluídas.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function determinarSeEstaBloqueada(
        array $grupos,
    ): bool {
        if ($grupos === []) {
            return false;
        }

        $posicoesEsperadas = range(
            MusicaFavoritaEdicao::POSICAO_MINIMA,
            MusicaFavoritaEdicao::POSICAO_MAXIMA,
        );

        foreach ($grupos as $grupo) {
            $posicoesRegistadas =
                $grupo['escolhas']
                    ->pluck(
                        'posicao',
                    )
                    ->map(
                        static fn (
                            mixed $posicao,
                        ): int => (int) $posicao,
                    )
                    ->sort()
                    ->values()
                    ->all();

            if (
                $posicoesRegistadas
                !== $posicoesEsperadas
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtém o identificador persistido da edição.
     *
     * @param  Edicao  $edicao  Edição recebida.
     * @return int Identificador da edição.
     *
     * @throws LogicException Quando a edição não está persistida ou o
     *                        identificador não é um inteiro positivo.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorEdicao(
        Edicao $edicao,
    ): int {
        if (! $edicao->exists) {
            throw new LogicException(
                'A edição deve estar persistida antes de preparar os respetivos detalhes.',
            );
        }

        $identificador =
            $edicao->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (! is_string($identificador)) {
            throw new LogicException(
                'A edição deve possuir um identificador válido.',
            );
        }

        $identificadorNormalizado = trim(
            $identificador,
        );

        if (
            $identificadorNormalizado === ''
            || ! ctype_digit(
                $identificadorNormalizado,
            )
            || (int) $identificadorNormalizado < 1
        ) {
            throw new LogicException(
                'A edição deve possuir um identificador válido.',
            );
        }

        return (int) $identificadorNormalizado;
    }

    /**
     * Obtém o identificador persistido de um utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador consultado.
     * @return int Identificador do utilizador.
     *
     * @throws LogicException Quando o utilizador não está persistido ou o
     *                        identificador não é um inteiro positivo.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $utilizador,
    ): int {
        if (! $utilizador->exists) {
            throw new LogicException(
                'O utilizador deve estar persistido para preparar as músicas favoritas.',
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

        if (! is_string($identificador)) {
            throw new LogicException(
                'O utilizador deve possuir um identificador válido.',
            );
        }

        $identificadorNormalizado = trim(
            $identificador,
        );

        if (
            $identificadorNormalizado === ''
            || ! ctype_digit(
                $identificadorNormalizado,
            )
            || (int) $identificadorNormalizado < 1
        ) {
            throw new LogicException(
                'O utilizador deve possuir um identificador válido.',
            );
        }

        return (int) $identificadorNormalizado;
    }

    /**
     * Obtém a ligação validada da compilação.
     *
     * O atributo do modelo já normaliza e valida a ligação antes da
     * persistência. Este método apenas protege a forma do valor recebido de
     * uma instância hidratada.
     *
     * @param  Edicao  $edicao  Edição apresentada.
     * @return string|null Ligação da compilação ou nula.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterLigacaoCompilacao(
        Edicao $edicao,
    ): ?string {
        $ligacao =
            $edicao->ligacao_compilacao;

        if (
            ! is_string($ligacao)
            || $ligacao === ''
        ) {
            return null;
        }

        return $ligacao;
    }
}
