import axios from 'axios';
import { Tooltip } from 'bootstrap';
import GestorAlertas from './GestorAlertas';

/**
 * Gere as interações assíncronas e os controlos dos comentários.
 *
 * @since 1.0.0
 */
class GestorInteracoes {
    /**
     * Tipos tratados apenas na interface.
     *
     * @type {ReadonlyArray<string>}
     *
     * @since 2.0.0
     */
    static TIPOS_LOCAIS = Object.freeze([
        'alternar-resposta-comentario',
        'cancelar-resposta-comentario',
        'iniciar-edicao-comentario',
        'cancelar-edicao-comentario',
    ]);

    /**
     * Tipos que originam pedidos HTTP.
     *
     * @type {ReadonlyArray<string>}
     *
     * @since 2.0.0
     */
    static TIPOS_REMOTOS = Object.freeze([
        'alternar-gosto',
        'alternar-audicao',
        'eliminar',
    ]);

    /**
     * Cria e inicia o gestor.
     *
     * @param {string} seletorContentor Seletor do contentor principal.
     *
     * @throws {TypeError} Quando o seletor ou o contentor não são válidos.
     *
     * @since 1.0.0
     */
    constructor(seletorContentor = 'body') {
        this.contentor = this.obterContentor(
            seletorContentor,
        );

        /**
         * Elementos que possuem uma operação assíncrona em curso.
         *
         * @type {WeakSet<HTMLElement>}
         *
         * @since 2.0.0
         */
        this.elementosEmProcessamento = new WeakSet();

        /**
         * Identificadores dos pedidos de tooltips de gostos ainda ativos.
         *
         * @type {WeakMap<HTMLElement, symbol>}
         *
         * @since 2.0.0
         */
        this.pedidosTooltipGostos = new WeakMap();

        this.contentor.addEventListener(
            'click',
            (evento) => this.tratarClique(evento),
        );

        this.contentor.addEventListener(
            'submit',
            (evento) => this.tratarSubmissao(evento),
        );

        this.contentor.addEventListener(
            'mouseover',
            (evento) => this.tratarAtivacaoTooltipGostos(evento),
        );

        this.contentor.addEventListener(
            'focusin',
            (evento) => this.tratarAtivacaoTooltipGostos(evento),
        );
    }

    /**
     * Trata os cliques nos botões de interação suportados.
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

        const botao = evento.target.closest(
            'button[data-tipo-interacao]',
        );

        if (!(botao instanceof HTMLButtonElement)) {
            return;
        }

        const tipo = botao.dataset.tipoInteracao?.trim() ?? '';

        if (
            !GestorInteracoes.TIPOS_LOCAIS.includes(tipo)
            && !GestorInteracoes.TIPOS_REMOTOS.includes(tipo)
        ) {
            return;
        }

        evento.preventDefault();

        void this.tratarInteracao(
            botao,
            tipo,
        );
    }

    /**
     * Trata a submissão da edição de comentários.
     *
     * @param {SubmitEvent} evento Evento de submissão.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarSubmissao(evento) {
        const formulario = evento.target;

        if (
            !(formulario instanceof HTMLFormElement)
            || !formulario.matches(
                'form[data-formulario-edicao-comentario]',
            )
        ) {
            return;
        }

        evento.preventDefault();

        void this.submeterEdicaoComentario(formulario);
    }

    /**
     * Inicia o carregamento do tooltip de gostos através do rato ou teclado.
     *
     * @param {Event} evento Evento recebido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarAtivacaoTooltipGostos(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const elemento = evento.target.closest(
            '[data-endereco-utilizadores-gosto]'
            + '[data-bs-toggle="tooltip"]',
        );

        if (
            !(elemento instanceof HTMLElement)
            || ['a-carregar', 'carregado'].includes(
                elemento.dataset.estadoTooltipGostos,
            )
        ) {
            return;
        }

        void this.carregarTooltipGostos(elemento);
    }

    /**
     * Carrega os utilizadores que gostaram de um comentário.
     *
     * Apenas a resposta do pedido ainda associado ao elemento pode alterar o
     * tooltip, evitando que respostas antigas substituam informação recente.
     *
     * @param {HTMLElement} elemento Elemento associado ao tooltip.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     */
    async carregarTooltipGostos(elemento) {
        const endereco = this.normalizarEndereco(
            elemento.dataset.enderecoUtilizadoresGosto,
        );

        if (endereco === null) {
            return;
        }

        const identificadorPedido = Symbol();

        this.pedidosTooltipGostos.set(
            elemento,
            identificadorPedido,
        );

        elemento.dataset.estadoTooltipGostos = 'a-carregar';

        try {
            const resposta = await axios.get(endereco);

            if (
                this.pedidosTooltipGostos.get(elemento)
                !== identificadorPedido
            ) {
                return;
            }

            this.pedidosTooltipGostos.delete(elemento);

            if (!elemento.isConnected) {
                return;
            }

            const conteudo = resposta.data
                ?.conteudo_indicador_html;

            if (
                typeof conteudo !== 'string'
                || conteudo.trim() === ''
            ) {
                delete elemento.dataset.estadoTooltipGostos;

                return;
            }

            this.atualizarTooltip(
                elemento,
                conteudo,
            );

            elemento.dataset.estadoTooltipGostos = 'carregado';
        } catch {
            if (
                this.pedidosTooltipGostos.get(elemento)
                !== identificadorPedido
            ) {
                return;
            }

            this.pedidosTooltipGostos.delete(elemento);

            if (elemento.isConnected) {
                delete elemento.dataset.estadoTooltipGostos;
            }
        }
    }

