/**
 * Gere uma opção global que representa todas as permissões disponíveis.
 *
 * Quando a opção global é selecionada, as permissões individuais permanecem
 * visíveis e são apresentadas como incluídas, mas ficam desativadas. As
 * escolhas individuais existentes são memorizadas para que possam ser
 * restauradas quando a opção global for desmarcada.
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

        this.memorizarSelecoesIndividuais();

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

                this.memorizarSelecoesIndividuais();

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
     * As permissões individuais permanecem sempre visíveis. Quando a permissão
     * global está ativa, são apresentadas como incluídas e ficam desativadas.
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
        const bloquearIndividuais =
            this.campoTodas.checked;

        this.itensIndividuais.forEach(
            (item, indice) => {
                const campo =
                    this.camposIndividuais[
                        indice
                    ];

                item.hidden =
                    false;

                this.atualizarCampo(
                    campo,
                    bloquearIndividuais,
                    restaurarSelecoes,
                );
            },
        );

        this.sincronizarCamposPreservados();
    }

    /**
     * Atualiza um campo de permissão individual.
     *
     * Quando a permissão global está ativa, o campo é apresentado como
     * selecionado para indicar que essa comunicação está incluída, mas fica
     * desativado. Ao remover a permissão global, são restauradas as escolhas
     * específicas memorizadas anteriormente.
     *
     * @param {HTMLInputElement} campo Campo atualizado.
     * @param {boolean} bloquear Indicação de bloqueio pela permissão global.
     * @param {boolean} restaurarSelecao Indicação de restauro.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarCampo(
        campo,
        bloquear,
        restaurarSelecao,
    ) {
        if (bloquear) {
            campo.checked =
                true;

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
     * Sincroniza os campos ocultos utilizados para preservar a submissão das
     * escolhas individuais enquanto a permissão global está ativa.
     *
     * As checkboxes individuais desativadas não são submetidas pelo navegador.
     * Por esse motivo, cada escolha específica memorizada é representada por um
     * campo oculto com o mesmo nome e valor. Quando a permissão global deixa de
     * estar ativa, esses campos deixam de ser necessários e são removidos.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    sincronizarCamposPreservados() {
        const formulario =
            this.campoTodas.form;

        if (!(formulario instanceof HTMLFormElement)) {
            return;
        }

        formulario
            .querySelectorAll(
                'input[type="hidden"][data-permissao-email-preservada="true"]',
            )
            .forEach(
                (campo) => {
                    campo.remove();
                },
            );

        if (!this.campoTodas.checked) {
            return;
        }

        this.selecoesAnteriores.forEach(
            (campo) => {
                const campoPreservado =
                    document.createElement(
                        'input',
                    );

                campoPreservado.type =
                    'hidden';

                campoPreservado.name =
                    campo.name;

                campoPreservado.value =
                    campo.value;

                campoPreservado.dataset
                    .permissaoEmailPreservada =
                    'true';

                formulario.append(
                    campoPreservado,
                );
            },
        );
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
