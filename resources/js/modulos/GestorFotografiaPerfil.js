/**
 * Gere a pré-visualização da fotografia do perfil.
 *
 * O gestor preserva o estado inicial apresentado pela página. Quando uma
 * fotografia selecionada é inválida ou removida antes da submissão, a
 * fotografia atual do utilizador é restaurada.
 *
 * @since 1.0.0
 * @version 2.1.0
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
     * @throws { @param {string|null} seletorBotaoLimpar
     *     Seletor opcional do botãoTypeError} Quando algum seletor CSS é inválido.
     *
     * @since 1.0.0
     * @version 2.1.0
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
            : this.obterElemento(seletorBotaoLimpar);

        this.circuloAvatar =
            this.elementoIniciais instanceof HTMLElement
                ? this.elementoIniciais.closest('.avatar-circle')
                : null;

        this.origemFotografiaInicial = null;
        this.urlPrevisualizacaoTemporaria = null;
        this.iniciado = false;

        this.aoAlterarCampoFicheiro = (evento) => {
            this.manipularAlteracaoCampoFicheiro(evento);
        };

        this.aoClicarBotaoLimpar = (evento) => {
            this.manipularCliqueBotaoLimpar(evento);
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
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    estaDisponivel() {
        return this.campoFicheiro instanceof HTMLInputElement
            && this.campoFicheiro.type === 'file'
            && this.elementoPrevisualizacao instanceof HTMLImageElement
            && this.elementoIniciais instanceof HTMLElement;
    }

    /**
     * Configura os eventos do gestor.
     *
     * @returns {void}
     *
     * @since 2.1.0
     * @version 1.0.0
     */
    iniciar() {
        if (!this.estaDisponivel() || this.iniciado) {
            return;
        }

        this.campoFicheiro.addEventListener(
            'change',
            this.aoAlterarCampoFicheiro,
        );

        if (this.botaoLimpar instanceof HTMLElement) {
            this.botaoLimpar.addEventListener(
                'click',
                this.aoClicarBotaoLimpar,
            );
        }

        this.iniciado = true;
    }

    /**
     * Obtém o campo de ficheiro gerido.
     *
     * @returns {HTMLInputElement|null}
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
     * Trata a alteração do campo de ficheiro.
     *
     * @param {Event} evento Evento de alteração.
     *
     * @returns {void}
     *
     * @since 2.1.0
     * @version 1.0.0
     */
    manipularAlteracaoCampoFicheiro(evento) {
        const campo = evento.currentTarget;

        if (!(campo instanceof HTMLInputElement)) {
            return;
        }

        campo.setCustomValidity('');

        const ficheiro = campo.files?.item(0) ?? null;

        if (ficheiro === null) {
            this.restaurarPrevisualizacao();

            return;
        }

        if (!this.eImagemValida(ficheiro)) {
            campo.value = '';

            campo.setCustomValidity(
                'Seleciona um ficheiro de imagem válido.',
            );

            campo.reportValidity();

            this.restaurarPrevisualizacao();

            return;
        }

        this.previsualizarImagem(ficheiro);
    }

    /**
     * Verifica se o ficheiro selecionado representa uma imagem.
     *
     * @param {File} ficheiro Ficheiro selecionado.
     *
     * @returns {boolean}
     *
     * @since 2.1.0
     * @version 1.0.0
     */
    eImagemValida(ficheiro) {
        return ficheiro.type.startsWith('image/');
    }

    /**
     * Pré-visualiza uma fotografia selecionada.
     *
     * É utilizado um URL de objeto temporário, evitando converter todo o
     * ficheiro para Base64 e reduzindo a utilização de memória.
     *
     * @param {File|null} ficheiro Fotografia selecionada.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.1.0
     */
    previsualizarImagem(ficheiro) {
        if (!this.estaDisponivel()) {
            return;
        }

        if (
            !(ficheiro instanceof File)
            || !this.eImagemValida(ficheiro)
        ) {
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

        const origem = this.elementoPrevisualizacao
            .getAttribute('src')
            ?.trim();

        const fotografiaVisivel =
            typeof origem === 'string'
            && origem !== ''
            && !this.elementoPrevisualizacao.classList.contains(
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
            this.campoFicheiro.value = '';
            this.campoFicheiro.setCustomValidity('');
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

        this.urlPrevisualizacaoTemporaria = null;
    }

    /**
     * Obtém um elemento através de um seletor CSS.
     *
     * @param {unknown} seletor Seletor CSS.
     *
     * @returns {Element|null}
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

        try {
            return document.querySelector(
                seletor,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletor}" é inválido.`,
            );
        }
    }

    /**
     * Liberta recursos e remove os eventos configurados.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    destruir() {
        if (this.iniciado) {
            if (
                this.campoFicheiro
                instanceof HTMLInputElement
            ) {
                this.campoFicheiro.removeEventListener(
                    'change',
                    this.aoAlterarCampoFicheiro,
                );
            }

            if (this.botaoLimpar instanceof HTMLElement) {
                this.botaoLimpar.removeEventListener(
                    'click',
                    this.aoClicarBotaoLimpar,
                );
            }
        }

        this.revogarUrlTemporario();
        this.iniciado = false;
    }
}

export default GestorFotografiaPerfil;
