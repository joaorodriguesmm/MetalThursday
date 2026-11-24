<?php

declare(strict_types=1);

namespace App\Servicos\Musica;

use App\Enumeracoes\TipoLancamento;
use App\Models\Musica\Artista;
use App\Models\Musica\FaixaLancamento;
use App\Models\Musica\Lancamento;
use App\Models\Musica\Musica;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Importa lançamentos musicais obtidos através do Discogs.
 *
 * A integração Discogs é responsável pela consulta e normalização dos dados.
 * Este serviço decide quais desses dados pertencem ao catálogo persistido.
 *
 * @phpstan-import-type DadosLancamentoDiscogs from ServicoDiscogs
 *
 * @since 2.0.0
 */
final class ServicoImportacaoLancamento
{
    /**
     * Prefixo utilizado para serializar a persistência da mesma Release.
     *
     * @since 2.0.0
     */
    private const PREFIXO_BLOQUEIO_IMPORTACAO =
        'integracoes:discogs:importacao-lancamento:';

    /**
     * Duração máxima do bloqueio distribuído.
     *
     * O bloqueio cobre apenas a persistência local. A consulta ao Discogs
     * ocorre antes da sua aquisição.
     *
     * @since 2.0.0
     */
    private const DURACAO_BLOQUEIO_SEGUNDOS =
        30;

    /**
     * Tempo máximo de espera pela importação concorrente da mesma Release.
     *
     * @since 2.0.0
     */
    private const ESPERA_BLOQUEIO_SEGUNDOS =
        15;

    /**
     * Número máximo de tentativas perante conflitos transitórios da base de
     * dados.
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO =
        3;

    /**
     * Cria o serviço.
     *
     * @param  ServicoDiscogs  $discogs  Integração com o Discogs.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoDiscogs $discogs,
        private readonly ServicoSugestoesAlbum $sugestoesAlbum,
    ) {}

    /**
     * Importa a edição e obtém ligações complementares independentes do
     * catálogo. Uma falha ao obter as ligações nunca impede a reutilização de
     * um lançamento que já esteja completo e disponível localmente.
     *
     * @return array{lancamento: Lancamento, sugestoes: array{
     *     descricao: string|null,
     *     descricao_fonte: string|null,
     *     notas_edicao: string|null,
     *     ligacoes: list<array{etiqueta: string, url: string}>
     * }}
     *
     * @since 2.0.0
     */
    public function importarComSugestoes(
        int $identificadorDiscogs,
        ?Closure $validarAutorizacao = null,
        ?Artista $artista = null,
    ): array {
        // Quando o lançamento já está completo, a reutilização e a autorização
        // transacional têm de ocorrer antes de qualquer pedido externo opcional.
        $existente = Lancamento::withTrashed()
            ->where('discogs_release_id', $identificadorDiscogs)
            ->first();

        $lancamento = null;
        if (
            $existente instanceof Lancamento
            && ! $this->necessitaMetadadosExternos($existente)
        ) {
            $lancamento = $this->importar(
                $identificadorDiscogs,
                $validarAutorizacao,
            );
        } elseif ($validarAutorizacao !== null) {
            // A consulta complementar não deve iniciar-se se a autorização
            // tiver sido entretanto revogada. A persistência volta a validar.
            DB::transaction(static function () use ($validarAutorizacao): bool {
                $validarAutorizacao();

                return true;
            });
        }

        $dados = null;

        try {
            $dados = $this->discogs->obterLancamento($identificadorDiscogs);
        } catch (RuntimeException) {
            // A edição pode já existir no catálogo local; as sugestões são opcionais.
        }

        if (
            $dados !== null
            && $dados['discogs_release_id'] !== $identificadorDiscogs
        ) {
            throw new RuntimeException('O Discogs devolveu uma edição diferente da solicitada.');
        }

        if ($lancamento === null) {
            $lancamento = $this->importar(
                $identificadorDiscogs,
                $validarAutorizacao,
                $dados,
            );
        }

        $sugestoesExternas = [
            'descricao' => null,
            'descricao_fonte' => null,
            'ligacoes' => [],
        ];

        if ($artista instanceof Artista && $dados !== null) {
            try {
                $sugestoesExternas = $this->sugestoesAlbum->obter($artista, $dados);
            } catch (Throwable) {
                // O enriquecimento nunca invalida uma importação Discogs concluída.
            }
        }

        $ligacoesDiscogs = $dados['ligacoes_sugeridas'] ?? [
            [
                'etiqueta' => 'Discogs — edição',
                'url' => 'https://www.discogs.com/release/'.$identificadorDiscogs,
            ],
        ];

        $ligacoes = [];
        $vistos = [];
        foreach (array_merge($sugestoesExternas['ligacoes'], $ligacoesDiscogs) as $ligacao) {
            $url = $ligacao['url'];
            if (isset($vistos[$url])) {
                continue;
            }
            $vistos[$url] = true;
            $ligacoes[] = $ligacao;
        }

        return [
            'lancamento' => $lancamento,
            'sugestoes' => [
                'descricao' => $sugestoesExternas['descricao'],
                'descricao_fonte' => $sugestoesExternas['descricao_fonte'],
                'notas_edicao' => $dados['notas_edicao'] ?? null,
                'ligacoes' => $ligacoes,
            ],
        ];
    }

