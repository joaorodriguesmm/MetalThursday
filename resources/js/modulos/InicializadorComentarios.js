import axios from 'axios';

import TratadorFormularioAjax
    from './TratadorFormularioAjax';

import ValidadorFormulario
    from './ValidadorFormulario';

/**
 * Gere a publicação e o carregamento assíncrono da árvore de comentários.
 *
 * Os comentários principais são apresentados inicialmente sem as respetivas
 * respostas. Cada ramo é carregado apenas quando o utilizador o expande.
 *
 * A hierarquia persistida pode possuir qualquer profundidade. A profundidade
 * visual é limitada a três níveis através do atributo `data-nivel-visual`.
 *
 * @since 2.0.0
 */
class InicializadorComentarios {
    /**
     * Seletor dos formulários suportados.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static SELETOR_FORMULARIOS = [
        'form.formulario-comentario',
        'form.formulario-resposta-comentario',
    ].join(', ');

    /**
     * Seletor dos botões que expandem ou recolhem respostas.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static SELETOR_ALTERNADOR_RESPOSTAS =
        'button[data-acao-comentarios="alternar-respostas"]';

    /**
     * Comprimento máximo utilizado quando o campo não declara `maxlength`.
     *
     * @type {number}
     *
     * @since 2.0.0
     */
    static COMPRIMENTO_MAXIMO_PREDEFINIDO = 2000;

    /**
     * Profundidade visual máxima da árvore.
     *
     * A hierarquia persistida não é limitada por este valor.
     *
     * @type {number}
     *
     * @since 2.0.0
     */
    static NIVEL_VISUAL_MAXIMO = 3;

    /**
     * Cria e inicializa o gestor de comentários.
     *
     * @param {Document|Element} contentor Contentor principal.
     *
     * @throws {TypeError} Quando o contentor não é válido.
     *
     * @since 2.0.0
     */
    constructor(contentor = document) {
        if (
            !(contentor instanceof Document)
            && !(contentor instanceof Element)
        ) {
            throw new TypeError(
                'O contentor dos comentários é inválido.',
            );
        }

        /**
         * Contentor utilizado na delegação dos eventos.
         *
         * @type {Document|Element}
         */
        this.contentor = contentor;

        /**
         * Formulários que já receberam validação e submissão AJAX.
         *
         * @type {WeakSet<HTMLFormElement>}
         */
        this.formulariosInicializados =
            new WeakSet();

        /**
         * Carregamentos de respostas atualmente associados a cada alternador.
         *
         * A promessa é partilhada quando mais do que um fluxo necessita do mesmo
         * ramo, impedindo pedidos concorrentes sobre o mesmo comentário.
         *
         * @type {WeakMap<HTMLButtonElement, Promise<void>>}
         */
        this.carregamentosRespostas =
            new WeakMap();

        this.inicializarFormularios(
            contentor,
        );

        this.contentor.addEventListener(
            'click',
            (evento) => {
                this.tratarClique(
                    evento,
                );
            },
        );
    }

