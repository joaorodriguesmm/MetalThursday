import axios from 'axios';
import { Tooltip } from 'bootstrap';
import Swal from 'sweetalert2';

/**
 * Gere as interações assíncronas da aplicação.
 *
 * @since 1.0.0
 * @version 2.0.0
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
     * @version 1.0.0
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
     * Trata a submissão de formulários de edição de comentários.
     *
     * @param {SubmitEvent} evento Evento de submissão.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    tratarSubmissao(evento) {
        const formulario = evento.target;

        if (
            !(formulario instanceof HTMLFormElement)
            || !formulario.matches('.formulario-edicao-comentario form')
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
     * @version 1.0.0
     */
    tratarSobreposicao(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const elemento = evento.target.closest(
            '[data-comentario-id][data-bs-toggle="tooltip"]',
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
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    async carregarTooltipGostos(elemento) {
        const url = elemento.dataset.urlUtilizadoresGosto;

        if (!url) {
            return;
        }

        elemento.dataset.estadoTooltipGostos = 'a-carregar';

        try {
            const resposta = await axios.get(url, {
                headers: this.obterCabecalhosJson(),
            });

            const conteudo = resposta.data?.html_tooltip;

            if (typeof conteudo === 'string') {
                this.atualizarTooltip(elemento, conteudo);
                elemento.dataset.estadoTooltipGostos = 'carregado';

                return;
            }

            delete elemento.dataset.estadoTooltipGostos;
        } catch {
            delete elemento.dataset.estadoTooltipGostos;
        }
    }

    /**
     * Trata uma interação iniciada pelo utilizador.
     *
     * @param {HTMLElement} botao Elemento que iniciou a interação.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    async tratarInteracao(botao) {
        const tipo = botao.dataset.tipoInteracao;

        if (!tipo) {
            return;
        }

        if (
            [
                'alternar-resposta',
                'cancelar-resposta',
                'iniciar-edicao',
                'cancelar-edicao',
            ].includes(tipo)
        ) {
            this.atualizarInterface(botao, tipo);

            return;
        }

        const url = botao.dataset.url;

        if (!url || this.elementosEmProcessamento.has(botao)) {
            return;
        }

        if (tipo === 'eliminar' && !(await this.confirmarEliminacao())) {
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
                    ? await axios.delete(url, {
                        headers: this.obterCabecalhosJson(),
                    })
                    : await axios.post(url, {}, {
                        headers: this.obterCabecalhosJson(),
                    });

            const mensagem = resposta.data?.mensagem;

            if (typeof mensagem === 'string' && mensagem.trim() !== '') {
                this.mostrarMensagemSucesso(mensagem);
            }

            this.atualizarInterface(
                botao,
                tipo,
                resposta.data ?? {},
            );
        } catch (erro) {
            this.mostrarErroPedido(erro);
        } finally {
            this.elementosEmProcessamento.delete(botao);
            this.definirElementoDesativado(botao, estavaDesativado);
        }
    }

    /**
     * Solicita a confirmação da eliminação.
     *
     * @returns {Promise<boolean>}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    async confirmarEliminacao() {
        const resultado = await Swal.fire({
            title: 'Tens a certeza de que pretendes eliminar?',
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
     *
     * @returns {Promise<void>}
     *
     * @since 1.1.0
     * @version 2.0.0
     */
    async submeterEdicaoComentario(formulario) {
        if (this.elementosEmProcessamento.has(formulario)) {
            return;
        }

        const botaoSubmeter = formulario.querySelector(
            'button[type="submit"]',
        );
        const campoConteudo = formulario.querySelector('textarea');
        const elementoErro = formulario.querySelector('.invalid-feedback');
        const url = formulario.dataset.url;

        if (
            !(botaoSubmeter instanceof HTMLButtonElement)
            || !(campoConteudo instanceof HTMLTextAreaElement)
            || !(elementoErro instanceof HTMLElement)
            || !url
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

        campoConteudo.classList.remove('is-invalid');
        campoConteudo.removeAttribute('aria-invalid');
        elementoErro.textContent = '';
        elementoErro.classList.remove('d-block');

        try {
            const resposta = await axios.patch(
                url,
                {
                    conteudo: campoConteudo.value,
                },
                {
                    headers: this.obterCabecalhosJson(),
                },
            );

            const contentorComentario = formulario.closest('.comentario');
            const apresentacaoConteudo = contentorComentario?.querySelector(
                '.conteudo-comentario p',
            );
            const contentorFormulario = formulario.closest(
                '.formulario-edicao-comentario',
            );
            const conteudoHtml = resposta.data?.conteudo_html;

            if (
                contentorComentario instanceof HTMLElement
                && apresentacaoConteudo instanceof HTMLElement
                && contentorFormulario instanceof HTMLElement
                && typeof conteudoHtml === 'string'
            ) {
                apresentacaoConteudo.innerHTML = conteudoHtml;
                contentorFormulario.style.display = 'none';

                const conteudoComentario =
                    contentorComentario.querySelector(
                        '.conteudo-comentario',
                    );

                if (conteudoComentario instanceof HTMLElement) {
                    conteudoComentario.style.display = 'block';
                }

                campoConteudo.dataset.valorOriginal =
                    campoConteudo.value;
            }
        } catch (erro) {
            const mensagemValidacao =
                axios.isAxiosError(erro)
                && erro.response?.status === 422
                    ? erro.response.data?.errors?.conteudo?.[0]
                    : null;

            if (typeof mensagemValidacao === 'string') {
                campoConteudo.classList.add('is-invalid');
                campoConteudo.setAttribute('aria-invalid', 'true');
                elementoErro.textContent = mensagemValidacao;
                elementoErro.classList.add('d-block');
                campoConteudo.focus();
            } else {
                this.mostrarErroPedido(erro);
            }
        } finally {
            botaoSubmeter.disabled = botaoEstavaDesativado;
            botaoSubmeter.innerHTML = conteudoOriginalBotao;
            this.elementosEmProcessamento.delete(formulario);
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
     * @version 2.0.0
     */
    atualizarInterface(botao, tipo, dados = {}) {
        switch (tipo) {
            case 'gosto': {
                this.atualizarGosto(botao, dados);
                break;
            }

            case 'audicao': {
                this.atualizarAudicao(botao, dados);
                break;
            }

            case 'eliminar': {
                this.removerElemento(botao);
                break;
            }

            case 'alternar-resposta':
            case 'cancelar-resposta': {
                this.alternarFormularioResposta(botao, tipo);
                break;
            }

            case 'iniciar-edicao': {
                this.iniciarEdicaoComentario(botao);
                break;
            }

            case 'cancelar-edicao': {
                this.cancelarEdicaoComentario(botao);
                break;
            }

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
     * @version 1.0.0
     */
    atualizarGosto(botao, dados) {
        const envolucro =
            botao.querySelector('[data-comentario-id]')
            ?? botao.closest('[data-comentario-id]');

        if (!(envolucro instanceof HTMLElement)) {
            return;
        }

        const total = envolucro.querySelector('.total-atual');

        if (
            total instanceof HTMLElement
            && Number.isFinite(Number(dados.total_gostos))
        ) {
            total.textContent = String(dados.total_gostos);
        }

        const temGosto = dados.tem_gosto === true;
        const icone = envolucro.querySelector('i');

        if (icone instanceof HTMLElement) {
            icone.classList.toggle('bi-heart', !temGosto);
            icone.classList.toggle('bi-heart-fill', temGosto);
            icone.classList.toggle('text-danger', temGosto);
        }

        delete envolucro.dataset.estadoTooltipGostos;
        this.atualizarTooltip(envolucro, 'A carregar...');
    }

    /**
     * Atualiza a apresentação de uma audição.
     *
     * @param {HTMLElement} botao Botão da interação.
     * @param {Record<string, unknown>} dados Dados devolvidos pelo servidor.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    atualizarAudicao(botao, dados) {
        const textoBotao = botao.querySelector(
            '[data-texto-interacao]',
        );
        const tipoAudivel = botao.dataset.tipoAudivel;
        const foiOuvido = dados.foi_ouvido === true;

        if (textoBotao instanceof HTMLElement) {
            textoBotao.textContent =
                tipoAudivel === 'secao'
                    ? foiOuvido
                        ? 'Ouvido'
                        : 'Marcar como ouvido'
                    : foiOuvido
                        ? 'Ouvida'
                        : 'Marcar MetalThursday como ouvida';
        }

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

        const totalAudicoes = apresentacaoAudicoes.querySelector(
            '.total-audicoes',
        );

        if (
            totalAudicoes instanceof HTMLElement
            && Number.isFinite(Number(dados.total_audicoes))
        ) {
            totalAudicoes.textContent =
                String(dados.total_audicoes);
        }

        if (typeof dados.html_tooltip === 'string') {
            this.atualizarTooltip(
                apresentacaoAudicoes,
                dados.html_tooltip,
            );
        }
    }

    /**
     * Remove o elemento associado a uma interação de eliminação.
     *
     * @param {HTMLElement} botao Botão da interação.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    removerElemento(botao) {
        const seletor = botao.dataset.seletorRemocao;

        if (!seletor) {
            return;
        }

        let elemento;

        try {
            elemento = botao.closest(seletor);
        } catch {
            return;
        }

        if (!(elemento instanceof HTMLElement)) {
            return;
        }

        elemento.style.transition = 'opacity 0.3s ease-out';
        elemento.style.opacity = '0';

        window.setTimeout(
            () => elemento.remove(),
            300,
        );
    }

    /**
     * Alterna a apresentação do formulário de resposta.
     *
     * @param {HTMLElement} botao Botão da interação.
     * @param {string} tipo Tipo da interação.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    alternarFormularioResposta(botao, tipo) {
        const comentario = botao.closest('.comentario');
        const formularioResposta = comentario?.querySelector(
            '.contentor-formulario-resposta',
        );

        if (!(formularioResposta instanceof HTMLElement)) {
            return;
        }

        const deveMostrar =
            tipo === 'alternar-resposta'
            && formularioResposta.style.display === 'none';

        formularioResposta.style.display =
            deveMostrar
                ? 'block'
                : 'none';

        if (deveMostrar) {
            formularioResposta.querySelector('textarea')?.focus();
        }
    }

    /**
     * Inicia a edição de um comentário.
     *
     * @param {HTMLElement} botao Botão da interação.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    iniciarEdicaoComentario(botao) {
        const comentario = botao.closest('.comentario');
        const conteudo = comentario?.querySelector(
            '.conteudo-comentario',
        );
        const contentorFormulario = comentario?.querySelector(
            '.formulario-edicao-comentario',
        );
        const campo = contentorFormulario?.querySelector('textarea');

        if (
            !(conteudo instanceof HTMLElement)
            || !(contentorFormulario instanceof HTMLElement)
            || !(campo instanceof HTMLTextAreaElement)
        ) {
            return;
        }

        campo.dataset.valorOriginal = campo.value;
        conteudo.style.display = 'none';
        contentorFormulario.style.display = 'block';
        campo.focus();
    }

    /**
     * Cancela a edição de um comentário.
     *
     * @param {HTMLElement} botao Botão da interação.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    cancelarEdicaoComentario(botao) {
        const comentario = botao.closest('.comentario');
        const conteudo = comentario?.querySelector(
            '.conteudo-comentario',
        );
        const contentorFormulario = comentario?.querySelector(
            '.formulario-edicao-comentario',
        );
        const campo = contentorFormulario?.querySelector('textarea');

        if (
            !(conteudo instanceof HTMLElement)
            || !(contentorFormulario instanceof HTMLElement)
            || !(campo instanceof HTMLTextAreaElement)
        ) {
            return;
        }

        if (typeof campo.dataset.valorOriginal === 'string') {
            campo.value = campo.dataset.valorOriginal;
        }

        campo.classList.remove('is-invalid');
        campo.removeAttribute('aria-invalid');

        const elementoErro = contentorFormulario.querySelector(
            '.invalid-feedback',
        );

        if (elementoErro instanceof HTMLElement) {
            elementoErro.textContent = '';
            elementoErro.classList.remove('d-block');
        }

        contentorFormulario.style.display = 'none';
        conteudo.style.display = 'block';
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
    atualizarTooltip(elemento, conteudo) {
        elemento.setAttribute('data-bs-title', conteudo);

        Tooltip.getInstance(elemento)?.dispose();

        new Tooltip(elemento, {
            html: true,
            title: conteudo,
        });
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
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    mostrarErroPedido(erro) {
        const mensagem =
            axios.isAxiosError(erro)
            && typeof erro.response?.data?.mensagem === 'string'
                ? erro.response.data.mensagem
                : 'Ocorreu um erro ao processar a ação.';

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
    definirElementoDesativado(elemento, desativado) {
        if (elemento instanceof HTMLButtonElement) {
            elemento.disabled = desativado;
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