    /**
     * Importa uma edição concreta do Discogs para o catálogo local.
     *
     * Uma edição já importada é reutilizada. Quando ainda não possui tipo ou
     * ano original, esses metadados são completados a partir do Discogs sem
     * substituir valores que tenham sido corrigidos manualmente.
     * Na primeira importação são persistidos o lançamento, a sugestão de tipo,
     * o ano original fiável e as respetivas ocorrências na tracklist.
     *
     * Os artistas devolvidos pelo Discogs são deliberadamente ignorados. A
     * associação de artistas ao MetalThursday continua a ser uma decisão
     * manual do utilizador e não altera o catálogo automaticamente.
     *
     * @param  int  $identificadorDiscogs  Identificador da Release Discogs.
     * @param  (Closure(): void)|null  $validarAutorizacao
     * @param  DadosLancamentoDiscogs|null  $dadosPrecarregados  Ficha já obtida para sugestões.
     * @return Lancamento Lançamento persistido.
     *
     * @since 2.0.0
     */
    public function importar(
        int $identificadorDiscogs,
        ?Closure $validarAutorizacao = null,
        ?array $dadosPrecarregados = null,
    ): Lancamento {
        $existente =
            Lancamento::withTrashed()
                ->where(
                    'discogs_release_id',
                    $identificadorDiscogs,
                )
                ->first();

        if (
            $validarAutorizacao === null
            && $existente instanceof Lancamento
            && ! $existente->trashed()
            && ! $this->necessitaMetadadosExternos(
                $existente,
            )
        ) {
            return $existente;
        }

        $dados = null;

        if (
            ! $existente instanceof Lancamento
            || $this->necessitaMetadadosExternos(
                $existente,
            )
        ) {
            $dados = $dadosPrecarregados
                ?? $this->discogs->obterLancamento($identificadorDiscogs);
        }

        try {
            $resultado =
                Cache::lock(
                    self::PREFIXO_BLOQUEIO_IMPORTACAO
                        .$identificadorDiscogs,
                    self::DURACAO_BLOQUEIO_SEGUNDOS,
                )->block(
                    self::ESPERA_BLOQUEIO_SEGUNDOS,
                    function () use (
                        $identificadorDiscogs,
                        $dados,
                        $validarAutorizacao,
                    ): Lancamento {
                        return DB::transaction(
                            function () use (
                                $identificadorDiscogs,
                                $dados,
                                $validarAutorizacao,
                            ): Lancamento {
                                if ($validarAutorizacao !== null) {
                                    $validarAutorizacao();
                                }

                                $existenteBloqueado =
                                    Lancamento::withTrashed()
                                        ->where(
                                            'discogs_release_id',
                                            $identificadorDiscogs,
                                        )
                                        ->lockForUpdate()
                                        ->first();

                                if (
                                    $existenteBloqueado
                                    instanceof Lancamento
                                ) {
                                    return $this->reutilizarLancamento(
                                        $existenteBloqueado,
                                        $dados,
                                    );
                                }

                                if ($dados === null) {
                                    throw new RuntimeException(
                                        'Os dados do lançamento não estão disponíveis para concluir a importação.',
                                    );
                                }

                                return $this->criarLancamento(
                                    $dados,
                                );
                            },
                            self::TENTATIVAS_TRANSACAO,
                        );
                    },
                );

            if (! $resultado instanceof Lancamento) {
                throw new RuntimeException(
                    'A importação do lançamento não devolveu um lançamento válido.',
                );
            }

            return $resultado;
        } catch (LockTimeoutException $excecao) {
            throw new RuntimeException(
                'Não foi possível coordenar a importação concorrente do lançamento.',
                previous: $excecao,
            );
        }
    }

