import Tooltip from 'bootstrap/js/dist/tooltip';

/**
 * Gere a adição, a remoção e a configuração dinâmica de secções.
 *
 * @since 1.0.0
 */
class GestorSeccoes {
    /**
     * Regista gestores por contentor, botão e modelo.
     *
     * Evita duplicar os listeners e mantém o contador de índices
     * da primeira instância.
     *
     * @type {WeakMap<HTMLElement, WeakMap<HTMLButtonElement, WeakMap<HTMLTemplateElement, GestorSeccoes>>>}
     *
     * @since 2.0.0
     */
    static instanciasPorContentor = new WeakMap();

    /**
     * Marcador utilizado pelo modelo HTML para representar o índice.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static MARCADOR_INDICE = '__INDICE_SECCAO__';

    /**
     * Evento emitido depois de atualizar o estado de uma secção.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static EVENTO_ESTADO_ATUALIZADO =
        'metal-thursday:estado-seccao-atualizado';

    /**
     * Campos que são obrigatórios quando a secção exige detalhes.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static SELETOR_CAMPOS_DETALHES_OBRIGATORIOS = [
        'select[name$="[artista_id]"]',
        'input[name$="[titulo]"]',
        'input[name$="[ligacao]"]',
        'input[name$="[ano]"]',
    ].join(', ');

    /**
     * Controlos pertencentes às linhas condicionais de detalhes.
     *
     * Os campos gerados internamente pelo Tom Select não possuem `name` e
     * são geridos através da própria instância do componente.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static SELETOR_CONTROLOS_DETALHES = [
        'input[name]',
        'select[name]',
        'textarea[name]',
        'button',
    ].join(', ');

    /**
     * Cria e inicializa um gestor de secções dinâmicas.
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
     * @throws {TypeError} Quando algum argumento ou elemento é inválido.
     * @throws {Error} Quando o modelo ou os índices existentes são inválidos.
     *
     * @since 1.0.0
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
            HTMLElement,
            'contentor das secções',
        );

        this.botaoAdicionar = this.obterElemento(
            seletorBotaoAdicionar,
            HTMLButtonElement,
            'botão de adição de secções',
        );

        this.modelo = this.obterElemento(
            seletorModelo,
            HTMLTemplateElement,
            'modelo da secção',
        );

        this.validarModelo();

        const gestoresDoContentor =
            GestorSeccoes.instanciasPorContentor.get(
                this.contentor,
            );

        const gestoresDoBotao =
            gestoresDoContentor?.get(
                this.botaoAdicionar,
            );

        const gestorExistente =
            gestoresDoBotao?.get(
                this.modelo,
            );

        if (gestorExistente) {
            return gestorExistente;
        }

        this.aoAdicionarSeccao = aoAdicionarSeccao;

        /**
         * Índice utilizado pela próxima secção criada.
         *
         * @type {number}
         *
         * @since 2.0.0
         */
        this.indiceSeguinte =
            this.calcularProximoIndice();

        this.normalizarSeccoesExistentes();
        this.atualizarControlosOrdenacao();

        this.botaoAdicionar.addEventListener(
            'click',
            () => this.adicionar(),
        );

        this.contentor.addEventListener(
            'click',
            (evento) => this.tratarClique(evento),
        );

        this.contentor.addEventListener(
            'change',
            (evento) => this.tratarAlteracao(evento),
        );

        this.contentor.addEventListener(
            'input',
            (evento) => this.atualizarResumoPorEvento(evento),
        );

        // O evento `invalid` não propaga. Capturá-lo permite expandir
        // a secção antes de o navegador tentar focar o campo inválido.
        this.contentor.addEventListener(
            'invalid',
            (evento) => this.expandirCampoInvalido(evento),
            true,
        );

        let gestoresContentor =
            GestorSeccoes.instanciasPorContentor.get(
                this.contentor,
            );

        if (!gestoresContentor) {
            gestoresContentor = new WeakMap();

            GestorSeccoes.instanciasPorContentor.set(
                this.contentor,
                gestoresContentor,
            );
        }

        let gestoresBotao =
            gestoresContentor.get(
                this.botaoAdicionar,
            );