    /**
     * Trata uma interação local ou remota.
     *
     * @param {HTMLButtonElement} botao Botão acionado.
     * @param {string} tipo Tipo de interação validado.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     */
    async tratarInteracao(
        botao,
        tipo,
    ) {
        if (GestorInteracoes.TIPOS_LOCAIS.includes(tipo)) {
            this.atualizarInterface(
                botao,
                tipo,
            );

            return;
        }

        if (
            !GestorInteracoes.TIPOS_REMOTOS.includes(tipo)
            || this.elementosEmProcessamento.has(botao)
        ) {
            return;
        }

        const endereco = this.normalizarEndereco(
            botao.dataset.endereco,
        );

        if (endereco === null) {
            void this.mostrarErroPedido(
                null,
                botao.dataset.mensagemErro,
            );

            return;
        }

        const estadoOriginal = {
            desativado: botao.disabled,
            ariaBusy: botao.getAttribute('aria-busy'),
        };

        this.elementosEmProcessamento.add(botao);

        let pedidoIniciado = false;
        let elementoEliminado = false;

        try {
            if (
                tipo === 'eliminar'
                && !(await this.confirmarEliminacao(botao))
            ) {
                return;
            }

            this.definirBotaoOcupado(botao);
            pedidoIniciado = true;

            const resposta = tipo === 'eliminar'
                ? await axios.delete(endereco)
                : await axios.post(endereco, {});

            const dados = this.eObjeto(resposta.data)
                ? resposta.data
                : {};

            this.atualizarInterface(
                botao,
                tipo,
                dados,
            );

            elementoEliminado = tipo === 'eliminar'
                && !botao.isConnected;

            const mensagemResposta =
                typeof dados.mensagem === 'string'
                    ? dados.mensagem.trim()
                    : '';

            const mensagemConfigurada =
                botao.dataset.mensagemSucesso?.trim()
                ?? '';

            const mensagem =
                mensagemResposta || mensagemConfigurada;

            if (mensagem !== '') {
                void this.mostrarMensagemSucesso(mensagem);
            }
        } catch (erro) {
            void this.mostrarErroPedido(
                erro,
                botao.dataset.mensagemErro,
            );
        } finally {
            this.elementosEmProcessamento.delete(botao);

            if (pedidoIniciado && !elementoEliminado) {
                this.restaurarEstadoBotao(
                    botao,
                    estadoOriginal,
                );
            }
        }
    }

    /**
     * Solicita confirmação antes de uma eliminação.
     *
     * @param {HTMLButtonElement} botao Botão acionado.
     *
     * @returns {Promise<boolean>}
     *     Verdadeiro quando a ação foi confirmada.
     *
     * @since 2.0.0
     */
    async confirmarEliminacao(botao) {
        const mensagem =
            botao.dataset
                .mensagemConfirmacao
                ?.trim()
            || 'Tens a certeza de que pretendes eliminar?';

        return GestorAlertas.confirmar({
            titulo:
                'Confirmar eliminação',

            mensagem,

            textoConfirmar:
                'Sim, eliminar',

            textoCancelar:
                'Cancelar',
        });
    }

