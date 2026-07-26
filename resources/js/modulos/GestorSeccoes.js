/**
 * Gere a adição, a remoção e a configuração dinâmica de secções.
 *
 * Os seletores CSS permanecem temporariamente inalterados por corresponderem
 * à estrutura atual das views.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class GestorSeccoes {
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
     * @version 2.0.0
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

        this.aoAdicionarSeccao = aoAdicionarSeccao;
        this.indiceSeguinte = this.calcularProximoIndice();
        this.iniciado = false;

        this.aoClicarAdicionar = (evento) => {
            evento.preventDefault();
            this.adicionar();
        };

        this.aoClicarContentor = (evento) => {
            this.tratarClique(evento);
        };

        this.aoAlterarContentor = (evento) => {
            this.tratarAlteracao(evento);
        };

        if (this.estaAtivo()) {
            this.iniciar();
        }
    }

    /**
     * Verifica se todos os elementos obrigatórios estão disponíveis.
     *
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    estaAtivo() {
        return this.contentor instanceof HTMLElement
            && this.botaoAdicionar instanceof HTMLElement
            && this.modelo instanceof HTMLElement;
    }

    /**
     * Inicia os eventos do gestor.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        if (!this.estaAtivo() || this.iniciado) {
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
            .querySelectorAll('.section-type-select')
            .forEach((selecao) => {
                if (selecao instanceof HTMLSelectElement) {
                    this.atualizarEstadoSeccao(selecao);
                }
            });

        this.iniciado = true;
    }

    /**
     * Adiciona uma nova secção ao contentor.
     *
     * @returns {HTMLElement|null} Elemento criado.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    adicionar() {
        if (!this.estaAtivo()) {
            return null;
        }

        const conteudoModelo = this.modelo.innerHTML.replace(
            /__INDEX__/g,
            String(this.indiceSeguinte),
        );

        const modeloTemporario = document.createElement('template');

        modeloTemporario.innerHTML = conteudoModelo.trim();

        const fragmento = modeloTemporario.content.cloneNode(true);
        const novaSeccao = fragmento.querySelector('.section-item');

        if (!(novaSeccao instanceof HTMLElement)) {
            return null;
        }

        novaSeccao.dataset.indiceSeccao = String(
            this.indiceSeguinte,
        );

        this.contentor.append(fragmento);
        this.indiceSeguinte += 1;

        const selecaoTipo = novaSeccao.querySelector(
            '.section-type-select',
        );

        if (selecaoTipo instanceof HTMLSelectElement) {
            this.atualizarEstadoSeccao(selecaoTipo);
        }

        if (this.aoAdicionarSeccao !== null) {
            this.aoAdicionarSeccao(novaSeccao);
        }

        return novaSeccao;
    }

    /**
     * Trata os cliques ocorridos dentro do contentor.
     *
     * @param {MouseEvent} evento Evento de clique.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    tratarClique(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botaoRemover = evento.target.closest(
            '.remove-section-btn',
        );

        if (!(botaoRemover instanceof HTMLElement)) {
            return;
        }

        evento.preventDefault();

        this.remover(botaoRemover);
    }

    /**
     * Trata as alterações ocorridas dentro do contentor.
     *
     * @param {Event} evento Evento de alteração.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    tratarAlteracao(evento) {
        const selecaoTipo = evento.target;

        if (
            !(selecaoTipo instanceof HTMLSelectElement)
            || !selecaoTipo.classList.contains(
                'section-type-select',
            )
        ) {
            return;
        }

        this.atualizarEstadoSeccao(selecaoTipo);
    }

    /**
     * Remove uma secção e destrói as respetivas instâncias do Tom Select.
     *
     * @param {HTMLElement} botaoRemover
     *     Botão que solicitou a remoção.
     *
     * @returns {boolean} Indica se a secção foi removida.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    remover(botaoRemover) {
        const seccao = botaoRemover.closest(
            '.section-item',
        );

        if (!(seccao instanceof HTMLElement)) {
            return false;
        }

        seccao
            .querySelectorAll('.tomselected')
            .forEach((elemento) => {
                if (
                    typeof elemento.tomselect?.destroy
                    === 'function'
                ) {
                    elemento.tomselect.destroy();
                }
            });

        seccao.remove();

        return true;
    }

    /**
     * Atualiza os campos visíveis e obrigatórios de uma secção.
     *
     * @param {HTMLSelectElement} selecaoTipo
     *     Campo do tipo de secção.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    atualizarEstadoSeccao(selecaoTipo) {
        const seccao = selecaoTipo.closest(
            '.section-item',
        );

        if (!(seccao instanceof HTMLElement)) {
            return;
        }

        const opcaoSelecionada =
            selecaoTipo.options[
                selecaoTipo.selectedIndex
            ]
            ?? null;

        const temDetalhes = [
            'true',
            '1',
        ].includes(
            opcaoSelecionada?.dataset.hasDetails
            ?? '',
        );

        seccao
            .querySelectorAll(
                '.section-details-row-1, .section-details-row-2',
            )
            .forEach((contentorDetalhes) => {
                if (
                    !(
                        contentorDetalhes
                        instanceof HTMLElement
                    )
                ) {
                    return;
                }

                contentorDetalhes.style.display =
                    temDetalhes
                        ? ''
                        : 'none';

                contentorDetalhes.setAttribute(
                    'aria-hidden',
                    String(!temDetalhes),
                );
            });

        seccao
            .querySelectorAll(
                [
                    '.section-details-row-1 input',
                    '.section-details-row-1 select',
                    '.section-details-row-1 textarea',
                    '.section-details-row-2 input',
                    '.section-details-row-2 select',
                    '.section-details-row-2 textarea',
                ].join(', '),
            )
            .forEach((campo) => {
                if (
                    campo instanceof HTMLInputElement
                    || campo instanceof HTMLSelectElement
                    || campo instanceof HTMLTextAreaElement
                ) {
                    campo.required = temDetalhes;
                    campo.disabled = !temDetalhes;
                }
            });

        const indicadorTituloObrigatorio =
            seccao.querySelector(
                '.title-required-indicator',
            );

        if (
            indicadorTituloObrigatorio
            instanceof HTMLElement
        ) {
            indicadorTituloObrigatorio.style.display =
                temDetalhes
                    ? ''
                    : 'none';
        }
    }

    /**
     * Calcula o próximo índice disponível para uma nova secção.
     *
     * @returns {number}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    calcularProximoIndice() {
        if (!(this.contentor instanceof HTMLElement)) {
            return 0;
        }

        let maiorIndice = -1;

        this.contentor
            .querySelectorAll('.section-item')
            .forEach((seccao) => {
                if (!(seccao instanceof HTMLElement)) {
                    return;
                }

                const indiceDados = Number.parseInt(
                    seccao.dataset.indiceSeccao
                    ?? seccao.dataset.sectionIndex
                    ?? '',
                    10,
                );

                if (Number.isInteger(indiceDados)) {
                    maiorIndice = Math.max(
                        maiorIndice,
                        indiceDados,
                    );
                }

                seccao
                    .querySelectorAll('[name]')
                    .forEach((campo) => {
                        const correspondencia =
                            campo
                                .getAttribute('name')
                                ?.match(/\[(\d+)]/);

                        if (!correspondencia) {
                            return;
                        }

                        const indiceNome =
                            Number.parseInt(
                                correspondencia[1],
                                10,
                            );

                        if (Number.isInteger(indiceNome)) {
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
     * @returns {Element|null}
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
     * Remove os eventos associados ao gestor.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        if (!this.estaAtivo() || !this.iniciado) {
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

        this.iniciado = false;
    }
}

export default GestorSeccoes;
