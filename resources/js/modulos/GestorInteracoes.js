import axios from 'axios';
import { Tooltip } from 'bootstrap';
import Swal from 'sweetalert2';

/**
 * Gere as interações assíncronas e os controlos dos comentários.
 *
 * @since 1.0.0
 * @version 4.0.0
 */
class GestorInteracoes {
    /**
     * Tipos tratados apenas na interface.
     *
     * @type {ReadonlyArray<string>}
     *
     * @since 4.0.0
     * @version 1.0.0
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
     * @since 4.0.0
     * @version 1.0.0
     */
    static TIPOS_REMOTOS = Object.freeze([
        'alternar-gosto',
        'alternar-audicao',
        'eliminar',
    ]);

    /**
     * Cria o gestor.
     *
     * @param {string} seletorContentor Seletor do contentor principal.
     *
     * @throws {TypeError} Quando o seletor não é válido.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    constructor(seletorContentor = 'body') {
        this.contentor = this.obterContentor(
            seletorContentor,
        );

        this.elementosEmProcessamento =
            new WeakSet();

        this.iniciado =
            false;

        this.aoClicar = (evento) => {
            this.tratarClique(
                evento,
            );
        };

        this.aoSubmeter = (evento) => {
            this.tratarSubmissao(
                evento,
            );
        };

        this.aoSobrepor = (evento) => {
            this.tratarSobreposicao(
                evento,
            );
        };

        if (this.contentor instanceof HTMLElement) {
            this.iniciar();
        }
    }

    /**
     * Inicia os eventos delegados.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    iniciar() {
        if (
            !(this.contentor instanceof HTMLElement)
            || this.iniciado
        ) {
            return;
        }

        this.contentor.addEventListener(
            'click',
            this.aoClicar,
        );

        this.contentor.addEventListener(
            'submit',
            this.aoSubmeter,
        );

        this.contentor.addEventListener(
            'mouseover',
            this.aoSobrepor,
        );

        this.iniciado =
            true;
    }

    /**
     * Trata os cliques nos botões de interação.
     *
     * @param {MouseEvent} evento Evento de clique.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 3.0.0
     */
    tratarClique(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botao =
            evento.target.closest(
                'button[data-tipo-interacao]',
            );

        if (!(botao instanceof HTMLButtonElement)) {
            return;
        }

        evento.preventDefault();

        this.tratarInteracao(
            botao,
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
     * @version 3.0.0
     */
    tratarSubmissao(evento) {
        const formulario =
            evento.target;

        if (
            !(formulario instanceof HTMLFormElement)
            || !formulario.matches(
                'form[data-formulario-edicao-comentario]',
            )
        ) {
            return;
        }

        evento.preventDefault();

        this.submeterEdicaoComentario(
            formulario,
        );
    }

    /**
     * Inicia o carregamento do tooltip de gostos.
     *
     * @param {MouseEvent} evento Evento de sobreposição.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 3.0.0
     */
    tratarSobreposicao(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const elemento =
            evento.target.closest(
                '[data-endereco-utilizadores-gosto]'
                + '[data-bs-toggle="tooltip"]',
            );

        if (
            !(elemento instanceof HTMLElement)
            || [
                'a-carregar',
                'carregado',
            ].includes(
                elemento.dataset
                    .estadoTooltipGostos,
            )
        ) {
            return;
        }

        this.carregarTooltipGostos(
            elemento,
        );
    }

    /**
     * Carrega os utilizadores que gostaram de um comentário.
     *
     * @param {HTMLElement} elemento Elemento associado ao tooltip.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 4.0.0
     */
    async carregarTooltipGostos(elemento) {
        const endereco =
            this.normalizarEndereco(
                elemento.dataset
                    .enderecoUtilizadoresGosto,
            );

        if (endereco === null) {
            return;
        }

        elemento.dataset.estadoTooltipGostos =
            'a-carregar';

        try {
            const resposta =
                await axios.get(
                    endereco,
                    {
                        headers:
                            this.obterCabecalhosJson(),
                    },
                );

            const conteudo =
                resposta.data
                    ?.conteudo_indicador_html;

            if (
                typeof conteudo !== 'string'
                || conteudo.trim() === ''
            ) {
                delete elemento.dataset
                    .estadoTooltipGostos;

                return;
            }

            this.atualizarTooltip(
                elemento,
                conteudo,
            );

            elemento.dataset.estadoTooltipGostos =
                'carregado';
        } catch {
            delete elemento.dataset
                .estadoTooltipGostos;
        }
    }

    /**
     * Trata uma interação.
     *
     * @param {HTMLButtonElement} botao Botão acionado.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 4.0.0
     */
    async tratarInteracao(botao) {
        const tipo =
            botao.dataset.tipoInteracao
                ?.trim()
            ?? '';

        if (
            GestorInteracoes.TIPOS_LOCAIS.includes(
                tipo,
            )
        ) {
            this.atualizarInterface(
                botao,
                tipo,
            );

            return;
        }

        if (
            !GestorInteracoes.TIPOS_REMOTOS.includes(
                tipo,
            )
            || this.elementosEmProcessamento.has(
                botao,
            )
        ) {
            return;
        }

        const endereco =
            this.normalizarEndereco(
                botao.dataset.endereco,
            );

        if (endereco === null) {
            this.mostrarErroPedido(
                null,
                botao.dataset.mensagemErro,
            );

            return;
        }

        if (
            tipo === 'eliminar'
            && !(await this.confirmarEliminacao(
                botao,
            ))
        ) {
            return;
        }

        const estavaDesativado =
            botao.disabled;

        this.elementosEmProcessamento.add(
            botao,
        );

        this.definirElementoDesativado(
            botao,
            true,
        );

        try {
            const resposta =
                tipo === 'eliminar'
                    ? await axios.delete(
                        endereco,
                        {
                            headers:
                                this.obterCabecalhosJson(),
                        },
                    )
                    : await axios.post(
                        endereco,
                        {},
                        {
                            headers:
                                this.obterCabecalhosJson(),
                        },
                    );

            const dados =
                typeof resposta.data === 'object'
                && resposta.data !== null
                && !Array.isArray(
                    resposta.data,
                )
                    ? resposta.data
                    : {};

            this.atualizarInterface(
                botao,
                tipo,
                dados,
            );

            const mensagemResposta =
                typeof dados.mensagem === 'string'
                    ? dados.mensagem.trim()
                    : '';

            const mensagemConfigurada =
                botao.dataset.mensagemSucesso
                    ?.trim()
                ?? '';

            const mensagem =
                mensagemResposta
                || mensagemConfigurada;

            if (mensagem !== '') {
                this.mostrarMensagemSucesso(
                    mensagem,
                );
            }
        } catch (erro) {
            this.mostrarErroPedido(
                erro,
                botao.dataset.mensagemErro,
            );
        } finally {
            this.elementosEmProcessamento.delete(
                botao,
            );

            this.definirElementoDesativado(
                botao,
                estavaDesativado,
            );
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
     * @version 3.0.0
     */
    async confirmarEliminacao(botao) {
        const mensagem =
            botao.dataset.mensagemConfirmacao
                ?.trim()
            || 'Tens a certeza de que pretendes eliminar?';

        const resultado =
            await Swal.fire({
                title:
                    mensagem,

                text:
                    'Esta ação não pode ser revertida.',

                icon:
                    'warning',

                showCancelButton:
                    true,

                confirmButtonText:
                    'Sim, eliminar',

                cancelButtonText:
                    'Cancelar',

                focusCancel:
                    true,
            });

        return resultado.isConfirmed;
    }

    /**
     * Submete a edição de um comentário.
     *
     * @param {HTMLFormElement} formulario Formulário de edição.
     *
     * @returns {Promise<void>}
     *
     * @since 1.1.0
     * @version 4.0.0
     */
    async submeterEdicaoComentario(formulario) {
        if (
            this.elementosEmProcessamento.has(
                formulario,
            )
        ) {
            return;
        }

        const botaoSubmeter =
            formulario.querySelector(
                'button[type="submit"]',
            );

        const campoConteudo =
            formulario.querySelector(
                '[data-campo-conteudo-comentario]',
            );

        const elementoErro =
            formulario.querySelector(
                '.invalid-feedback',
            );

        const endereco =
            this.normalizarEndereco(
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
            this.validarConteudoComentario(
                campoConteudo,
            );

        if (mensagemValidacao !== null) {
            this.apresentarErroCampo(
                campoConteudo,
                elementoErro,
                mensagemValidacao,
            );

            campoConteudo.focus();

            return;
        }

        const conteudoOriginalBotao =
            botaoSubmeter.innerHTML;

        const botaoEstavaDesativado =
            botaoSubmeter.disabled;

        this.elementosEmProcessamento.add(
            formulario,
        );

        formulario.setAttribute(
            'aria-busy',
            'true',
        );

        botaoSubmeter.disabled =
            true;

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
            const resposta =
                await axios.patch(
                    endereco,
                    {
                        conteudo:
                            campoConteudo.value,
                    },
                    {
                        headers:
                            this.obterCabecalhosJson(),
                    },
                );

            const conteudoAtualizado =
                resposta.data
                    ?.comentario
                    ?.conteudo;

            const comentario =
                formulario.closest(
                    '.comentario',
                );

            const apresentacao =
                comentario?.querySelector(
                    '[data-conteudo-comentario] p',
                );

            const contentorFormulario =
                formulario.closest(
                    '.contentor-edicao-comentario',
                );

            const contentorConteudo =
                comentario?.querySelector(
                    '[data-conteudo-comentario]',
                );

            if (
                typeof conteudoAtualizado !== 'string'
                || !(apresentacao instanceof HTMLElement)
                || !(
                    contentorFormulario
                    instanceof HTMLElement
                )
                || !(
                    contentorConteudo
                    instanceof HTMLElement
                )
            ) {
                throw new Error(
                    'A resposta da edição do comentário é inválida.',
                );
            }

            this.apresentarTextoComQuebras(
                apresentacao,
                conteudoAtualizado,
            );

            campoConteudo.value =
                conteudoAtualizado;

            campoConteudo.dataset.valorOriginal =
                conteudoAtualizado;

            contentorFormulario.hidden =
                true;

            contentorFormulario.setAttribute(
                'aria-hidden',
                'true',
            );

            contentorConteudo.hidden =
                false;

            this.atualizarControladores(
                contentorFormulario.id,
                false,
            );

            const mensagem =
                resposta.data?.mensagem;

            if (
                typeof mensagem === 'string'
                && mensagem.trim() !== ''
            ) {
                this.mostrarMensagemSucesso(
                    mensagem,
                );
            }
        } catch (erro) {
            const mensagemValidacaoServidor =
                axios.isAxiosError(
                    erro,
                )
                && erro.response?.status === 422
                    ? erro.response
                        .data
                        ?.errors
                        ?.conteudo
                        ?.[0]
                    : null;

            if (
                typeof mensagemValidacaoServidor
                    === 'string'
                && mensagemValidacaoServidor.trim()
                    !== ''
            ) {
                this.apresentarErroCampo(
                    campoConteudo,
                    elementoErro,
                    mensagemValidacaoServidor,
                );

                campoConteudo.focus();
            } else {
                this.mostrarErroPedido(
                    erro,
                );
            }
        } finally {
            botaoSubmeter.disabled =
                botaoEstavaDesativado;

            botaoSubmeter.innerHTML =
                conteudoOriginalBotao;

            formulario.removeAttribute(
                'aria-busy',
            );

            this.elementosEmProcessamento.delete(
                formulario,
            );
        }
    }

    /**
     * Valida o conteúdo de um comentário editado.
     *
     * @param {HTMLTextAreaElement} campo Campo do conteúdo.
     *
     * @returns {string|null} Mensagem de erro ou nulo.
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    validarConteudoComentario(campo) {
        if (campo.value.trim() === '') {
            return 'Por favor, insere o texto do comentário.';
        }

        if (
            Number.isInteger(
                campo.maxLength,
            )
            && campo.maxLength > 0
            && campo.value.length
                > campo.maxLength
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
     * @version 4.0.0
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
                this.removerElemento(
                    botao,
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
     * @version 3.0.0
     */
    atualizarGosto(
        botao,
        dados,
    ) {
        const adicionado =
            dados.adicionado === true;

        const numeroGostos =
            Number.parseInt(
                String(
                    dados.numero_gostos
                    ?? '',
                ),
                10,
            );

        const icone =
            botao.querySelector(
                '[data-icone-gosto]',
            );

        const quantidade =
            botao.querySelector(
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
            && Number.isInteger(
                numeroGostos,
            )
            && numeroGostos >= 0
        ) {
            quantidade.textContent =
                String(
                    numeroGostos,
                );
        }

        botao.setAttribute(
            'aria-pressed',
            String(
                adicionado,
            ),
        );

        if (
            Number.isInteger(
                numeroGostos,
            )
            && numeroGostos >= 0
        ) {
            const acao =
                adicionado
                    ? 'Remover gosto'
                    : 'Adicionar gosto';

            const unidade =
                numeroGostos === 1
                    ? 'gosto'
                    : 'gostos';

            botao.setAttribute(
                'aria-label',
                `${acao}. ${numeroGostos} ${unidade}.`,
            );
        }

        const conteudo =
            dados.conteudo_indicador_html;

        if (
            typeof conteudo === 'string'
            && conteudo.trim() !== ''
        ) {
            this.atualizarTooltip(
                botao,
                conteudo,
            );

            botao.dataset.estadoTooltipGostos =
                'carregado';

            return;
        }

        delete botao.dataset
            .estadoTooltipGostos;
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
     * @version 3.0.0
     */
    atualizarAudicao(
        botao,
        dados,
    ) {
        const marcadoComoOuvido =
            dados.marcado_como_ouvido === true;

        const numeroAudicoes =
            Number.parseInt(
                String(
                    dados.numero_audicoes
                    ?? '',
                ),
                10,
            );

        const tipoAudivel =
            botao.dataset.tipoAudivel;

        if (
            tipoAudivel !== 'seccao-metal-thursday'
            && tipoAudivel !== 'metal-thursday'
        ) {
            return;
        }

        const texto =
            tipoAudivel === 'seccao-metal-thursday'
                ? marcadoComoOuvido
                    ? 'Ouvido'
                    : 'Marcar como ouvido'
                : marcadoComoOuvido
                    ? 'Ouvida'
                    : 'Marcar MetalThursday como ouvida';

        const textoBotao =
            botao.querySelector(
                '[data-texto-interacao]',
            );

        if (textoBotao instanceof HTMLElement) {
            textoBotao.textContent =
                texto;
        }

        botao.setAttribute(
            'aria-pressed',
            String(
                marcadoComoOuvido,
            ),
        );

        botao.setAttribute(
            'aria-label',
            texto,
        );

        const contentorInteracoes =
            botao.closest(
                '[data-contentor-interacoes]',
            );

        const apresentacaoAudicoes =
            contentorInteracoes?.querySelector(
                '.apresentacao-audicoes',
            );

        if (
            !(apresentacaoAudicoes instanceof HTMLElement)
        ) {
            return;
        }

        const quantidade =
            apresentacaoAudicoes.querySelector(
                '.quantidade-audicoes',
            );

        if (
            quantidade instanceof HTMLElement
            && Number.isInteger(
                numeroAudicoes,
            )
            && numeroAudicoes >= 0
        ) {
            quantidade.textContent =
                String(
                    numeroAudicoes,
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
     * Remove o elemento associado a uma eliminação.
     *
     * @param {HTMLButtonElement} botao Botão da interação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 3.0.0
     */
    removerElemento(botao) {
        const seletor =
            botao.dataset
                .seletorElementoRemovivel
                ?.trim();

        if (
            !seletor
            || !(this.contentor instanceof HTMLElement)
        ) {
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

        const contentorPai =
            elemento.parentElement;

        [
            elemento,
            ...elemento.querySelectorAll(
                '[data-bs-toggle="tooltip"]',
            ),
        ].forEach((elementoTooltip) => {
            if (
                elementoTooltip
                instanceof HTMLElement
            ) {
                Tooltip
                    .getInstance(
                        elementoTooltip,
                    )
                    ?.dispose();
            }
        });

        elemento.style.transition =
            'opacity 0.3s ease-out';

        elemento.style.opacity =
            '0';

        window.setTimeout(
            () => {
                elemento.remove();

                this.atualizarContentorDepoisEliminacao(
                    contentorPai,
                );
            },
            300,
        );
    }

    /**
     * Atualiza o contentor que recebeu um elemento eliminado.
     *
     * @param {HTMLElement|null} contentor Contentor anterior.
     *
     * @returns {void}
     *
     * @since 4.0.0
     * @version 1.0.0
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
            contentor.hidden =
                true;

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
            const mensagem =
                document.createElement(
                    'p',
                );

            mensagem.className =
                'sem-comentarios small text-muted text-center';

            mensagem.textContent =
                'Ainda não existem comentários. Sê a primeira pessoa a comentar!';

            contentor.append(
                mensagem,
            );
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
     * @version 3.0.0
     */
    alternarFormularioResposta(
        botao,
        alternar,
    ) {
        const contentor =
            this.obterElementoControlado(
                botao,
            );

        if (!(contentor instanceof HTMLElement)) {
            return;
        }

        const mostrar =
            alternar
                ? contentor.hidden
                : false;

        contentor.hidden =
            !mostrar;

        contentor.setAttribute(
            'aria-hidden',
            String(
                !mostrar,
            ),
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

        const formulario =
            contentor.querySelector(
                'form',
            );

        const campo =
            formulario?.querySelector(
                'textarea',
            );

        const elementoErro =
            formulario?.querySelector(
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
    }

    /**
     * Inicia a edição de um comentário.
     *
     * @param {HTMLButtonElement} botao Botão da interação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 3.0.0
     */
    iniciarEdicaoComentario(botao) {
        const contentorFormulario =
            this.obterElementoControlado(
                botao,
            );

        const comentario =
            botao.closest(
                '.comentario',
            );

        const conteudo =
            comentario?.querySelector(
                '[data-conteudo-comentario]',
            );

        const campo =
            contentorFormulario?.querySelector(
                '[data-campo-conteudo-comentario]',
            );

        if (
            !(
                contentorFormulario
                instanceof HTMLElement
            )
            || !(conteudo instanceof HTMLElement)
            || !(campo instanceof HTMLTextAreaElement)
        ) {
            return;
        }

        campo.dataset.valorOriginal =
            campo.value;

        conteudo.hidden =
            true;

        contentorFormulario.hidden =
            false;

        contentorFormulario.setAttribute(
            'aria-hidden',
            'false',
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
     * @version 3.0.0
     */
    cancelarEdicaoComentario(botao) {
        const contentorFormulario =
            this.obterElementoControlado(
                botao,
            );

        const comentario =
            botao.closest(
                '.comentario',
            );

        const conteudo =
            comentario?.querySelector(
                '[data-conteudo-comentario]',
            );

        const campo =
            contentorFormulario?.querySelector(
                '[data-campo-conteudo-comentario]',
            );

        const elementoErro =
            contentorFormulario?.querySelector(
                '.invalid-feedback',
            );

        if (
            !(
                contentorFormulario
                instanceof HTMLElement
            )
            || !(conteudo instanceof HTMLElement)
            || !(campo instanceof HTMLTextAreaElement)
        ) {
            return;
        }

        if (
            typeof campo.dataset.valorOriginal
            === 'string'
        ) {
            campo.value =
                campo.dataset.valorOriginal;
        }

        if (elementoErro instanceof HTMLElement) {
            this.limparErroCampo(
                campo,
                elementoErro,
            );
        }

        contentorFormulario.hidden =
            true;

        contentorFormulario.setAttribute(
            'aria-hidden',
            'true',
        );

        conteudo.hidden =
            false;

        this.atualizarControladores(
            contentorFormulario.id,
            false,
        );
    }

    /**
     * Atualiza os botões que controlam um contentor.
     *
     * @param {string} identificador Identificador do contentor.
     * @param {boolean} expandido Estado de expansão.
     *
     * @returns {void}
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    atualizarControladores(
        identificador,
        expandido,
    ) {
        if (
            identificador === ''
            || !(this.contentor instanceof HTMLElement)
        ) {
            return;
        }

        this.contentor.querySelectorAll(
            'button[data-tipo-interacao][aria-controls]',
        ).forEach((botao) => {
            if (
                botao instanceof HTMLButtonElement
                && botao.getAttribute(
                    'aria-controls',
                ) === identificador
            ) {
                botao.setAttribute(
                    'aria-expanded',
                    String(
                        expandido,
                    ),
                );
            }
        });
    }

    /**
     * Obtém o elemento identificado por `aria-controls`.
     *
     * @param {HTMLElement} botao Botão controlador.
     *
     * @returns {HTMLElement|null} Elemento encontrado ou nulo.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    obterElementoControlado(botao) {
        const identificador =
            botao.getAttribute(
                'aria-controls',
            )?.trim();

        if (!identificador) {
            return null;
        }

        const elemento =
            document.getElementById(
                identificador,
            );

        return elemento instanceof HTMLElement
            ? elemento
            : null;
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
     * @since 4.0.0
     * @version 1.0.0
     */
    apresentarErroCampo(
        campo,
        elementoErro,
        mensagem,
    ) {
        campo.classList.add(
            'is-invalid',
        );

        campo.setAttribute(
            'aria-invalid',
            'true',
        );

        campo.setCustomValidity(
            mensagem,
        );

        elementoErro.textContent =
            mensagem;

        elementoErro.classList.add(
            'd-block',
        );

        elementoErro.removeAttribute(
            'hidden',
        );
    }

    /**
     * Limpa o erro de um campo.
     *
     * @param {HTMLTextAreaElement} campo Campo validado.
     * @param {HTMLElement} elementoErro Elemento da mensagem.
     *
     * @returns {void}
     *
     * @since 3.0.0
     * @version 2.0.0
     */
    limparErroCampo(
        campo,
        elementoErro,
    ) {
        campo.classList.remove(
            'is-invalid',
        );

        campo.removeAttribute(
            'aria-invalid',
        );

        campo.setCustomValidity(
            '',
        );

        elementoErro.textContent =
            '';

        elementoErro.classList.remove(
            'd-block',
        );

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
     * @since 3.0.0
     * @version 1.0.0
     */
    apresentarTextoComQuebras(
        elemento,
        texto,
    ) {
        elemento.replaceChildren();

        texto.split(
            /\r?\n/u,
        ).forEach(
            (
                linha,
                indice,
            ) => {
                if (indice > 0) {
                    elemento.append(
                        document.createElement(
                            'br',
                        ),
                    );
                }

                elemento.append(
                    document.createTextNode(
                        linha,
                    ),
                );
            },
        );
    }

    /**
     * Atualiza um tooltip do Bootstrap.
     *
     * @param {HTMLElement} elemento Elemento associado.
     * @param {string} conteudo Conteúdo do tooltip.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    atualizarTooltip(
        elemento,
        conteudo,
    ) {
        elemento.setAttribute(
            'data-bs-title',
            conteudo,
        );

        Tooltip
            .getInstance(
                elemento,
            )
            ?.dispose();

        new Tooltip(
            elemento,
            {
                html: true,
                title: conteudo,
            },
        );
    }

    /**
     * Normaliza um endereço da origem atual.
     *
     * @param {unknown} endereco Endereço recebido.
     *
     * @returns {string|null} Endereço validado ou nulo.
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    normalizarEndereco(endereco) {
        if (
            typeof endereco !== 'string'
            || endereco.trim() === ''
        ) {
            return null;
        }

        try {
            const url =
                new URL(
                    endereco,
                    window.location.origin,
                );

            if (
                ![
                    'http:',
                    'https:',
                ].includes(
                    url.protocol,
                )
                || url.origin
                    !== window.location.origin
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
     * @param {string} mensagem Mensagem apresentada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    mostrarMensagemSucesso(mensagem) {
        const texto =
            mensagem.trim();

        if (texto === '') {
            return;
        }

        Swal.fire({
            toast:
                true,

            position:
                'top-end',

            icon:
                'success',

            title:
                texto,

            showConfirmButton:
                false,

            timer:
                3000,

            timerProgressBar:
                true,
        });
    }

    /**
     * Apresenta a mensagem de erro de um pedido.
     *
     * @param {unknown} erro Erro capturado.
     * @param {unknown} mensagemPredefinida Mensagem configurada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 3.0.0
     */
    mostrarErroPedido(
        erro,
        mensagemPredefinida = undefined,
    ) {
        const mensagemResposta =
            axios.isAxiosError(
                erro,
            )
            && typeof erro.response
                ?.data
                ?.mensagem
                === 'string'
                ? erro.response.data
                    .mensagem
                    .trim()
                : '';

        const mensagemConfigurada =
            typeof mensagemPredefinida === 'string'
                ? mensagemPredefinida.trim()
                : '';

        Swal.fire({
            icon:
                'error',

            title:
                'Erro',

            text:
                mensagemResposta
                || mensagemConfigurada
                || 'Ocorreu um erro ao processar a ação.',
        });
    }

    /**
     * Ativa ou desativa um botão.
     *
     * @param {HTMLButtonElement} botao Botão atualizado.
     * @param {boolean} desativado Estado pretendido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    definirElementoDesativado(
        botao,
        desativado,
    ) {
        botao.disabled =
            desativado;

        botao.setAttribute(
            'aria-busy',
            String(
                desativado,
            ),
        );
    }

    /**
     * Devolve os cabeçalhos utilizados nos pedidos JSON.
     *
     * @returns {Record<string, string>} Cabeçalhos HTTP.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterCabecalhosJson() {
        return {
            Accept:
                'application/json',

            'X-Requested-With':
                'XMLHttpRequest',
        };
    }

    /**
     * Obtém o contentor principal.
     *
     * @param {unknown} seletor Seletor CSS.
     *
     * @returns {HTMLElement|null} Contentor encontrado ou nulo.
     *
     * @throws {TypeError} Quando o seletor não é válido.
     *
     * @since 4.0.0
     * @version 1.0.0
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

        const seletorNormalizado =
            seletor.trim();

        try {
            const elemento =
                document.querySelector(
                    seletorNormalizado,
                );

            return elemento instanceof HTMLElement
                ? elemento
                : null;
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
     * @version 2.0.0
     */
    destruir() {
        if (
            !(this.contentor instanceof HTMLElement)
            || !this.iniciado
        ) {
            return;
        }

        this.contentor.removeEventListener(
            'click',
            this.aoClicar,
        );

        this.contentor.removeEventListener(
            'submit',
            this.aoSubmeter,
        );

        this.contentor.removeEventListener(
            'mouseover',
            this.aoSobrepor,
        );

        this.iniciado =
            false;
    }
}

export default GestorInteracoes;