    /**
     * Submete a edição de um comentário.
     *
     * @param {HTMLFormElement} formulario Formulário de edição.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     */
    async submeterEdicaoComentario(formulario) {
        if (this.elementosEmProcessamento.has(formulario)) {
            return;
        }

        const botaoSubmeter = formulario.querySelector(
            'button[type="submit"]',
        );

        const campoConteudo = formulario.querySelector(
            '[data-campo-conteudo-comentario]',
        );

        const elementoErro = formulario.querySelector(
            '.invalid-feedback',
        );

        const endereco = this.normalizarEndereco(
            formulario.dataset.endereco,
        );

        if (
            !(botaoSubmeter instanceof HTMLButtonElement)
            || !(campoConteudo instanceof HTMLTextAreaElement)
            || !(elementoErro instanceof HTMLElement)
            || endereco === null
        ) {
            return;
        }

        const mensagemValidacao =
            this.validarConteudoComentario(campoConteudo);

        if (mensagemValidacao !== null) {
            this.apresentarErroCampo(
                campoConteudo,
                elementoErro,
                mensagemValidacao,
            );

            campoConteudo.focus();

            return;
        }

        const devolverFocoAposSucesso = formulario.contains(
            document.activeElement,
        );

        const conteudoOriginalBotao = botaoSubmeter.innerHTML;
        const campoEraSoDeLeitura = campoConteudo.readOnly;
        const ariaBusyOriginal = formulario.getAttribute('aria-busy');
        const estadosBotoes = new Map();

        formulario.querySelectorAll('button').forEach((botao) => {
            if (!(botao instanceof HTMLButtonElement)) {
                return;
            }

            estadosBotoes.set(
                botao,
                botao.disabled,
            );

            botao.disabled = true;
        });

        this.elementosEmProcessamento.add(formulario);

        formulario.setAttribute(
            'aria-busy',
            'true',
        );

        campoConteudo.readOnly = true;

        botaoSubmeter.innerHTML = [
            '<span class="spinner-border spinner-border-sm"',
            'role="status" aria-hidden="true"></span>',
            '<span>A guardar...</span>',
        ].join(' ');

        this.limparErroCampo(
            campoConteudo,
            elementoErro,
        );

        try {
            const resposta = await axios.patch(
                endereco,
                {
                    conteudo: campoConteudo.value,
                },
            );

            const conteudoAtualizado = resposta.data
                ?.comentario
                ?.conteudo;

            const editadoEm =
                resposta.data
                    ?.comentario
                    ?.editado_em;

            const comentario = formulario.closest(
                '.comentario',
            );

            const apresentacao = comentario?.querySelector(
                '[data-conteudo-comentario] p',
            );

            const contentorFormulario = formulario.closest(
                '.contentor-edicao-comentario',
            );

            const contentorConteudo = comentario?.querySelector(
                '[data-conteudo-comentario]',
            );

            const indicadorEditado = comentario?.querySelector(
                '[data-indicador-comentario-editado]',
            );

            if (
                typeof conteudoAtualizado !== 'string'
                || !(apresentacao instanceof HTMLElement)
                || !(contentorFormulario instanceof HTMLElement)
                || !(contentorConteudo instanceof HTMLElement)
            ) {
                throw new Error(
                    'A resposta da edição do comentário é inválida.',
                );
            }

            this.apresentarTextoComQuebras(
                apresentacao,
                conteudoAtualizado,
            );

            campoConteudo.value = conteudoAtualizado;
            campoConteudo.dataset.valorOriginal = conteudoAtualizado;

            if (indicadorEditado instanceof HTMLElement) {
                indicadorEditado.hidden =
                    !(
                        typeof editadoEm === 'string'
                        && editadoEm.trim() !== ''
                    );
            }

            this.definirVisibilidade(
                contentorFormulario,
                false,
            );

            contentorConteudo.hidden = false;

            this.atualizarControladores(
                contentorFormulario.id,
                false,
            );

            if (devolverFocoAposSucesso) {
                this.focarControlador(
                    contentorFormulario.id,
                );
            }

            const mensagem = resposta.data?.mensagem;

            if (
                typeof mensagem === 'string'
                && mensagem.trim() !== ''
            ) {
                void this.mostrarMensagemSucesso(
                    mensagem,
                );
            }
        } catch (erro) {
            const mensagemValidacaoServidor =
                axios.isAxiosError(erro)
                && erro.response?.status === 422
                    ? erro.response.data
                        ?.errors
                        ?.conteudo
                        ?.[0]
                    : null;

            if (
                typeof mensagemValidacaoServidor === 'string'
                && mensagemValidacaoServidor.trim() !== ''
            ) {
                this.apresentarErroCampo(
                    campoConteudo,
                    elementoErro,
                    mensagemValidacaoServidor,
                );

                campoConteudo.focus();
            } else {
                void this.mostrarErroPedido(erro);
            }
        } finally {
            estadosBotoes.forEach(
                (desativado, botao) => {
                    botao.disabled = desativado;
                },
            );

            botaoSubmeter.innerHTML = conteudoOriginalBotao;
            campoConteudo.readOnly = campoEraSoDeLeitura;

            if (ariaBusyOriginal === null) {
                formulario.removeAttribute('aria-busy');
            } else {
                formulario.setAttribute(
                    'aria-busy',
                    ariaBusyOriginal,
                );
            }

            this.elementosEmProcessamento.delete(formulario);
        }
    }

    /**
     * Valida o conteúdo de um comentário editado.
     *
     * @param {HTMLTextAreaElement} campo Campo do conteúdo.
     *
     * @returns {string|null} Mensagem de erro ou nulo.
     *
     * @since 2.0.0
     */
    validarConteudoComentario(campo) {
        if (campo.value.trim() === '') {
            return 'Por favor, insere o texto do comentário.';
        }

        if (
            Number.isInteger(campo.maxLength)
            && campo.maxLength > 0
            && campo.value.length > campo.maxLength
        ) {
            return `O comentário não pode ter mais de ${campo.maxLength} caracteres.`;
        }

        return null;
    }