        if (!gestoresBotao) {
            gestoresBotao = new WeakMap();

            gestoresContentor.set(
                this.botaoAdicionar,
                gestoresBotao,
            );
        }

        gestoresBotao.set(
            this.modelo,
            this,
        );
    }

    /**
     * Normaliza o estado das secções inicialmente renderizadas.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    normalizarSeccoesExistentes() {
        this.contentor.querySelectorAll(
            '.seletor-tipo-seccao',
        ).forEach((selecao) => {
            if (selecao instanceof HTMLSelectElement) {
                this.atualizarEstadoSeccao(selecao);
            }
        });
    }

    /**
     * Anuncia a adicao ou remocao de uma seccao.
     *
     * @param {string} acao Operacao realizada.
     * @returns {void}
     *
     * @since 2.0.0
     */
    anunciarAlteracao(acao) {
        const estado = this.botaoAdicionar.parentElement?.querySelector(
            '[data-estado-seccoes]',
        );

        if (!(estado instanceof HTMLElement)) {
            return;
        }

        const quantidade = this.contentor.querySelectorAll(
            ':scope > .item-seccao',
        ).length;

        const designacao = quantidade === 1
            ? 'secção'
            : 'secções';

        estado.textContent =
            `Secção ${acao}. ${quantidade} ${designacao}.`;
    }

    /**
     * Adiciona uma nova secção ao contentor.
     *
     * @returns {HTMLElement} Secção criada.
     *
     * @throws {Error} Quando o modelo não produz uma secção válida.
     *
     * @since 1.0.0
     */
    adicionar() {
        const indice = this.indiceSeguinte;

        const novaSeccao =
            this.criarSeccaoDoModelo(indice);

        const selecaoTipo = novaSeccao.querySelector(
            '.seletor-tipo-seccao',
        );

        if (!(selecaoTipo instanceof HTMLSelectElement)) {
            throw new Error(
                'O modelo da secção não contém um seletor de tipo válido.',
            );
        }

        this.contentor.append(novaSeccao);

        this.indiceSeguinte = indice + 1;

        this.atualizarEstadoSeccao(
            selecaoTipo,
        );

        this.aoAdicionarSeccao?.(
            novaSeccao,
        );

        this.atualizarControlosOrdenacao();
        this.anunciarAlteracao('adicionada');

        return novaSeccao;
    }

    /**
     * Cria uma secção a partir do modelo HTML.
     *
     * @param {number} indice Índice da nova secção.
     *
     * @returns {HTMLElement} Secção criada.
     *
     * @throws {Error} Quando o modelo não produz exatamente uma secção.
     *
     * @since 2.0.0
     */
    criarSeccaoDoModelo(indice) {
        const conteudoModelo = this.modelo.innerHTML
            .replaceAll(
                GestorSeccoes.MARCADOR_INDICE,
                String(indice),
            )
            .trim();

        const modeloTemporario =
            document.createElement('template');

        modeloTemporario.innerHTML =
            conteudoModelo;

        const elementosRaiz = Array.from(
            modeloTemporario.content.children,
        );

        if (
            elementosRaiz.length !== 1
            || !(elementosRaiz[0] instanceof HTMLElement)
            || !elementosRaiz[0].classList.contains(
                'item-seccao',
            )
        ) {
            throw new Error(
                'O modelo deve produzir exatamente um item de secção válido.',
            );
        }

        const novaSeccao = elementosRaiz[0];

        novaSeccao.dataset.indiceSeccao =
            String(indice);

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
     */
    tratarClique(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botaoAlternar = evento.target.closest(
            '.botao-alternar-seccao',
        );

        if (botaoAlternar instanceof HTMLButtonElement) {
            const seccao = botaoAlternar.closest('.item-seccao');

            if (seccao instanceof HTMLElement) {
                this.definirSeccaoExpandida(
                    seccao,
                    botaoAlternar.getAttribute('aria-expanded') !== 'true',
                );
            }

            return;
        }

        const botaoOrdenar = evento.target.closest(
            '.botao-subir-seccao, .botao-descer-seccao',
        );

        if (botaoOrdenar instanceof HTMLButtonElement) {
            this.mover(
                botaoOrdenar,
                botaoOrdenar.classList.contains('botao-subir-seccao')
                    ? -1
                    : 1,
            );

            return;
        }

        const botaoRemover = evento.target.closest(
            '.botao-remover-seccao',
        );

        if (!(botaoRemover instanceof HTMLButtonElement)) {
            return;
        }

        this.remover(botaoRemover);
    }

    /**
     * Expande ou recolhe apenas o conteúdo visual da secção.
     * Os controlos e respetivos valores continuam no formulário.
     *
     * @param {HTMLElement} seccao Secção do formulário.
     * @param {boolean} expandir Estado pretendido.
     * @returns {boolean} Indica se os controlos existem.
     *
     * @since 2.0.0
     */
    definirSeccaoExpandida(seccao, expandir) {
        const conteudo = seccao.querySelector('[data-conteudo-seccao]');
        const botao = seccao.querySelector('.botao-alternar-seccao');

        if (
            !(conteudo instanceof HTMLElement)
            || !(botao instanceof HTMLButtonElement)
        ) {
            return false;
        }

        if (!expandir && conteudo.contains(document.activeElement)) {
            botao.focus({ preventScroll: true });
        }

        if (!expandir) {
            this.atualizarResumoSeccao(seccao);
        }

        conteudo.hidden = !expandir;
        seccao.classList.toggle('item-seccao--recolhida', !expandir);
        botao.setAttribute('aria-expanded', String(expandir));

        const rotulo = botao.querySelector('[data-rotulo-alternar-seccao]');
        const icone = botao.querySelector('[data-icone-alternar-seccao]');

        if (rotulo instanceof HTMLElement) {
            rotulo.textContent = expandir ? 'Recolher' : 'Expandir';
        }

        if (icone instanceof HTMLElement) {
            icone.classList.toggle('bi-chevron-up', expandir);
            icone.classList.toggle('bi-chevron-down', !expandir);
        }

        return true;
    }

    /**
     * Mostra a secção que contém um campo rejeitado pela validação nativa.
     *
     * @param {Event} evento Evento de validação.
     * @returns {void}
     *
     * @since 2.0.0
     */
    expandirCampoInvalido(evento) {
        if (!(evento.target instanceof HTMLElement)) {
            return;
        }

        const seccao = evento.target.closest('.item-seccao');

        if (seccao instanceof HTMLElement) {
            this.definirSeccaoExpandida(seccao, true);
        }
    }

    /**
     * Sincroniza o resumo após edição de qualquer campo da secção.
     *
     * @param {Event} evento Alteração do campo.
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarResumoPorEvento(evento) {
        if (!(evento.target instanceof HTMLElement)) {
            return;
        }

        const seccao = evento.target.closest('.item-seccao');

        if (seccao instanceof HTMLElement) {
            this.atualizarResumoSeccao(seccao);
        }
    }

    /**
     * Resume os campos principais sem depender da posição técnica da secção.
     *
     * @param {HTMLElement} seccao Secção do formulário.
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarResumoSeccao(seccao) {
        const resumo = seccao.querySelector('[data-resumo-seccao]');

        if (!(resumo instanceof HTMLElement)) {
            return;
        }

        const tipo = seccao.querySelector('.seletor-tipo-seccao');
        const artista = seccao.querySelector('.tom-select-artistas');
        const titulo = seccao.querySelector('[data-campo-titulo-seccao]');
        const descricao = seccao.querySelector('textarea[name$="[descricao]"]');

        const designacaoTipo = tipo instanceof HTMLSelectElement && tipo.value
            ? tipo.selectedOptions.item(0)?.textContent.trim()
            : '';
        const designacaoArtista = artista instanceof HTMLSelectElement && artista.value
            ? artista.selectedOptions.item(0)?.textContent.trim()
            : '';
        const designacaoTitulo = titulo instanceof HTMLInputElement
            ? titulo.value.trim()
            : '';
        const excertoDescricao = descricao instanceof HTMLTextAreaElement
            ? descricao.value.trim().replaceAll(/\s+/g, ' ').slice(0, 70)
            : '';

        const componentes = [
            designacaoTipo,
            designacaoArtista,
            designacaoTitulo || (!designacaoArtista ? excertoDescricao : ''),
        ].filter(Boolean);

        resumo.textContent = componentes.length > 0
            ? componentes.join(' · ')
            : 'Por preencher';
    }

    /**
     * Move uma secção uma posição, mantendo os nós e controlos existentes.
     *
     * Os nomes dos campos e os índices técnicos não são alterados. A ordem
     * visual passa a ser a ordem de submissão dos controlos HTML e o servidor
     * já reindexa as secções de acordo com a ordem recebida.
     *
     * @param {HTMLButtonElement} botao Botão que iniciou a operação.
     * @param {-1|1} direcao Posição anterior ou seguinte.
     * @returns {boolean} Indica se a secção foi movida.
     *
     * @since 2.0.0
     */
    mover(botao, direcao) {
        if (
            !(botao instanceof HTMLButtonElement)
            || botao.disabled
            || (direcao !== -1 && direcao !== 1)
        ) {
            return false;
        }

        const seccao = botao.closest('.item-seccao');

        if (
            !(seccao instanceof HTMLElement)
            || seccao.parentElement !== this.contentor
        ) {
            return false;
        }

        const vizinha = direcao === -1
            ? seccao.previousElementSibling
            : seccao.nextElementSibling;

        if (
            !(vizinha instanceof HTMLElement)
            || !vizinha.classList.contains('item-seccao')
        ) {
            return false;
        }

        this.contentor.insertBefore(
            seccao,
            direcao === -1
                ? vizinha
                : vizinha.nextSibling,
        );

        const seccoes = this.atualizarControlosOrdenacao();
        const posicao = seccoes.indexOf(seccao) + 1;
        const estado = this.botaoAdicionar.parentElement?.querySelector(
            '[data-estado-seccoes]',
        );

        if (estado instanceof HTMLElement) {
            estado.textContent =
                `Secção movida para a posição ${posicao} de ${seccoes.length}.`;
        }

        // Ao chegar ao limite, o botão utilizado fica desativado.
        // Transferir o foco para o sentido oposto mantém o teclado funcional.
        const destinoFoco = botao.disabled
            ? seccao.querySelector(
                direcao === -1
                    ? '.botao-descer-seccao'
                    : '.botao-subir-seccao',
            )
            : botao;

        if (destinoFoco instanceof HTMLButtonElement) {
            destinoFoco.focus({ preventScroll: true });
        }

        return true;
    }

    /**
     * Atualiza a posição apresentada e os limites dos controlos de ordenação.
     *
     * @returns {HTMLElement[]} Secções por ordem visual.
     *
     * @since 2.0.0
     */
    atualizarControlosOrdenacao() {
        const seccoes = Array.from(this.contentor.children)
            .filter((elemento) =>
                elemento instanceof HTMLElement
                && elemento.classList.contains('item-seccao'),
            );

        seccoes.forEach((seccao, indice) => {
            const posicao = seccao.querySelector('[data-posicao-seccao]');
            const subir = seccao.querySelector('.botao-subir-seccao');
            const descer = seccao.querySelector('.botao-descer-seccao');

            if (posicao instanceof HTMLElement) {
                posicao.textContent =
                    `Secção ${indice + 1} de ${seccoes.length}`;
            }

            this.atualizarResumoSeccao(seccao);

            if (subir instanceof HTMLButtonElement) {
                subir.disabled = indice === 0;
            }

            if (descer instanceof HTMLButtonElement) {
                descer.disabled = indice === seccoes.length - 1;
            }
        });

        return seccoes;
    }

    /**
     * Trata as alterações ocorridas dentro do contentor.
     *
     * @param {Event} evento Evento de alteração.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarAlteracao(evento) {
        this.atualizarResumoPorEvento(evento);

        const selecaoTipo = evento.target;

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
     * Remove uma secção e liberta os componentes associados.
     *
     * @param {HTMLButtonElement} botaoRemover
     *     Botão que solicitou a remoção.
     *
     * @returns {boolean} Indica se foi removida uma secção.
     *
     * @since 1.0.0
     */
    remover(botaoRemover) {
        const seccao = botaoRemover.closest(
            '.item-seccao',
        );

        if (
            !(seccao instanceof HTMLElement)
            || !this.contentor.contains(seccao)
        ) {
            return false;
        }

        const reporFoco =
            seccao.contains(document.activeElement);

        const seccoes = Array.from(
            this.contentor.querySelectorAll('.item-seccao'),
        );

        const indice = seccoes.indexOf(seccao);

        const vizinha =
            seccoes[indice + 1]
            ?? seccoes[indice - 1];

        const destino =
            vizinha?.querySelector('.seletor-tipo-seccao')
            ?? vizinha?.querySelector('.botao-remover-seccao')
            ?? this.botaoAdicionar;

        this.destruirTooltipsDaSeccao(
            seccao,
        );

        this.destruirTomSelectDaSeccao(
            seccao,
        );

        seccao.remove();
        this.atualizarControlosOrdenacao();

        if (reporFoco) {
            if (
                destino instanceof HTMLSelectElement
                && destino.tomselect
            ) {
                destino.tomselect.focus();
            } else if (destino instanceof HTMLElement) {
                destino.focus();
            }
        }

        this.anunciarAlteracao('removida');

        return true;
    }

    /**
     * Destrói as instâncias Bootstrap Tooltip pertencentes a uma secção.
     *
     * A libertação ocorre antes da remoção do elemento da DOM para que o
     * Bootstrap possa eliminar também qualquer tooltip atualmente gerado.
     *
     * @param {HTMLElement} seccao Secção removida.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    destruirTooltipsDaSeccao(seccao) {
        seccao.querySelectorAll(
            '[data-bs-toggle="tooltip"]',
        ).forEach((elemento) => {
            if (!(elemento instanceof HTMLElement)) {
                return;
            }

            Tooltip.getInstance(
                elemento,
            )?.dispose();
        });
    }

    /**
     * Liberta as instâncias Tom Select pertencentes a uma secção.
     *
     * @param {HTMLElement} seccao Secção removida.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    destruirTomSelectDaSeccao(seccao) {
        seccao.querySelectorAll('select')
            .forEach((selecao) => {
                if (!(selecao instanceof HTMLSelectElement)) {
                    return;
                }

                const instancia = selecao.tomselect;

                if (
                    instancia
                    && typeof instancia.destroy === 'function'
                ) {
                    instancia.destroy();
                }
            });
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
     */
    atualizarEstadoSeccao(selecaoTipo) {
        const seccao = selecaoTipo.closest(
            '.item-seccao',
        );

        if (
            !(seccao instanceof HTMLElement)
            || !this.contentor.contains(seccao)
        ) {
            return;
        }

        const opcaoSelecionada =
            selecaoTipo.selectedOptions.item(0);

        const exigeDetalhes =
            opcaoSelecionada
                ?.dataset
                .exigeDetalhes === 'true';

        seccao.querySelectorAll(
            '.linha-detalhes-seccao',
        ).forEach((linhaDetalhes) => {
            if (!(linhaDetalhes instanceof HTMLElement)) {
                return;
            }

            this.definirVisibilidade(
                linhaDetalhes,
                exigeDetalhes,
            );

            linhaDetalhes.querySelectorAll(
                GestorSeccoes
                    .SELETOR_CONTROLOS_DETALHES,
            ).forEach((controlo) => {
                this.definirControloAtivo(
                    controlo,
                    exigeDetalhes,
                );
            });
        });

        seccao.querySelectorAll(
            GestorSeccoes
                .SELETOR_CAMPOS_DETALHES_OBRIGATORIOS,
        ).forEach((campo) => {
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

        seccao.dispatchEvent(
            new CustomEvent(
                GestorSeccoes.EVENTO_ESTADO_ATUALIZADO,
                {
                    detail: {
                        exigeDetalhes,
                    },
                },
            ),
        );
    }

    /**
     * Atualiza a visibilidade visual e acessível de uma linha.
     *
     * @param {HTMLElement} elemento Elemento atualizado.
     * @param {boolean} mostrar Indicação de apresentação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    definirVisibilidade(
        elemento,
        mostrar,
    ) {
        elemento.hidden = !mostrar;

        if (mostrar) {
            elemento.removeAttribute(
                'aria-hidden',
            );

            return;
        }

        elemento.setAttribute(
            'aria-hidden',
            'true',
        );
    }

    /**
     * Ativa ou desativa um controlo pertencente aos detalhes.
     *
     * As instâncias Tom Select são geridas através da respetiva API para que
     * o estado visual permaneça sincronizado com o `<select>` original.
     *
     * @param {Element} controlo Controlo atualizado.
     * @param {boolean} ativo Estado pretendido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    definirControloAtivo(
        controlo,
        ativo,
    ) {
        if (controlo instanceof HTMLSelectElement) {
            const instancia = controlo.tomselect;

            if (instancia) {
                const metodo = ativo
                    ? instancia.enable
                    : instancia.disable;

                if (typeof metodo === 'function') {
                    metodo.call(instancia);

                    return;
                }
            }

            controlo.disabled = !ativo;

            return;
        }

        if (
            controlo instanceof HTMLInputElement
            || controlo instanceof HTMLTextAreaElement
            || controlo instanceof HTMLButtonElement
        ) {
            controlo.disabled = !ativo;
        }
    }

    /**
     * Calcula o próximo índice disponível para uma nova secção.
     *
     * Todos os itens já renderizados devem declarar um índice inteiro,
     * não negativo e único através de `data-indice-seccao`.
     *
     * @returns {number} Próximo índice.
     *
     * @throws {Error} Quando algum índice é inválido ou duplicado.
     *
     * @since 2.0.0
     */
    calcularProximoIndice() {
        const indices = new Set();
        let maiorIndice = -1;

        this.contentor.querySelectorAll(
            '.item-seccao',
        ).forEach((seccao) => {
            if (!(seccao instanceof HTMLElement)) {
                return;
            }

            const valorIndice =
                seccao.dataset.indiceSeccao?.trim()
                ?? '';

            const indice = Number(valorIndice);

            if (
                valorIndice === ''
                || !Number.isSafeInteger(indice)
                || indice < 0
            ) {
                throw new Error(
                    'Foi encontrada uma secção com um índice inválido.',
                );
            }

            if (indices.has(indice)) {
                throw new Error(
                    `O índice de secção "${indice}" está repetido.`,
                );
            }

            indices.add(indice);

            maiorIndice = Math.max(
                maiorIndice,
                indice,
            );
        });

        return maiorIndice + 1;
    }

    /**
     * Valida a estrutura base do modelo de secção.
     *
     * @returns {void}
     *
     * @throws {Error} Quando o modelo não possui o marcador ou a estrutura
     *     esperada.
     *
     * @since 2.0.0
     */
    validarModelo() {
        if (
            !this.modelo.innerHTML.includes(
                GestorSeccoes.MARCADOR_INDICE,
            )
        ) {
            throw new Error(
                'O modelo da secção não contém o marcador do índice.',
            );
        }

        const elementosRaiz = Array.from(
            this.modelo.content.children,
        );

        if (
            elementosRaiz.length !== 1
            || !(elementosRaiz[0] instanceof HTMLElement)
            || !elementosRaiz[0].classList.contains(
                'item-seccao',
            )
        ) {
            throw new Error(
                'O modelo deve conter exatamente um item de secção.',
            );
        }
    }

    /**
     * Obtém obrigatoriamente um elemento através de um seletor CSS.
     *
     * @param {unknown} seletor Seletor CSS.
     * @param {Function} tipoElemento Tipo esperado.
     * @param {string} descricao Descrição do elemento.
     *
     * @returns {Element} Elemento encontrado.
     *
     * @throws {TypeError} Quando o seletor ou o elemento são inválidos.
     *
     * @since 2.0.0
     */
    obterElemento(
        seletor,
        tipoElemento,
        descricao,
    ) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                `O seletor do ${descricao} é obrigatório.`,
            );
        }

        const seletorNormalizado =
            seletor.trim();

        let elemento;

        try {
            elemento = document.querySelector(
                seletorNormalizado,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletorNormalizado}" é inválido.`,
            );
        }

        if (!(elemento instanceof tipoElemento)) {
            throw new TypeError(
                `Não foi encontrado um ${descricao} válido através de "${seletorNormalizado}".`,
            );
        }

        return elemento;
    }
}

export default GestorSeccoes;