    /**
     * Inicializa todos os formulários ainda não tratados num contentor.
     *
     * @param {Document|Element} contentor Contentor pesquisado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    inicializarFormularios(
        contentor,
    ) {
        contentor
            .querySelectorAll(
                InicializadorComentarios.SELETOR_FORMULARIOS,
            )
            .forEach(
                (formulario) => {
                    if (
                        formulario
                        instanceof HTMLFormElement
                    ) {
                        this.inicializarFormulario(
                            formulario,
                        );
                    }
                },
            );
    }

    /**
     * Inicializa um formulário de comentário ou resposta.
     *
     * @param {HTMLFormElement} formulario Formulário recebido.
     *
     * @returns {void}
     *
     * @throws {Error} Quando faltam contratos obrigatórios.
     *
     * @since 2.0.0
     */
    inicializarFormulario(
        formulario,
    ) {
        if (
            this.formulariosInicializados.has(
                formulario,
            )
        ) {
            return;
        }

        const identificador =
            formulario.id.trim();

        const endereco =
            formulario.action.trim();

        const campoConteudo =
            formulario.elements.namedItem(
                'conteudo',
            );

        if (
            identificador === ''
            || endereco === ''
            || !(
                campoConteudo
                instanceof HTMLTextAreaElement
            )
        ) {
            throw new Error(
                'Cada formulário de comentário deve possuir identificador, endereço e campo de conteúdo.',
            );
        }

        const comprimentoMaximo =
            Number.isInteger(
                campoConteudo.maxLength,
            )
            && campoConteudo.maxLength > 0
                ? campoConteudo.maxLength
                : InicializadorComentarios
                    .COMPRIMENTO_MAXIMO_PREDEFINIDO;

        const tratadorAjax =
            new TratadorFormularioAjax(
                identificador,
                endereco,
                async (
                    dadosResposta,
                ) => {
                    await this.atualizarInterfaceAposPublicacao(
                        formulario,
                        dadosResposta,
                    );
                },
            );

        new ValidadorFormulario(
            formulario,
            {
                regras: {
                    conteudo: [
                        'obrigatorio',
                        `maximo:${comprimentoMaximo}`,
                    ],
                },

                mensagens: {
                    conteudo: {
                        obrigatorio:
                            'Por favor, insere o texto do comentário.',

                        maximo:
                            `O comentário não pode ter mais de ${comprimentoMaximo} caracteres.`,
                    },
                },

                eventosTempoReal: [
                    'input',
                ],

                aoSucesso: () => {
                    /*
                     * O validador exige uma função síncrona. O tratador AJAX
                     * gere internamente o respetivo ciclo assíncrono.
                     */
                    void tratadorAjax.submeter();
                },
            },
        );

        this.formulariosInicializados.add(
            formulario,
        );
    }

    /**
     * Trata cliques nos controlos próprios da árvore de comentários.
     *
     * @param {Event} evento Evento recebido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarClique(
        evento,
    ) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botao =
            evento.target.closest(
                InicializadorComentarios
                    .SELETOR_ALTERNADOR_RESPOSTAS,
            );

        if (
            !(botao instanceof HTMLButtonElement)
            || !this.contentor.contains(
                botao,
            )
        ) {
            return;
        }

        evento.preventDefault();

        void this.alternarRespostas(
            botao,
        );
    }

    /**
     * Expande ou recolhe as respostas de um comentário.
     *
     * O primeiro acesso carrega os filhos através do servidor. Os acessos
     * seguintes reutilizam os elementos já existentes na página.
     *
     * @param {HTMLButtonElement} botao Alternador acionado.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    async alternarRespostas(
        botao,
    ) {
        if (
            this.carregamentosRespostas.has(
                botao,
            )
        ) {
            return;
        }

        const comentario =
            botao.closest(
                '.comentario',
            );

        if (!(comentario instanceof HTMLElement)) {
            return;
        }

        const contentorRespostas =
            this.obterContentorRespostas(
                comentario,
            );

        if (
            !(contentorRespostas
                instanceof HTMLElement)
        ) {
            return;
        }

        const estaExpandido =
            botao.getAttribute(
                'aria-expanded',
            ) === 'true';

        if (estaExpandido) {
            this.recolherRespostas(
                botao,
                contentorRespostas,
            );

            return;
        }

        if (
            contentorRespostas
                .dataset
                .respostasCarregadas
            !== 'true'
        ) {
            try {
                await this.carregarRespostas(
                    comentario,
                    botao,
                    contentorRespostas,
                );
            } catch {
                this.apresentarFalhaCarregamento(
                    botao,
                );

                return;
            }
        }

        /*
        * A quantidade inicial da página pode ter ficado desatualizada enquanto
        * esta permanecia aberta. Se o servidor já não devolver respostas, o
        * alternador fica oculto e o ramo permanece corretamente recolhido.
        */
        if (
            this.obterQuantidadeRespostas(
                botao,
            ) === 0
        ) {
            this.recolherRespostas(
                botao,
                contentorRespostas,
            );

            return;
        }