    /**
     * Indica se um lançamento necessita de metadados complementares do
     * Discogs.
     *
     * @since 2.0.0
     */
    private function necessitaMetadadosExternos(
        Lancamento $lancamento,
    ): bool {
        return $lancamento->tipo === null
            || $lancamento->ano_original === null;
    }

    /**
     * Reutiliza um lançamento encontrado depois de adquirir o bloqueio.
     *
     * Um registo eliminado logicamente é restaurado. Metadados obtidos do
     * Discogs preenchem apenas valores ainda desconhecidos, preservando
     * correções efetuadas manualmente.
     *
     * @param  DadosLancamentoDiscogs|null  $dados  Dados normalizados do Discogs.
     *
     * @since 2.0.0
     */
    private function reutilizarLancamento(
        Lancamento $lancamento,
        ?array $dados,
    ): Lancamento {
        if ($lancamento->trashed()) {
            $lancamento->restore();
        }

        if ($dados === null) {
            return $lancamento;
        }

        $alterado = false;

        $tipoSugerido =
            $dados['tipo_sugerido'];

        if (
            $lancamento->tipo === null
            && $tipoSugerido !== null
        ) {
            $tipo =
                TipoLancamento::tryFrom(
                    $tipoSugerido,
                );

            if (! $tipo instanceof TipoLancamento) {
                throw new RuntimeException(
                    'O Discogs devolveu um tipo de lançamento inválido.',
                );
            }

            $lancamento->tipo =
                $tipo;

            $alterado = true;
        }

        if (
            $lancamento->ano_original === null
            && ($dados['ano_original'] ?? null) !== null
        ) {
            $lancamento->ano_original =
                $dados['ano_original'];

            $alterado = true;
        }

        if ($alterado) {
            $lancamento->saveOrFail();
        }

        return $lancamento;
    }

    /**
     * Persiste um lançamento novo e a respetiva tracklist.
     *
     * @param  DadosLancamentoDiscogs  $dados  Dados normalizados do Discogs.
     *
     * @since 2.0.0
     */
    private function criarLancamento(
        array $dados,
    ): Lancamento {
        $lancamento = new Lancamento;

        $lancamento->fill([
            'titulo' => $dados['titulo'],
            'tipo' => $dados['tipo_sugerido']
                ?? null,
            'ano_original' => $dados['ano_original']
                ?? null,
            'discogs_release_id' => $dados['discogs_release_id'],
        ]);

        $lancamento->saveOrFail();

        foreach ($dados['faixas'] as $dadosFaixa) {
            $musica = new Musica;

            $musica->fill([
                'titulo' => $dadosFaixa['titulo'],
            ]);

            $musica->saveOrFail();

            $faixa = new FaixaLancamento;

            $faixa->fill([
                'lancamento_id' => $lancamento->getKey(),
                'musica_id' => $musica->getKey(),
                'posicao' => $dadosFaixa['posicao'],
                'ordem' => $dadosFaixa['ordem'],
            ]);

            $faixa->saveOrFail();
        }

        return $lancamento;
    }
}
