<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Gere a sincronização das músicas favoritas de uma edição.
 *
 * Apenas os utilizadores presentes no pedido são alterados. As escolhas
 * anteriores desses utilizadores são substituídas atomicamente.
 *
 * A edição e todos os utilizadores envolvidos são bloqueados durante a
 * transação, impedindo alterações concorrentes incompatíveis.
 *
 * @since 2.0.0
 */
final class ServicoMusicasFavoritasEdicao
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @since 2.0.0
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
     * @param  array<array-key, mixed>  $musicasFavoritas  Escolhas recebidas,
     *                                                     agrupadas por
     *                                                     utilizador.
     * @param  Utilizador  $registador  Utilizador responsável pelo registo.
     *
     * @throws InvalidArgumentException Quando a edição ou o registador não
     *                                  estão persistidos.
     * @throws ValidationException Quando algum dado recebido não é válido ou
     *                             uma entidade deixa de estar disponível.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     */
    public function sincronizar(
        Edicao $edicao,
        array $musicasFavoritas,
        Utilizador $registador,
    ): void {
        $identificadorEdicao =
            $this->obterIdentificadorEdicao(
                $edicao,
            );

        $identificadorRegistador =
            $this->obterIdentificadorRegistador(
                $registador,
            );

        $musicasFavoritasNormalizadas =
            $this->normalizarMusicasFavoritas(
                $musicasFavoritas,
            );

        DB::transaction(
            function () use (
                $identificadorEdicao,
                $identificadorRegistador,
                $musicasFavoritasNormalizadas,
            ): void {
                $this->bloquearEdicao(
                    $identificadorEdicao,
                );

                $identificadoresUtilizadores =
                    array_keys(
                        $musicasFavoritasNormalizadas,
                    );

                $this->bloquearEValidarUtilizadores(
                    $identificadorRegistador,
                    $identificadoresUtilizadores,
                );

                $this->substituirMusicasFavoritas(
                    $identificadorEdicao,
                    $musicasFavoritasNormalizadas,
                    $identificadorRegistador,
                );
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Normaliza as músicas favoritas recebidas.
     *
     * @param  array<array-key, mixed>  $musicasFavoritas  Escolhas originais.
     * @return array<int, array<int, string|null>> Escolhas normalizadas por
     *                                             utilizador e posição.
     *
     * @throws ValidationException Quando os dados não são válidos.
     *
     * @since 2.0.0
     */
    private function normalizarMusicasFavoritas(
        array $musicasFavoritas,
    ): array {
        if ($musicasFavoritas === []) {
            throw ValidationException::withMessages([
                'musicas_favoritas' => 'Deve ser enviada pelo menos uma escolha de músicas favoritas.',
            ]);
        }

        $normalizadas = [];

        foreach (
            $musicasFavoritas as $identificadorRecebido => $entradasRecebidas
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
                    'musicas_favoritas' => 'O mesmo utilizador foi indicado mais do que uma vez.',
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
     * Normaliza o identificador de um utilizador selecionado.
     *
     * @param  int|string  $identificador  Identificador recebido.
     * @return int Identificador normalizado.
     *
     * @throws ValidationException Quando o identificador não é um inteiro
     *                             positivo.
     *
     * @since 2.0.0
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
            $identificadorNormalizado = trim(
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
            'musicas_favoritas' => 'Foi indicado um utilizador inválido.',
        ]);
    }

    /**
     * Normaliza as posições recebidas para um utilizador.
     *
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  mixed  $entradasRecebidas  Posições recebidas.
     * @return array<int, string|null> Escolhas indexadas pela posição real.
     *
     * @throws ValidationException Quando as posições não são válidas.
     *
     * @since 2.0.0
     */
    private function normalizarMusicasUtilizador(
        int $identificadorUtilizador,
        mixed $entradasRecebidas,
    ): array {
        if (
            ! is_array($entradasRecebidas)
            || ! array_is_list($entradasRecebidas)
            || count($entradasRecebidas)
            !== MusicaFavoritaEdicao::NUMERO_POSICOES
        ) {
            throw ValidationException::withMessages([
                sprintf(
                    'musicas_favoritas.%d',
                    $identificadorUtilizador,
                ) => sprintf(
                    'Devem ser enviadas exatamente %d posições.',
                    MusicaFavoritaEdicao::NUMERO_POSICOES,
                ),
            ]);
        }

        $musicas = [];

        foreach (
            $entradasRecebidas as $indice => $entradaRecebida
        ) {
            $posicao =
                MusicaFavoritaEdicao::POSICAO_MINIMA
                + $indice;

            $musicas[$posicao] =
                $this->normalizarMusica(
                    $entradaRecebida,
                    $identificadorUtilizador,
                    $indice,
                );
        }

        $this->validarUnicidadeMusicas(
            $identificadorUtilizador,
            $musicas,
        );

        return $musicas;
    }

    /**
     * Confirma que a mesma música não ocupa duas posições.
     *
     * A comparação utiliza os pesos da própria collation
     * `utf8mb4_unicode_ci`, correspondendo ao contrato do índice único da
     * tabela em vez de aproximar esse comportamento através de transliteração.
     *
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  array<int, string|null>  $musicas  Músicas normalizadas.
     *
     * @throws ValidationException Quando existem músicas equivalentes segundo
     *                             a collation da base de dados.
     * @throws RuntimeException Quando não é possível obter uma chave de
     *                          comparação.
     *
     * @since 2.0.0
     */
    private function validarUnicidadeMusicas(
        int $identificadorUtilizador,
        array $musicas,
    ): void {
        $chavesUtilizadas = [];

        foreach (
            $this->obterChavesComparacaoMusicas(
                $musicas,
            ) as $posicao => $chave
        ) {
            if (
                array_key_exists(
                    $chave,
                    $chavesUtilizadas,
                )
            ) {
                $indice =
                    $posicao
                    - MusicaFavoritaEdicao::POSICAO_MINIMA;

                throw ValidationException::withMessages([
                    $this->obterChaveMusica(
                        $identificadorUtilizador,
                        $indice,
                    ) => 'A mesma música não pode ocupar duas posições.',
                ]);
            }

            $chavesUtilizadas[$chave] = true;
        }
    }

    /**
     * Obtém as chaves de comparação das músicas segundo a collation da tabela.
     *
     * Todas as músicas não nulas do utilizador são avaliadas numa única
     * consulta. `WEIGHT_STRING` devolve o peso utilizado pela MariaDB para
     * ordenar e comparar o valor segundo `utf8mb4_unicode_ci`.
     *
     * @param  array<int, string|null>  $musicas  Músicas normalizadas.
     * @return array<int, string> Chaves indexadas pela posição.
     *
     * @throws RuntimeException Quando a base de dados não devolve as chaves
     *                          esperadas.
     *
     * @since 2.0.0
     */
    private function obterChavesComparacaoMusicas(
        array $musicas,
    ): array {
        $expressoes = [];
        $parametros = [];
        $posicoes = [];

        foreach ($musicas as $posicao => $musica) {
            if ($musica === null) {
                continue;
            }

            $expressoes[] = sprintf(
                <<<'SQL'
                HEX(
                    WEIGHT_STRING(
                        CONVERT(? USING utf8mb4)
                        COLLATE utf8mb4_unicode_ci
                    )
                ) AS chave_%d
                SQL,
                $posicao,
            );

            $parametros[] = $musica;
            $posicoes[] = $posicao;
        }

        if ($expressoes === []) {
            return [];
        }

        $resultado = DB::selectOne(
            'SELECT '.implode(
                ', ',
                $expressoes,
            ),
            $parametros,
        );

        if ($resultado === null) {
            throw new RuntimeException(
                'Não foi possível comparar as músicas favoritas.',
            );
        }

        $chaves = [];

        foreach ($posicoes as $posicao) {
            $nomeAtributo =
                "chave_{$posicao}";

            $chave =
                $resultado->{$nomeAtributo}
                ?? null;

            if (! is_string($chave)) {
                throw new RuntimeException(
                    'Não foi possível obter a chave de comparação de uma música favorita.',
                );
            }

            $chaves[$posicao] = $chave;
        }

        return $chaves;
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
                $this->obterChaveMusica(
                    $identificadorUtilizador,
                    $indice,
                ) => 'A música indicada não é válida.',
            ]);
        }

        if (
            preg_match(
                '//u',
                $entrada,
            ) !== 1
        ) {
            throw ValidationException::withMessages([
                $this->obterChaveMusica(
                    $identificadorUtilizador,
                    $indice,
                ) => 'A música indicada contém texto inválido.',
            ]);
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $entrada,
            ) === 1
        ) {
            throw ValidationException::withMessages([
                $this->obterChaveMusica(
                    $identificadorUtilizador,
                    $indice,
                ) => 'A música contém caracteres inválidos.',
            ]);
        }

        $musica = preg_replace(
            '/\s+/u',
            ' ',
            $entrada,
        );

        if (! is_string($musica)) {
            throw ValidationException::withMessages([
                $this->obterChaveMusica(
                    $identificadorUtilizador,
                    $indice,
                ) => 'Não foi possível normalizar a música indicada.',
            ]);
        }

        $musica = trim(
            $musica,
        );

        if ($musica === '') {
            return null;
        }

        if (
            mb_strlen(
                $musica,
            ) > MusicaFavoritaEdicao::COMPRIMENTO_MAXIMO_MUSICA
        ) {
            throw ValidationException::withMessages([
                $this->obterChaveMusica(
                    $identificadorUtilizador,
                    $indice,
                ) => sprintf(
                    'A música não pode exceder %d caracteres.',
                    MusicaFavoritaEdicao::COMPRIMENTO_MAXIMO_MUSICA,
                ),
            ]);
        }

        return $musica;
    }

    /**
     * Obtém a chave de validação de uma música.
     *
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  int  $indice  Índice da música.
     * @return string Chave correspondente no pedido.
     *
     * @since 2.0.0
     */
    private function obterChaveMusica(
        int $identificadorUtilizador,
        int $indice,
    ): string {
        return sprintf(
            'musicas_favoritas.%d.%d',
            $identificadorUtilizador,
            $indice,
        );
    }

    /**
     * Obtém o identificador de uma edição persistida.
     *
     * @param  Edicao  $edicao  Edição recebida.
     * @return int Identificador da edição.
     *
     * @throws InvalidArgumentException Quando a edição não está persistida ou
     *                                  não possui um identificador válido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorEdicao(
        Edicao $edicao,
    ): int {
        if (! $edicao->exists) {
            throw new InvalidArgumentException(
                'A edição deve estar persistida para sincronizar as músicas favoritas.',
            );
        }

        $identificador = $edicao->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (! is_string($identificador)) {
            throw new InvalidArgumentException(
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
            throw new InvalidArgumentException(
                'A edição deve possuir um identificador válido.',
            );
        }

        return (int) $identificadorNormalizado;
    }

    /**
     * Obtém o identificador do utilizador responsável pelo registo.
     *
     * @param  Utilizador  $registador  Utilizador recebido.
     * @return int Identificador do utilizador.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorRegistador(
        Utilizador $registador,
    ): int {
        if (! $registador->exists) {
            throw new InvalidArgumentException(
                'O utilizador responsável pelo registo deve estar persistido.',
            );
        }

        $identificador = $registador->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (! is_string($identificador)) {
            throw new InvalidArgumentException(
                'O utilizador responsável pelo registo deve possuir um identificador válido.',
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
            throw new InvalidArgumentException(
                'O utilizador responsável pelo registo deve possuir um identificador válido.',
            );
        }

        return (int) $identificadorNormalizado;
    }

    /**
     * Bloqueia e confirma a existência da edição.
     *
     * @param  int  $identificadorEdicao  Identificador da edição.
     *
     * @throws ValidationException Quando a edição deixou de existir.
     *
     * @since 2.0.0
     */
    private function bloquearEdicao(
        int $identificadorEdicao,
    ): void {
        $edicao = Edicao::query()
            ->whereKey(
                $identificadorEdicao,
            )
            ->lockForUpdate()
            ->first();

        if ($edicao instanceof Edicao) {
            return;
        }

        throw ValidationException::withMessages([
            'edicao' => 'A edição indicada deixou de estar disponível.',
        ]);
    }

    /**
     * Bloqueia e valida todos os utilizadores envolvidos na operação.
     *
     * Os registos são bloqueados por ordem crescente de identificador para
     * reduzir a possibilidade de impasses entre sincronizações concorrentes.
     * O registador pode possuir qualquer papel, mas os utilizadores cujas
     * escolhas são alteradas têm de pertencer ao âmbito `selecionaveis`.
     *
     * @param  int  $identificadorRegistador  Utilizador responsável.
     * @param  list<int>  $identificadoresUtilizadores  Utilizadores alterados.
     *
     * @throws ValidationException Quando algum utilizador deixou de existir ou
     *                             não pode ser selecionado.
     *
     * @since 2.0.0
     */
    private function bloquearEValidarUtilizadores(
        int $identificadorRegistador,
        array $identificadoresUtilizadores,
    ): void {
        $identificadoresEnvolvidos = array_values(
            array_unique([
                $identificadorRegistador,
                ...$identificadoresUtilizadores,
            ]),
        );

        sort(
            $identificadoresEnvolvidos,
            SORT_NUMERIC,
        );

        $identificadoresExistentes =
            Utilizador::query()
                ->whereKey(
                    $identificadoresEnvolvidos,
                )
                ->orderBy(
                    'id',
                )
                ->lockForUpdate()
                ->pluck(
                    'id',
                )
                ->map(
                    static fn (
                        mixed $identificador,
                    ): int => (int) $identificador,
                )
                ->all();

        if (
            ! in_array(
                $identificadorRegistador,
                $identificadoresExistentes,
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'musicas_favoritas' => 'O utilizador responsável pelo registo deixou de estar disponível.',
            ]);
        }

        $identificadoresSelecionaveis =
            Utilizador::query()
                ->selecionaveis()
                ->whereKey(
                    $identificadoresUtilizadores,
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
            $identificadoresUtilizadores;

        sort(
            $identificadoresEsperados,
            SORT_NUMERIC,
        );

        if (
            $identificadoresSelecionaveis
            === $identificadoresEsperados
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'musicas_favoritas' => 'Foi indicado um utilizador inexistente ou indisponível.',
        ]);
    }

    /**
     * Substitui as músicas favoritas dos utilizadores indicados.
     *
     * @param  int  $identificadorEdicao  Identificador da edição.
     * @param  array<int, array<int, string|null>>  $musicasFavoritas  Escolhas
     *                                                                 normalizadas.
     * @param  int  $identificadorRegistador  Utilizador responsável pelo
     *                                        registo.
     *
     * @since 2.0.0
     */
    private function substituirMusicasFavoritas(
        int $identificadorEdicao,
        array $musicasFavoritas,
        int $identificadorRegistador,
    ): void {
        $identificadoresUtilizadores =
            array_keys(
                $musicasFavoritas,
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

        $momento = CarbonImmutable::now();
        $registos = [];

        foreach (
            $musicasFavoritas as $identificadorUtilizador => $musicas
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
