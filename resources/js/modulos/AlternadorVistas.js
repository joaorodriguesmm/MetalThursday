/**
 * Gere a alternância entre as vistas completa e simplificada de uma página.
 *
 * @since 1.0.0
 * @version 3.0.0
 */
class AlternadorVistas {
    /**
     * Cria um alternador de vistas.
     *
     * @param {object} opcoes Opções de configuração.
     * @param {string} opcoes.seletorBotao
     *     Seletor CSS do botão que alterna a vista.
     * @param {string} opcoes.seletorCampoVista
     *     Seletor CSS do campo que guarda a vista selecionada.
     * @param {string} opcoes.seletorFormulario
     *     Seletor CSS do formulário submetido após a alteração.
     * @param {object} opcoes.vistas Valores utilizados nas vistas.
     * @param {string} opcoes.vistas.completa Valor da vista completa.
     * @param {string} opcoes.vistas.simplificada Valor da vista simplificada.
     * @param {object} [opcoes.textosBotao] Textos apresentados no botão.
     * @param {string} [opcoes.textosBotao.mostrarCompleta]
     *     Texto utilizado para mudar para a vista completa.
     * @param {string} [opcoes.textosBotao.mostrarSimplificada]
     *     Texto utilizado para mudar para a vista simplificada.
     *
     * @throws {TypeError} Quando a configuração é inválida.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    constructor({
        seletorBotao,
        seletorCampoVista,
        seletorFormulario,
        vistas,
        textosBotao = {},
    } = {}) {
        this.vistas =
            this.normalizarVistas(
                vistas,
            );

        this.textosBotao =
            this.normalizarTextosBotao(
                textosBotao,
            );

        this.botaoAlternar =
            this.obterElemento(
                seletorBotao,
                HTMLButtonElement,
                'O seletor do botão de alternância é obrigatório.',
            );

        this.campoVista =
            this.obterElemento(
                seletorCampoVista,
                HTMLInputElement,
                'O seletor do campo da vista é obrigatório.',
            );

        this.formulario =
            this.obterElemento(
                seletorFormulario,
                HTMLFormElement,
                'O seletor do formulário é obrigatório.',
            );

        this.iniciado =
            false;

        this.aoClicarBotao = (evento) => {
            this.alternar(
                evento,
            );
        };

        if (this.estaAtivo()) {
            this.iniciar();
        }
    }

    /**
     * Verifica se os elementos obrigatórios estão disponíveis.
     *
     * @returns {boolean} Verdadeiro quando o alternador pode funcionar.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    estaAtivo() {
        return this.botaoAlternar instanceof HTMLButtonElement
            && this.campoVista instanceof HTMLInputElement
            && this.formulario instanceof HTMLFormElement;
    }

    /**
     * Inicia o alternador de vistas.
     *
     * @returns {void}
     *
     * @throws {Error} Quando o campo contém uma vista desconhecida.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    iniciar() {
        if (!this.estaAtivo() || this.iniciado) {
            return;
        }

        if (!this.eVistaValida(this.campoVista.value)) {
            throw new Error(
                'O formulário contém uma vista de MetalThursday inválida.',
            );
        }

        this.botaoAlternar.addEventListener(
            'click',
            this.aoClicarBotao,
        );

        this.atualizarTextoBotao(
            this.campoVista.value,
        );

        this.iniciado =
            true;
    }

    /**
     * Alterna a vista selecionada e submete o formulário.
     *
     * @param {MouseEvent|null} evento Evento de clique.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    alternar(evento = null) {
        evento?.preventDefault();

        if (!this.estaAtivo()) {
            return;
        }

        const novaVista =
            this.campoVista.value === this.vistas.completa
                ? this.vistas.simplificada
                : this.vistas.completa;

        this.campoVista.value =
            novaVista;

        this.atualizarTextoBotao(
            novaVista,
        );

        this.formulario.requestSubmit();
    }

    /**
     * Atualiza o texto do botão de acordo com a vista atual.
     *
     * O texto apresentado indica a vista para a qual o botão permite mudar.
     *
     * @param {string} vistaAtual Valor da vista atual.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    atualizarTextoBotao(vistaAtual) {
        if (!(this.botaoAlternar instanceof HTMLButtonElement)) {
            return;
        }

        this.botaoAlternar.textContent =
            vistaAtual === this.vistas.simplificada
                ? this.textosBotao.mostrarCompleta
                : this.textosBotao.mostrarSimplificada;
    }

    /**
     * Verifica se um valor corresponde a uma vista configurada.
     *
     * @param {unknown} vista Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando a vista é válida.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    eVistaValida(vista) {
        return vista === this.vistas.completa
            || vista === this.vistas.simplificada;
    }

    /**
     * Normaliza os valores das vistas configuradas.
     *
     * @param {unknown} vistas Configuração recebida.
     *
     * @returns {{completa: string, simplificada: string}}
     *     Vistas normalizadas.
     *
     * @throws {TypeError} Quando a configuração é inválida.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    normalizarVistas(vistas) {
        if (
            typeof vistas !== 'object'
            || vistas === null
            || Array.isArray(vistas)
            || typeof vistas.completa !== 'string'
            || typeof vistas.simplificada !== 'string'
        ) {
            throw new TypeError(
                'As vistas completa e simplificada são obrigatórias.',
            );
        }

        const completa =
            vistas.completa.trim();

        const simplificada =
            vistas.simplificada.trim();

        if (
            completa === ''
            || simplificada === ''
            || completa === simplificada
        ) {
            throw new TypeError(
                'As vistas completa e simplificada devem ser diferentes e não podem estar vazias.',
            );
        }

        return Object.freeze({
            completa,
            simplificada,
        });
    }

    /**
     * Normaliza os textos apresentados no botão.
     *
     * @param {unknown} textos Textos recebidos.
     *
     * @returns {{
     *     mostrarCompleta: string,
     *     mostrarSimplificada: string
     * }} Textos normalizados.
     *
     * @throws {TypeError} Quando os textos são inválidos.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    normalizarTextosBotao(textos) {
        if (
            typeof textos !== 'object'
            || textos === null
            || Array.isArray(textos)
        ) {
            throw new TypeError(
                'Os textos do botão devem ser apresentados num objeto.',
            );
        }

        const mostrarCompleta =
            textos.mostrarCompleta
            ?? 'Ver vista completa';

        const mostrarSimplificada =
            textos.mostrarSimplificada
            ?? 'Ver vista simplificada';

        if (
            typeof mostrarCompleta !== 'string'
            || mostrarCompleta.trim() === ''
            || typeof mostrarSimplificada !== 'string'
            || mostrarSimplificada.trim() === ''
        ) {
            throw new TypeError(
                'Os textos do botão de alternância não podem estar vazios.',
            );
        }

        return Object.freeze({
            mostrarCompleta:
                mostrarCompleta.trim(),

            mostrarSimplificada:
                mostrarSimplificada.trim(),
        });
    }

    /**
     * Obtém um elemento através de um seletor CSS.
     *
     * @param {unknown} seletor Seletor CSS.
     * @param {Function} tipoElemento Tipo de elemento esperado.
     * @param {string} mensagem Mensagem utilizada quando o seletor está vazio.
     *
     * @returns {Element|null} Elemento encontrado ou nulo.
     *
     * @throws {TypeError} Quando o seletor ou o elemento são inválidos.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    obterElemento(
        seletor,
        tipoElemento,
        mensagem,
    ) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                mensagem,
            );
        }

        const seletorNormalizado =
            seletor.trim();

        let elemento;

        try {
            elemento = document.querySelector(
                seletorNormalizado,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletorNormalizado}" é inválido.`,
            );
        }

        if (
            elemento !== null
            && !(elemento instanceof tipoElemento)
        ) {
            throw new TypeError(
                `O elemento encontrado através de "${seletorNormalizado}" possui um tipo inválido.`,
            );
        }

        return elemento;
    }

    /**
     * Remove os eventos associados ao alternador.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        if (!this.estaAtivo() || !this.iniciado) {
            return;
        }

        this.botaoAlternar.removeEventListener(
            'click',
            this.aoClicarBotao,
        );

        this.iniciado =
            false;
    }
}

export default AlternadorVistas;
