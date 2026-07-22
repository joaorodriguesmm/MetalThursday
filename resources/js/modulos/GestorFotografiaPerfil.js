/**
 * Gere a pré-visualização da fotografia do perfil.
 *
 * O gestor preserva o estado inicial apresentado pela página. Quando uma
 * fotografia selecionada é inválida ou removida antes da submissão, a
 * fotografia atual do utilizador é restaurada em vez de ser substituída
 * incorretamente pelas iniciais.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class GestorFotografiaPerfil {
    /**
     * Cria o gestor da fotografia.
     *
     * @param {string} seletorCampoFicheiro - Seletor do campo de ficheiro.
     * @param {string} seletorPrevisualizacao - Seletor da imagem de
     * pré-visualização.
     * @param {string} seletorIniciais - Seletor das iniciais do utilizador.
     * @param {string|null} seletorBotaoLimpar - Seletor opcional do botão que
     * limpa apenas a nova seleção.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor(
        seletorCampoFicheiro,
        seletorPrevisualizacao,
        seletorIniciais,
        seletorBotaoLimpar = null,
    ) {
        /**
         * Campo utilizado para selecionar a fotografia.
         *
         * @type {HTMLInputElement|null}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.campoFicheiro = document.querySelector(
            seletorCampoFicheiro,
        );

        /**
         * Elemento utilizado para apresentar a fotografia.
         *
         * @type {HTMLImageElement|null}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.elementoPrevisualizacao = document.querySelector(
            seletorPrevisualizacao,
        );

        /**
         * Elemento que contém as iniciais.
         *
         * @type {HTMLElement|null}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.elementoIniciais = document.querySelector(
            seletorIniciais,
        );

        /**
         * Botão opcional que limpa a seleção atual.
         *
         * Este botão não elimina a fotografia persistida. Limita-se a remover
         * o ficheiro selecionado antes da submissão.
         *
         * @type {HTMLElement|null}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.botaoLimpar = seletorBotaoLimpar
            ? document.querySelector(seletorBotaoLimpar)
            : null;

        /**
         * Elemento que apresenta o avatar com as iniciais.
         *
         * @type {HTMLElement|null}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.circuloAvatar = this.elementoIniciais instanceof HTMLElement
            ? this.elementoIniciais.closest('.avatar-circle')
            : null;

        /**
         * Origem da fotografia apresentada no carregamento inicial.
         *
         * @type {string|null}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.origemFotografiaInicial = null;

        /**
         * URL temporário criado para a fotografia selecionada.
         *
         * @type {string|null}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.urlPrevisualizacaoTemporaria = null;

        /**
         * Referência estável do manipulador do botão.
         *
         * @type {(evento: Event) => void}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.manipularCliqueBotaoLimpar =
            this.manipularCliqueBotaoLimpar.bind(this);

        if (!this.estaDisponivel()) {
            return;
        }

        this.registarEstadoInicial();
        this.configurarEventos();
        this.restaurarPrevisualizacao();
    }

    /**
     * Determina se os elementos obrigatórios estão disponíveis.
     *
     * @return {boolean} Verdadeiro quando o gestor pode ser utilizado.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    estaDisponivel() {
        return (
            this.campoFicheiro instanceof HTMLInputElement
            && this.campoFicheiro.type === 'file'
            && this.elementoPrevisualizacao instanceof HTMLImageElement
            && this.elementoIniciais instanceof HTMLElement
        );
    }

    /**
     * Obtém o campo de ficheiro gerido.
     *
     * @return {HTMLInputElement|null} Campo de ficheiro ou nulo.
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
     * Pré-visualiza uma fotografia selecionada.
     *
     * É utilizado um URL de objeto temporário, evitando converter todo o
     * ficheiro para Base64 e mantendo menor utilização de memória.
     *
     * @param {File|null} ficheiro - Fotografia selecionada.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    previsualizarImagem(ficheiro) {
        if (!this.estaDisponivel()) {
            return;
        }

        if (!(ficheiro instanceof File)) {
            this.restaurarPrevisualizacao();

            return;
        }

        this.revogarUrlTemporario();

        this.urlPrevisualizacaoTemporaria =
            URL.createObjectURL(ficheiro);

        this.elementoPrevisualizacao.src =
            this.urlPrevisualizacaoTemporaria;

        this.alternarApresentacao(true);
        this.atualizarBotaoLimpar(true);
    }

    /**
     * Restaura a fotografia inicialmente apresentada.
     *
     * Quando o utilizador ainda não possui fotografia, volta a apresentar as
     * iniciais.
     *
     * @return {void}
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

            this.alternarApresentacao(true);
        } else {
            this.elementoPrevisualizacao.removeAttribute(
                'src',
            );

            this.alternarApresentacao(false);
        }

        this.atualizarBotaoLimpar(false);
    }

    /**
     * Liberta recursos e remove os eventos configurados.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        if (this.botaoLimpar instanceof HTMLElement) {
            this.botaoLimpar.removeEventListener(
                'click',
                this.manipularCliqueBotaoLimpar,
            );
        }

        this.revogarUrlTemporario();
    }

    /**
     * Regista a fotografia apresentada no carregamento inicial.
     *
     * A origem é lida através de `getAttribute`, evitando que um atributo
     * vazio seja convertido pelo navegador na ligação da própria página.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    registarEstadoInicial() {
        if (!(this.elementoPrevisualizacao instanceof HTMLImageElement)) {
            return;
        }

        const origem = this.elementoPrevisualizacao
            .getAttribute('src')
            ?.trim();

        const fotografiaVisivel = (
            typeof origem === 'string'
            && origem !== ''
            && !this.elementoPrevisualizacao.classList.contains(
                'd-none',
            )
        );

        this.origemFotografiaInicial = fotografiaVisivel
            ? origem
            : null;
    }

    /**
     * Configura os eventos opcionais do gestor.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    configurarEventos() {
        if (!(this.botaoLimpar instanceof HTMLElement)) {
            return;
        }

        this.botaoLimpar.addEventListener(
            'click',
            this.manipularCliqueBotaoLimpar,
        );
    }

    /**
     * Limpa a nova seleção e restaura o estado inicial.
     *
     * @param {Event} evento - Evento de clique.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    manipularCliqueBotaoLimpar(evento) {
        evento.preventDefault();

        if (this.campoFicheiro instanceof HTMLInputElement) {
            this.campoFicheiro.value = '';
        }

        this.restaurarPrevisualizacao();
    }

    /**
     * Alterna entre a fotografia e o avatar com iniciais.
     *
     * @param {boolean} mostrarFotografia - Indicação de apresentação.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    alternarApresentacao(mostrarFotografia) {
        if (!(this.elementoPrevisualizacao instanceof HTMLImageElement)) {
            return;
        }

        this.elementoPrevisualizacao.classList.toggle(
            'd-none',
            !mostrarFotografia,
        );

        this.elementoPrevisualizacao.setAttribute(
            'aria-hidden',
            mostrarFotografia ? 'false' : 'true',
        );

        if (this.circuloAvatar instanceof HTMLElement) {
            this.circuloAvatar.classList.toggle(
                'd-none',
                mostrarFotografia,
            );

            this.circuloAvatar.setAttribute(
                'aria-hidden',
                mostrarFotografia ? 'true' : 'false',
            );
        }
    }

    /**
     * Atualiza a visibilidade do botão de limpeza.
     *
     * @param {boolean} mostrar - Indicação de apresentação.
     *
     * @return {void}
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
            mostrar ? 'false' : 'true',
        );
    }

    /**
     * Revoga o URL temporário da pré-visualização.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    revogarUrlTemporario() {
        if (this.urlPrevisualizacaoTemporaria === null) {
            return;
        }

        URL.revokeObjectURL(
            this.urlPrevisualizacaoTemporaria,
        );

        this.urlPrevisualizacaoTemporaria = null;
    }
}

export default GestorFotografiaPerfil;
