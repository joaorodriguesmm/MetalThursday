/**
 * Gere a adição, a remoção e a configuração dinâmica de secções.
 *
 * @since 1.0.0
 * @version 3.0.0
 */
class GestorSeccoes {
    /**
     * Marcador utilizado pelo modelo HTML para representar o índice.
     *
     * @type {string}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    static MARCADOR_INDICE =
        '__INDICE_SECCAO__';

    /**
     * Cria um gestor de secções dinâmicas.
     *
     * @param {string} seletorContentor
     *     Seletor CSS do contentor principal.
     * @param {string} seletorBotaoAdicionar
     *     Seletor CSS do botão de adição.
     * @param {string} seletorModelo
     *     Seletor CSS do modelo da secção.
     * @param {((elemento: HTMLElement) => void)|null} aoAdicionarSeccao
     *     Função executada depois de uma secção ser adicionada.
     *
     * @throws {TypeError} Quando algum argumento é inválido.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    constructor(
        seletorContentor,
        seletorBotaoAdicionar,
        seletorModelo,
        aoAdicionarSeccao = null,
    ) {
        if (
            aoAdicionarSeccao !== null
            && typeof aoAdicionarSeccao !== 'function'
        ) {
            throw new TypeError(
                'A função executada após a adição da secção é inválida.',
            );
        }

        this.contentor = this.obterElemento(
            seletorContentor,
            'O seletor do contentor das secções é obrigatório.',
        );

        this.botaoAdicionar = this.obterElemento(
            seletorBotaoAdicionar,
            'O seletor do botão de adição é obrigatório.',
        );

        this.modelo = this.obterElemento(
            seletorModelo,
            'O seletor do modelo da secção é obrigatório.',
        );

        this.aoAdicionarSeccao =
            aoAdicionarSeccao;

        this.indiceSeguinte =
            this.calcularProximoIndice();

        this.iniciado =
            false;

        this.aoClicarAdicionar = (evento) => {
            evento.preventDefault();

            this.adicionar();
        };

        this.aoClicarContentor = (evento) => {
            this.tratarClique(
                evento,
            );
        };

        this.aoAlterarContentor = (evento) => {
            this.tratarAlteracao(
                evento,
            );
        };

        if (this.estaAtivo()) {
            this.iniciar();
        }
    }

    /**
     * Verifica se todos os elementos obrigatórios estão disponíveis.
     *
     * @returns {boolean} Verdadeiro quando o gestor pode funcionar.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    estaAtivo() {
        return this.contentor instanceof HTMLElement
            && this.botaoAdicionar instanceof HTMLButtonElement
            && this.modelo instanceof HTMLTemplateElement;
    }

    /**
     * Inicia os eventos do gestor e normaliza as secções existentes.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    iniciar() {
        if (
            !this.estaAtivo()
            || this.iniciado
        ) {
            return;
        }

        this.botaoAdicionar.addEventListener(
            'click',
            this.aoClicarAdicionar,
        );

        this.contentor.addEventListener(
            'click',
            this.aoClicarContentor,
        );

        this.contentor.addEventListener(
            'change',
            this.aoAlterarContentor,
        );

        this.contentor
            .querySelectorAll(
                '.seletor-tipo-seccao',
            )
            .forEach((selecao) => {
                if (
                    selecao instanceof HTMLSelectElement
                ) {
                    this.atualizarEstadoSeccao(
                        selecao,
                    );
                }
            });

        this.iniciado =
            true;
    }

    /**
     * Adiciona uma nova secção ao contentor.
     *
     * @returns {HTMLElement|null} Secção criada ou nulo.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    adicionar() {
        if (!this.estaAtivo()) {
            return null;
        }

        const conteudoModelo =
            this.modelo.innerHTML.replaceAll(
                GestorSeccoes.MARCADOR_INDICE,
                String(
                    this.indiceSeguinte,
                ),
            );

        if (
            conteudoModelo.includes(
                GestorSeccoes.MARCADOR_INDICE,
            )
        ) {
            throw new Error(
                'Não foi possível substituir o índice do modelo da secção.',
            );
        }

        const modeloTemporario =
            document.createElement(
                'template',
            );

        modeloTemporario.innerHTML =
            conteudoModelo.trim();

        const fragmento =
            modeloTemporario.content.cloneNode(
                true,
            );

        const novaSeccao =
            fragmento.querySelector(
                '.item-seccao',
            );

        if (!(novaSeccao instanceof HTMLElement)) {
            throw new Error(
                'O modelo não contém um item de secção válido.',
            );
        }

        novaSeccao.dataset.indiceSeccao =
            String(
                this.indiceSeguinte,
            );

        this.contentor.append(
            fragmento,
        );

        this.indiceSeguinte += 1;

        const selecaoTipo =
            novaSeccao.querySelector(
                '.seletor-tipo-seccao',
            );

        if (
            selecaoTipo instanceof HTMLSelectElement
        ) {
            this.atualizarEstadoSeccao(
                selecaoTipo,
            );
        }

        if (this.aoAdicionarSeccao !== null) {
            this.aoAdicionarSeccao(
                novaSeccao,
            );
        }

        return novaSeccao;
    }

    /**
     * Trata os cliques ocorridos dentro do contentor.
     *
     * @param {MouseEvent} evento Evento de clique.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    tratarClique(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botaoRemover =
            evento.target.closest(
                '.botao-remover-seccao',
            );

        if (
            !(botaoRemover instanceof HTMLButtonElement)
        ) {
            return;
        }

        evento.preventDefault();

        this.remover(
            botaoRemover,
        );
    }

    /**
     * Trata as alterações ocorridas dentro do contentor.
     *
     * @param {Event} evento Evento de alteração.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    tratarAlteracao(evento) {
        const selecaoTipo =
            evento.target;

        if (
            !(selecaoTipo instanceof HTMLSelectElement)
            || !selecaoTipo.classList.contains(
                'seletor-tipo-seccao',
            )
        ) {
            return;
        }

        this.atualizarEstadoSeccao(
            selecaoTipo,
        );
    }

    /**
     * Remove uma secção e destrói as respetivas instâncias Tom Select.
     *
     * @param {HTMLButtonElement} botaoRemover
     *     Botão que solicitou a remoção.
     *
     * @returns {boolean} Indica se foi removida uma secção.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    remover(botaoRemover) {
        const seccao =
            botaoRemover.closest(
                '.item-seccao',
            );

        if (!(seccao instanceof HTMLElement)) {
            return false;
        }

        seccao
            .querySelectorAll(
                'select.tomselected',
            )
            .forEach((elemento) => {
                const instancia =
                    elemento.tomselect;

                if (
                    instancia
                    && typeof instancia.destroy
                        === 'function'
                ) {
                    instancia.destroy();
                }
            });

        seccao.remove();

        return true;
    }

    /**
     * Atualiza a visibilidade e o estado dos detalhes de uma secção.
     *
     * @param {HTMLSelectElement} selecaoTipo
     *     Campo do tipo de secção.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    atualizarEstadoSeccao(
        selecaoTipo,
    ) {
        const seccao =
            selecaoTipo.closest(
                '.item-seccao',
            );

        if (!(seccao instanceof HTMLElement)) {
            return;
        }

        const opcaoSelecionada =
            selecaoTipo.options[
                selecaoTipo.selectedIndex
            ]
            ?? null;

        const exigeDetalhes =
            opcaoSelecionada
                ?.dataset
                .exigeDetalhes
            === 'true';

        const linhasDetalhes =
            seccao.querySelectorAll(
                '.linha-detalhes-seccao',
            );

        linhasDetalhes.forEach(
            (linhaDetalhes) => {
                if (
                    !(linhaDetalhes instanceof HTMLElement)
                ) {
                    return;
                }

                linhaDetalhes.hidden =
                    !exigeDetalhes;

                linhaDetalhes.setAttribute(
                    'aria-hidden',
                    String(
                        !exigeDetalhes,
                    ),
                );

                linhaDetalhes
                    .querySelectorAll(
                        'input, select, textarea, button',
                    )
                    .forEach((elemento) => {
                        if (
                            elemento instanceof HTMLInputElement
                            || elemento instanceof HTMLSelectElement
                            || elemento instanceof HTMLTextAreaElement
                            || elemento instanceof HTMLButtonElement
                        ) {
                            elemento.disabled =
                                !exigeDetalhes;
                        }
                    });
            },
        );

        [
            'select[name$="[banda_id]"]',
            'input[name$="[titulo]"]',
            'input[name$="[ligacao]"]',
            'input[name$="[ano]"]',
        ].forEach((seletor) => {
            const campo =
                seccao.querySelector(
                    seletor,
                );

            if (
                campo instanceof HTMLInputElement
                || campo instanceof HTMLSelectElement
            ) {
                campo.required =
                    exigeDetalhes;
            }
        });

        const indicadorTituloObrigatorio =
            seccao.querySelector(
                '.indicador-titulo-obrigatorio',
            );

        if (
            indicadorTituloObrigatorio
            instanceof HTMLElement
        ) {
            indicadorTituloObrigatorio.hidden =
                !exigeDetalhes;
        }
    }

    /**
     * Calcula o próximo índice disponível para uma nova secção.
     *
     * @returns {number} Próximo índice.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    calcularProximoIndice() {
        if (!(this.contentor instanceof HTMLElement)) {
            return 0;
        }

        let maiorIndice =
            -1;

        this.contentor
            .querySelectorAll(
                '.item-seccao',
            )
            .forEach((seccao) => {
                if (!(seccao instanceof HTMLElement)) {
                    return;
                }

                const indiceDados =
                    Number.parseInt(
                        seccao.dataset.indiceSeccao
                        ?? '',
                        10,
                    );

                if (
                    Number.isInteger(
                        indiceDados,
                    )
                    && indiceDados >= 0
                ) {
                    maiorIndice = Math.max(
                        maiorIndice,
                        indiceDados,
                    );
                }

                seccao
                    .querySelectorAll(
                        '[name]',
                    )
                    .forEach((campo) => {
                        const nome =
                            campo.getAttribute(
                                'name',
                            );

                        const correspondencia =
                            nome?.match(
                                /^seccoes\[(\d+)]/,
                            )
                            ?? null;

                        if (correspondencia === null) {
                            return;
                        }

                        const indiceNome =
                            Number.parseInt(
                                correspondencia[1],
                                10,
                            );

                        if (
                            Number.isInteger(
                                indiceNome,
                            )
                            && indiceNome >= 0
                        ) {
                            maiorIndice = Math.max(
                                maiorIndice,
                                indiceNome,
                            );
                        }
                    });
            });

        return maiorIndice + 1;
    }

    /**
     * Obtém um elemento através de um seletor CSS obrigatório.
     *
     * @param {unknown} seletor Seletor CSS.
     * @param {string} mensagem
     *     Mensagem utilizada quando o seletor está vazio.
     *
     * @returns {Element|null} Elemento encontrado.
     *
     * @throws {TypeError} Quando o seletor é inválido.
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
            throw new TypeError(
                mensagem,
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
     * Remove os eventos associados ao gestor.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        if (
            !this.estaAtivo()
            || !this.iniciado
        ) {
            return;
        }

        this.botaoAdicionar.removeEventListener(
            'click',
            this.aoClicarAdicionar,
        );

        this.contentor.removeEventListener(
            'click',
            this.aoClicarContentor,
        );

        this.contentor.removeEventListener(
            'change',
            this.aoAlterarContentor,
        );

        this.iniciado =
            false;
    }
}

export default GestorSeccoes;
