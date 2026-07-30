/**
 * Gere a pré-visualização da fotografia do perfil.
 *
 * O gestor preserva o estado inicial apresentado pela página. A validação
 * do ficheiro pertence a ValidadorFicheiro; este módulo limita-se a gerir a
 * imagem apresentada e os respetivos recursos temporários.
 *
 * @since 1.0.0
 * @version 3.0.0
 */
class GestorFotografiaPerfil {
    /**
     * Cria o gestor da fotografia do perfil.
     *
     * @param {string} seletorCampoFicheiro
     *     Seletor CSS do campo de ficheiro.
     * @param {string} seletorPrevisualizacao
     *     Seletor CSS da imagem de pré-visualização.
     * @param {string} seletorIniciais
     *     Seletor CSS das iniciais do utilizador.
     * @param {string|null} seletorBotaoLimpar
     *     Seletor opcional do botão que limpa apenas a nova seleção.
     *
     * @throws {TypeError} Quando algum seletor CSS é inválido.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    constructor(
        seletorCampoFicheiro,
        seletorPrevisualizacao,
        seletorIniciais,
        seletorBotaoLimpar = null,
    ) {
        this.campoFicheiro = this.obterElemento(
            seletorCampoFicheiro,
        );

        this.elementoPrevisualizacao = this.obterElemento(
            seletorPrevisualizacao,
        );

        this.elementoIniciais = this.obterElemento(
            seletorIniciais,
        );

        this.botaoLimpar = seletorBotaoLimpar === null
            ? null
            : this.obterElemento(
                seletorBotaoLimpar,
            );

        this.circuloAvatar =
            this.elementoIniciais instanceof HTMLElement
                ? this.elementoIniciais.closest(
                    '.avatar-circle',
                )
                : null;

        this.origemFotografiaInicial =
            null;

        this.urlPrevisualizacaoTemporaria =
            null;

        this.iniciado =
            false;

        this.aoClicarBotaoLimpar = (evento) => {
            this.manipularCliqueBotaoLimpar(
                evento,
            );
        };

        if (!this.estaDisponivel()) {
            return;
        }

        this.registarEstadoInicial();
        this.iniciar();
        this.restaurarPrevisualizacao();
    }

    /**
     * Determina se os elementos obrigatórios estão disponíveis.
     *
     * @returns {boolean} Verdadeiro quando o gestor pode funcionar.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    estaDisponivel() {
        return this.campoFicheiro instanceof HTMLInputElement
            && this.campoFicheiro.type === 'file'
            && this.elementoPrevisualizacao
                instanceof HTMLImageElement
            && this.elementoIniciais instanceof HTMLElement;
    }

    /**
     * Configura o botão opcional de limpeza.
     *
     * O campo de ficheiro não é observado diretamente para impedir que a
     * pré-visualização seja atualizada antes da validação do ficheiro.
     *
     * @returns {void}
     *
     * @since 2.1.0
     * @version 2.0.0
     */
    iniciar() {
        if (!this.estaDisponivel() || this.iniciado) {
            return;
        }

        if (this.botaoLimpar instanceof HTMLElement) {
            this.botaoLimpar.addEventListener(
                'click',
                this.aoClicarBotaoLimpar,
            );
        }

        this.iniciado =
            true;
    }

