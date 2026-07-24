<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Gere a sincronização das músicas favoritas de uma edição.
 *
 * Apenas os utilizadores presentes no pedido são alterados. As escolhas
 * anteriores desses utilizadores são substituídas atomicamente.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
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
     * Comprimento máximo do nome de uma música.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_MUSICA = 255;

    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Sincroniza as músicas favoritas recebidas.
     *
     * Uma posição vazia remove a escolha anteriormente registada nessa
     * posição. Uma lista totalmente vazia para um utilizador remove todas as
     * respetivas escolhas.
     *
     * @param  Edicao  $edicao  Edição alterada.
     * @param  array<array-key, mixed>  $classificacoes  Escolhas recebidas,
     *                                                   agrupadas por
     *                                                   utilizador.
     * @param  int  $identificadorRegistador  Identificador do utilizador que
     *                                        efetuou o registo.
     *
     * @throws ValidationException Quando algum dado não é válido.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function sincronizar(
        Edicao $edicao,
        array $classificacoes,
        int $identificadorRegistador,
    ): void {
        $identificadorEdicao =
            $this->obterIdentificadorEdicao(
                $edicao,
            );

        $this->validarIdentificadorRegistador(
            $identificadorRegistador,
        );

        $classificacoesNormalizadas =
            $this->normalizarClassificacoes(
                $classificacoes,
            );

        DB::transaction(
            function () use (
                $identificadorEdicao,
                $classificacoesNormalizadas,
                $identificadorRegistador,
            ): void {
                $this->bloquearEdicao(
                    $identificadorEdicao,
                );

                $this->garantirRegistadorExistente(
                    $identificadorRegistador,
                );

                $identificadoresUtilizadores =
                    array_keys(
                        $classificacoesNormalizadas,
                    );

                $this->garantirUtilizadoresSelecionaveis(
                    $identificadoresUtilizadores,
                );

                $this->substituirClassificacoes(
                    $identificadorEdicao,
                    $classificacoesNormalizadas,
                    $identificadorRegistador,
                );
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Normaliza as classificações recebidas.
     *
     * @param  array<array-key, mixed>  $classificacoes  Classificações
     *                                                   originais.
     * @return array<int, array<int, string|null>> Classificações
     *                                             normalizadas.
     *
     * @throws ValidationException Quando os dados não são válidos.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function normalizarClassificacoes(
        array $classificacoes,
    ): array {
        if ($classificacoes === []) {
            throw ValidationException::withMessages([
                'classificacoes' => 'Deve ser enviada pelo menos uma classificação.',
            ]);
        }

        $normalizadas = [];

        foreach (
            $classificacoes as $identificadorRecebido => $entradasRecebidas
        ) {
            $identificadorUtilizador =
                $this->normalizarIdentificadorUtilizador(
                    $identificadorRecebido,
                );

            if (
                array_key_exists(
                    $identificadorUtilizador,
                    $normalizadas,
                )
            ) {
                throw ValidationException::withMessages([
                    'classificacoes' => 'O mesmo utilizador foi indicado mais do que uma vez.',
                ]);
            }

            $normalizadas[$identificadorUtilizador] =
                $this->normalizarMusicasUtilizador(
                    $identificadorUtilizador,
                    $entradasRecebidas,
                );
        }

        ksort(
            $normalizadas,
            SORT_NUMERIC,
        );

        return $normalizadas;
    }

    /**
     * Normaliza o identificador de um utilizador.
     *
     * @param  int|string  $identificador  Identificador recebido.
     * @return int Identificador normalizado.
     *
     * @throws ValidationException Quando o identificador não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificadorUtilizador(
        int|string $identificador,
    ): int {
        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (is_string($identificador)) {
            $identificadorNormalizado =
                trim(
                    $identificador,
                );

            if (
                $identificadorNormalizado !== ''
                && ctype_digit(
                    $identificadorNormalizado,
                )
                && (int) $identificadorNormalizado > 0
            ) {
                return (int) $identificadorNormalizado;
            }
        }

        throw ValidationException::withMessages([
            'classificacoes' => 'Foi indicado um utilizador inválido.',
        ]);
    }

    /**
     * Normaliza as posições recebidas para um utilizador.
     *
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  mixed  $entradasRecebidas  Posições recebidas.
     * @return array<int, string|null> Posições normalizadas.
     *
     * @throws ValidationException Quando as posições não são válidas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarMusicasUtilizador(
        int $identificadorUtilizador,
        mixed $entradasRecebidas,
    ): array {
        if (
            ! is_array($entradasRecebidas)
            || ! array_is_list($entradasRecebidas)
            || count($entradasRecebidas)
            !== self::NUMERO_POSICOES
        ) {
            throw ValidationException::withMessages([
                sprintf(
                    'classificacoes.%d',
                    $identificadorUtilizador,
                ) => sprintf(
                    'Devem ser enviadas exatamente %d posições.',
                    self::NUMERO_POSICOES,
                ),
            ]);
        }

        $musicas = [];
        $musicasUtilizadas = [];

        foreach (
            $entradasRecebidas as $indice => $entradaRecebida
        ) {
            $posicao =
                $indice + 1;

            $musica =
                $this->normalizarMusica(
                    $entradaRecebida,
                    $identificadorUtilizador,
                    $indice,
                );

            if ($musica === null) {
                $musicas[$posicao] = null;

                continue;
            }

            $chaveMusica =
                mb_strtolower(
                    $musica,
                );

            if (
                isset(
                    $musicasUtilizadas[$chaveMusica],
                )
            ) {
                throw ValidationException::withMessages([
                    sprintf(
                        'classificacoes.%d.%d',
                        $identificadorUtilizador,
                        $indice,
                    ) => 'A mesma música não pode ocupar duas posições.',
                ]);
            }

            $musicasUtilizadas[$chaveMusica] =
                true;

            $musicas[$posicao] =
                $musica;
        }

        return $musicas;
    }

    /**
     * Normaliza o nome de uma música.
     *
     * @param  mixed  $entrada  Valor recebido.
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  int  $indice  Índice recebido no pedido.
     * @return string|null Nome normalizado ou nulo.
     *
     * @throws ValidationException Quando o valor não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarMusica(
        mixed $entrada,
        int $identificadorUtilizador,
        int $indice,
    ): ?string {
        if ($entrada === null) {
            return null;
        }

        if (! is_string($entrada)) {
            throw ValidationException::withMessages([
                sprintf(
                    'classificacoes.%d.%d',
                    $identificadorUtilizador,
                    $indice,
                ) => 'A música indicada não é válida.',
            ]);
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $entrada,
            ) === 1
        ) {
            throw ValidationException::withMessages([
                sprintf(
                    'classificacoes.%d.%d',
                    $identificadorUtilizador,
                    $indice,
                ) => 'A música contém caracteres inválidos.',
            ]);
        }

        $musica =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $entrada,
                ),
            );

        if (! is_string($musica)) {
            throw ValidationException::withMessages([
                sprintf(
                    'classificacoes.%d.%d',
                    $identificadorUtilizador,
                    $indice,
                ) => 'Não foi possível normalizar a música indicada.',
            ]);
        }

        if ($musica === '') {
            return null;
        }

        if (
            mb_strlen(
                $musica,
            ) > self::COMPRIMENTO_MAXIMO_MUSICA
        ) {
            throw ValidationException::withMessages([
                sprintf(
                    'classificacoes.%d.%d',
                    $identificadorUtilizador,
                    $indice,
                ) => sprintf(
                    'A música não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_MUSICA,
                ),
            ]);
        }

        return $musica;
    }

    /**
     * Obtém o identificador de uma edição persistida.
     *
     * @param  Edicao  $edicao  Edição recebida.
     * @return int Identificador da edição.
     *
     * @throws ValidationException Quando a edição não está persistida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorEdicao(
        Edicao $edicao,
    ): int {
        $identificador =
            $edicao->getKey();

        if (
            ! $edicao->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw ValidationException::withMessages([
                'edicao_id' => 'A edição indicada não é válida.',
            ]);
        }

        return (int) $identificador;
    }

    /**
     * Valida o identificador do utilizador registador.
     *
     * @param  int  $identificador  Identificador recebido.
     *
     * @throws ValidationException Quando o identificador não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarIdentificadorRegistador(
        int $identificador,
    ): void {
        if ($identificador > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'classificacoes' => 'Não foi possível identificar o utilizador autenticado.',
        ]);
    }

    /**
     * Bloqueia e confirma a existência da edição.
     *
     * @param  int  $identificadorEdicao  Identificador da edição.
     *
     * @throws ValidationException Quando a edição deixou de existir.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function bloquearEdicao(
        int $identificadorEdicao,
    ): void {
        $edicao =
            Edicao::query()
                ->whereKey(
                    $identificadorEdicao,
                )
                ->lockForUpdate()
                ->first();

        if ($edicao instanceof Edicao) {
            return;
        }

        throw ValidationException::withMessages([
            'edicao_id' => 'A edição indicada deixou de estar disponível.',
        ]);
    }

    /**
     * Confirma a existência do utilizador que efetuou o registo.
     *
     * O registador pode ser um administrador ou superadministrador, pelo que
     * não é aplicado o âmbito `selecionaveis`.
     *
     * @param  int  $identificador  Identificador do utilizador.
     *
     * @throws ValidationException Quando o utilizador não existe.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirRegistadorExistente(
        int $identificador,
    ): void {
        if (
            Utilizador::query()
                ->whereKey(
                    $identificador,
                )
                ->exists()
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'classificacoes' => 'O utilizador responsável pelo registo não existe.',
        ]);
    }

    /**
     * Confirma que todos os utilizadores podem ser selecionados.
     *
     * @param  list<int>  $identificadores  Identificadores recebidos.
     *
     * @throws ValidationException Quando algum utilizador não é selecionável.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function garantirUtilizadoresSelecionaveis(
        array $identificadores,
    ): void {
        $identificadoresExistentes =
            Utilizador::query()
                ->selecionaveis()
                ->whereKey(
                    $identificadores,
                )
                ->pluck(
                    'id',
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
            SORT_NUMERIC,
        );

        if (
            $identificadoresExistentes
            === $identificadoresEsperados
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'classificacoes' => 'Foi indicado um utilizador inexistente ou indisponível.',
        ]);
    }

    /**
     * Substitui as classificações dos utilizadores indicados.
     *
     * @param  int  $identificadorEdicao  Identificador da edição.
     * @param  array<int, array<int, string|null>>  $classificacoes
     *                                                               Classificações
     *                                                               normalizadas.
     * @param  int  $identificadorRegistador  Utilizador responsável pelo
     *                                        registo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function substituirClassificacoes(
        int $identificadorEdicao,
        array $classificacoes,
        int $identificadorRegistador,
    ): void {
        $identificadoresUtilizadores =
            array_keys(
                $classificacoes,
            );

        MusicaFavoritaEdicao::query()
            ->where(
                'edicao_id',
                $identificadorEdicao,
            )
            ->whereIn(
                'utilizador_id',
                $identificadoresUtilizadores,
            )
            ->delete();

        $momento =
            CarbonImmutable::now();

        $registos = [];

        foreach (
            $classificacoes as $identificadorUtilizador => $musicas
        ) {
            foreach ($musicas as $posicao => $musica) {
                if ($musica === null) {
                    continue;
                }

                $registos[] = [
                    'edicao_id' => $identificadorEdicao,

                    'utilizador_id' => $identificadorUtilizador,

                    'posicao' => $posicao,

                    'musica' => $musica,

                    'registado_por_id' => $identificadorRegistador,

                    'created_at' => $momento,

                    'updated_at' => $momento,
                ];
            }
        }

        if ($registos === []) {
            return;
        }

        MusicaFavoritaEdicao::query()
            ->insert(
                $registos,
            );
    }
}