    /**
     * Atualiza a interface depois de uma interação.
     *
     * @param {HTMLButtonElement} botao Botão acionado.
     * @param {string} tipo Tipo da interação.
     * @param {Record<string, unknown>} dados Dados devolvidos.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    atualizarInterface(
        botao,
        tipo,
        dados = {},
    ) {
        switch (tipo) {
            case 'alternar-gosto':
                this.atualizarGosto(
                    botao,
                    dados,
                );
                break;

            case 'alternar-audicao':
                this.atualizarAudicao(
                    botao,
                    dados,
                );
                break;

            case 'eliminar':
                this.atualizarEliminacaoComentario(
                    botao,
                    dados,
                );
                break;

            case 'alternar-resposta-comentario':
                this.alternarFormularioResposta(
                    botao,
                    true,
                );
                break;

            case 'cancelar-resposta-comentario':
                this.alternarFormularioResposta(
                    botao,
                    false,
                );
                break;

            case 'iniciar-edicao-comentario':
                this.iniciarEdicaoComentario(
                    botao,
                );
                break;

            case 'cancelar-edicao-comentario':
                this.cancelarEdicaoComentario(
                    botao,
                );
                break;

            default:
                break;
        }
    }

    /**
     * Atualiza a apresentação de um gosto.
     *
     * @param {HTMLButtonElement} botao Botão da interação.
     * @param {Record<string, unknown>} dados Dados devolvidos.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarGosto(
        botao,
        dados,
    ) {
        if (typeof dados.adicionado !== 'boolean') {
            return;
        }

        const adicionado = dados.adicionado;

        const numeroGostos = Number.parseInt(
            String(dados.numero_gostos ?? ''),
            10,
        );

        const icone = botao.querySelector(
            '[data-icone-gosto]',
        );

        const quantidade = botao.querySelector(
            '[data-quantidade-gostos]',
        );

        if (icone instanceof HTMLElement) {
            icone.classList.toggle(
                'bi-heart',
                !adicionado,
            );

            icone.classList.toggle(
                'bi-heart-fill',
                adicionado,
            );

            icone.classList.toggle(
                'text-danger',
                adicionado,
            );
        }

        if (
            quantidade instanceof HTMLElement
            && Number.isInteger(numeroGostos)
            && numeroGostos >= 0
        ) {
            quantidade.textContent = String(numeroGostos);
        }

        botao.setAttribute(
            'aria-pressed',
            String(adicionado),
        );

        if (
            Number.isInteger(numeroGostos)
            && numeroGostos >= 0
        ) {
            const unidade = numeroGostos === 1
                ? 'gosto'
                : 'gostos';

            botao.setAttribute(
                'aria-label',
                `Gosto. ${numeroGostos} ${unidade}.`,
            );
        }

        this.pedidosTooltipGostos.delete(botao);

        const conteudo = dados.conteudo_indicador_html;

        if (
            typeof conteudo === 'string'
            && conteudo.trim() !== ''
        ) {
            this.atualizarTooltip(
                botao,
                conteudo,
            );

            botao.dataset.estadoTooltipGostos = 'carregado';

            return;
        }

        delete botao.dataset.estadoTooltipGostos;
    }

    /**
     * Atualiza a apresentação de uma audição.
     *
     * @param {HTMLButtonElement} botao Botão da interação.
     * @param {Record<string, unknown>} dados Dados devolvidos.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarAudicao(
        botao,
        dados,
    ) {
        if (typeof dados.marcado_como_ouvido !== 'boolean') {
            return;
        }

        const tipoAudivel = botao.dataset.tipoAudivel;

        if (
            tipoAudivel !== 'seccao-metal-thursday'
            && tipoAudivel !== 'metal-thursday'
        ) {
            return;
        }

        const marcadoComoOuvido =
            dados.marcado_como_ouvido;

        const numeroAudicoes = Number.parseInt(
            String(dados.numero_audicoes ?? ''),
            10,
        );

        const texto =
            tipoAudivel === 'seccao-metal-thursday'
                ? marcadoComoOuvido
                    ? 'Ouvido'
                    : 'Marcar como ouvido'
                : marcadoComoOuvido
                    ? 'Ouvida'
                    : 'Marcar como ouvida';

        const descricaoAcao =
            tipoAudivel === 'seccao-metal-thursday'
                ? marcadoComoOuvido
                    ? 'Marcar como não ouvido'
                    : 'Marcar como ouvido'
                : marcadoComoOuvido
                    ? 'Marcar MetalThursday como não ouvida'
                    : 'Marcar MetalThursday como ouvida';

        const textoBotao = botao.querySelector(
            '[data-texto-interacao]',
        );

        if (textoBotao instanceof HTMLElement) {
            textoBotao.textContent = texto;
        }

        botao.removeAttribute('aria-pressed');

        botao.setAttribute(
            'aria-label',
            descricaoAcao,
        );

        const contentorInteracoes = botao.closest(
            '[data-contentor-interacoes]',
        );

        const apresentacaoAudicoes =
            contentorInteracoes?.querySelector(
                '.apresentacao-audicoes',
            );

        if (!(apresentacaoAudicoes instanceof HTMLElement)) {
            return;
        }

        const quantidade = apresentacaoAudicoes.querySelector(
            '.quantidade-audicoes',
        );

        if (
            quantidade instanceof HTMLElement
            && Number.isInteger(numeroAudicoes)
            && numeroAudicoes >= 0
        ) {
            quantidade.textContent =
                String(numeroAudicoes);
        }

        if (
            Number.isInteger(numeroAudicoes)
            && numeroAudicoes >= 0
        ) {
            apresentacaoAudicoes.setAttribute(
                'aria-label',
                `Consultar detalhes das audições. Total: ${numeroAudicoes}.`,
            );
        }

        const conteudo =
            dados.conteudo_indicador_html;

        if (
            typeof conteudo === 'string'
            && conteudo.trim() !== ''
        ) {
            this.atualizarTooltip(
                apresentacaoAudicoes,
                conteudo,
            );
        }
    }

    /**
     * Atualiza a interface depois da eliminação de um comentário.
     *
     * Um comentário com respostas é substituído por um marcador estrutural.
     * Uma folha é removida juntamente com eventuais tombstones ancestrais que o
     * servidor tenha determinado como desnecessários.
     *
     * @param {HTMLButtonElement} botao Botão utilizado na eliminação.
     * @param {Record<string, unknown>} dados Dados devolvidos pelo servidor.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarEliminacaoComentario(
        botao,
        dados,
    ) {
        const comentario =
            botao.closest(
                '.comentario',
            );

        if (!(comentario instanceof HTMLElement)) {
            this.removerElemento(
                botao,
            );

            return;
        }

        const modo =
            typeof dados.modo_eliminacao === 'string'
                ? dados.modo_eliminacao.trim()
                : '';

        if (
            modo !== 'marcador'
            && modo !== 'remover'
        ) {
            this.removerElemento(
                botao,
            );

            return;
        }

        const numeroConteudosRemovidos =
            Number.parseInt(
                String(
                    dados.numero_conteudos_removidos
                    ?? '',
                ),
                10,
            );

        if (
            Number.isInteger(
                numeroConteudosRemovidos,
            )
            && numeroConteudosRemovidos > 0
        ) {
            this.decrementarContadorComentarios(
                comentario,
                numeroConteudosRemovidos,
            );
        }

        if (modo === 'marcador') {
            this.substituirComentarioPorMarcador(
                comentario,
                dados,
            );

            return;
        }

        this.removerComentariosEliminados(
            comentario,
            dados,
        );

        this.atualizarPaiDepoisEliminacao(
            dados.pai_atualizado,
        );
    }

    /**
     * Substitui um comentário eliminado pelo marcador estrutural devolvido pelo
     * servidor.
     *
     * O ramo é recolhido depois da substituição. As respostas continuam
     * disponíveis através do respetivo carregamento assíncrono.
     *
     * @param {HTMLElement} comentario Comentário original.
     * @param {Record<string, unknown>} dados Dados devolvidos.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    substituirComentarioPorMarcador(
        comentario,
        dados,
    ) {
        const html =
            typeof dados.comentario_html === 'string'
                ? dados.comentario_html.trim()
                : '';

        if (html === '') {
            return;
        }

        const novoComentario =
            this.criarComentarioAPartirHtml(
                html,
            );

        if (!(novoComentario instanceof HTMLElement)) {
            return;
        }

        const nivelVisual =
            comentario.dataset
                .nivelVisual
                ?.trim();

        if (
            typeof nivelVisual === 'string'
            && nivelVisual !== ''
        ) {
            novoComentario.dataset
                .nivelVisual =
                    nivelVisual;
        }

        this.eliminarTooltips(
            comentario,
        );

        comentario.replaceWith(
            novoComentario,
        );
    }

    /**
     * Remove da interface todos os comentários que o servidor indicou como
     * eliminados logicamente.
     *
     * A lista pode conter, além da folha eliminada pelo utilizador, tombstones
     * ancestrais que deixaram de ser necessários.
     *
     * @param {HTMLElement} comentarioOriginal Comentário eliminado.
     * @param {Record<string, unknown>} dados Dados devolvidos.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    removerComentariosEliminados(
        comentarioOriginal,
        dados,
    ) {
        const identificadores =
            Array.isArray(
                dados.comentarios_removidos_ids,
            )
                ? dados.comentarios_removidos_ids
                : [];

        const identificadoresValidos =
            identificadores
                .map(
                    (valor) =>
                        Number.parseInt(
                            String(
                                valor,
                            ),
                            10,
                        ),
                )
                .filter(
                    (identificador) =>
                        Number.isSafeInteger(
                            identificador,
                        )
                        && identificador > 0,
                );

        if (identificadoresValidos.length === 0) {
            this.removerElementoHtml(
                comentarioOriginal,
            );

            return;
        }

        identificadoresValidos.forEach(
            (identificador) => {
                const elemento =
                    document.getElementById(
                        `comentario-${identificador}`,
                    );

                if (
                    !(elemento instanceof HTMLElement)
                    || !this.contentor.contains(
                        elemento,
                    )
                ) {
                    return;
                }

                this.removerElementoHtml(
                    elemento,
                );
            },
        );
    }

    /**
     * Atualiza o alternador de respostas do primeiro pai que permaneceu ativo
     * depois de uma eliminação.
     *
     * @param {unknown} dadosPai Dados do pai devolvidos pelo servidor.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarPaiDepoisEliminacao(
        dadosPai,
    ) {
        if (!this.eObjeto(dadosPai)) {
            return;
        }

        const identificador =
            Number.parseInt(
                String(
                    dadosPai.id
                    ?? '',
                ),
                10,
            );

        const numeroRespostas =
            Number.parseInt(
                String(
                    dadosPai.numero_respostas
                    ?? '',
                ),
                10,
            );

        if (
            !Number.isSafeInteger(
                identificador,
            )
            || identificador < 1
            || !Number.isSafeInteger(
                numeroRespostas,
            )
            || numeroRespostas < 0
        ) {
            return;
        }

        const comentarioPai =
            document.getElementById(
                `comentario-${identificador}`,
            );

        if (
            !(comentarioPai instanceof HTMLElement)
            || !this.contentor.contains(
                comentarioPai,
            )
        ) {
            return;
        }

        const alternador =
            Array.from(
                comentarioPai.querySelectorAll(
                    'button[data-acao-comentarios="alternar-respostas"]',
                ),
            ).find(
                (elemento) =>
                    elemento
                        instanceof HTMLButtonElement
                    && elemento.closest(
                        '.comentario',
                    ) === comentarioPai,
            );

        if (!(alternador instanceof HTMLButtonElement)) {
            return;
        }

        alternador.dataset
            .quantidadeRespostas =
                String(
                    numeroRespostas,
                );

        alternador.hidden =
            numeroRespostas === 0;

        const texto =
            alternador.querySelector(
                '[data-texto-alternador-respostas]',
            );

        if (numeroRespostas === 0) {
            alternador.setAttribute(
                'aria-expanded',
                'false',
            );

            const identificadorContentor =
                alternador.getAttribute(
                    'aria-controls',
                );

            if (identificadorContentor !== null) {
                const contentorRespostas =
                    document.getElementById(
                        identificadorContentor,
                    );

                if (
                    contentorRespostas
                    instanceof HTMLElement
                ) {
                    contentorRespostas.hidden =
                        true;
                }
            }

            if (texto instanceof HTMLElement) {
                texto.textContent =
                    'Ver 0 respostas';
            }

            return;
        }

        if (
            alternador.getAttribute(
                'aria-expanded',
            ) === 'true'
        ) {
            return;
        }

        if (texto instanceof HTMLElement) {
            texto.textContent =
                numeroRespostas === 1
                    ? 'Ver 1 resposta'
                    : `Ver ${numeroRespostas} respostas`;
        }
    }

    /**
     * Decrementa o contador global de comentários da conversa apresentada.
     *
     * Tombstones estruturais não são contabilizados. Por isso o valor decrementado
     * corresponde ao número de conteúdos efetivamente eliminados indicado pelo
     * servidor, e não ao número de nós removidos da árvore.
     *
     * @param {HTMLElement} comentario Comentário que originou a eliminação.
     * @param {number} quantidade Quantidade de conteúdos eliminados.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    decrementarContadorComentarios(
        comentario,
        quantidade,
    ) {
        const seccaoComentarios =
            comentario.closest(
                'section[aria-label="Comentários"]',
            );

        if (!(seccaoComentarios instanceof HTMLElement)) {
            return;
        }

        const contentorColapsavel =
            seccaoComentarios.closest(
                '.collapse',
            );

        if (
            !(contentorColapsavel instanceof HTMLElement)
            || contentorColapsavel.id.trim() === ''
        ) {
            return;
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

        if (!(controlador instanceof HTMLButtonElement)) {
            return;
        }

        const contador =
            controlador.querySelector(
                '[data-quantidade-comentarios]',
            );

        if (!(contador instanceof HTMLElement)) {
            return;
        }

        const quantidadeAtual =
            Number.parseInt(
                contador.textContent?.trim()
                ?? '',
                10,
            );

        if (
            !Number.isSafeInteger(
                quantidadeAtual,
            )
            || quantidadeAtual < 0
        ) {
            return;
        }

        contador.textContent =
            String(
                Math.max(
                    0,
                    quantidadeAtual - quantidade,
                ),
            );
    }

    /**
     * Converte o fragmento HTML de um comentário num elemento válido.
     *
     * @param {string} html Fragmento renderizado pelo servidor.
     *
     * @returns {HTMLElement|null} Comentário criado ou nulo.
     *
     * @since 2.0.0
     */
    criarComentarioAPartirHtml(
        html,
    ) {
        const modelo =
            document.createElement(
                'template',
            );

        modelo.innerHTML =
            html.trim();

        const elementos =
            Array.from(
                modelo.content.children,
            );

        if (
            elementos.length !== 1
            || !(elementos[0] instanceof HTMLElement)
            || !elementos[0]
                .classList
                .contains(
                    'comentario',
                )
        ) {
            return null;
        }

        return elementos[0];
    }

