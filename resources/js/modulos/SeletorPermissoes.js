/**
 * Gere uma opção global que representa todas as permissões disponíveis.
 *
 * Quando a opção global é selecionada, as permissões individuais são
 * ocultadas, desmarcadas e desativadas para não serem submetidas. Quando a
 * opção global é desmarcada, as escolhas individuais anteriores são
 * restauradas.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class SeletorPermissoes {
    /**
     * Cria o seletor de permissões.
     *
     * @param {string|HTMLInputElement} campoTodasOuSeletor - Campo que
     * representa todas as permissões ou respetivo seletor.
     * @param {string|Iterable<HTMLElement>} itensOuSeletor - Itens das
     * permissões ou respetivo seletor.
     *
     * @throws {TypeError} Quando os elementos recebidos não são válidos.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor(
        campoTodasOuSeletor,
        itensOuSeletor,
    ) {
        /**
         * Campo que representa todas as permissões.
         *
         * @type {HTMLInputElement}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.campoTodas = this.obterCampoTodas(
            campoTodasOuSeletor,
        );

        /**
         * Elementos que contêm as permissões individuais.
         *
         * O item da própria permissão global é excluído automaticamente.
         *
         * @type {Array<HTMLElement>}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.itensIndividuais = this.obterItensIndividuais(
            itensOuSeletor,
        );

        /**
         * Campos das permissões individuais.
         *
         * @type {Array<HTMLInputElement>}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.camposIndividuais = this.itensIndividuais.map(
            (item) => this.obterCampoDoItem(item),
        );

        /**
         * Valores selecionados antes da ativação da opção global.
         *
         * @type {Set<string>}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.selecoesAnteriores = new Set();

        /**
         * Formulário ao qual pertence o seletor.
         *
         * @type {HTMLFormElement|null}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.formulario = this.campoTodas.form;

        /**
         * Referência estável do manipulador de alteração.
         *
         * @type {(evento: Event) => void}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.manipularAlteracao =
            this.manipularAlteracao.bind(this);

        /**
         * Referência estável do manipulador de reposição.
         *
         * @type {() => void}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.manipularReposicao =
            this.manipularReposicao.bind(this);

        this.registarEstadoInicial();
        this.configurarEventos();
        this.atualizarEstado({
            restaurarSelecoes: false,
        });
    }

    /**
     * Remove os eventos configurados pelo módulo.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        this.campoTodas.removeEventListener(
            'change',
            this.manipularAlteracao,
        );

        if (this.formulario instanceof HTMLFormElement) {
            this.formulario.removeEventListener(
                'reset',
                this.manipularReposicao,
            );
        }
    }

    /**
     * Configura os eventos necessários.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    configurarEventos() {
        this.campoTodas.addEventListener(
            'change',
            this.manipularAlteracao,
        );

        if (this.formulario instanceof HTMLFormElement) {
            this.formulario.addEventListener(
                'reset',
                this.manipularReposicao,
            );
        }
    }

    /**
     * Regista as seleções apresentadas inicialmente.
     *
     * Quando a opção global já está selecionada, não existem escolhas
     * individuais para preservar.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    registarEstadoInicial() {
        if (this.campoTodas.checked) {
            return;
        }

        this.memorizarSelecoesIndividuais();
    }

    /**
     * Processa a alteração da opção global.
     *
     * @param {Event} evento - Evento de alteração.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    manipularAlteracao(evento) {
        if (evento.target !== this.campoTodas) {
            return;
        }

        if (this.campoTodas.checked) {
            this.memorizarSelecoesIndividuais();

            this.atualizarEstado({
                restaurarSelecoes: false,
            });

            return;
        }

        this.atualizarEstado({
            restaurarSelecoes: true,
        });
    }

    /**
     * Processa a reposição do formulário.
     *
     * O navegador repõe os valores dos campos depois do evento `reset`.
     * A atualização é, por isso, adiada para o ciclo seguinte.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    manipularReposicao() {
        window.setTimeout(
            () => {
                this.selecoesAnteriores.clear();

                if (!this.campoTodas.checked) {
                    this.memorizarSelecoesIndividuais();
                }

                this.atualizarEstado({
                    restaurarSelecoes: false,
                });
            },
            0,
        );
    }

    /**
     * Atualiza a apresentação e o estado dos campos individuais.
     *
     * @param {Object} opcoes - Opções da atualização.
     * @param {boolean} opcoes.restaurarSelecoes - Indica se as escolhas
     * anteriores devem ser restauradas.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    atualizarEstado({
        restaurarSelecoes,
    }) {
        const ocultarIndividuais =
            this.campoTodas.checked;

        this.itensIndividuais.forEach(
            (item, indice) => {
                const campo = this.camposIndividuais[
                    indice
                ];

                this.atualizarItem(
                    item,
                    ocultarIndividuais,
                );

                this.atualizarCampo(
                    campo,
                    ocultarIndividuais,
                    restaurarSelecoes,
                );
            },
        );
    }

    /**
     * Atualiza a apresentação de um item individual.
     *
     * @param {HTMLElement} item - Elemento da permissão.
     * @param {boolean} ocultar - Indicação de ocultação.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    atualizarItem(
        item,
        ocultar,
    ) {
        item.hidden = ocultar;

        item.classList.toggle(
            'd-none',
            ocultar,
        );

        item.setAttribute(
            'aria-hidden',
            ocultar ? 'true' : 'false',
        );
    }

    /**
     * Atualiza um campo de permissão individual.
     *
     * Os campos ocultos são também desativados, impedindo a sua submissão e
     * remoção através do teclado.
     *
     * @param {HTMLInputElement} campo - Campo atualizado.
     * @param {boolean} ocultar - Indicação de ocultação.
     * @param {boolean} restaurarSelecao - Indicação de restauro.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    atualizarCampo(
        campo,
        ocultar,
        restaurarSelecao,
    ) {
        if (ocultar) {
            campo.checked = false;
            campo.disabled = true;

            return;
        }

        campo.disabled = false;

        if (restaurarSelecao) {
            campo.checked = this.selecoesAnteriores.has(
                campo.value,
            );
        }
    }

    /**
     * Memoriza as permissões individuais selecionadas.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    memorizarSelecoesIndividuais() {
        this.selecoesAnteriores.clear();

        this.camposIndividuais.forEach((campo) => {
            if (campo.checked) {
                this.selecoesAnteriores.add(
                    campo.value,
                );
            }
        });
    }

    /**
     * Obtém o campo que representa todas as permissões.
     *
     * @param {string|HTMLInputElement} campoOuSeletor - Campo ou seletor.
     *
     * @return {HTMLInputElement} Campo encontrado.
     *
     * @throws {TypeError} Quando o campo não é uma checkbox.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterCampoTodas(campoOuSeletor) {
        const campo = typeof campoOuSeletor === 'string'
            ? document.querySelector(campoOuSeletor)
            : campoOuSeletor;

        if (
            !(campo instanceof HTMLInputElement)
            || campo.type !== 'checkbox'
        ) {
            throw new TypeError(
                'A permissão global deve ser representada por uma checkbox válida.',
            );
        }

        return campo;
    }

    /**
     * Obtém os itens das permissões individuais.
     *
     * @param {string|Iterable<HTMLElement>} itensOuSeletor - Itens ou seletor.
     *
     * @return {Array<HTMLElement>} Itens individuais.
     *
     * @throws {TypeError} Quando os itens não são válidos.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterItensIndividuais(itensOuSeletor) {
        let elementos;

        if (typeof itensOuSeletor === 'string') {
            elementos = Array.from(
                document.querySelectorAll(
                    itensOuSeletor,
                ),
            );
        } else if (
            itensOuSeletor !== null
            && typeof itensOuSeletor[
                Symbol.iterator
            ] === 'function'
        ) {
            elementos = Array.from(
                itensOuSeletor,
            );
        } else {
            throw new TypeError(
                'Os itens das permissões devem ser indicados através de um seletor ou coleção.',
            );
        }

        const itens = elementos.filter(
            (elemento) => (
                elemento instanceof HTMLElement
                && !elemento.contains(this.campoTodas)
            ),
        );

        if (
            itens.length !== elementos.length
                - (
                    elementos.some(
                        (elemento) => (
                            elemento instanceof HTMLElement
                            && elemento.contains(
                                this.campoTodas,
                            )
                        ),
                    )
                        ? 1
                        : 0
                )
        ) {
            throw new TypeError(
                'Foi recebido um item de permissão inválido.',
            );
        }

        return itens;
    }

    /**
     * Obtém a checkbox contida num item individual.
     *
     * @param {HTMLElement} item - Item da permissão.
     *
     * @return {HTMLInputElement} Campo encontrado.
     *
     * @throws {TypeError} Quando o item não possui uma checkbox válida.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterCampoDoItem(item) {
        const campo = item.querySelector(
            'input[type="checkbox"]',
        );

        if (
            !(campo instanceof HTMLInputElement)
            || campo === this.campoTodas
        ) {
            throw new TypeError(
                'Cada item individual deve conter uma checkbox válida.',
            );
        }

        return campo;
    }
}

export default SeletorPermissoes;
