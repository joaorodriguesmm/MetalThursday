import axios from 'axios';
import Swal from 'sweetalert2';

/**
 * Gere os botões de seleção de utilizadores nomeados.
 *
 * @since 1.0.0
 * @version 3.0.0
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
     *     Instância Tom Select associada ao campo de nomeados.
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
        this.botaoAleatorio =
            this.obterElementoOpcional(
                seletorBotaoAleatorio,
            );

        this.botaoMaisAntigo =
            this.obterElementoOpcional(
                seletorBotaoMaisAntigo,
            );

        this.tomSelect =
            instanciaTomSelect;

        this.urlNomeadoMaisAntigo =
            typeof urlNomeadoMaisAntigo === 'string'
                ? urlNomeadoMaisAntigo.trim()
                : '';

        this.emPedidoMaisAntigo =
            false;

        this.iniciado =
            false;

        this.aoClicarAleatorio = () => {
            this.selecionarAleatorio();
        };

        this.aoClicarMaisAntigo = () => {
            /*
             * A promessa pertence ao próprio manipulador e não deve ser
             * devolvida ao sistema de eventos do navegador.
             */
            this.selecionarMaisAntigo();
        };

        if (this.temTomSelectValido()) {
            this.iniciar();
        }
    }

    /**
     * Verifica se a instância Tom Select pode ser utilizada.
     *
     * @returns {boolean} Verdadeiro quando a instância é válida.
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
     * @returns {void}
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

        this.iniciado =
            true;
    }

    /**
     * Seleciona aleatoriamente um dos nomeados disponíveis.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    selecionarAleatorio() {
        if (!this.temTomSelectValido()) {
            return;
        }

        const valoresDisponiveis =
            Object.keys(
                this.tomSelect.options,
            ).filter((valor) => {
                if (valor.trim() === '') {
                    return false;
                }

                const opcao =
                    this.tomSelect.options[
                        valor
                    ];

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

        const indiceAleatorio =
            Math.floor(
                Math.random()
                * valoresDisponiveis.length,
            );

        this.tomSelect.setValue(
            valoresDisponiveis[
                indiceAleatorio
            ],
        );
    }

    /**
     * Seleciona o utilizador há mais tempo sem ser nomeado.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    async selecionarMaisAntigo() {
        if (
            !this.temTomSelectValido()
            || this.urlNomeadoMaisAntigo === ''
            || this.emPedidoMaisAntigo
        ) {
            return;
        }

        this.emPedidoMaisAntigo =
            true;

        const botaoEstavaDesativado =
            this.botaoMaisAntigo
                instanceof HTMLButtonElement
                ? this.botaoMaisAntigo.disabled
                : false;

        this.definirEstadoCarregamentoMaisAntigo(
            true,
        );

        try {
            const resposta =
                await axios.get(
                    this.urlNomeadoMaisAntigo,
                    {
                        headers: {
                            Accept:
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                    },
                );

            const identificador =
                resposta.data
                    ?.identificador;

            if (
                !Number.isInteger(
                    identificador,
                )
                || identificador < 1
            ) {
                this.apresentarNomeadoNaoEncontrado();

                return;
            }

            const valor =
                String(
                    identificador,
                );

            if (
                !Object.hasOwn(
                    this.tomSelect.options,
                    valor,
                )
            ) {
                Swal.fire({
                    icon: 'error',
                    title: 'Nomeado indisponível',
                    text: 'O utilizador devolvido já não está disponível para seleção.',
                });

                return;
            }

            this.tomSelect.setValue(
                valor,
            );
        } catch (erro) {
            const mensagem =
                axios.isAxiosError(
                    erro,
                )
                && typeof erro.response
                    ?.data
                    ?.mensagem === 'string'
                    ? erro.response.data.mensagem
                    : 'Não foi possível obter o utilizador há mais tempo sem nomeação.';

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

            this.emPedidoMaisAntigo =
                false;
        }
    }

    /**
     * Apresenta a ausência de um utilizador elegível.
     *
     * @returns {void}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    apresentarNomeadoNaoEncontrado() {
        Swal.fire({
            icon: 'info',
            title: 'Nomeado não encontrado',
            text: 'Não foi encontrado nenhum utilizador disponível para nomeação.',
        });
    }

    /**
     * Define o estado de carregamento do botão do nomeado mais antigo.
     *
     * @param {boolean} emCarregamento Estado de carregamento.
     * @param {boolean} estadoAnterior Estado anterior do botão.
     *
     * @returns {void}
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
     * @returns {Element|null} Elemento encontrado.
     *
     * @throws {TypeError} Quando o seletor CSS é inválido.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    obterElementoOpcional(
        seletor,
    ) {
        if (
            seletor === null
            || seletor === undefined
        ) {
            return null;
        }

        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                'O seletor indicado deve ser uma sequência de caracteres não vazia.',
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
     * Remove os eventos associados aos botões.
     *
     * @returns {void}
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

        this.iniciado =
            false;
    }
}

export default SeletorNomeados;