    /**
     * Elimina instâncias de tooltip existentes dentro de um elemento.
     *
     * @param {HTMLElement} elemento Elemento processado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    eliminarTooltips(
        elemento,
    ) {
        [
            elemento,
            ...elemento.querySelectorAll(
                '[data-bs-toggle="tooltip"]',
            ),
        ].forEach(
            (elementoTooltip) => {
                if (
                    elementoTooltip
                    instanceof HTMLElement
                ) {
                    Tooltip.getInstance(
                        elementoTooltip,
                    )?.dispose();
                }
            },
        );
    }

    /**
     * Remove um elemento da DOM e atualiza o respetivo contentor.
     *
     * @param {HTMLElement} elemento Elemento removido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    removerElementoHtml(
        elemento,
    ) {
        const contentorPai =
            elemento.parentElement;

        this.eliminarTooltips(
            elemento,
        );

        elemento.remove();

        this.atualizarContentorDepoisEliminacao(
            contentorPai,
        );
    }

    /**
     * Remove o elemento associado a uma eliminação genérica.
     *
     * @param {HTMLButtonElement} botao Botão da interação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    removerElemento(
        botao,
    ) {
        const seletor =
            botao.dataset
                .seletorElementoRemovivel
                ?.trim();

        if (!seletor) {
            return;
        }

        let elemento;

        try {
            elemento =
                botao.closest(
                    seletor,
                )
                ?? this.contentor.querySelector(
                    seletor,
                );
        } catch {
            return;
        }

        if (!(elemento instanceof HTMLElement)) {
            return;
        }

        this.removerElementoHtml(
            elemento,
        );
    }

    /**
     * Atualiza o contentor que recebeu um elemento eliminado.
     *
     * @param {HTMLElement|null} contentor Contentor anterior.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarContentorDepoisEliminacao(contentor) {
        if (!(contentor instanceof HTMLElement)) {
            return;
        }

        if (
            contentor.classList.contains(
                'respostas-comentario',
            )
            && !contentor.querySelector(
                ':scope > .comentario',
            )
        ) {
            contentor.hidden = true;

            return;
        }

        if (
            contentor.classList.contains(
                'lista-comentarios',
            )
            && !contentor.querySelector(
                ':scope > .comentario',
            )
            && !contentor.querySelector(
                '.sem-comentarios',
            )
        ) {
            const mensagem = document.createElement('p');

            mensagem.className =
                'sem-comentarios small text-muted text-center';

            mensagem.textContent =
                'Ainda não existem comentários. Sê a primeira pessoa a comentar!';

            contentor.append(mensagem);
        }
    }

    /**
     * Alterna o formulário de resposta.
     *
     * @param {HTMLButtonElement} botao Botão da interação.
     * @param {boolean} alternar
     *     Indica se o estado atual deve ser alternado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    alternarFormularioResposta(
        botao,
        alternar,
    ) {
        const contentor = this.obterElementoControlado(
            botao,
        );

        if (!(contentor instanceof HTMLElement)) {
            return;
        }

        const focoEstavaNoContentor = contentor.contains(
            document.activeElement,
        );

        const mostrar = alternar
            ? contentor.hidden
            : false;

        this.definirVisibilidade(
            contentor,
            mostrar,
        );

        this.atualizarControladores(
            contentor.id,
            mostrar,
        );

        if (mostrar) {
            contentor.querySelector(
                'textarea',
            )?.focus();

            return;
        }

        const formulario = contentor.querySelector(
            'form',
        );

        const campo = formulario?.querySelector(
            'textarea',
        );

        const elementoErro = formulario?.querySelector(
            '.invalid-feedback',
        );

        if (formulario instanceof HTMLFormElement) {
            formulario.reset();
        }

        if (
            campo instanceof HTMLTextAreaElement
            && elementoErro instanceof HTMLElement
        ) {
            this.limparErroCampo(
                campo,
                elementoErro,
            );
        }

        if (focoEstavaNoContentor) {
            this.focarControlador(contentor.id);
        }
    }

    /**
     * Inicia a edição de um comentário.
     *
     * @param {HTMLButtonElement} botao Botão da interação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    iniciarEdicaoComentario(botao) {
        const contentorFormulario =
            this.obterElementoControlado(botao);

        const comentario = botao.closest(
            '.comentario',
        );

        const conteudo = comentario?.querySelector(
            '[data-conteudo-comentario]',
        );

        const campo = contentorFormulario?.querySelector(
            '[data-campo-conteudo-comentario]',
        );

        if (
            !(contentorFormulario instanceof HTMLElement)
            || !(conteudo instanceof HTMLElement)
            || !(campo instanceof HTMLTextAreaElement)
        ) {
            return;
        }

        if (!contentorFormulario.hidden) {
            campo.focus();

            return;
        }

        campo.dataset.valorOriginal = campo.value;

        conteudo.hidden = true;

        this.definirVisibilidade(
            contentorFormulario,
            true,
        );

        this.atualizarControladores(
            contentorFormulario.id,
            true,
        );

        campo.focus();

        campo.setSelectionRange(
            campo.value.length,
            campo.value.length,
        );
    }

    /**
     * Cancela a edição de um comentário.
     *
     * @param {HTMLButtonElement} botao Botão da interação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    cancelarEdicaoComentario(botao) {
        const contentorFormulario =
            this.obterElementoControlado(botao);

        const comentario = botao.closest(
            '.comentario',
        );

        const conteudo = comentario?.querySelector(
            '[data-conteudo-comentario]',
        );

        const campo = contentorFormulario?.querySelector(
            '[data-campo-conteudo-comentario]',
        );

        const elementoErro =
            contentorFormulario?.querySelector(
                '.invalid-feedback',
            );

        if (
            !(contentorFormulario instanceof HTMLElement)
            || !(conteudo instanceof HTMLElement)
            || !(campo instanceof HTMLTextAreaElement)
        ) {
            return;
        }

        const focoEstavaNoContentor =
            contentorFormulario.contains(
                document.activeElement,
            );

        if (
            typeof campo.dataset.valorOriginal === 'string'
        ) {
            campo.value = campo.dataset.valorOriginal;
        }

        if (elementoErro instanceof HTMLElement) {
            this.limparErroCampo(
                campo,
                elementoErro,
            );
        }

        this.definirVisibilidade(
            contentorFormulario,
            false,
        );

        conteudo.hidden = false;

        this.atualizarControladores(
            contentorFormulario.id,
            false,
        );

        if (focoEstavaNoContentor) {
            this.focarControlador(
                contentorFormulario.id,
            );
        }
    }

    /**
     * Atualiza os botões que controlam um contentor.
     *
     * @param {string} identificador Identificador do contentor.
     * @param {boolean} expandido Estado de expansão.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarControladores(
        identificador,
        expandido,
    ) {
        this.obterControladores(
            identificador,
        ).forEach((botao) => {
            if (!botao.hasAttribute('aria-expanded')) {
                return;
            }

            botao.setAttribute(
                'aria-expanded',
                String(expandido),
            );
        });
    }

    /**
     * Obtém os botões que controlam um contentor.
     *
     * @param {string} identificador Identificador do contentor.
     *
     * @returns {Array<HTMLButtonElement>} Botões encontrados.
     *
     * @since 2.0.0
     */
    obterControladores(identificador) {
        if (
            typeof identificador !== 'string'
            || identificador === ''
        ) {
            return [];
        }

        return Array.from(
            this.contentor.querySelectorAll(
                'button[data-tipo-interacao][aria-controls]',
            ),
        ).filter(
            (botao) =>
                botao instanceof HTMLButtonElement
                && botao.getAttribute(
                    'aria-controls',
                ) === identificador,
        );
    }

