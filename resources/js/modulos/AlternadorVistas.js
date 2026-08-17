/**
 * Gere a alternância entre as vistas completa e simplificada de uma página.
 *
 * @since 1.0.0
 */
class AlternadorVistas {
    /**
     * Cria e inicia um alternador de vistas.
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
     *
     * @throws {TypeError} Quando a configuração ou os elementos são inválidos.
     * @throws {Error} Quando o campo contém uma vista desconhecida.
     *
     * @since 1.0.0
     */
    constructor({
        seletorBotao,
        seletorCampoVista,
        seletorFormulario,
        vistas,
    } = {}) {
        this.vistas = this.normalizarVistas(vistas);

        this.botaoAlternar = this.obterElemento(
            seletorBotao,
            HTMLButtonElement,
            'botão de alternância',
        );

        this.campoVista = this.obterElemento(
            seletorCampoVista,
            HTMLInputElement,
            'campo da vista',
        );

        this.formulario = this.obterElemento(
            seletorFormulario,
            HTMLFormElement,
            'formulário',
        );

        if (this.campoVista.form !== this.formulario) {
            throw new TypeError(
                'O campo da vista deve pertencer ao formulário configurado.',
            );
        }

        this.validarVistaAtual();

        this.botaoAlternar.type = 'button';

        this.botaoAlternar.addEventListener(
            'click',
            () => this.alternar(),
        );
    }

    /**
     * Alterna a vista selecionada e submete o formulário.
     *
     * @returns {void}
     *
     * @throws {Error} Quando o campo contém uma vista desconhecida.
     *
     * @since 1.0.0
     */
    alternar() {
        this.validarVistaAtual();

        this.campoVista.value =
            this.campoVista.value === this.vistas.completa
                ? this.vistas.simplificada
                : this.vistas.completa;

        this.formulario.requestSubmit();
    }

    /**
     * Valida a vista atualmente guardada no formulário.
     *
     * @returns {void}
     *
     * @throws {Error} Quando o campo contém uma vista desconhecida.
     *
     * @since 2.0.0
     */
    validarVistaAtual() {
        if (this.eVistaValida(this.campoVista.value)) {
            return;
        }

        throw new Error(
            'O formulário contém uma vista de MetalThursday inválida.',
        );
    }

    /**
     * Verifica se um valor corresponde a uma vista configurada.
     *
     * @param {unknown} vista Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando a vista é válida.
     *
     * @since 2.0.0
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

        const completa = vistas.completa.trim();
        const simplificada = vistas.simplificada.trim();

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
     * Obtém e valida um elemento através de um seletor CSS.
     *
     * @param {unknown} seletor Seletor CSS.
     * @param {Function} tipoElemento Tipo de elemento esperado.
     * @param {string} descricaoElemento Descrição do elemento esperado.
     *
     * @returns {Element} Elemento encontrado.
     *
     * @throws {TypeError} Quando o seletor ou o elemento são inválidos.
     *
     * @since 2.0.0
     */
    obterElemento(
        seletor,
        tipoElemento,
        descricaoElemento,
    ) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                `O seletor do ${descricaoElemento} é obrigatório.`,
            );
        }

        const seletorNormalizado = seletor.trim();

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

        if (!(elemento instanceof tipoElemento)) {
            throw new TypeError(
                `Não foi encontrado um ${descricaoElemento} válido através de "${seletorNormalizado}".`,
            );
        }

        return elemento;
    }
}

export default AlternadorVistas;