        this.expandirRespostas(
            botao,
            contentorRespostas,
        );
    }

    /**
     * Carrega os filhos diretos de um comentário.
     *
     * @param {HTMLElement} comentario Comentário pai.
     * @param {HTMLButtonElement} botao Alternador do ramo.
     * @param {HTMLElement} contentorRespostas Contentor dos filhos.
     *
     * @returns {Promise<void>}
     *
     * @throws {Error} Quando o endereço ou a resposta são inválidos.
     *
     * @since 2.0.0
     */
    async carregarRespostas(
        comentario,
        botao,
        contentorRespostas,
    ) {
        const carregamentoExistente =
            this.carregamentosRespostas.get(
                botao,
            );

        if (carregamentoExistente) {
            await carregamentoExistente;

            return;
        }

        const carregamento =
            this.executarCarregamentoRespostas(
                comentario,
                botao,
                contentorRespostas,
            );

        this.carregamentosRespostas.set(
            botao,
            carregamento,
        );

        try {
            await carregamento;
        } finally {
            if (
                this.carregamentosRespostas.get(
                    botao,
                ) === carregamento
            ) {
                this.carregamentosRespostas.delete(
                    botao,
                );
            }
        }
    }

    async executarCarregamentoRespostas(
        comentario,
        botao,
        contentorRespostas,
    ) {
        const endereco =
            this.normalizarEndereco(
                botao.dataset
                    .enderecoRespostas,
            );

        if (endereco === null) {
            throw new Error(
                'O endereço das respostas é inválido.',
            );
        }

        const quantidadeOriginal =
            this.obterQuantidadeRespostas(
                botao,
            );

        botao.disabled = true;

        botao.setAttribute(
            'aria-busy',
            'true',
        );

        this.definirTextoAlternador(
            botao,
            'A carregar respostas...',
        );

        try {
            const resposta =
                await axios.get(
                    endereco,
                );

            const dados =
                resposta.data;

            if (
                !this.eObjeto(
                    dados,
                )
                || !Array.isArray(
                    dados.respostas,
                )
            ) {
                throw new Error(
                    'A resposta do servidor não contém uma lista de respostas válida.',
                );
            }

            const numeroRespostas =
                this.normalizarQuantidade(
                    dados.numero_respostas,
                );

            if (
                numeroRespostas === null
                || numeroRespostas
                !== dados.respostas.length
            ) {
                throw new Error(
                    'A quantidade de respostas devolvida pelo servidor é inválida.',
                );
            }

            const nivelPai =
                this.obterNivelVisual(
                    comentario,
                );

            const nivelFilho =
                Math.min(
                    nivelPai + 1,
                    InicializadorComentarios
                        .NIVEL_VISUAL_MAXIMO,
                );

            const novosComentarios =
                [];

            const fragmento =
                document.createDocumentFragment();

            dados.respostas.forEach(
                (entrada) => {
                    if (
                        !this.eObjeto(
                            entrada,
                        )
                        || typeof entrada
                            .comentario_html
                            !== 'string'
                    ) {
                        throw new Error(
                            'Uma das respostas devolvidas pelo servidor é inválida.',
                        );
                    }

                    const respostaComentario =
                        this.criarElementoComentario(
                            entrada
                                .comentario_html,
                        );

                    respostaComentario
                        .dataset
                        .nivelVisual =
                            String(
                                nivelFilho,
                            );

                    novosComentarios.push(
                        respostaComentario,
                    );

                    fragmento.append(
                        respostaComentario,
                    );
                },
            );

            contentorRespostas
                .replaceChildren(
                    fragmento,
                );

            contentorRespostas
                .dataset
                .respostasCarregadas =
                    'true';

            novosComentarios.forEach(
                (novoComentario) => {
                    this.inicializarFormularios(
                        novoComentario,
                    );
                },
            );

            this.atualizarQuantidadeRespostas(
                botao,
                numeroRespostas,
            );
        } catch (erro) {
            this.atualizarQuantidadeRespostas(
                botao,
                quantidadeOriginal,
            );

            throw erro;
        } finally {
            botao.disabled = false;

            botao.removeAttribute(
                'aria-busy',
            );
        }
    }

    /**
     * Atualiza a interface depois de publicar um comentário ou resposta.
     *
     * @param {HTMLFormElement} formulario Formulário submetido.
     * @param {unknown} dadosResposta Dados devolvidos pelo servidor.
     *
     * @returns {Promise<void>}
     *
     * @throws {Error} Quando a resposta ou a estrutura da página são inválidas.
     *
     * @since 2.0.0
     */
    async atualizarInterfaceAposPublicacao(
        formulario,
        dadosResposta,
    ) {
        const seccaoComentarios =
            formulario.closest(
                'section[aria-label="Comentários"]',
            );

        if (
            !(seccaoComentarios
                instanceof HTMLElement)
        ) {
            throw new Error(
                'Não foi possível localizar a secção de comentários.',
            );
        }

        if (
            formulario.classList.contains(
                'formulario-resposta-comentario',
            )
        ) {
            /*
             * A resposta já foi persistida neste momento. O contador geral é
             * atualizado independentemente de ser necessário recarregar o
             * ramo da conversa.
             */
            this.incrementarContadorComentarios(
                seccaoComentarios,
            );

            await this.atualizarAposResposta(
                formulario,
                dadosResposta,
                seccaoComentarios,
            );

            return;
        }

        this.inserirComentarioPrincipal(
            dadosResposta,
            seccaoComentarios,
        );

        this.incrementarContadorComentarios(
            seccaoComentarios,
        );
    }

    /**
     * Insere um comentário principal recém-publicado.
     *
     * @param {unknown} dadosResposta Dados devolvidos pelo servidor.
     * @param {HTMLElement} seccaoComentarios Secção da entidade comentada.
     *
     * @returns {void}
     *
     * @throws {Error} Quando o fragmento ou a lista são inválidos.
     *
     * @since 2.0.0
     */
    inserirComentarioPrincipal(
        dadosResposta,
        seccaoComentarios,
    ) {
        const htmlComentario =
            this.obterHtmlComentario(
                dadosResposta,
            );

        const novoComentario =
            this.criarElementoComentario(
                htmlComentario,
            );

        novoComentario
            .dataset
            .nivelVisual =
                '1';

        const listaComentarios =
            seccaoComentarios.querySelector(
                '.lista-comentarios',
            );

        if (
            !(listaComentarios
                instanceof HTMLElement)
        ) {
            throw new Error(
                'Não foi possível localizar a lista de comentários.',
            );
        }

        listaComentarios
            .querySelector(
                '.sem-comentarios',
            )
            ?.remove();

        listaComentarios.prepend(
            novoComentario,
        );

        this.inicializarFormularios(
            novoComentario,
        );
    }

    /**
     * Atualiza o ramo onde uma resposta foi publicada.
     *
     * Quando o ramo já estava carregado, a resposta pode ser acrescentada
     * imediatamente. Quando existiam respostas ainda não carregadas, o ramo
     * completo é consultado novamente para não apresentar apenas a resposta
     * recém-criada.
     *
     * @param {HTMLFormElement} formulario Formulário da resposta.
     * @param {unknown} dadosResposta Dados devolvidos pelo servidor.
     * @param {HTMLElement} seccaoComentarios Secção dos comentários.
     *
     * @returns {Promise<void>}
     *
     * @throws {Error} Quando não é possível localizar o comentário pai.
     *
     * @since 2.0.0
     */
    async atualizarAposResposta(
        formulario,
        dadosResposta,
        seccaoComentarios,
    ) {
        const identificadorPai =
            this.normalizarIdentificador(
                formulario.dataset
                    .identificadorComentarioPai,
            );

        const comentarioPai =
            document.getElementById(
                `comentario-${identificadorPai}`,
            );

        if (
            !(comentarioPai
                instanceof HTMLElement)
            || !seccaoComentarios.contains(
                comentarioPai,
            )
        ) {
            throw new Error(
                'Não foi possível localizar o comentário respondido.',
            );
        }

        const botao =
            this.obterAlternadorRespostas(
                comentarioPai,
            );

        const contentorRespostas =
            this.obterContentorRespostas(
                comentarioPai,
            );

        if (
            !(botao instanceof HTMLButtonElement)
            || !(contentorRespostas
                instanceof HTMLElement)
        ) {
            throw new Error(
                'Não foi possível localizar o ramo de respostas.',
            );
        }

        const quantidadeAnterior =
            this.obterQuantidadeRespostas(
                botao,
            );

        const novaQuantidade =
            quantidadeAnterior + 1;

        this.atualizarQuantidadeRespostas(
            botao,
            novaQuantidade,
        );

        this.fecharFormularioResposta(
            formulario,
        );

        const respostasJaCarregadas =
            contentorRespostas
                .dataset
                .respostasCarregadas
            === 'true';

        if (
            respostasJaCarregadas
            || quantidadeAnterior === 0
        ) {
            const htmlComentario =
                this.obterHtmlComentario(
                    dadosResposta,
                );

            const novaResposta =
                this.criarElementoComentario(
                    htmlComentario,
                );

            const nivelPai =
                this.obterNivelVisual(
                    comentarioPai,
                );

            novaResposta
                .dataset
                .nivelVisual =
                    String(
                        Math.min(
                            nivelPai + 1,
                            InicializadorComentarios
                                .NIVEL_VISUAL_MAXIMO,
                        ),
                    );

            if (!respostasJaCarregadas) {
                contentorRespostas
                    .replaceChildren();

                contentorRespostas
                    .dataset
                    .respostasCarregadas =
                        'true';
            }

            contentorRespostas.append(
                novaResposta,
            );

            this.inicializarFormularios(
                novaResposta,
            );

            this.expandirRespostas(
                botao,
                contentorRespostas,
            );

            return;
        }

        /*
        * Já existiam respostas, mas ainda não tinham sido carregadas.
        *
        * Se já existir um carregamento iniciado antes da publicação desta resposta,
        * aguardamos que termine e invalidamos explicitamente esse resultado. Esse
        * pedido pode representar um estado anterior ao POST que acabou de concluir.
        */
        const carregamentoAnterior =
            this.carregamentosRespostas.get(
                botao,
            );

        if (carregamentoAnterior) {
            try {
                await carregamentoAnterior;
            } catch {
                /*
                * A falha desse pedido anterior não invalida a resposta acabada de
                * publicar. O ramo será novamente solicitado abaixo.
                */
            }

            contentorRespostas
                .dataset
                .respostasCarregadas =
                    'false';
        }

        /*
        * Consultamos agora uma representação posterior à criação, garantindo que
        * inclui os filhos antigos e a nova resposta.
        */
        await this.carregarRespostas(
            comentarioPai,
            botao,
            contentorRespostas,
        );

        this.expandirRespostas(
            botao,
            contentorRespostas,
        );
    }

    /**
     * Expande um ramo já carregado.
     *
     * @param {HTMLButtonElement} botao Alternador do ramo.
     * @param {HTMLElement} contentorRespostas Contentor das respostas.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    expandirRespostas(
        botao,
        contentorRespostas,
    ) {
        contentorRespostas.hidden =
            false;

        botao.setAttribute(
            'aria-expanded',
            'true',
        );

        this.definirTextoAlternador(
            botao,
            'Ocultar respostas',
        );
    }

    /**
     * Recolhe um ramo sem remover os elementos já carregados.
     *
     * @param {HTMLButtonElement} botao Alternador do ramo.
     * @param {HTMLElement} contentorRespostas Contentor das respostas.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    recolherRespostas(
        botao,
        contentorRespostas,
    ) {
        contentorRespostas.hidden =
            true;

        botao.setAttribute(
            'aria-expanded',
            'false',
        );

        this.atualizarTextoAlternadorFechado(
            botao,
        );
    }

    /**
     * Apresenta no alternador que o carregamento não foi concluído.
     *
     * O ramo permanece recolhido e pode ser novamente solicitado pelo
     * utilizador.
     *
     * @param {HTMLButtonElement} botao Alternador afetado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarFalhaCarregamento(
        botao,
    ) {
        botao.setAttribute(
            'aria-expanded',
            'false',
        );

        const quantidade =
            this.obterQuantidadeRespostas(
                botao,
            );

        const sufixo =
            quantidade === 1
                ? 'resposta'
                : 'respostas';

        this.definirTextoAlternador(
            botao,
            `Tentar carregar ${quantidade} ${sufixo}`,
        );
    }

    /**
     * Obtém o alternador pertencente diretamente a um comentário.
     *
     * @param {HTMLElement} comentario Comentário pesquisado.
     *
     * @returns {HTMLButtonElement|null} Alternador encontrado.
     *
     * @since 2.0.0
     */
    obterAlternadorRespostas(
        comentario,
    ) {
        const alternadores =
            comentario.querySelectorAll(
                InicializadorComentarios
                    .SELETOR_ALTERNADOR_RESPOSTAS,
            );

        for (const alternador of alternadores) {
            if (
                alternador
                    instanceof HTMLButtonElement
                && alternador.closest(
                    '.comentario',
                ) === comentario
            ) {
                return alternador;
            }
        }

        return null;
    }

    /**
     * Obtém o contentor de respostas pertencente diretamente ao comentário.
     *
     * @param {HTMLElement} comentario Comentário pesquisado.
     *
     * @returns {HTMLElement|null} Contentor encontrado.
     *
     * @since 2.0.0
     */
    obterContentorRespostas(
        comentario,
    ) {
        const contentores =
            comentario.querySelectorAll(
                '[data-respostas-comentario]',
            );

        for (const contentor of contentores) {
            if (
                contentor
                    instanceof HTMLElement
                && contentor.closest(
                    '.comentario',
                ) === comentario
            ) {
                return contentor;
            }
        }

        return null;
    }

    /**
     * Obtém o fragmento HTML de um comentário devolvido pelo servidor.
     *
     * @param {unknown} dadosResposta Dados recebidos.
     *
     * @returns {string} HTML validado.
     *
     * @throws {Error} Quando a resposta não contém o fragmento.
     *
     * @since 2.0.0
     */
    obterHtmlComentario(
        dadosResposta,
    ) {
        if (!this.eObjeto(dadosResposta)) {
            throw new Error(
                'A resposta da publicação do comentário é inválida.',
            );
        }

        const htmlComentario =
            dadosResposta.comentario_html;

        if (
            typeof htmlComentario !== 'string'
            || htmlComentario.trim() === ''
        ) {
            throw new Error(
                'A resposta não contém o comentário renderizado.',
            );
        }

        return htmlComentario;
    }

    /**
     * Converte um fragmento HTML num elemento de comentário.
     *
     * @param {string} htmlComentario Fragmento recebido.
     *
     * @returns {HTMLElement} Comentário criado.
     *
     * @throws {Error} Quando o fragmento não contém exactamente um comentário.
     *
     * @since 2.0.0
     */
    criarElementoComentario(
        htmlComentario,
    ) {
        const modelo =
            document.createElement(
                'template',
            );

        modelo.innerHTML =
            htmlComentario.trim();

        const elementos =
            Array.from(
                modelo.content.children,
            );

        if (
            elementos.length !== 1
            || !(
                elementos[0]
                instanceof HTMLElement
            )
            || !elementos[0]
                .classList
                .contains(
                    'comentario',
                )
        ) {
            throw new Error(
                'O fragmento devolvido não representa um comentário válido.',
            );
        }

        return elementos[0];
    }

    /**
     * Atualiza a quantidade de respostas associada ao alternador.
     *
     * @param {HTMLButtonElement} botao Alternador atualizado.
     * @param {number} quantidade Nova quantidade.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarQuantidadeRespostas(
        botao,
        quantidade,
    ) {
        const valor =
            Math.max(
                0,
                quantidade,
            );

        botao.dataset
            .quantidadeRespostas =
                String(
                    valor,
                );

        botao.hidden =
            valor === 0;

        if (
            botao.getAttribute(
                'aria-expanded',
            ) !== 'true'
        ) {
            this.atualizarTextoAlternadorFechado(
                botao,
            );
        }
    }

    /**
     * Atualiza o texto de um ramo recolhido.
     *
     * @param {HTMLButtonElement} botao Alternador atualizado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarTextoAlternadorFechado(
        botao,
    ) {
        const quantidade =
            this.obterQuantidadeRespostas(
                botao,
            );

        const sufixo =
            quantidade === 1
                ? 'resposta'
                : 'respostas';

        this.definirTextoAlternador(
            botao,
            `Ver ${quantidade} ${sufixo}`,
        );
    }

    /**
     * Substitui apenas o texto do alternador, preservando o ícone.
     *
     * @param {HTMLButtonElement} botao Alternador.
     * @param {string} texto Texto apresentado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    definirTextoAlternador(
        botao,
        texto,
    ) {
        const elemento =
            botao.querySelector(
                '[data-texto-alternador-respostas]',
            );

        if (!(elemento instanceof HTMLElement)) {
            return;
        }

        elemento.textContent =
            texto;
    }

    /**
     * Obtém a quantidade de respostas declarada pelo botão.
     *
     * @param {HTMLButtonElement} botao Alternador recebido.
     *
     * @returns {number} Quantidade não negativa.
     *
     * @since 2.0.0
     */
    obterQuantidadeRespostas(
        botao,
    ) {
        return this.normalizarQuantidade(
            botao.dataset
                .quantidadeRespostas,
        )
            ?? 0;
    }

    /**
     * Obtém o nível visual de um comentário.
     *
     * @param {HTMLElement} comentario Comentário recebido.
     *
     * @returns {number} Nível entre 1 e 3.
     *
     * @since 2.0.0
     */
    obterNivelVisual(
        comentario,
    ) {
        const nivel =
            this.normalizarQuantidade(
                comentario.dataset
                    .nivelVisual,
            );

        if (
            nivel === null
            || nivel < 1
        ) {
            return 1;
        }

        return Math.min(
            nivel,
            InicializadorComentarios
                .NIVEL_VISUAL_MAXIMO,
        );
    }

    /**
     * Fecha o formulário de resposta após publicação bem-sucedida.
     *
     * @param {HTMLFormElement} formulario Formulário submetido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    fecharFormularioResposta(
        formulario,
    ) {
        const contentor =
            formulario.closest(
                '.contentor-formulario-resposta',
            );

        if (!(contentor instanceof HTMLElement)) {
            return;
        }

        contentor.hidden =
            true;

        contentor.setAttribute(
            'aria-hidden',
            'true',
        );

        const identificadorContentor =
            contentor.id.trim();

        if (identificadorContentor === '') {
            return;
        }

        const comentarioOrigem =
            formulario.closest(
                '.comentario',
            );

        if (
            !(comentarioOrigem
                instanceof HTMLElement)
        ) {
            return;
        }

        const controladores =
            comentarioOrigem.querySelectorAll(
                'button[aria-controls]',
            );

        for (const controlador of controladores) {
            if (
                !(
                    controlador
                    instanceof HTMLButtonElement
                )
                || controlador.closest(
                    '.comentario',
                ) !== comentarioOrigem
                || controlador.getAttribute(
                    'aria-controls',
                ) !== identificadorContentor
            ) {
                continue;
            }

            controlador.setAttribute(
                'aria-expanded',
                'false',
            );

            break;
        }
    }

    /**
     * Incrementa o contador geral apresentado no botão de comentários.
     *
     * O contador geral inclui comentários principais e respostas.
     *
     * @param {HTMLElement} seccaoComentarios Secção de comentários.
     *
     * @returns {void}
     *
     * @throws {Error} Quando o contador não é encontrado.
     *
     * @since 2.0.0
     */
    incrementarContadorComentarios(
        seccaoComentarios,
    ) {
        const contentorColapsavel =
            seccaoComentarios.closest(
                '.collapse',
            );

        if (
            !(
                contentorColapsavel
                instanceof HTMLElement
            )
            || contentorColapsavel.id.trim() === ''
        ) {
            throw new Error(
                'Não foi possível identificar a conversa apresentada.',
            );
        }

        const identificadorContentor =
            contentorColapsavel.id;

        const controlador =
            Array.from(
                document.querySelectorAll(
                    'button[aria-controls]',
                ),
            ).find(
                (elemento) =>
                    elemento
                        instanceof HTMLButtonElement
                    && elemento.getAttribute(
                        'aria-controls',
                    ) === identificadorContentor
                    && elemento.querySelector(
                        '[data-quantidade-comentarios]',
                    ) !== null,
            );

        if (
            !(controlador
                instanceof HTMLButtonElement)
        ) {
            throw new Error(
                'Não foi possível localizar o contador de comentários.',
            );
        }

        const contador =
            controlador.querySelector(
                '[data-quantidade-comentarios]',
            );

        if (!(contador instanceof HTMLElement)) {
            throw new Error(
                'O contador de comentários não está disponível.',
            );
        }

        const quantidadeAtual =
            this.normalizarQuantidade(
                contador.textContent,
            );

        if (quantidadeAtual === null) {
            throw new Error(
                'O contador de comentários possui um valor inválido.',
            );
        }

        contador.textContent =
            String(
                quantidadeAtual + 1,
            );
    }

    /**
     * Normaliza um identificador HTML positivo.
     *
     * @param {string|undefined} valor Valor recebido.
     *
     * @returns {number} Identificador positivo.
     *
     * @throws {Error} Quando o valor é inválido.
     *
     * @since 2.0.0
     */
    normalizarIdentificador(
        valor,
    ) {
        if (
            typeof valor !== 'string'
            || !/^\d+$/.test(
                valor,
            )
        ) {
            throw new Error(
                'O identificador do comentário é inválido.',
            );
        }

        const identificador =
            Number.parseInt(
                valor,
                10,
            );

        if (
            !Number.isSafeInteger(
                identificador,
            )
            || identificador < 1
        ) {
            throw new Error(
                'O identificador do comentário é inválido.',
            );
        }

        return identificador;
    }

    /**
     * Normaliza uma quantidade não negativa.
     *
     * @param {unknown} valor Valor recebido.
     *
     * @returns {number|null} Quantidade ou nulo.
     *
     * @since 2.0.0
     */
    normalizarQuantidade(
        valor,
    ) {
        const texto =
            typeof valor === 'number'
                ? String(
                    valor,
                )
                : typeof valor === 'string'
                    ? valor.trim()
                    : '';

        if (
            texto === ''
            || !/^\d+$/.test(
                texto,
            )
        ) {
            return null;
        }

        const quantidade =
            Number.parseInt(
                texto,
                10,
            );

        return Number.isSafeInteger(
            quantidade,
        )
            ? quantidade
            : null;
    }

    /**
     * Normaliza um endereço recebido através do HTML.
     *
     * @param {string|undefined} valor Valor recebido.
     *
     * @returns {string|null} Endereço válido ou nulo.
     *
     * @since 2.0.0
     */
    normalizarEndereco(
        valor,
    ) {
        if (typeof valor !== 'string') {
            return null;
        }

        const endereco =
            valor.trim();

        return endereco !== ''
            ? endereco
            : null;
    }

    /**
     * Determina se um valor é um objeto comum.
     *
     * @param {unknown} valor Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando é um objeto.
     *
     * @since 2.0.0
     */
    eObjeto(
        valor,
    ) {
        return typeof valor === 'object'
            && valor !== null
            && !Array.isArray(
                valor,
            );
    }
}

export default InicializadorComentarios;
