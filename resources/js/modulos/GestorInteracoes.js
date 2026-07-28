import axios from 'axios';
import { Tooltip } from 'bootstrap';
import Swal from 'sweetalert2';

/**
 * Gere as interações assíncronas da aplicação.
 *
 * @since 1.0.0
 * @version 3.0.0
 */
class GestorInteracoes {
    /**
     * Cria um gestor de interações.
     *
     * @param {string} seletorContentor Seletor CSS do contentor principal.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor(seletorContentor = 'body') {
        this.contentor = document.querySelector(seletorContentor);
        this.elementosEmProcessamento = new WeakSet();
        this.iniciado = false;

        this.aoClicar = (evento) => this.tratarClique(evento);
        this.aoSubmeter = (evento) => this.tratarSubmissao(evento);
        this.aoSobrepor = (evento) => this.tratarSobreposicao(evento);

        if (this.contentor instanceof HTMLElement) {
            this.iniciar();
        }
    }

    /**
     * Inicia os eventos delegados do gestor.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        if (!(this.contentor instanceof HTMLElement) || this.iniciado) {
            return;
        }

        this.contentor.addEventListener('click', this.aoClicar);
        this.contentor.addEventListener('submit', this.aoSubmeter);
        this.contentor.addEventListener('mouseover', this.aoSobrepor);

        this.iniciado = true;
    }

    /**
     * Trata os cliques em elementos de interação.
     *
     * @param {MouseEvent} evento Evento de clique.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    tratarClique(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botao = evento.target.closest('[data-tipo-interacao]');

        if (!(botao instanceof HTMLElement)) {
            return;
        }

        evento.preventDefault();
        this.tratarInteracao(botao);
    }

    /**
     * Trata a submissão dos formulários de edição de comentários.
     *
     * @param {SubmitEvent} evento Evento de submissão.
     *
     * @since 2.0.0
     * @version 2.0.0
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
        this.submeterEdicaoComentario(formulario);
    }

    /**
     * Carrega o conteúdo do tooltip de gostos quando necessário.
     *
     * @param {MouseEvent} evento Evento de sobreposição.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    tratarSobreposicao(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const elemento = evento.target.closest(
            '[data-endereco-utilizadores-gosto]'
                + '[data-bs-toggle="tooltip"]',
        );

        if (
            !(elemento instanceof HTMLElement)
            || elemento.dataset.estadoTooltipGostos === 'a-carregar'
            || elemento.dataset.estadoTooltipGostos === 'carregado'
        ) {
            return;
        }

        this.carregarTooltipGostos(elemento);
    }

    /**
     * Carrega os utilizadores que gostaram de um comentário.
     *
     * @param {HTMLElement} elemento Elemento associado ao tooltip.
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    async carregarTooltipGostos(elemento) {
        const endereco = elemento.dataset.enderecoUtilizadoresGosto;

        if (!endereco) {
            return;
        }

        elemento.dataset.estadoTooltipGostos = 'a-carregar';

        try {
            const resposta = await axios.get(endereco, {
                headers: this.obterCabecalhosJson(),
            });

            const conteudo =
                resposta.data?.conteudo_indicador_html;

            if (typeof conteudo !== 'string') {
                delete elemento.dataset.estadoTooltipGostos;

                return;
            }

            this.atualizarTooltip(elemento, conteudo);
            elemento.dataset.estadoTooltipGostos = 'carregado';
        } catch {
            delete elemento.dataset.estadoTooltipGostos;
        }
    }

    /**
     * Trata uma interação iniciada pelo utilizador.
     *
     * @param {HTMLElement} botao Elemento que iniciou a interação.
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    async tratarInteracao(botao) {
        const tipo = botao.dataset.tipoInteracao;

        if (!tipo) {
            return;
        }

        if (
            [
                'alternar-resposta-comentario',
                'cancelar-resposta-comentario',
                'iniciar-edicao-comentario',
                'cancelar-edicao-comentario',
            ].includes(tipo)
        ) {
            this.atualizarInterface(botao, tipo);

            return;
        }

        const endereco = botao.dataset.endereco;

        if (!endereco || this.elementosEmProcessamento.has(botao)) {
            return;
        }

        if (
            tipo === 'eliminar'
            && !(await this.confirmarEliminacao(botao))
        ) {
            return;
        }

        const estavaDesativado =
            botao instanceof HTMLButtonElement
                ? botao.disabled
                : false;

        this.elementosEmProcessamento.add(botao);
        this.definirElementoDesativado(botao, true);

        try {
            const resposta =
                tipo === 'eliminar'
                    ? await axios.delete(endereco, {
                        headers: this.obterCabecalhosJson(),
                    })
                    : await axios.post(endereco, {}, {
                        headers: this.obterCabecalhosJson(),
                    });

            const dados =
                resposta.data
                && typeof resposta.data === 'object'
                    ? resposta.data
                    : {};

            this.atualizarInterface(botao, tipo, dados);

            const mensagemResposta = dados.mensagem;
            const mensagemConfigurada = botao.dataset.mensagemSucesso;

            const mensagem =
                typeof mensagemResposta === 'string'
                && mensagemResposta.trim() !== ''
                    ? mensagemResposta
                    : mensagemConfigurada;

            if (
                typeof mensagem === 'string'
                && mensagem.trim() !== ''
            ) {
                this.mostrarMensagemSucesso(mensagem);
            }
        } catch (erro) {
            this.mostrarErroPedido(
                erro,
                botao.dataset.mensagemErro,
            );
        } finally {
            this.elementosEmProcessamento.delete(botao);
            this.definirElementoDesativado(botao, estavaDesativado);
        }
    }

    /**
     * Solicita a confirmação da eliminação.
     *
     * @param {HTMLElement} botao Botão que iniciou a eliminação.
     * @returns {Promise<boolean>}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    async confirmarEliminacao(botao) {
        const mensagem =
            botao.dataset.mensagemConfirmacao
            ?? 'Tens a certeza de que pretendes eliminar?';

        const resultado = await Swal.fire({
            title: mensagem,
            text: 'Esta ação não pode ser revertida.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, eliminar',
            cancelButtonText: 'Cancelar',
        });

        return resultado.isConfirmed;
    }

    /**
     * Submete a edição de um comentário.
     *
     * @param {HTMLFormElement} formulario Formulário de edição.
     * @returns {Promise<void>}
     *
     * @since 1.1.0
     * @version 3.0.0
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

        const endereco = formulario.dataset.endereco;

        if (
            !(botaoSubmeter instanceof HTMLButtonElement)
            || !(campoConteudo instanceof HTMLTextAreaElement)
            || !(elementoErro instanceof HTMLElement)
            || !endereco
        ) {
            return;
        }

        const conteudoOriginalBotao = botaoSubmeter.innerHTML;
        const botaoEstavaDesativado = botaoSubmeter.disabled;

        this.elementosEmProcessamento.add(formulario);
        botaoSubmeter.disabled = true;

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
                {
                    headers: this.obterCabecalhosJson(),
                },
            );

            const conteudoAtualizado =
                resposta.data?.comentario?.conteudo;

            const comentario =
                formulario.closest('.comentario');

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
                typeof conteudoAtualizado === 'string'
                && apresentacao instanceof HTMLElement
                && contentorFormulario instanceof HTMLElement
                && contentorConteudo instanceof HTMLElement
            ) {
                this.apresentarTextoComQuebras(
                    apresentacao,
                    conteudoAtualizado,
                );

                campoConteudo.value =
                    conteudoAtualizado;

                campoConteudo.dataset.valorOriginal =
                    conteudoAtualizado;

                contentorFormulario.hidden = true;

                contentorFormulario.setAttribute(
                    'aria-hidden',
                    'true',
                );

                contentorConteudo.hidden = false;
            }

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
            const mensagemValidacao =
                axios.isAxiosError(erro)
                && erro.response?.status === 422
                    ? erro.response.data?.errors?.conteudo?.[0]
                    : null;

            if (typeof mensagemValidacao === 'string') {
                campoConteudo.classList.add('is-invalid');

                campoConteudo.setAttribute(
                    'aria-invalid',
                    'true',
                );

                elementoErro.textContent =
                    mensagemValidacao;

                elementoErro.classList.add('d-block');
                campoConteudo.focus();
            } else {
                this.mostrarErroPedido(erro);
            }
        } finally {
            botaoSubmeter.disabled =
                botaoEstavaDesativado;

            botaoSubmeter.innerHTML =
                conteudoOriginalBotao;

            this.elementosEmProcessamento.delete(
                formulario,
            );
        }
    }

    /**
     * Atualiza a interface após uma interação.
     *
     * @param {HTMLElement} botao Elemento que iniciou a interação.
     * @param {string} tipo Tipo da interação.
     * @param {Record<string, unknown>} dados Dados devolvidos pelo servidor.
     *
     * @since 1.0.0
     * @version 3.0.0
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
     * @param {HTMLElement} botao Botão da interação.
     * @param {Record<string, unknown>} dados Dados devolvidos pelo servidor.
     *
     * @since 2.0.0
     * @version 2.0.0
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
            && Number.isInteger(numeroGostos)
            && numeroGostos >= 0
        ) {
            quantidade.textContent =
                String(numeroGostos);
        }

        botao.setAttribute(
            'aria-pressed',
            String(adicionado),
        );

        if (
            Number.isInteger(numeroGostos)
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

        if (typeof conteudo === 'string') {
            this.atualizarTooltip(
                botao,
                conteudo,
            );

            botao.dataset.estadoTooltipGostos =
                'carregado';
        } else {
            delete botao.dataset.estadoTooltipGostos;
        }
    }

    /**
     * Atualiza a apresentação de uma audição.
     *
     * @param {HTMLElement} botao Botão da interação.
     * @param {Record<string, unknown>} dados Dados devolvidos pelo servidor.
     *
     * @since 2.0.0
     * @version 2.0.0
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

        const textoBotao =
            botao.querySelector(
                '[data-texto-interacao], span',
            );

        if (textoBotao instanceof HTMLElement) {
            textoBotao.textContent =
                tipoAudivel === 'seccao-metal-thursday'
                    ? marcadoComoOuvido
                        ? 'Ouvido'
                        : 'Marcar como ouvido'
                    : marcadoComoOuvido
                        ? 'Ouvida'
                        : 'Marcar MetalThursday como ouvida';
        }

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
            && Number.isInteger(numeroAudicoes)
            && numeroAudicoes >= 0
        ) {
            quantidade.textContent =
                String(numeroAudicoes);
        }

        const conteudo =
            dados.conteudo_indicador_html;

        if (typeof conteudo === 'string') {
            this.atualizarTooltip(
                apresentacaoAudicoes,
                conteudo,
            );
        }
    }

    /**
     * Remove o elemento associado a uma interação de eliminação.
     *
     * @param {HTMLElement} botao Botão da interação.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    removerElemento(botao) {
        const seletor =
            botao.dataset.seletorElementoRemovivel;

        if (!seletor) {
            return;
        }

        let elemento = null;

        try {
            elemento =
                botao.closest(seletor)
                ?? document.querySelector(seletor);
        } catch {
            return;
        }

        if (!(elemento instanceof HTMLElement)) {
            return;
        }

        elemento.style.transition =
            'opacity 0.3s ease-out';

        elemento.style.opacity =
            '0';

        window.setTimeout(
            () => elemento.remove(),
            300,
        );
    }

    /**
     * Alterna a apresentação do formulário de resposta.
     *
     * @param {HTMLElement} botao Botão da interação.
     * @param {boolean} alternar Indica se o estado deve ser alternado.
     *
     * @since 2.0.0
     * @version 2.0.0
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
            String(!mostrar),
        );

        botao.setAttribute(
            'aria-expanded',
            String(mostrar),
        );

        if (mostrar) {
            contentor
                .querySelector('textarea')
                ?.focus();
        }
    }

    /**
     * Inicia a edição de um comentário.
     *
     * @param {HTMLElement} botao Botão da interação.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    iniciarEdicaoComentario(botao) {
        const contentorFormulario =
            this.obterElementoControlado(
                botao,
            );

        const comentario =
            botao.closest('.comentario');

        const conteudo =
            comentario?.querySelector(
                '[data-conteudo-comentario]',
            );

        const campo =
            contentorFormulario?.querySelector(
                '[data-campo-conteudo-comentario]',
            );

        if (
            !(contentorFormulario instanceof HTMLElement)
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

        botao.setAttribute(
            'aria-expanded',
            'true',
        );

        campo.focus();
    }

    /**
     * Cancela a edição de um comentário.
     *
     * @param {HTMLElement} botao Botão da interação.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    cancelarEdicaoComentario(botao) {
        const contentorFormulario =
            this.obterElementoControlado(
                botao,
            );

        const comentario =
            botao.closest('.comentario');

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
            !(contentorFormulario instanceof HTMLElement)
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
    }

    /**
     * Obtém o elemento identificado por `aria-controls`.
     *
     * @param {HTMLElement} botao Botão controlador.
     * @returns {HTMLElement|null}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    obterElementoControlado(botao) {
        const identificador =
            botao.getAttribute(
                'aria-controls',
            );

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
     * Limpa o erro de validação de um campo.
     *
     * @param {HTMLTextAreaElement} campo Campo validado.
     * @param {HTMLElement} elementoErro Elemento da mensagem.
     *
     * @since 3.0.0
     * @version 1.0.0
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

        elementoErro.textContent =
            '';

        elementoErro.classList.remove(
            'd-block',
        );
    }

    /**
     * Apresenta texto preservando quebras de linha sem introduzir HTML.
     *
     * @param {HTMLElement} elemento Elemento de destino.
     * @param {string} texto Texto apresentado.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    apresentarTextoComQuebras(
        elemento,
        texto,
    ) {
        elemento.replaceChildren();

        const linhas =
            texto.split(/\r?\n/u);

        linhas.forEach(
            (
                linha,
                indice,
            ) => {
                if (indice > 0) {
                    elemento.append(
                        document.createElement('br'),
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
     * @param {HTMLElement} elemento Elemento associado ao tooltip.
     * @param {string} conteudo Conteúdo do tooltip.
     *
     * @since 1.0.0
     * @version 2.0.0
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
            .getInstance(elemento)
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
     * Apresenta uma mensagem de sucesso.
     *
     * @param {string} mensagem Mensagem a apresentar.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    mostrarMensagemSucesso(mensagem) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: mensagem,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }

    /**
     * Apresenta a mensagem de erro de um pedido.
     *
     * @param {unknown} erro Erro capturado.
     * @param {string|undefined} mensagemPredefinida Mensagem configurada.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    mostrarErroPedido(
        erro,
        mensagemPredefinida = undefined,
    ) {
        const mensagemResposta =
            axios.isAxiosError(erro)
            && typeof erro.response?.data?.mensagem
            === 'string'
                ? erro.response.data.mensagem
                : null;

        const mensagem =
            mensagemResposta
            ?? mensagemPredefinida
            ?? 'Ocorreu um erro ao processar a ação.';

        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: mensagem,
        });
    }

    /**
     * Ativa ou desativa um elemento interativo.
     *
     * @param {HTMLElement} elemento Elemento a atualizar.
     * @param {boolean} desativado Estado pretendido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    definirElementoDesativado(
        elemento,
        desativado,
    ) {
        if (elemento instanceof HTMLButtonElement) {
            elemento.disabled =
                desativado;
        }

        elemento.setAttribute(
            'aria-busy',
            String(desativado),
        );
    }

    /**
     * Retorna os cabeçalhos usados nos pedidos JSON.
     *
     * @returns {Record<string, string>}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterCabecalhosJson() {
        return {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    /**
     * Remove os eventos associados ao gestor.
     *
     * @since 2.0.0
     * @version 1.0.0
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

        this.iniciado = false;
    }
}

export default GestorInteracoes;
