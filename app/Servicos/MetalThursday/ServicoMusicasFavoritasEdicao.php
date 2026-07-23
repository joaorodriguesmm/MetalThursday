<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gere a sincronização das músicas favoritas de uma edição.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoMusicasFavoritasEdicao
{
    /**
     * Número de posições disponíveis por utilizador.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const NUMERO_POSICOES = 3;

    /**
     * Sincroniza as músicas favoritas recebidas.
     *
     * Apenas os utilizadores presentes no pedido são alterados. As escolhas
     * anteriores desses utilizadores são substituídas atomicamente.
     *
     * @param  Edicao  $edicao  Edição alterada.
     * @param  array<mixed>  $classificacoes  Escolhas recebidas.
     * @param  int  $identificadorRegistador  Utilizador que efetuou o registo.
     *
     * @throws ValidationException Quando os dados não são válidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function sincronizar(
        Edicao $edicao,
        array $classificacoes,
        int $identificadorRegistador,
    ): void {
        if ($identificadorRegistador < 1) {
            throw ValidationException::withMessages([
                'rankings' => 'Não foi possível identificar o utilizador autenticado.',
            ]);
        }

        $classificacoesNormalizadas =
            $this->normalizarClassificacoes(
                $classificacoes,
            );

        $this->garantirUtilizadoresSelecionaveis(
            array_keys(
                $classificacoesNormalizadas,
            ),
        );

        DB::transaction(
            static function () use (
                $edicao,
                $classificacoesNormalizadas,
                $identificadorRegistador,
            ): void {
                Edicao::query()
                    ->whereKey(
                        $edicao->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                foreach (
                    $classificacoesNormalizadas as $identificadorUtilizador => $musicas
                ) {
                    MusicaFavoritaEdicao::query()
                        ->where(
                            'edicao_id',
                            $edicao->getKey(),
                        )
                        ->where(
                            'utilizador_id',
                            $identificadorUtilizador,
                        )
                        ->delete();

                    $registos = [];
                    $agora = now();

                    foreach ($musicas as $posicao => $musica) {
                        if ($musica === null) {
                            continue;
                        }

                        $registos[] = [
                            'edicao_id' => $edicao->getKey(),

                            'utilizador_id' => $identificadorUtilizador,

                            'posicao' => $posicao,

                            'musica' => $musica,

                            'registado_por_id' => $identificadorRegistador,

                            'created_at' => $agora,

                            'updated_at' => $agora,
                        ];
                    }

                    if ($registos !== []) {
                        MusicaFavoritaEdicao::query()
                            ->insert(
                                $registos,
                            );
                    }
                }
            },
        );
    }

    /**
     * Normaliza as classificações recebidas.
     *
     * @param  array<mixed>  $classificacoes  Classificações originais.
     * @return array<int, array<int, string|null>> Classificações normalizadas.
     *
     * @throws ValidationException Quando os dados não são válidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarClassificacoes(
        array $classificacoes,
    ): array {
        if ($classificacoes === []) {
            throw ValidationException::withMessages([
                'rankings' => 'Deve ser enviada pelo menos uma classificação.',
            ]);
        }

        $normalizadas = [];

        foreach (
            $classificacoes as $identificadorRecebido => $entradasRecebidas
        ) {
            $identificadorUtilizador = filter_var(
                $identificadorRecebido,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                    ],
                ],
            );

            if ($identificadorUtilizador === false) {
                throw ValidationException::withMessages([
                    'rankings' => 'Foi indicado um utilizador inválido.',
                ]);
            }

            if (
                ! is_array($entradasRecebidas)
                || count($entradasRecebidas)
                !== self::NUMERO_POSICOES
            ) {
                throw ValidationException::withMessages([
                    sprintf(
                        'rankings.%d',
                        $identificadorUtilizador,
                    ) => 'Devem ser enviadas exatamente três posições.',
                ]);
            }

            $musicas = [];
            $musicasUtilizadas = [];

            foreach (
                array_values($entradasRecebidas) as $indice => $entradaRecebida
            ) {
                $posicao = $indice + 1;

                if (
                    $entradaRecebida === null
                    || $entradaRecebida === ''
                ) {
                    $musicas[$posicao] = null;

                    continue;
                }

                if (! is_string($entradaRecebida)) {
                    throw ValidationException::withMessages([
                        sprintf(
                            'rankings.%d.%d',
                            $identificadorUtilizador,
                            $indice,
                        ) => 'A música indicada não é válida.',
                    ]);
                }

                $musica = trim(
                    $entradaRecebida,
                );

                if ($musica === '') {
                    $musicas[$posicao] = null;

                    continue;
                }

                if (mb_strlen($musica) > 255) {
                    throw ValidationException::withMessages([
                        sprintf(
                            'rankings.%d.%d',
                            $identificadorUtilizador,
                            $indice,
                        ) => 'A música não pode exceder 255 caracteres.',
                    ]);
                }

                $chaveMusica = mb_strtolower(
                    $musica,
                );

                if (isset($musicasUtilizadas[$chaveMusica])) {
                    throw ValidationException::withMessages([
                        sprintf(
                            'rankings.%d.%d',
                            $identificadorUtilizador,
                            $indice,
                        ) => 'A mesma música não pode ocupar duas posições.',
                    ]);
                }

                $musicasUtilizadas[$chaveMusica] = true;
                $musicas[$posicao] = $musica;
            }

            $normalizadas[(int) $identificadorUtilizador] = $musicas;
        }

        return $normalizadas;
    }

    /**
     * Confirma que todos os utilizadores podem ser selecionados.
     *
     * @param  array<int, int>  $identificadores  Identificadores recebidos.
     *
     * @throws ValidationException Quando algum utilizador não é selecionável.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirUtilizadoresSelecionaveis(
        array $identificadores,
    ): void {
        $identificadoresExistentes = Utilizador::query()
            ->selecionaveis()
            ->whereKey(
                $identificadores,
            )
            ->pluck(
                'utilizadores.id',
            )
            ->map(
                static fn (
                    mixed $identificador,
                ): int => (int) $identificador,
            )
            ->sort()
            ->values()
            ->all();

        $identificadoresEsperados =
            $identificadores;

        sort(
            $identificadoresEsperados,
        );

        if (
            $identificadoresExistentes
            === $identificadoresEsperados
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'rankings' => 'Foi indicado um utilizador inexistente ou indisponível.',
        ]);
    }
}
