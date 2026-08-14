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
 * @since 2.0.0
 */
abstract class LayoutBase extends Component
{
    /**
     * Nome utilizado quando a configuração da aplicação é inválida.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const NOME_APLICACAO_PREDEFINIDO = 'MetalThursday';

    /**
     * Idioma utilizado quando a configuração da aplicação é inválida.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const IDIOMA_PREDEFINIDO = 'pt-PT';

    /**
     * Nome da aplicação.
     *
     * @since 2.0.0
     */
    public readonly string $nomeAplicacao;

    /**
     * Idioma utilizado no documento HTML.
     *
     * @since 2.0.0
     */
    public readonly string $idiomaDocumento;

    /**
     * Ano apresentado no rodapé.
     *
     * @since 2.0.0
     */
    public readonly int $anoAtual;

    /**
     * Cria uma nova instância do layout.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $this->nomeAplicacao =
            $this->normalizarNomeAplicacao(
                config(
                    'app.name',
                    self::NOME_APLICACAO_PREDEFINIDO,
                ),
            );

        $this->idiomaDocumento =
            $this->normalizarIdioma(
                app()->getLocale(),
            );

        $this->anoAtual =
            (int) now()->format(
                'Y',
            );
    }

    /**
     * Compõe o título completo do documento.
     *
     * O conteúdo HTML do slot é removido e os espaços consecutivos são
     * normalizados antes da composição do título.
     *
     * @param  Htmlable|Stringable|string|null  $titulo  Conteúdo recebido
     *                                                   através do slot.
     * @return string Título completo do documento.
     *
     * @since 2.0.0
     */
    public function tituloDocumento(
        Htmlable|Stringable|string|null $titulo = null,
    ): string {
        $tituloNormalizado =
            $this->normalizarTitulo(
                $titulo,
            );

        if ($tituloNormalizado === null) {
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
     * @since 2.0.0
     */
    private function normalizarNomeAplicacao(
        mixed $nome,
    ): string {
        if (
            ! is_string($nome)
            && ! $nome instanceof Stringable
        ) {
            return self::NOME_APLICACAO_PREDEFINIDO;
        }

        $nomeNormalizado = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) $nome,
            ),
        );

        if (
            ! is_string($nomeNormalizado)
            || $nomeNormalizado === ''
        ) {
            return self::NOME_APLICACAO_PREDEFINIDO;
        }

        return $nomeNormalizado;
    }

    /**
     * Normaliza o idioma configurado.
     *
     * O separador interno do Laravel é convertido para o formato esperado
     * pelo atributo `lang` do documento HTML.
     *
     * @param  string  $idioma  Idioma configurado.
     * @return string Idioma normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarIdioma(
        string $idioma,
    ): string {
        $idiomaNormalizado = trim(
            str_replace(
                '_',
                '-',
                $idioma,
            ),
        );

        if (
            $idiomaNormalizado === ''
            || preg_match(
                '/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/',
                $idiomaNormalizado,
            ) !== 1
        ) {
            return self::IDIOMA_PREDEFINIDO;
        }

        return $idiomaNormalizado;
    }

    /**
     * Normaliza o título específico da página.
     *
     * @param  Htmlable|Stringable|string|null  $titulo  Conteúdo recebido.
     * @return string|null Título normalizado ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarTitulo(
        Htmlable|Stringable|string|null $titulo,
    ): ?string {
        if ($titulo === null) {
            return null;
        }

        $conteudo =
            $titulo instanceof Htmlable
            ? $titulo->toHtml()
            : (string) $titulo;

        $tituloSemHtml = strip_tags(
            html_entity_decode(
                $conteudo,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            ),
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
            return null;
        }

        return $tituloNormalizado;
    }
}
