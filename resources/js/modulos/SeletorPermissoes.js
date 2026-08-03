/**
 * Gere uma opção global que representa todas as permissões disponíveis.
 *
 * Quando a opção global é selecionada, as permissões individuais são
 * ocultadas, desmarcadas e desativadas para não serem submetidas. Quando a
 * opção global é desmarcada, as escolhas individuais anteriores são
 * restauradas.
 *
 * @since 1.0.0
 * @version 2.1.0
 */
class SeletorPermissoes {
    /**
     * Cria o seletor de permissões.
     *
     * @param {string|HTMLInputElement} campoTodasOuSeletor
     *     Campo que representa todas as permissões ou respetivo seletor.
     * @param {string|Iterable<HTMLElement>} itensOuSeletor
     *     Itens das permissões ou respetivo seletor.
     *
     * @throws {TypeError} Quando os elementos recebidos não são válidos.
     *
     * @since 1.0.0
     * @version 2.1.0
     */
    constructor(
        campoTodasOuSeletor,
        itensOuSeletor,
    ) {
        this.campoTodas = this.obterCampoTodas(
            campoTodasOuSeletor,
        );

        this.itensIndividuais = this.obterItensIndividuais(
            itensOuSeletor,
        );

        this.camposIndividuais = this.itensIndividuais.map(
            (item) => this.obterCampoDoItem(item),
        );

        this.selecoesAnteriores = new Set();
        this.formulario = this.campoTodas.form;
        this.iniciado = false;
        this.identificadorReposicao = null;

        this.manipularAlteracao =
            this.manipularAlteracao.bind(this);

        this.manipularReposicao =
            this.manipularReposicao.bind(this);

        this.registarEstadoInicial();
        this.configurarEventos();

        this.atualizarEstado({
            restaurarSelecoes: false,
        });
    }

    /**
     * Configura os eventos necessários.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.1.0
     */
    configurarEventos() {
        if (this.iniciado) {
            return;
        }

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

        this.iniciado = true;
    }

    /**
     * Regista as seleções apresentadas inicialmente.
     *
     * Quando a opção global já está selecionada, não existem escolhas
     * individuais para preservar.
     *
     * @returns {void}
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
     * @param {Event} evento Evento de alteração.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    manipularAlteracao(evento) {
        if (evento.currentTarget !== this.campoTodas) {
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
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    manipularReposicao() {
        if (this.identificadorReposicao !== null) {
            window.clearTimeout(
                this.identificadorReposicao,
            );
        }

        this.identificadorReposicao = window.setTimeout(
            () => {
                this.identificadorReposicao = null;
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
     * @param {object} opcoes Opções da atualização.
     * @param {boolean} opcoes.restaurarSelecoes
     *     Indica se as escolhas anteriores devem ser restauradas.
     *
     * @returns {void}
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
                const campo =
                    this.camposIndividuais[indice];

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
     * @param {HTMLElement} item Elemento da permissão.
     * @param {boolean} ocultar Indicação de ocultação.
     *
     * @returns {void}
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
            ocultar
                ? 'true'
                : 'false',
        );
    }

    /**
     * Atualiza um campo de permissão individual.
     *
     * Os campos ocultos são também desativados, impedindo a sua submissão e
     * utilização através do teclado.
     *
     * @param {HTMLInputElement} campo Campo atualizado.
     * @param {boolean} ocultar Indicação de ocultação.
     * @param {boolean} restaurarSelecao Indicação de restauro.
     *
     * @returns {void}
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
     * @returns {void}
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
     * @param {string|HTMLInputElement} campoOuSeletor
     *     Campo ou seletor.
     *
     * @returns {HTMLInputElement} Campo encontrado.
     *
     * @throws {TypeError} Quando o seletor ou o campo não são válidos.
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    obterCampoTodas(campoOuSeletor) {
        let campo = campoOuSeletor;

        if (typeof campoOuSeletor === 'string') {
            const seletor =
                campoOuSeletor.trim();

            if (seletor === '') {
                throw new TypeError(
                    'O seletor da permissão global não pode estar vazio.',
                );
            }

            try {
                campo = document.querySelector(
                    seletor,
                );
            } catch {
                throw new TypeError(
                    `O seletor CSS "${seletor}" é inválido.`,
                );
            }
        }

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
     * O item da própria permissão global é excluído automaticamente.
     *
     * @param {string|Iterable<HTMLElement>} itensOuSeletor
     *     Itens ou seletor.
     *
     * @returns {Array<HTMLElement>} Itens individuais únicos.
     *
     * @throws {TypeError} Quando o seletor ou os itens não são válidos.
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    obterItensIndividuais(itensOuSeletor) {
        let elementos;

        if (typeof itensOuSeletor === 'string') {
            const seletor =
                itensOuSeletor.trim();

            if (seletor === '') {
                throw new TypeError(
                    'O seletor das permissões individuais não pode estar vazio.',
                );
            }

            try {
                elementos = Array.from(
                    document.querySelectorAll(
                        seletor,
                    ),
                );
            } catch {
                throw new TypeError(
                    `O seletor CSS "${seletor}" é inválido.`,
                );
            }
        } else if (
            itensOuSeletor !== null
            && itensOuSeletor !== undefined
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

        if (
            elementos.some(
                (elemento) =>
                    !(elemento instanceof HTMLElement),
            )
        ) {
            throw new TypeError(
                'Todos os itens das permissões devem ser elementos HTML válidos.',
            );
        }

        return Array.from(
            new Set(elementos),
        ).filter(
            (elemento) =>
                !elemento.contains(
                    this.campoTodas,
                ),
        );
    }

    /**
     * Obtém a checkbox contida num item individual.
     *
     * @param {HTMLElement} item Item da permissão.
     *
     * @returns {HTMLInputElement} Campo encontrado.
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

    /**
     * Remove os eventos configurados pelo módulo.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    destruir() {
        if (!this.iniciado) {
            return;
        }

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

        if (this.identificadorReposicao !== null) {
            window.clearTimeout(
                this.identificadorReposicao,
            );

            this.identificadorReposicao = null;
        }

        this.iniciado = false;
    }
}

export default SeletorPermissoes;