    /**
     * Devolve o foco ao primeiro controlador visível de um contentor.
     *
     * @param {string} identificador Identificador do contentor.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    focarControlador(identificador) {
        const controlador = this.obterControladores(
            identificador,
        ).find(
            (botao) =>
                !botao.disabled
                && !botao.hidden
                && botao.closest('[hidden]') === null,
        );

        controlador?.focus();
    }

    /**
     * Obtém o elemento identificado por `aria-controls`.
     *
     * @param {HTMLElement} botao Botão controlador.
     *
     * @returns {HTMLElement|null} Elemento encontrado ou nulo.
     *
     * @since 2.0.0
     */
    obterElementoControlado(botao) {
        const identificador = botao.getAttribute(
            'aria-controls',
        )?.trim();

        if (!identificador) {
            return null;
        }

        const elemento = document.getElementById(
            identificador,
        );

        return elemento instanceof HTMLElement
            ? elemento
            : null;
    }

    /**
     * Atualiza a visibilidade e o estado acessível de um elemento.
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
            elemento.removeAttribute('aria-hidden');

            return;
        }

        elemento.setAttribute(
            'aria-hidden',
            'true',
        );
    }

    /**
     * Apresenta um erro num campo.
     *
     * @param {HTMLTextAreaElement} campo Campo validado.
     * @param {HTMLElement} elementoErro Elemento da mensagem.
     * @param {string} mensagem Mensagem apresentada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarErroCampo(
        campo,
        elementoErro,
        mensagem,
    ) {
        campo.classList.add('is-invalid');

        campo.setAttribute(
            'aria-invalid',
            'true',
        );

        campo.setCustomValidity(mensagem);

        elementoErro.textContent = mensagem;
        elementoErro.classList.add('d-block');
        elementoErro.removeAttribute('hidden');
    }

    /**
     * Limpa o erro de um campo.
     *
     * @param {HTMLTextAreaElement} campo Campo validado.
     * @param {HTMLElement} elementoErro Elemento da mensagem.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    limparErroCampo(
        campo,
        elementoErro,
    ) {
        campo.classList.remove('is-invalid');
        campo.removeAttribute('aria-invalid');
        campo.setCustomValidity('');

        elementoErro.textContent = '';
        elementoErro.classList.remove('d-block');

        elementoErro.setAttribute(
            'hidden',
            '',
        );
    }

    /**
     * Apresenta texto preservando as quebras de linha.
     *
     * @param {HTMLElement} elemento Elemento de destino.
     * @param {string} texto Texto apresentado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarTextoComQuebras(
        elemento,
        texto,
    ) {
        elemento.replaceChildren();

        texto.split(/\r?\n/u).forEach(
            (linha, indice) => {
                if (indice > 0) {
                    elemento.append(
                        document.createElement('br'),
                    );
                }

                elemento.append(
                    document.createTextNode(linha),
                );
            },
        );
    }

    /**
     * Atualiza um tooltip do Bootstrap sem destruir a instância existente.
     *
     * @param {HTMLElement} elemento Elemento associado.
     * @param {string} conteudo Conteúdo do tooltip.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    atualizarTooltip(
        elemento,
        conteudo,
    ) {
        elemento.setAttribute(
            'data-bs-title',
            conteudo,
        );

        const instancia = Tooltip.getOrCreateInstance(
            elemento,
        );

        instancia.setContent({
            '.tooltip-inner': conteudo,
        });
    }

    /**
     * Normaliza um endereço da origem atual.
     *
     * @param {unknown} endereco Endereço recebido.
     *
     * @returns {string|null} Endereço validado ou nulo.
     *
     * @since 2.0.0
     */
    normalizarEndereco(endereco) {
        if (
            typeof endereco !== 'string'
            || endereco.trim() === ''
        ) {
            return null;
        }

        try {
            const url = new URL(
                endereco,
                window.location.origin,
            );

            if (
                ![
                    'http:',
                    'https:',
                ].includes(url.protocol)
                || url.origin !== window.location.origin
            ) {
                return null;
            }

            return url.href;
        } catch {
            return null;
        }
    }

