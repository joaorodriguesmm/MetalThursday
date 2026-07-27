<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Stringable;

/**
 * Disponibiliza os dados comuns aos layouts da aplicação.
 *
 * A classe centraliza o nome da aplicação, o idioma do documento, o ano
 * atual e a composição segura do título apresentado no documento HTML.
 *
 * @since 3.0.0
 *
 * @version 1.0.0
 */
abstract class LayoutBase extends Component
{
    /**
     * Nome da aplicação.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $nomeAplicacao;

    /**
     * Idioma utilizado no documento HTML.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $idiomaDocumento;

    /**
     * Ano apresentado no rodapé.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly int $anoAtual;

    /**
     * Cria uma nova instância do layout.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function __construct()
    {
        $this->nomeAplicacao = $this->normalizarNomeAplicacao(
            config(
                'app.name',
                'MetalThursday',
            ),
        );

        $this->idiomaDocumento = $this->normalizarIdioma(
            app()->getLocale(),
        );

        $this->anoAtual = (int) now()->year;
    }

    /**
     * Compõe o título completo do documento.
     *
     * O conteúdo HTML do slot é removido e os espaços consecutivos são
     * normalizados antes da composição do título.
     *
     * @param  mixed  $titulo  Conteúdo recebido através do slot.
     * @return string Título completo do documento.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function tituloDocumento(
        mixed $titulo,
    ): string {
        $conteudo = match (true) {
            $titulo instanceof Htmlable => $titulo->toHtml(),

            $titulo instanceof Stringable => (string) $titulo,

            is_string($titulo) => $titulo,

            default => '',
        };

        $tituloSemHtml = html_entity_decode(
            strip_tags(
                $conteudo,
            ),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        $tituloNormalizado = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                $tituloSemHtml,
            ),
        );

        if (
            ! is_string($tituloNormalizado)
            || $tituloNormalizado === ''
        ) {
            return $this->nomeAplicacao;
        }

        return sprintf(
            '%s - %s',
            $tituloNormalizado,
            $this->nomeAplicacao,
        );
    }

    /**
     * Normaliza o nome configurado para a aplicação.
     *
     * @param  mixed  $nome  Valor configurado.
     * @return string Nome normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarNomeAplicacao(
        mixed $nome,
    ): string {
        if (
            ! is_string($nome)
            && ! $nome instanceof Stringable
        ) {
            return 'MetalThursday';
        }

        $nomeNormalizado = trim(
            (string) $nome,
        );

        return $nomeNormalizado !== ''
            ? $nomeNormalizado
            : 'MetalThursday';
    }

    /**
     * Normaliza o idioma configurado.
     *
     * O separador interno do Laravel é convertido para o formato esperado
     * pelo atributo `lang` do documento HTML.
     *
     * @param  mixed  $idioma  Idioma configurado.
     * @return string Idioma normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdioma(
        mixed $idioma,
    ): string {
        if (! is_string($idioma)) {
            return 'pt-PT';
        }

        $idiomaNormalizado = trim(
            str_replace(
                '_',
                '-',
                $idioma,
            ),
        );

        return $idiomaNormalizado !== ''
            ? $idiomaNormalizado
            : 'pt-PT';
    }
}
