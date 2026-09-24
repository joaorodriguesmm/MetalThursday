<?php

declare(strict_types=1);

namespace App\Servicos\Musica;

use App\Models\Musica\FaixaLancamento;
use App\Models\Musica\Lancamento;
use App\Models\Musica\Musica;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Importa lançamentos musicais obtidos através do Discogs.
 *
 * A integração Discogs é responsável pela consulta e normalização dos dados.
 * Este serviço decide quais desses dados pertencem ao catálogo persistido.
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
    ) {}

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
     * @return Lancamento Lançamento persistido.
     *
     * @since 2.0.0
     */
    public function importar(
        int $identificadorDiscogs,
    ): Lancamento {
        $existente =
            Lancamento::withTrashed()
                ->where(
                    'discogs_release_id',
                    $identificadorDiscogs,
                )
                ->first();

        if (
            $existente instanceof Lancamento
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
            $dados =
                $this
                    ->discogs
                    ->obterLancamento(
                        $identificadorDiscogs,
                    );
        }

        try {
            return Cache::lock(
                self::PREFIXO_BLOQUEIO_IMPORTACAO
                    .$identificadorDiscogs,
                self::DURACAO_BLOQUEIO_SEGUNDOS,
            )->block(
                self::ESPERA_BLOQUEIO_SEGUNDOS,
                function () use (
                    $identificadorDiscogs,
                    $dados,
                ): Lancamento {
                    return DB::transaction(
                        function () use (
                            $identificadorDiscogs,
                            $dados,
                        ): Lancamento {
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
     * @param  array<string, mixed>|null  $dados  Dados normalizados do Discogs.
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

        if (
            $lancamento->tipo === null
            && ($dados['tipo_sugerido'] ?? null) !== null
        ) {
            $lancamento->tipo =
                $dados['tipo_sugerido'];

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
     * @param  array<string, mixed>  $dados  Dados normalizados do Discogs.
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