    /**
     * Apresenta uma mensagem de sucesso.
     *
     * @param {unknown} mensagem Mensagem apresentada.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    async mostrarMensagemSucesso(
        mensagem,
    ) {
        if (typeof mensagem !== 'string') {
            return;
        }

        const texto =
            mensagem.trim();

        if (texto === '') {
            return;
        }

        await GestorAlertas.mostrarSucesso(
            texto,
        );
    }

    /**
     * Apresenta a mensagem de erro de um pedido.
     *
     * @param {unknown} erro Erro capturado.
     * @param {unknown} mensagemPredefinida Mensagem configurada.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    async mostrarErroPedido(
        erro,
        mensagemPredefinida = undefined,
    ) {
        const mensagemResposta =
            axios.isAxiosError(
                erro,
            )
            && typeof erro.response
                ?.data
                ?.mensagem === 'string'
                ? erro.response.data
                    .mensagem
                    .trim()
                : '';

        const mensagemConfigurada =
            typeof mensagemPredefinida
                === 'string'
                ? mensagemPredefinida.trim()
                : '';

        const mensagem =
            mensagemResposta
            || mensagemConfigurada
            || 'Ocorreu um erro ao processar a ação.';

        await GestorAlertas.mostrarErro(
            mensagem,
        );
    }

    /**
     * Marca um botão como ocupado durante um pedido remoto.
     *
     * @param {HTMLButtonElement} botao Botão atualizado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    definirBotaoOcupado(botao) {
        botao.disabled = true;

        botao.setAttribute(
            'aria-busy',
            'true',
        );
    }

    /**
     * Restaura o estado de um botão depois de um pedido remoto.
     *
     * @param {HTMLButtonElement} botao Botão atualizado.
     * @param {{desativado: boolean, ariaBusy: string|null}} estado Estado
     *     anterior.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    restaurarEstadoBotao(
        botao,
        estado,
    ) {
        botao.disabled = estado.desativado;

        if (estado.ariaBusy === null) {
            botao.removeAttribute('aria-busy');

            return;
        }

        botao.setAttribute(
            'aria-busy',
            estado.ariaBusy,
        );
    }

    /**
     * Verifica se um valor é um objeto não nulo.
     *
     * @param {unknown} valor Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando o valor é um objeto simples.
     *
     * @since 2.0.0
     */
    eObjeto(valor) {
        return typeof valor === 'object'
            && valor !== null
            && !Array.isArray(valor);
    }

    /**
     * Obtém obrigatoriamente o contentor principal.
     *
     * @param {unknown} seletor Seletor CSS.
     *
     * @returns {HTMLElement} Contentor encontrado.
     *
     * @throws {TypeError} Quando o seletor ou o contentor não são válidos.
     *
     * @since 2.0.0
     */
    obterContentor(seletor) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                'O seletor do contentor das interações é obrigatório.',
            );
        }

        const seletorNormalizado = seletor.trim();

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

        if (!(elemento instanceof HTMLElement)) {
            throw new TypeError(
                `Não foi encontrado um contentor de interações válido através de "${seletorNormalizado}".`,
            );
        }

        return elemento;
    }
}

export default GestorInteracoes;
