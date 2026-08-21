import axios from 'axios';
import Swal from 'sweetalert2';

/**
 * Gere os botões de seleção de utilizadores nomeados.
 *
 * @since 1.0.0
 */
class SeletorNomeados {
    /**
     * Cria e inicializa um seletor de utilizadores nomeados.
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
     * @param {Function|null} opcoes.obterValorExcluido
     *     Callback que devolve o identificador que não pode ser nomeado.
     *
     * @throws {TypeError} Quando algum seletor CSS ou elemento é inválido.
     *
     * @since 1.0.0
     */
    constructor({
        seletorBotaoAleatorio = null,
        seletorBotaoMaisAntigo = null,
        instanciaTomSelect = null,
        urlNomeadoMaisAntigo = null,
        obterValorExcluido = null,
    } = {}) {
        this.botaoAleatorio =
            this.obterBotaoOpcional(
                seletorBotaoAleatorio,
                'botão de seleção aleatória',
            );

        this.botaoMaisAntigo =
            this.obterBotaoOpcional(
                seletorBotaoMaisAntigo,
                'botão de seleção do nomeado mais antigo',
            );

        this.tomSelect =
            instanciaTomSelect;

        this.urlNomeadoMaisAntigo =
            typeof urlNomeadoMaisAntigo === 'string'
                ? urlNomeadoMaisAntigo.trim()
                : '';

        this.obterValorExcluido =
            typeof obterValorExcluido === 'function'
                ? obterValorExcluido
                : null;

        this.emPedidoMaisAntigo =
            false;

        if (!this.temTomSelectValido()) {
            return;
        }

        this.botaoAleatorio?.addEventListener(
            'click',
            () => {
                this.selecionarAleatorio();
            },
        );

        this.botaoMaisAntigo?.addEventListener(
            'click',
            () => {
                void this.selecionarMaisAntigo();
            },
        );
    }

    /**
     * Verifica se a instância Tom Select possui a API necessária.
     *
     * @returns {boolean} Verdadeiro quando a instância é válida.
     *
     * @since 2.0.0
     */
    temTomSelectValido() {
        return typeof this.tomSelect === 'object'
            && this.tomSelect !== null
            && typeof this.tomSelect.setValue === 'function'
            && typeof this.tomSelect.lock === 'function'
            && typeof this.tomSelect.unlock === 'function'
            && typeof this.tomSelect.options === 'object'
            && this.tomSelect.options !== null;
    }

    /**
     * Obtém o identificador atualmente excluído da nomeação.
     *
     * @returns {string} Identificador positivo ou cadeia vazia.
     *
     * @since 2.0.0
     */
    obterValorExcluidoNormalizado() {
        if (this.obterValorExcluido === null) {
            return '';
        }

        const valor =
            this.obterValorExcluido();

        if (
            typeof valor === 'number'
            && Number.isInteger(valor)
            && valor > 0
        ) {
            return String(valor);
        }

        if (
            typeof valor === 'string'
            && /^[1-9]\d*$/u.test(
                valor.trim(),
            )
        ) {
            return valor.trim();
        }

        return '';
    }

