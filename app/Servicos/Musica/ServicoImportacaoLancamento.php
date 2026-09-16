<?php

declare(strict_types=1);

namespace App\Servicos\Musica;

use App\Models\Musica\FaixaLancamento;
use App\Models\Musica\Lancamento;
use App\Models\Musica\Musica;
use Illuminate\Support\Facades\DB;

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

        if ($existente instanceof Lancamento) {
            if ($existente->trashed()) {
                $existente->restore();
            }

            if (
                $existente->tipo === null
                || $existente->ano_original === null
            ) {
                $dados =
                    $this
                        ->discogs
                        ->obterLancamento(
                            $identificadorDiscogs,
                        );

                $alterado = false;

                if (
                    $existente->tipo === null
                    && ($dados['tipo_sugerido'] ?? null) !== null
                ) {
                    $existente->tipo =
                        $dados['tipo_sugerido'];

                    $alterado = true;
                }

                if (
                    $existente->ano_original === null
                    && ($dados['ano_original'] ?? null) !== null
                ) {
                    $existente->ano_original =
                        $dados['ano_original'];

                    $alterado = true;
                }

                if ($alterado) {
                    $existente->saveOrFail();
                }
            }

            return $existente;
        }

        $dados =
            $this
                ->discogs
                ->obterLancamento(
                    $identificadorDiscogs,
                );

        return DB::transaction(
            static function () use (
                $dados,
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
            },
        );
    }
}
