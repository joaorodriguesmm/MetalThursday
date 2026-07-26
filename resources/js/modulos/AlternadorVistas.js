/**
 * Gere a alternância entre diferentes vistas de uma página.
 *
 * A chave `metalThursdayView` permanece temporariamente inalterada por poder
 * ser utilizada por outros módulos ainda não analisados.
 *
 * @since 1.0.0
 * @version 2.0.0
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
     * @param {object} opcoes.vistas
     *     Valores utilizados para identificar as vistas.
     * @param {string} opcoes.vistas.completa
     *     Valor correspondente à vista completa.
     * @param {string} opcoes.vistas.simplificada
     *     Valor correspondente à vista simplificada.
     * @param {object} [opcoes.textosBotao]
     *     Textos opcionais apresentados no botão.
     * @param {string} [opcoes.textosBotao.mostrarCompleta]
     *     Texto utilizado para mudar para a vista completa.
     * @param {string} [opcoes.textosBotao.mostrarSimplificada]
     *     Texto utilizado para mudar para a vista simplificada.
     *
     * @throws {TypeError} Quando a configuração é inválida.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor({
        seletorBotao,
        seletorCampoVista,
        seletorFormulario,
        vistas,
        textosBotao = {},
    } = {}) {
        this.validarVistas(vistas);

        this.botaoAlternar = this.obterElemento(
            seletorBotao,
            'O seletor do botão de alternância é obrigatório.',
        );

        this.campoVista = this.obterElemento(
            seletorCampoVista,
            'O seletor do campo da vista é obrigatório.',
        );

        this.formulario = this.obterElemento(
            seletorFormulario,
            'O seletor do formulário é obrigatório.',
        );

        this.vistas = {
            completa: vistas.completa,
            simplificada: vistas.simplificada,
        };

        this.textosBotao = {
            mostrarCompleta:
                textosBotao.mostrarCompleta
                ?? 'Ver vista completa',

            mostrarSimplificada:
                textosBotao.mostrarSimplificada
                ?? 'Ver vista simplificada',
        };

        this.chaveArmazenamento = 'metalThursdayView';
        this.iniciado = false;

        this.aoClicarBotao = (evento) => {
            this.alternar(evento);
        };

        if (this.estaAtivo()) {
            this.iniciar();
        }
    }

    /**
     * Verifica se os elementos obrigatórios estão disponíveis.
     *
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    estaAtivo() {
        return this.botaoAlternar instanceof HTMLElement
            && this.campoVista instanceof HTMLInputElement
            && this.formulario instanceof HTMLFormElement;
    }

    /**
     * Inicia o alternador de vistas.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        if (!this.estaAtivo() || this.iniciado) {
            return;
        }

        this.botaoAlternar.addEventListener(
            'click',
            this.aoClicarBotao,
        );

        this.atualizarTextoBotao(
            this.campoVista.value,
        );

        this.iniciado = true;
    }

    /**
     * Alterna a vista selecionada e submete o formulário.
     *
     * @param {MouseEvent|null} evento Evento de clique.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    alternar(evento = null) {
        evento?.preventDefault();

        if (!this.estaAtivo()) {
            return;
        }

        const vistaAtual =
            this.campoVista.value;

        const novaVista =
            vistaAtual === this.vistas.completa
                ? this.vistas.simplificada
                : this.vistas.completa;

        this.campoVista.value =
            novaVista;

        this.guardarPreferencia(
            novaVista,
        );

        this.atualizarTextoBotao(
            novaVista,
        );

        this.submeterFormulario();
    }

    /**
     * Atualiza o texto do botão de acordo com a vista atual.
     *
     * O texto apresentado indica a vista para a qual o botão permite mudar.
     *
     * @param {string} vistaAtual Valor da vista atual.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    atualizarTextoBotao(vistaAtual) {
        if (!(this.botaoAlternar instanceof HTMLElement)) {
            return;
        }

        this.botaoAlternar.textContent =
            vistaAtual === this.vistas.simplificada
                ? this.textosBotao.mostrarCompleta
                : this.textosBotao.mostrarSimplificada;
    }

    /**
     * Guarda localmente a vista selecionada.
     *
     * Falhas de acesso ao armazenamento local não impedem a alteração da
     * vista nem a submissão do formulário.
     *
     * @param {string} vista Vista selecionada.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    guardarPreferencia(vista) {
        try {
            window.localStorage.setItem(
                this.chaveArmazenamento,
                vista,
            );
        } catch {
            // O armazenamento local pode estar indisponível no navegador.
        }
    }

    /**
     * Submete o formulário respeitando os eventos e a validação nativa.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    submeterFormulario() {
        if (!(this.formulario instanceof HTMLFormElement)) {
            return;
        }

        if (
            typeof this.formulario.requestSubmit
            === 'function'
        ) {
            this.formulario.requestSubmit();

            return;
        }

        this.formulario.submit();
    }

    /**
     * Valida os valores das vistas configuradas.
     *
     * @param {unknown} vistas Configuração a validar.
     *
     * @throws {TypeError} Quando a configuração é inválida.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    validarVistas(vistas) {
        if (
            typeof vistas !== 'object'
            || vistas === null
            || Array.isArray(vistas)
            || typeof vistas.completa !== 'string'
            || vistas.completa.trim() === ''
            || typeof vistas.simplificada !== 'string'
            || vistas.simplificada.trim() === ''
            || vistas.completa === vistas.simplificada
        ) {
            throw new TypeError(
                'Os valores das vistas completa e simplificada são obrigatórios e devem ser diferentes.',
            );
        }
    }

    /**
     * Obtém um elemento através de um seletor CSS.
     *
     * @param {unknown} seletor Seletor CSS.
     * @param {string} mensagem Mensagem utilizada quando está vazio.
     *
     * @returns {Element|null}
     *
     * @throws {TypeError} Quando o seletor CSS é inválido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterElemento(
        seletor,
        mensagem,
    ) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(mensagem);
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
     * Remove os eventos associados ao alternador.
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

        this.iniciado = false;
    }
}

export default AlternadorVistas;
