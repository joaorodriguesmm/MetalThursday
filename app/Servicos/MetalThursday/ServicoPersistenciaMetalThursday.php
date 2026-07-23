<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Gere a criação e a atualização transacional de MetalThursdays.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoPersistenciaMetalThursday
{
    /**
     * Cria uma MetalThursday e as respetivas secções.
     *
     * @param  array<string, mixed>  $dados  Dados validados.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function criar(
        array $dados,
    ): MetalThursday {
        $dadosNormalizados =
            $this->normalizarDados(
                $dados,
            );

        return DB::transaction(
            function () use (
                $dadosNormalizados,
            ): MetalThursday {
                $tiposSecao =
                    $this->obterTiposSecao(
                        $dadosNormalizados['seccoes'],
                    );

                $metalThursday =
                    MetalThursday::query()->create([
                        'edicao_id' => $dadosNormalizados['edicao_id'],

                        'data' => $dadosNormalizados['data'],

                        'nome' => $dadosNormalizados['nome'],

                        'autor_id' => $dadosNormalizados['autor_id'],

                        'proximo_nomeado_id' => $dadosNormalizados['proximo_nomeado_id'],
                    ]);

                foreach (
                    $dadosNormalizados['seccoes'] as $indice => $dadosSeccao
                ) {
                    $tipoSecao = $tiposSecao->get(
                        $dadosSeccao['tipo_secao_id'],
                    );

                    if (! $tipoSecao instanceof TipoSeccao) {
                        throw new InvalidArgumentException(
                            'Foi indicado um tipo de secção inexistente.',
                        );
                    }

                    $metalThursday
                        ->seccoes()
                        ->create(
                            $this->construirDadosSeccao(
                                $dadosSeccao,
                                $tipoSecao,
                                $indice + 1,
                            ),
                        );
                }

                return $metalThursday->refresh();
            },
        );
    }

    /**
     * Atualiza uma MetalThursday e sincroniza as respetivas secções.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday atualizada.
     * @param  array<string, mixed>  $dados  Dados validados.
     * @return MetalThursday MetalThursday atualizada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function atualizar(
        MetalThursday $metalThursday,
        array $dados,
    ): MetalThursday {
        $dadosNormalizados =
            $this->normalizarDados(
                $dados,
            );

        return DB::transaction(
            function () use (
                $metalThursday,
                $dadosNormalizados,
            ): MetalThursday {
                $metalThursdayBloqueada =
                    MetalThursday::query()
                        ->whereKey(
                            $metalThursday->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $seccoesExistentes =
                    $metalThursdayBloqueada
                        ->seccoes()
                        ->lockForUpdate()
                        ->get()
                        ->keyBy(
                            static fn (
                                SeccaoMetalThursday $seccao,
                            ): int => (int) $seccao->getKey(),
                        );

                $this->garantirIdentificadoresSecoesValidos(
                    $dadosNormalizados['seccoes'],
                    $seccoesExistentes,
                );

                $tiposSecao =
                    $this->obterTiposSecao(
                        $dadosNormalizados['seccoes'],
                    );

                $metalThursdayBloqueada
                    ->updateOrFail([
                        'edicao_id' => $dadosNormalizados['edicao_id'],

                        'data' => $dadosNormalizados['data'],

                        'nome' => $dadosNormalizados['nome'],

                        'autor_id' => $dadosNormalizados['autor_id'],

                        'proximo_nomeado_id' => $dadosNormalizados['proximo_nomeado_id'],
                    ]);

                $identificadoresRecebidos =
                    collect(
                        $dadosNormalizados['seccoes'],
                    )
                        ->pluck('id')
                        ->filter(
                            static fn (
                                mixed $identificador,
                            ): bool => is_int($identificador),
                        )
                        ->values();

                $identificadoresRemover =
                    $seccoesExistentes
                        ->keys()
                        ->diff(
                            $identificadoresRecebidos,
                        )
                        ->values()
                        ->all();

                if ($identificadoresRemover !== []) {
                    $metalThursdayBloqueada
                        ->seccoes()
                        ->whereKey(
                            $identificadoresRemover,
                        )
                        ->delete();
                }

                foreach (
                    $dadosNormalizados['seccoes'] as $indice => $dadosSeccao
                ) {
                    $tipoSecao = $tiposSecao->get(
                        $dadosSeccao['tipo_secao_id'],
                    );

                    if (! $tipoSecao instanceof TipoSeccao) {
                        throw new InvalidArgumentException(
                            'Foi indicado um tipo de secção inexistente.',
                        );
                    }

                    $atributos =
                        $this->construirDadosSeccao(
                            $dadosSeccao,
                            $tipoSecao,
                            $indice + 1,
                        );

                    $identificadorSeccao =
                        $dadosSeccao['id'];

                    if ($identificadorSeccao === null) {
                        $metalThursdayBloqueada
                            ->seccoes()
                            ->create(
                                $atributos,
                            );

                        continue;
                    }

                    $seccao = $seccoesExistentes->get(
                        $identificadorSeccao,
                    );

                    if (
                        ! $seccao
                            instanceof SeccaoMetalThursday
                    ) {
                        throw new InvalidArgumentException(
                            'Foi indicada uma secção inválida.',
                        );
                    }

                    $seccao->updateOrFail(
                        $atributos,
                    );
                }

                return $metalThursdayBloqueada
                    ->refresh();
            },
        );
    }

    /**
     * Normaliza os dados principais e as secções.
     *
     * Os nomes antigos continuam temporariamente suportados até à revisão dos
     * pedidos e das vistas.
     *
     * @param  array<string, mixed>  $dados  Dados recebidos.
     * @return array{
     *     edicao_id: int,
     *     data: string,
     *     nome: string|null,
     *     autor_id: int|null,
     *     proximo_nomeado_id: int|null,
     *     seccoes: array<int, array{
     *         id: int|null,
     *         tipo_secao_id: int,
     *         banda_id: int|null,
     *         titulo: string|null,
     *         ligacao: string|null,
     *         tipo_incorporacao: string|null,
     *         ano: int|null,
     *         descricao: string|null
     *     }>
     * } Dados normalizados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarDados(
        array $dados,
    ): array {
        $seccoesRecebidas =
            $this->obterValor(
                $dados,
                'seccoes',
                'sections',
                [],
            );

        if (! is_array($seccoesRecebidas)) {
            throw new InvalidArgumentException(
                'As secções devem ser enviadas numa lista.',
            );
        }

        $seccoes = [];

        foreach (
            array_values($seccoesRecebidas) as $dadosSeccao
        ) {
            if (! is_array($dadosSeccao)) {
                throw new InvalidArgumentException(
                    'Foi recebida uma secção inválida.',
                );
            }

            $seccoes[] = [
                'id' => $this->normalizarIdentificadorOpcional(
                    $dadosSeccao['id']
                        ?? null,
                ),

                'tipo_secao_id' => $this->normalizarIdentificadorObrigatorio(
                    $this->obterValor(
                        $dadosSeccao,
                        'tipo_secao_id',
                        'type_id',
                    ),
                    'tipo_secao_id',
                ),

                'banda_id' => $this->normalizarIdentificadorOpcional(
                    $this->obterValor(
                        $dadosSeccao,
                        'banda_id',
                        'band_id',
                    ),
                ),

                'titulo' => $this->normalizarTextoOpcional(
                    $this->obterValor(
                        $dadosSeccao,
                        'titulo',
                        'title',
                    ),
                ),

                'ligacao' => $this->normalizarTextoOpcional(
                    $this->obterValor(
                        $dadosSeccao,
                        'ligacao',
                        'link',
                    ),
                ),

                'tipo_incorporacao' => $this->normalizarTextoOpcional(
                    $this->obterValor(
                        $dadosSeccao,
                        'tipo_incorporacao',
                        'embed_type',
                    ),
                ),

                'ano' => $this->normalizarAno(
                    $this->obterValor(
                        $dadosSeccao,
                        'ano',
                        'year',
                    ),
                ),

                'descricao' => $this->normalizarTextoOpcional(
                    $this->obterValor(
                        $dadosSeccao,
                        'descricao',
                        'description',
                    ),
                ),
            ];
        }

        return [
            'edicao_id' => $this->normalizarIdentificadorObrigatorio(
                $this->obterValor(
                    $dados,
                    'edicao_id',
                    'edition_id',
                ),
                'edicao_id',
            ),

            'data' => $this->normalizarTextoObrigatorio(
                $this->obterValor(
                    $dados,
                    'data',
                    'date',
                ),
                'data',
            ),

            'nome' => $this->normalizarTextoOpcional(
                $this->obterValor(
                    $dados,
                    'nome',
                    'name',
                ),
            ),

            'autor_id' => $this->normalizarIdentificadorOpcional(
                $this->obterValor(
                    $dados,
                    'autor_id',
                    'author_id',
                ),
            ),

            'proximo_nomeado_id' => $this->normalizarIdentificadorOpcional(
                $this->obterValor(
                    $dados,
                    'proximo_nomeado_id',
                    'next_nominee_id',
                ),
            ),

            'seccoes' => $seccoes,
        ];
    }

    /**
     * Obtém os tipos utilizados pelas secções.
     *
     * @param  array<int, array<string, mixed>>  $seccoes  Secções normalizadas.
     * @return Collection<int, TipoSeccao> Tipos encontrados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterTiposSecao(
        array $seccoes,
    ): Collection {
        $identificadores = collect($seccoes)
            ->pluck('tipo_secao_id')
            ->unique()
            ->values()
            ->all();

        $tipos = TipoSeccao::query()
            ->whereKey(
                $identificadores,
            )
            ->get()
            ->keyBy(
                static fn (
                    TipoSeccao $tipoSecao,
                ): int => (int) $tipoSecao->getKey(),
            );

        if (
            $tipos->count()
            !== count($identificadores)
        ) {
            throw new InvalidArgumentException(
                'Foi indicado um tipo de secção inexistente.',
            );
        }

        return $tipos;
    }

    /**
     * Constrói os atributos persistidos numa secção.
     *
     * @param  array<string, mixed>  $dadosSeccao  Dados normalizados.
     * @param  TipoSeccao  $tipoSecao  Tipo da secção.
     * @param  int  $ordem  Posição da secção.
     * @return array<string, mixed> Atributos persistíveis.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function construirDadosSeccao(
        array $dadosSeccao,
        TipoSeccao $tipoSecao,
        int $ordem,
    ): array {
        if (! $tipoSecao->tem_detalhes) {
            return [
                'tipo_secao_id' => $tipoSecao->getKey(),

                'ordem' => $ordem,

                'banda_id' => null,

                'titulo' => null,

                'ligacao' => null,

                'tipo_incorporacao' => null,

                'ano' => null,

                'descricao' => $dadosSeccao['descricao'],
            ];
        }

        $tipoIncorporacao =
            TipoIncorporacao::tentarCriar(
                $dadosSeccao['tipo_incorporacao'],
            )
            ?? TipoIncorporacao::Ligacao;

        return [
            'tipo_secao_id' => $tipoSecao->getKey(),

            'ordem' => $ordem,

            'banda_id' => $dadosSeccao['banda_id'],

            'titulo' => $dadosSeccao['titulo'],

            'ligacao' => $dadosSeccao['ligacao'],

            'tipo_incorporacao' => $tipoIncorporacao->value,

            'ano' => $dadosSeccao['ano'],

            'descricao' => $dadosSeccao['descricao'],
        ];
    }

    /**
     * Confirma que as secções recebidas pertencem à MetalThursday.
     *
     * @param  array<int, array<string, mixed>>  $seccoes  Secções recebidas.
     * @param  Collection<int, SeccaoMetalThursday>  $existentes  Secções atuais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirIdentificadoresSecoesValidos(
        array $seccoes,
        Collection $existentes,
    ): void {
        $identificadores = collect($seccoes)
            ->pluck('id')
            ->filter(
                static fn (
                    mixed $identificador,
                ): bool => is_int($identificador),
            )
            ->values();

        if (
            $identificadores->unique()->count()
            !== $identificadores->count()
        ) {
            throw new InvalidArgumentException(
                'Uma secção foi enviada mais do que uma vez.',
            );
        }

        foreach ($identificadores as $identificador) {
            if (! $existentes->has($identificador)) {
                throw new InvalidArgumentException(
                    'Foi indicada uma secção que não pertence à MetalThursday.',
                );
            }
        }
    }

    /**
     * Obtém um valor com suporte temporário ao nome antigo.
     *
     * @param  array<string, mixed>  $dados  Dados disponíveis.
     * @param  string  $nomeAtual  Nome atual.
     * @param  string  $nomeAntigo  Nome antigo.
     * @param  mixed  $predefinido  Valor predefinido.
     * @return mixed Valor encontrado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterValor(
        array $dados,
        string $nomeAtual,
        string $nomeAntigo,
        mixed $predefinido = null,
    ): mixed {
        if (array_key_exists($nomeAtual, $dados)) {
            return $dados[$nomeAtual];
        }

        return $dados[$nomeAntigo]
            ?? $predefinido;
    }

    /**
     * Normaliza um identificador obrigatório.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @return int Identificador normalizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificadorObrigatorio(
        mixed $valor,
        string $campo,
    ): int {
        $identificador =
            $this->normalizarIdentificadorOpcional(
                $valor,
            );

        if ($identificador === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s não contém um identificador válido.',
                    $campo,
                ),
            );
        }

        return $identificador;
    }

    /**
     * Normaliza um identificador opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return int|null Identificador ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificadorOpcional(
        mixed $valor,
    ): ?int {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        $identificador = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        return $identificador === false
            ? null
            : (int) $identificador;
    }

    /**
     * Normaliza um texto obrigatório.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @return string Texto normalizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarTextoObrigatorio(
        mixed $valor,
        string $campo,
    ): string {
        $texto = $this->normalizarTextoOpcional(
            $valor,
        );

        if ($texto === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s não pode estar vazio.',
                    $campo,
                ),
            );
        }

        return $texto;
    }

    /**
     * Normaliza um texto opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Texto normalizado ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarTextoOpcional(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $texto = trim($valor);

        return $texto === ''
            ? null
            : $texto;
    }

    /**
     * Normaliza o ano de uma secção.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return int|null Ano ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarAno(
        mixed $valor,
    ): ?int {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        $ano = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1000,
                    'max_range' => 9999,
                ],
            ],
        );

        return $ano === false
            ? null
            : (int) $ano;
    }
}