    /**
     * Seleciona aleatoriamente um dos nomeados disponíveis.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    selecionarAleatorio() {
        if (
            !this.temTomSelectValido()
            || this.emPedidoMaisAntigo
        ) {
            return;
        }

        const valorExcluido =
            this.obterValorExcluidoNormalizado();

        const valoresDisponiveis =
            Object.keys(
                this.tomSelect.options,
            ).filter((valor) => {
                if (valor.trim() === '') {
                    return false;
                }

                if (valor === valorExcluido) {
                    return false;
                }

                const opcao =
                    this.tomSelect.options[
                        valor
                    ];

                return opcao?.disabled !== true;
            });

        if (valoresDisponiveis.length === 0) {
            void this.apresentarAlerta({
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

        const estadoAnterior =
            this.obterEstadoInteracao();

        this.bloquearInteracao();

        try {
            const valorExcluido =
                this.obterValorExcluidoNormalizado();

            const resposta =
                await axios.get(
                    this.urlNomeadoMaisAntigo,
                    {
                        params:
                            valorExcluido === ''
                                ? {}
                                : {
                                    excluir_utilizador_id:
                                        valorExcluido,
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
                await this.apresentarAlerta({
                    icon: 'info',
                    title: 'Nomeado não encontrado',
                    text: 'Não foi encontrado nenhum utilizador disponível para nomeação.',
                });

                return;
            }

            const valor =
                String(
                    identificador,
                );

            const opcao =
                Object.hasOwn(
                    this.tomSelect.options,
                    valor,
                )
                    ? this.tomSelect.options[
                        valor
                    ]
                    : null;

            if (
                !opcao
                || opcao.disabled === true
                || valor === valorExcluido
            ) {
                await this.apresentarAlerta({
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
            const mensagemRecebida =
                axios.isAxiosError(
                    erro,
                )
                && typeof erro.response
                    ?.data
                    ?.mensagem === 'string'
                    ? erro.response
                        .data
                        .mensagem
                        .trim()
                    : '';

            await this.apresentarAlerta({
                icon: 'error',
                title: 'Erro',

                text:
                    mensagemRecebida !== ''
                        ? mensagemRecebida
                        : 'Não foi possível obter o utilizador há mais tempo sem nomeação.',
            });
        } finally {
            this.reporEstadoInteracao(
                estadoAnterior,
            );

            this.emPedidoMaisAntigo =
                false;
        }
    }

    /**
     * Obtém o estado dos controlos antes de iniciar um pedido.
     *
     * @returns {{
     *     botaoAleatorioDesativado: boolean,
     *     botaoMaisAntigoDesativado: boolean,
     *     tomSelectBloqueado: boolean
     * }} Estado atual.
     *
     * @since 2.0.0
     */
    obterEstadoInteracao() {
        return {
            botaoAleatorioDesativado:
                this.botaoAleatorio
                    ?.disabled
                ?? false,

            botaoMaisAntigoDesativado:
                this.botaoMaisAntigo
                    ?.disabled
                ?? false,

            tomSelectBloqueado:
                this.tomSelect
                    .isLocked
                === true,
        };
    }

    /**
     * Bloqueia os controlos enquanto decorre o pedido.
     *
     * O Tom Select é bloqueado através da respetiva API sem desativar o
     * elemento `<select>`, preservando o valor numa eventual submissão.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    bloquearInteracao() {
        if (
            this.botaoAleatorio
            instanceof HTMLButtonElement
        ) {
            this.botaoAleatorio.disabled =
                true;
        }

        if (
            this.botaoMaisAntigo
            instanceof HTMLButtonElement
        ) {
            this.botaoMaisAntigo.disabled =
                true;

            this.botaoMaisAntigo.setAttribute(
                'aria-busy',
                'true',
            );
        }

        this.tomSelect.lock();
    }

    /**
     * Repõe o estado dos controlos existente antes do pedido.
     *
     * @param {{
     *     botaoAleatorioDesativado: boolean,
     *     botaoMaisAntigoDesativado: boolean,
     *     tomSelectBloqueado: boolean
     * }} estado Estado anterior.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    reporEstadoInteracao(estado) {
        if (
            this.botaoAleatorio
            instanceof HTMLButtonElement
        ) {
            this.botaoAleatorio.disabled =
                estado.botaoAleatorioDesativado;
        }

        if (
            this.botaoMaisAntigo
            instanceof HTMLButtonElement
        ) {
            this.botaoMaisAntigo.disabled =
                estado.botaoMaisAntigoDesativado;

            this.botaoMaisAntigo.removeAttribute(
                'aria-busy',
            );
        }

        if (!estado.tomSelectBloqueado) {
            this.tomSelect.unlock();
        }
    }

    /**
     * Apresenta uma mensagem através do SweetAlert2.
     *
     * @param {object} opcoes Opções transmitidas ao SweetAlert2.
     *
     * @returns {Promise<unknown>} Promessa da apresentação da mensagem.
     *
     * @since 2.0.0
     */
    apresentarAlerta(opcoes) {
        return Swal.fire(
            opcoes,
        );
    }

    /**
     * Obtém opcionalmente um botão através de um seletor CSS.
     *
     * @param {string|null} seletor Seletor CSS.
     * @param {string} descricao Descrição utilizada em mensagens de erro.
     *
     * @returns {HTMLButtonElement|null} Botão encontrado.
     *
     * @throws {TypeError} Quando o seletor ou o elemento são inválidos.
     *
     * @since 2.0.0
     */
    obterBotaoOpcional(
        seletor,
        descricao,
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
                `O seletor do ${descricao} deve ser uma sequência de caracteres não vazia.`,
            );
        }

        const seletorNormalizado =
            seletor.trim();

        let elemento;

        try {
            elemento =
                document.querySelector(
                    seletorNormalizado,
                );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletorNormalizado}" é inválido.`,
            );
        }

        if (
            elemento !== null
            && !(elemento
                instanceof HTMLButtonElement)
        ) {
            throw new TypeError(
                `O ${descricao} deve ser um botão HTML válido.`,
            );
        }

        return elemento;
    }
}

export default SeletorNomeados;