    /**
     * Obtém o campo de ficheiro gerido.
     *
     * @returns {HTMLInputElement|null} Campo de ficheiro ou nulo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterCampoFicheiro() {
        return this.campoFicheiro instanceof HTMLInputElement
            ? this.campoFicheiro
            : null;
    }

    /**
     * Pré-visualiza uma fotografia previamente validada.
     *
     * É utilizado um URL de objeto temporário, evitando converter o ficheiro
     * para Base64 e reduzindo a utilização de memória.
     *
     * @param {File} ficheiro Fotografia validada.
     *
     * @returns {boolean} Indica se a pré-visualização foi atualizada.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    previsualizarImagem(ficheiro) {
        if (
            !this.estaDisponivel()
            || !(ficheiro instanceof File)
            || !ficheiro.type.startsWith(
                'image/',
            )
        ) {
            this.restaurarPrevisualizacao();

            return false;
        }

        this.revogarUrlTemporario();

        this.urlPrevisualizacaoTemporaria =
            URL.createObjectURL(
                ficheiro,
            );

        this.elementoPrevisualizacao.src =
            this.urlPrevisualizacaoTemporaria;

        this.alternarApresentacao(
            true,
        );

        this.atualizarBotaoLimpar(
            true,
        );

        return true;
    }

    /**
     * Restaura a fotografia inicialmente apresentada.
     *
     * Quando o utilizador ainda não possui fotografia, volta a apresentar as
     * iniciais.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    restaurarPrevisualizacao() {
        if (!this.estaDisponivel()) {
            return;
        }

        this.revogarUrlTemporario();

        if (this.origemFotografiaInicial !== null) {
            this.elementoPrevisualizacao.src =
                this.origemFotografiaInicial;

            this.alternarApresentacao(
                true,
            );
        } else {
            this.elementoPrevisualizacao.removeAttribute(
                'src',
            );

            this.alternarApresentacao(
                false,
            );
        }

        this.atualizarBotaoLimpar(
            false,
        );
    }

    /**
     * Regista a fotografia apresentada no carregamento inicial.
     *
     * A origem é lida através de `getAttribute()`, evitando que um atributo
     * vazio seja convertido pelo navegador no endereço da própria página.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    registarEstadoInicial() {
        if (
            !(
                this.elementoPrevisualizacao
                instanceof HTMLImageElement
            )
        ) {
            return;
        }

        const origem =
            this.elementoPrevisualizacao
                .getAttribute(
                    'src',
                )
                ?.trim();

        const fotografiaVisivel =
            typeof origem === 'string'
            && origem !== ''
            && !this.elementoPrevisualizacao
                .classList
                .contains(
                    'd-none',
                );

        this.origemFotografiaInicial =
            fotografiaVisivel
                ? origem
                : null;
    }

    /**
     * Limpa a nova seleção e restaura o estado inicial.
     *
     * @param {Event} evento Evento de clique.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    manipularCliqueBotaoLimpar(evento) {
        evento.preventDefault();

        if (
            this.campoFicheiro
            instanceof HTMLInputElement
        ) {
            this.campoFicheiro.value =
                '';

            this.campoFicheiro.setCustomValidity(
                '',
            );

            this.campoFicheiro.dispatchEvent(
                new Event(
                    'change',
                    {
                        bubbles: true,
                    },
                ),
            );
        }

        this.restaurarPrevisualizacao();
    }

    /**
     * Alterna entre a fotografia e o avatar com iniciais.
     *
     * @param {boolean} mostrarFotografia Indicação de apresentação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    alternarApresentacao(mostrarFotografia) {
        if (
            !(
                this.elementoPrevisualizacao
                instanceof HTMLImageElement
            )
        ) {
            return;
        }

        this.elementoPrevisualizacao.classList.toggle(
            'd-none',
            !mostrarFotografia,
        );

        this.elementoPrevisualizacao.setAttribute(
            'aria-hidden',
            mostrarFotografia
                ? 'false'
                : 'true',
        );

        if (this.circuloAvatar instanceof HTMLElement) {
            this.circuloAvatar.classList.toggle(
                'd-none',
                mostrarFotografia,
            );

            this.circuloAvatar.setAttribute(
                'aria-hidden',
                mostrarFotografia
                    ? 'true'
                    : 'false',
            );
        }
    }

    /**
     * Atualiza a visibilidade do botão de limpeza.
     *
     * @param {boolean} mostrar Indicação de apresentação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    atualizarBotaoLimpar(mostrar) {
        if (!(this.botaoLimpar instanceof HTMLElement)) {
            return;
        }

        this.botaoLimpar.classList.toggle(
            'd-none',
            !mostrar,
        );

        this.botaoLimpar.setAttribute(
            'aria-hidden',
            mostrar
                ? 'false'
                : 'true',
        );

        if (
            this.botaoLimpar
            instanceof HTMLButtonElement
        ) {
            this.botaoLimpar.disabled =
                !mostrar;
        }
    }

    /**
     * Revoga o URL temporário da pré-visualização.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    revogarUrlTemporario() {
        if (
            this.urlPrevisualizacaoTemporaria
            === null
        ) {
            return;
        }

        URL.revokeObjectURL(
            this.urlPrevisualizacaoTemporaria,
        );

        this.urlPrevisualizacaoTemporaria =
            null;
    }

    /**
     * Obtém um elemento através de um seletor CSS.
     *
     * @param {unknown} seletor Seletor CSS.
     *
     * @returns {Element|null} Elemento encontrado ou nulo.
     *
     * @throws {TypeError} Quando o seletor é inválido.
     *
     * @since 2.1.0
     * @version 1.0.0
     */
    obterElemento(seletor) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                'O seletor indicado é obrigatório.',
            );
        }

        const seletorNormalizado =
            seletor.trim();

        try {
            return document.querySelector(
                seletorNormalizado,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletorNormalizado}" é inválido.`,
            );
        }
    }

    /**
     * Liberta recursos e remove os eventos configurados.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    destruir() {
        if (
            this.iniciado
            && this.botaoLimpar instanceof HTMLElement
        ) {
            this.botaoLimpar.removeEventListener(
                'click',
                this.aoClicarBotaoLimpar,
            );
        }

        this.revogarUrlTemporario();

        this.iniciado =
            false;
    }
}

export default GestorFotografiaPerfil;
