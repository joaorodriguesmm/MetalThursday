/**
 * Gere a pré-visualização da fotografia do perfil.
 *
 * O gestor preserva o estado inicial apresentado pela página. A validação
 * do ficheiro pertence a ValidadorFicheiro; este módulo limita-se a gerir a
 * imagem apresentada e os respetivos recursos temporários.
 *
 * @since 1.0.0
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
     *
     * @throws {TypeError} Quando algum seletor CSS é inválido.
     *
     * @since 1.0.0
     */
    constructor(
        seletorCampoFicheiro,
        seletorPrevisualizacao,
        seletorIniciais,
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

        this.elementoAvatarIniciais =
            this.elementoIniciais instanceof HTMLElement
                ? this.elementoIniciais.closest(
                    '.circulo-avatar, .circulo-avatar-registo',
                )
                : null;

        /**
         * Origem da fotografia apresentada no carregamento inicial.
         *
         * @type {string|null}
         *
         * @since 2.0.0
         */
        this.origemFotografiaInicial = null;

        /**
         * URL de objeto utilizado pela pré-visualização temporária.
         *
         * @type {string|null}
         *
         * @since 2.0.0
         */
        this.urlPrevisualizacaoTemporaria = null;

        if (!this.estaDisponivel()) {
            return;
        }

        this.registarEstadoInicial();
        this.restaurarPrevisualizacao();
    }

    /**
     * Determina se os elementos obrigatórios estão disponíveis.
     *
     * @returns {boolean} Verdadeiro quando o gestor pode funcionar.
     *
     * @since 2.0.0
     */
    estaDisponivel() {
        return this.campoFicheiro instanceof HTMLInputElement
            && this.campoFicheiro.type === 'file'
            && this.elementoPrevisualizacao
                instanceof HTMLImageElement
            && this.elementoIniciais instanceof HTMLElement
            && this.elementoAvatarIniciais
                instanceof HTMLElement;
    }

    /**
     * Obtém o campo de ficheiro gerido.
     *
     * @returns {HTMLInputElement|null} Campo de ficheiro ou nulo.
     *
     * @since 2.0.0
     */
    obterCampoFicheiro() {
        return this.campoFicheiro instanceof HTMLInputElement
            && this.campoFicheiro.type === 'file'
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
     */
    previsualizarImagem(ficheiro) {
        if (
            !this.estaDisponivel()
            || !(ficheiro instanceof File)
            || !ficheiro.type.startsWith('image/')
        ) {
            this.restaurarPrevisualizacao();

            return false;
        }

        this.revogarUrlTemporario();

        this.urlPrevisualizacaoTemporaria =
            URL.createObjectURL(ficheiro);

        this.elementoPrevisualizacao.src =
            this.urlPrevisualizacaoTemporaria;

        this.alternarApresentacao(true);

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

            return;
        }

        this.elementoPrevisualizacao.removeAttribute(
            'src',
        );

        this.alternarApresentacao(false);
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
     */
    registarEstadoInicial() {
        if (
            !(this.elementoPrevisualizacao
                instanceof HTMLImageElement)
        ) {
            return;
        }

        const origem = this.elementoPrevisualizacao
            .getAttribute('src')
            ?.trim();

        const fotografiaVisivel =
            typeof origem === 'string'
            && origem !== ''
            && !this.elementoPrevisualizacao.hidden
            && !this.elementoPrevisualizacao
                .classList
                .contains('d-none');

        this.origemFotografiaInicial =
            fotografiaVisivel
                ? origem
                : null;
    }

    /**
     * Alterna entre a fotografia e o avatar com iniciais.
     *
     * @param {boolean} mostrarFotografia Indicação de apresentação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    alternarApresentacao(mostrarFotografia) {
        if (!this.estaDisponivel()) {
            return;
        }

        this.atualizarVisibilidade(
            this.elementoPrevisualizacao,
            mostrarFotografia,
        );

        this.atualizarVisibilidade(
            this.elementoAvatarIniciais,
            !mostrarFotografia,
        );
    }

    /**
     * Atualiza a apresentação visual e acessível de um elemento.
     *
     * @param {HTMLElement} elemento Elemento atualizado.
     * @param {boolean} mostrar Indicação de apresentação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarVisibilidade(
        elemento,
        mostrar,
    ) {
        elemento.hidden = !mostrar;

        elemento.classList.toggle(
            'd-none',
            !mostrar,
        );

        if (mostrar) {
            elemento.removeAttribute('aria-hidden');

            return;
        }

        elemento.setAttribute(
            'aria-hidden',
            'true',
        );
    }

    /**
     * Revoga o URL temporário da pré-visualização.
     *
     * @returns {void}
     *
     * @since 2.0.0
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

    /**
     * Obtém um elemento através de um seletor CSS.
     *
     * A ausência do elemento é permitida para que o consumidor possa
     * consultar `estaDisponivel()`.
     *
     * @param {unknown} seletor Seletor CSS.
     *
     * @returns {Element|null} Elemento encontrado ou nulo.
     *
     * @throws {TypeError} Quando o seletor é inválido.
     *
     * @since 2.0.0
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

        const seletorNormalizado = seletor.trim();

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
}

export default GestorFotografiaPerfil;
