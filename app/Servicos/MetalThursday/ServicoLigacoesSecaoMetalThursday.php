<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Enumeracoes\PlataformaLigacao;
use App\Models\MetalThursday\LigacaoSeccaoMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use InvalidArgumentException;

/**
 * Centraliza as regras das ligações públicas associadas a uma secção.
 *
 * A plataforma é identificada exclusivamente através do hostname do URL. O
 * serviço normaliza também o contrato persistível e sincroniza a coleção
 * ordenada pertencente à secção.
 *
 * A geração dos URLs de incorporação será acrescentada no cutover da
 * apresentação pública.
 *
 * @since 2.0.0
 */
final class ServicoLigacoesSecaoMetalThursday
{
    /**
     * Deteta a plataforma correspondente a um URL.
     *
     * Domínios desconhecidos, inválidos ou que apenas contenham o nome de um
     * serviço são classificados como {@see PlataformaLigacao::Outro}.
     *
     * @since 2.0.0
     */
    public function detetarPlataforma(
        string $url,
    ): PlataformaLigacao {
        $dominio = $this->obterDominio(
            $url,
        );

        if (
            $this->dominioCorresponde(
                $dominio,
                'spotify.com',
            )
            || $this->dominioCorresponde(
                $dominio,
                'spotify.link',
            )
        ) {
            return PlataformaLigacao::Spotify;
        }

        if (
            $this->dominioCorresponde(
                $dominio,
                'music.apple.com',
            )
        ) {
            return PlataformaLigacao::AppleMusic;
        }

        if (
            $this->dominioCorresponde(
                $dominio,
                'youtube.com',
            )
            || $this->dominioCorresponde(
                $dominio,
                'youtu.be',
            )
        ) {
            return PlataformaLigacao::YouTube;
        }

        return PlataformaLigacao::Outro;
    }

