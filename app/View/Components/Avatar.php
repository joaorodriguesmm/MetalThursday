<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Prepara a apresentação do avatar de um utilizador.
 *
 * Quando o utilizador possui uma fotografia, o componente apresenta a
 * respetiva imagem. Caso contrário, apresenta as iniciais fornecidas pelo
 * modelo ou um ponto de interrogação.
 *
 * @since 1.0.0
 */
final class Avatar extends Component
{
    /**
     * Tamanho predefinido do avatar, em píxeis.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TAMANHO_PREDEFINIDO = 40;

    /**
     * Tamanho mínimo permitido, em píxeis.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TAMANHO_MINIMO = 16;

    /**
     * Tamanho máximo permitido, em píxeis.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TAMANHO_MAXIMO = 256;

    /**
     * Tamanho normalizado do avatar, em píxeis.
     *
     * @since 2.0.0
     */
    public readonly int $tamanho;

    /**
     * URL normalizada da fotografia.
     *
     * @since 2.0.0
     */
    public readonly string $urlFotografia;

    /**
     * Iniciais apresentadas na ausência de fotografia.
     *
     * @since 2.0.0
     */
    public readonly string $iniciais;

    /**
     * Indica se existe uma fotografia disponível.
     *
     * @since 2.0.0
     */
    public readonly bool $temFotografia;

    /**
     * Indica se o avatar é meramente decorativo.
     *
     * @since 2.0.0
     */
    public readonly bool $avatarDecorativo;

    /**
     * Descrição acessível do avatar.
     *
     * @since 2.0.0
     */
    public readonly string $descricaoAvatar;

    /**
     * Cria uma nova instância do componente.
     *
     * Uma descrição nula gera automaticamente o texto acessível. Uma
     * descrição vazia transforma o avatar num elemento decorativo.
     *
     * @param  Utilizador|null  $utilizador  Utilizador representado.
     * @param  int|string|null  $tamanho  Tamanho pretendido do avatar.
     * @param  string|null  $descricao  Descrição acessível pretendida.
     *
     * @since 1.0.0
     */
    public function __construct(
        ?Utilizador $utilizador = null,
        int|string|null $tamanho = self::TAMANHO_PREDEFINIDO,
        ?string $descricao = null,
    ) {
        $this->tamanho = $this->normalizarTamanho(
            $tamanho,
        );

        $nomeUtilizador = trim(
            (string) (
                $utilizador?->nome
                ?? ''
            ),
        );

        $this->urlFotografia = trim(
            (string) (
                $utilizador?->url_fotografia
                ?? ''
            ),
        );

        $this->iniciais = $this->normalizarIniciais(
            $utilizador?->iniciais,
        );

        $this->temFotografia =
            $this->urlFotografia !== '';

        $descricaoNormalizada =
            $descricao !== null
            ? trim(
                $descricao,
            )
            : null;

        $this->avatarDecorativo =
            $descricaoNormalizada === '';

        $this->descricaoAvatar =
            $descricaoNormalizada
            ?? (
                $nomeUtilizador !== ''
                ? "Avatar de {$nomeUtilizador}"
                : 'Utilizador não identificado'
            );
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista do avatar.
     *
     * @since 1.0.0
     */
    public function render(): View
    {
        return view(
            'components.avatar',
        );
    }

    /**
     * Normaliza o tamanho pretendido.
     *
     * Valores que não representem números inteiros utilizam o tamanho
     * predefinido. Os restantes são limitados ao intervalo permitido.
     *
     * @param  int|string|null  $tamanho  Tamanho recebido.
     * @return int Tamanho normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarTamanho(
        int|string|null $tamanho,
    ): int {
        if (is_string($tamanho)) {
            $tamanho = trim(
                $tamanho,
            );
        }

        if (
            $tamanho === null
            || $tamanho === ''
        ) {
            return self::TAMANHO_PREDEFINIDO;
        }

        $tamanhoValidado = filter_var(
            $tamanho,
            FILTER_VALIDATE_INT,
        );

        if ($tamanhoValidado === false) {
            return self::TAMANHO_PREDEFINIDO;
        }

        return max(
            self::TAMANHO_MINIMO,
            min(
                self::TAMANHO_MAXIMO,
                $tamanhoValidado,
            ),
        );
    }

    /**
     * Normaliza as iniciais fornecidas pelo modelo.
     *
     * O modelo é a fonte responsável pelo cálculo das iniciais. O componente
     * limita-se a normalizar a capitalização e o comprimento apresentado.
     *
     * @param  string|null  $iniciais  Iniciais recebidas.
     * @return string Iniciais normalizadas ou ponto de interrogação.
     *
     * @since 2.0.0
     */
    private function normalizarIniciais(
        ?string $iniciais,
    ): string {
        if ($iniciais === null) {
            return '?';
        }

        $iniciaisNormalizadas = trim(
            $iniciais,
        );

        if ($iniciaisNormalizadas === '') {
            return '?';
        }

        return mb_strtoupper(
            mb_substr(
                $iniciaisNormalizadas,
                0,
                2,
            ),
        );
    }
}
