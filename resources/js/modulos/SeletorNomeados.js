import axios from 'axios';
import Swal from 'sweetalert2';

/**
 * Gere os botões de seleção de utilizadores nomeados.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class SeletorNomeados {
    /**
     * Cria um seletor de utilizadores nomeados.
     *
     * @param {object} opcoes Opções de configuração.
     * @param {string|null} opcoes.seletorBotaoAleatorio
     *     Seletor CSS do botão de escolha aleatória.
     * @param {string|null} opcoes.seletorBotaoMaisAntigo
     *     Seletor CSS do botão que escolhe o nomeado mais antigo.
     * @param {object|null} opcoes.instanciaTomSelect
     *     Instância do Tom Select associada ao campo de nomeados.
     * @param {string|null} opcoes.urlNomeadoMaisAntigo
     *     Endereço utilizado para obter o nomeado mais antigo.
     *
     * @throws {TypeError} Quando algum seletor CSS é inválido.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor({
        seletorBotaoAleatorio = null,
        seletorBotaoMaisAntigo = null,
        instanciaTomSelect = null,
        urlNomeadoMaisAntigo = null,
    } = {}) {
        this.botaoAleatorio = this.obterElementoOpcional(
            seletorBotaoAleatorio,
        );

        this.botaoMaisAntigo = this.obterElementoOpcional(
            seletorBotaoMaisAntigo,
        );

        this.tomSelect = instanciaTomSelect;

        this.urlNomeadoMaisAntigo =
            typeof urlNomeadoMaisAntigo === 'string'
                ? urlNomeadoMaisAntigo.trim()
                : '';

        this.emPedidoMaisAntigo = false;
        this.iniciado = false;

        this.aoClicarAleatorio = () => {
            this.selecionarAleatorio();
        };

        this.aoClicarMaisAntigo = () => {
            this.selecionarMaisAntigo();
        };

        if (this.temTomSelectValido()) {
            this.iniciar();
        }
    }

    /**
     * Verifica se a instância do Tom Select pode ser utilizada.
     *
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    temTomSelectValido() {
        return typeof this.tomSelect === 'object'
            && this.tomSelect !== null
            && typeof this.tomSelect.setValue === 'function'
            && typeof this.tomSelect.options === 'object'
            && this.tomSelect.options !== null;
    }

    /**
     * Inicia os eventos dos botões de seleção.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        if (this.iniciado) {
            return;
        }

        this.botaoAleatorio?.addEventListener(
            'click',
            this.aoClicarAleatorio,
        );

        this.botaoMaisAntigo?.addEventListener(
            'click',
            this.aoClicarMaisAntigo,
        );

        this.iniciado = true;
    }

    /**
     * Seleciona aleatoriamente um dos nomeados disponíveis.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    selecionarAleatorio() {
        if (!this.temTomSelectValido()) {
            return;
        }

        const valoresDisponiveis = Object.keys(
            this.tomSelect.options,
        ).filter((valor) => {
            if (valor.trim() === '') {
                return false;
            }

            const opcao = this.tomSelect.options[valor];

            return opcao?.disabled !== true;
        });

        if (valoresDisponiveis.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sem nomeados disponíveis',
                text: 'Não existem nomeados disponíveis para selecionar.',
            });

            return;
        }

        const indiceAleatorio = Math.floor(
            Math.random() * valoresDisponiveis.length,
        );

        this.tomSelect.setValue(
            valoresDisponiveis[indiceAleatorio],
        );
    }

    /**
     * Seleciona o nomeado há mais tempo sem ser escolhido.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    async selecionarMaisAntigo() {
        if (
            !this.temTomSelectValido()
            || this.urlNomeadoMaisAntigo === ''
            || this.emPedidoMaisAntigo
        ) {
            return;
        }

        this.emPedidoMaisAntigo = true;

        const botaoEstavaDesativado =
            this.botaoMaisAntigo instanceof HTMLButtonElement
                ? this.botaoMaisAntigo.disabled
                : false;

        this.definirEstadoCarregamentoMaisAntigo(true);

        try {
            const resposta = await axios.get(
                this.urlNomeadoMaisAntigo,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            const identificador = resposta.data?.id;

            if (
                identificador !== null
                && identificador !== undefined
                && String(identificador).trim() !== ''
            ) {
                this.tomSelect.setValue(
                    String(identificador),
                );

                return;
            }

            Swal.fire({
                icon: 'info',
                title: 'Nomeado não encontrado',
                text: 'Não foi encontrado nenhum nomeado disponível.',
            });
        } catch (erro) {
            const mensagem =
                axios.isAxiosError(erro)
                && typeof erro.response?.data?.mensagem === 'string'
                    ? erro.response.data.mensagem
                    : 'Não foi possível obter o nomeado mais antigo.';

            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: mensagem,
            });
        } finally {
            this.definirEstadoCarregamentoMaisAntigo(
                false,
                botaoEstavaDesativado,
            );

            this.emPedidoMaisAntigo = false;
        }
    }

    /**
     * Define o estado de carregamento do botão do nomeado mais antigo.
     *
     * @param {boolean} emCarregamento Estado de carregamento.
     * @param {boolean} estadoAnterior Estado anterior do botão.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    definirEstadoCarregamentoMaisAntigo(
        emCarregamento,
        estadoAnterior = false,
    ) {
        if (
            !(this.botaoMaisAntigo instanceof HTMLButtonElement)
        ) {
            return;
        }

        this.botaoMaisAntigo.disabled =
            emCarregamento
                ? true
                : estadoAnterior;

        if (emCarregamento) {
            this.botaoMaisAntigo.setAttribute(
                'aria-busy',
                'true',
            );

            return;
        }

        this.botaoMaisAntigo.removeAttribute(
            'aria-busy',
        );
    }

    /**
     * Obtém opcionalmente um elemento através de um seletor CSS.
     *
     * @param {string|null} seletor Seletor CSS.
     *
     * @returns {Element|null}
     *
     * @throws {TypeError} Quando o seletor CSS é inválido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterElementoOpcional(seletor) {
        if (
            seletor === null
            || seletor === undefined
            || seletor === ''
        ) {
            return null;
        }

        if (typeof seletor !== 'string') {
            throw new TypeError(
                'O seletor indicado deve ser uma cadeia de caracteres.',
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
     * Remove os eventos associados aos botões.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        if (!this.iniciado) {
            return;
        }

        this.botaoAleatorio?.removeEventListener(
            'click',
            this.aoClicarAleatorio,
        );

        this.botaoMaisAntigo?.removeEventListener(
            'click',
            this.aoClicarMaisAntigo,
        );

        this.iniciado = false;
    }
}

export default SeletorNomeados;
