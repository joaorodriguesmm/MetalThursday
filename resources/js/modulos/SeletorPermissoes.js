/**
 * Gere uma opção global que representa todas as permissões disponíveis.
 *
 * Quando a opção global é selecionada, as permissões individuais são
 * ocultadas, desmarcadas e desativadas para não serem submetidas. Quando a
 * opção global é desmarcada, as escolhas individuais anteriores são
 * restauradas.
 *
 * @since 1.0.0
 */
class SeletorPermissoes {
    /**
     * Cria e inicializa o seletor de permissões.
     *
     * @param {string|HTMLInputElement} campoTodasOuSeletor
     *     Campo que representa todas as permissões ou respetivo seletor.
     * @param {string|Iterable<HTMLElement>} itensOuSeletor
     *     Itens das permissões ou respetivo seletor.
     *
     * @throws {TypeError} Quando os elementos recebidos não são válidos.
     *
     * @since 1.0.0
     */
    constructor(
        campoTodasOuSeletor,
        itensOuSeletor,
    ) {
        this.campoTodas =
            this.obterCampoTodas(
                campoTodasOuSeletor,
            );

        this.itensIndividuais =
            this.obterItensIndividuais(
                itensOuSeletor,
            );

        this.camposIndividuais =
            this.itensIndividuais.map(
                (item) =>
                    this.obterCampoDoItem(
                        item,
                    ),
            );

        /**
         * Checkboxes individuais seleccionadas antes de activar a permissão
         * global.
         *
         * @type {Set<HTMLInputElement>}
         *
         * @since 2.0.0
         */
        this.selecoesAnteriores =
            new Set();

        if (!this.campoTodas.checked) {
            this.memorizarSelecoesIndividuais();
        }

        this.campoTodas.addEventListener(
            'change',
            () => {
                this.tratarAlteracao();
            },
        );

        const formulario =
            this.campoTodas.form;

        if (
            formulario
            instanceof HTMLFormElement
        ) {
            formulario.addEventListener(
                'reset',
                () => {
                    this.tratarReposicao();
                },
            );
        }

        this.atualizarEstado({
            restaurarSelecoes: false,
        });
    }

    /**
     * Processa a alteração da opção global.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    tratarAlteracao() {
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
     * O evento `reset` é emitido antes de o navegador terminar a reposição
     * dos valores dos campos. A sincronização é, por isso, executada numa
     * tarefa posterior.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarReposicao() {
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
     * @param {object} opcoes Opções da atualização.
     * @param {boolean} opcoes.restaurarSelecoes
     *     Indica se as escolhas anteriores devem ser restauradas.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    atualizarEstado({
        restaurarSelecoes,
    }) {
        const ocultarIndividuais =
            this.campoTodas.checked;

        this.itensIndividuais.forEach(
            (item, indice) => {
                const campo =
                    this.camposIndividuais[
                        indice
                    ];

                item.hidden =
                    ocultarIndividuais;

                this.atualizarCampo(
                    campo,
                    ocultarIndividuais,
                    restaurarSelecoes,
                );
            },
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
     */
    atualizarCampo(
        campo,
        ocultar,
        restaurarSelecao,
    ) {
        if (ocultar) {
            campo.checked =
                false;

            campo.disabled =
                true;

            return;
        }

        campo.disabled =
            false;

        if (restaurarSelecao) {
            campo.checked =
                this.selecoesAnteriores.has(
                    campo,
                );
        }
    }

    /**
     * Memoriza as permissões individuais atualmente selecionadas.
     *
     * São guardadas referências às próprias checkboxes em vez dos respetivos
     * valores, evitando depender da unicidade desses valores.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    memorizarSelecoesIndividuais() {
        this.selecoesAnteriores.clear();

        this.camposIndividuais.forEach(
            (campo) => {
                if (campo.checked) {
                    this.selecoesAnteriores.add(
                        campo,
                    );
                }
            },
        );
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
     */
    obterCampoTodas(campoOuSeletor) {
        let campo =
            campoOuSeletor;

        if (
            typeof campoOuSeletor
            === 'string'
        ) {
            const seletor =
                campoOuSeletor.trim();

            if (seletor === '') {
                throw new TypeError(
                    'O seletor da permissão global não pode estar vazio.',
                );
            }

            try {
                campo =
                    document.querySelector(
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
     */
    obterItensIndividuais(itensOuSeletor) {
        let elementos;

        if (
            typeof itensOuSeletor
            === 'string'
        ) {
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
            elementos =
                Array.from(
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
                    !(elemento
                        instanceof HTMLElement),
            )
        ) {
            throw new TypeError(
                'Todos os itens das permissões devem ser elementos HTML válidos.',
            );
        }

        return Array.from(
            new Set(
                elementos,
            ),
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
     */
    obterCampoDoItem(item) {
        const campo =
            item.querySelector(
                'input[type="checkbox"]',
            );

        if (
            !(campo
                instanceof HTMLInputElement)
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