    /**
     * Normaliza a lista de ligações recebida pela camada de persistência.
     *
     * A ordem da lista é autoritativa. A plataforma nunca é recebida do
     * cliente e será novamente calculada pelo modelo antes da gravação.
     *
     * @param  mixed  $valor  Lista recebida.
     * @param  string  $campo  Nome do campo para mensagens de erro.
     * @param  bool  $exigirEtiquetaOutro  Obriga uma etiqueta nas novas
     *                                     ligações de plataforma desconhecida.
     * @return list<array{etiqueta: string|null, url: string, incorporar: bool}>
     *
     * @throws InvalidArgumentException Quando a lista não é válida.
     *
     * @since 2.0.0
     */
    public function normalizarDados(
        mixed $valor,
        string $campo = 'ligacoes',
        bool $exigirEtiquetaOutro = true,
    ): array {
        if ($valor === null) {
            return [];
        }

        if (
            ! is_array($valor)
            || ! array_is_list($valor)
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s deve ser uma lista.',
                    $campo,
                ),
            );
        }

        if (
            count($valor)
            > LigacaoSeccaoMetalThursday::NUMERO_MAXIMO_POR_SECCAO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s não pode possuir mais de %d ligações.',
                    $campo,
                    LigacaoSeccaoMetalThursday::NUMERO_MAXIMO_POR_SECCAO,
                ),
            );
        }

        $ligacoes = [];

        foreach ($valor as $indice => $dadosLigacao) {
            $campoLigacao = sprintf(
                '%s.%d',
                $campo,
                $indice,
            );

            if (! is_array($dadosLigacao)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'O campo %s não contém uma ligação válida.',
                        $campoLigacao,
                    ),
                );
            }

            $camposDesconhecidos = array_diff(
                array_keys($dadosLigacao),
                [
                    'url',
                    'etiqueta',
                    'incorporar',
                ],
            );

            if ($camposDesconhecidos !== []) {
                throw new InvalidArgumentException(
                    sprintf(
                        'O campo %s contém atributos desconhecidos.',
                        $campoLigacao,
                    ),
                );
            }

            $ligacao = new LigacaoSeccaoMetalThursday;

            $ligacao->url = $dadosLigacao['url']
                ?? null;

            $ligacao->etiqueta = $dadosLigacao['etiqueta']
                ?? null;

            $incorporar = $this->normalizarBooleano(
                $dadosLigacao['incorporar']
                    ?? false,
                $campoLigacao.'.incorporar',
            );

            $plataforma = $this->detetarPlataforma(
                $ligacao->url,
            );

            if (
                $exigirEtiquetaOutro
                && $plataforma === PlataformaLigacao::Outro
                && $ligacao->etiqueta === null
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'O campo %s.etiqueta é obrigatório para uma ligação personalizada.',
                        $campoLigacao,
                    ),
                );
            }

            if (! $plataforma->suportaIncorporacao()) {
                $incorporar = false;
            }

            $ligacoes[] = [
                'etiqueta' => $ligacao->etiqueta,
                'url' => $ligacao->url,
                'incorporar' => $incorporar,
            ];
        }

        return $ligacoes;
    }

    /**
     * Substitui atomicamente a coleção de ligações pertencente à secção.
     *
     * A transação exterior da persistência da MetalThursday garante que a
     * eliminação e a recriação ordenada não ficam num estado intermédio.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção persistida.
     * @param  list<array{etiqueta: string|null, url: string, incorporar: bool}>  $ligacoes  Ligações normalizadas.
     *
     * @throws InvalidArgumentException Quando a secção ainda não está
     *                                  persistida.
     *
     * @since 2.0.0
     */
    public function sincronizar(
        SeccaoMetalThursday $seccao,
        array $ligacoes,
    ): void {
        if (! $seccao->exists) {
            throw new InvalidArgumentException(
                'A secção deve estar persistida antes de sincronizar as ligações.',
            );
        }

        $seccao
            ->ligacoes()
            ->reorder()
            ->delete();

        foreach ($ligacoes as $indice => $dadosLigacao) {
            $ligacao = new LigacaoSeccaoMetalThursday([
                'etiqueta' => $dadosLigacao['etiqueta'],
                'url' => $dadosLigacao['url'],
                'incorporar' => $dadosLigacao['incorporar'],
                'ordem' => $indice + LigacaoSeccaoMetalThursday::ORDEM_MINIMA,
            ]);

            $seccao
                ->ligacoes()
                ->save(
                    $ligacao,
                );
        }

        $seccao->unsetRelation(
            'ligacoes',
        );
    }

    /**
     * Extrai e normaliza o domínio de um URL.
     *
     * @since 2.0.0
     */
    private function obterDominio(
        string $url,
    ): string {
        $dominio = parse_url(
            trim(
                $url,
            ),
            PHP_URL_HOST,
        );

        if (! is_string($dominio)) {
            return '';
        }

        return mb_strtolower(
            rtrim(
                $dominio,
                '.',
            ),
        );
    }

    /**
     * Normaliza uma opção booleana recebida pelo formulário ou serviço.
     *
     * @throws InvalidArgumentException Quando o valor não representa um
     *                                  booleano aceite.
     *
     * @since 2.0.0
     */
    private function normalizarBooleano(
        mixed $valor,
        string $campo,
    ): bool {
        if (is_bool($valor)) {
            return $valor;
        }

        if (
            $valor === 0
            || $valor === '0'
        ) {
            return false;
        }

        if (
            $valor === 1
            || $valor === '1'
        ) {
            return true;
        }

        throw new InvalidArgumentException(
            sprintf(
                'O campo %s deve ser verdadeiro ou falso.',
                $campo,
            ),
        );
    }

    /**
     * Confirma uma correspondência exata ou por subdomínio.
     *
     * Esta verificação impede falsos positivos como
     * `youtube.com.exemplo.test`.
     *
     * @since 2.0.0
     */
    private function dominioCorresponde(
        string $dominio,
        string $dominioEsperado,
    ): bool {
        return $dominio === $dominioEsperado
            || str_ends_with(
                $dominio,
                '.'.$dominioEsperado,
            );
    }
}
