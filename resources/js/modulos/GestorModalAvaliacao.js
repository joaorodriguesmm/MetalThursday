import { Modal, Tooltip } from 'bootstrap';
import TratadorFormularioAjax from './TratadorFormularioAjax';

/**
 * Gere a interatividade da janela modal de avaliação.
 *
 * @since 1.0.0
 * @version 3.0.0
 */
class GestorModalAvaliacao {
    /**
     * Cria um gestor da janela modal de avaliação.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    constructor() {
        this.modal =
            document.getElementById(
                'modal-avaliacao',
            );

        this.formulario =
            this.modal?.querySelector(
                'form[data-formulario-avaliacao]',
            );

        this.contentorEstrelas =
            this.modal?.querySelector(
                '[data-estrelas-avaliacao]',
            );

        this.campoPontuacao =
            this.formulario?.querySelector(
                'input[name="pontuacao"]',
            );

        this.elementoFeedback =
            this.modal?.querySelector(
                '[data-feedback-avaliacao]',
            );

        this.elementoNomeAvaliavel =
            this.modal?.querySelector(
                '[data-nome-avaliavel]',
            );

        this.pontuacaoAtual = 0;
        this.pontuacaoSelecionada = 0;
        this.botaoAcionador = null;
        this.emSubmissao = false;
        this.iniciado = false;

        this.aoAbrirModal =
            (evento) =>
                this.configurarModal(
                    evento,
                );

        this.aoFecharModal =
            () =>
                this.reporEstado();

        this.aoMoverRato =
            (evento) =>
                this.tratarMovimentoRato(
                    evento,
                );

        this.aoSairEstrelas =
            () =>
                this.tratarSaidaRato();

        this.aoClicarEstrela =
            (evento) =>
                this.tratarCliqueEstrela(
                    evento,
                );

        this.aoSubmeterFormulario =
            (evento) =>
                this.submeterFormulario(
                    evento,
                );

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
     * @version 2.0.0
     */
    estaAtivo() {
        return this.modal instanceof HTMLElement
            && this.formulario instanceof HTMLFormElement
            && this.formulario.id.trim() !== ''
            && this.contentorEstrelas instanceof HTMLElement
            && this.campoPontuacao instanceof HTMLInputElement
            && this.elementoNomeAvaliavel instanceof HTMLElement;
    }

    /**
     * Inicia os eventos da janela modal.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        if (!this.estaAtivo() || this.iniciado) {
            return;
        }

        this.modal.addEventListener(
            'show.bs.modal',
            this.aoAbrirModal,
        );

        this.modal.addEventListener(
            'hidden.bs.modal',
            this.aoFecharModal,
        );

        this.contentorEstrelas.addEventListener(
            'mousemove',
            this.aoMoverRato,
        );

        this.contentorEstrelas.addEventListener(
            'mouseleave',
            this.aoSairEstrelas,
        );

        this.contentorEstrelas.addEventListener(
            'click',
            this.aoClicarEstrela,
        );

        this.formulario.addEventListener(
            'submit',
            this.aoSubmeterFormulario,
        );

        this.iniciado = true;
    }

    /**
     * Configura a janela modal para o elemento selecionado.
     *
     * @param {Event} evento Evento de abertura da janela modal.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    configurarModal(evento) {
        const acionador =
            evento.relatedTarget;

        if (!(acionador instanceof HTMLElement)) {
            return;
        }

        const tipoAvaliavel =
            acionador.dataset.tipoAvaliavel?.trim()
            ?? '';

        const identificadorAvaliavel =
            acionador
                .dataset
                .identificadorAvaliavel
                ?.trim()
            ?? '';

        const nomeAvaliavel =
            acionador.dataset.nomeAvaliavel?.trim()
            ?? '';

        const enderecoAvaliacao =
            acionador.dataset.enderecoAvaliacao?.trim()
            ?? '';

        const pontuacaoUtilizador =
            this.normalizarPontuacao(
                acionador.dataset.pontuacaoUtilizador,
            );

        if (
            tipoAvaliavel === ''
            || identificadorAvaliavel === ''
            || enderecoAvaliacao === ''
        ) {
            this.apresentarErroAvaliacao(
                'Não foi possível preparar esta avaliação.',
            );

            return;
        }

        this.botaoAcionador =
            acionador;

        this.formulario.action =
            enderecoAvaliacao;

        this.formulario.dataset.tipoAvaliavel =
            tipoAvaliavel;

        this.formulario.dataset.identificadorAvaliavel =
            identificadorAvaliavel;

        this.elementoNomeAvaliavel.textContent =
            nomeAvaliavel;

        this.pontuacaoSelecionada =
            pontuacaoUtilizador;

        this.pontuacaoAtual =
            pontuacaoUtilizador;

        this.campoPontuacao.value =
            String(pontuacaoUtilizador);

        this.atualizarEstrelas(
            pontuacaoUtilizador,
        );

        this.limparErroAvaliacao();
    }

    /**
     * Converte um valor numa pontuação válida entre zero e dez.
     *
     * @param {unknown} valor Valor a normalizar.
     * @returns {number}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    normalizarPontuacao(valor) {
        const numero =
            Number.parseFloat(
                String(valor ?? ''),
            );

        if (!Number.isFinite(numero)) {
            return 0;
        }

        const limitado =
            Math.min(
                10,
                Math.max(
                    0,
                    numero,
                ),
            );

        return Math.round(
            limitado * 2,
        ) / 2;
    }

    /**
     * Trata o movimento do rato sobre as estrelas.
     *
     * @param {MouseEvent} evento Evento de movimento do rato.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    tratarMovimentoRato(evento) {
        const pontuacao =
            this.obterPontuacaoDoEvento(
                evento,
            );

        if (pontuacao === null) {
            return;
        }

        this.pontuacaoAtual =
            pontuacao;

        this.atualizarEstrelas(
            pontuacao,
        );
    }

    /**
     * Repõe a pontuação selecionada quando o rato sai das estrelas.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    tratarSaidaRato() {
        this.pontuacaoAtual =
            this.pontuacaoSelecionada;

        this.atualizarEstrelas(
            this.pontuacaoSelecionada,
        );
    }

    /**
     * Guarda a pontuação correspondente à estrela selecionada.
     *
     * @param {MouseEvent} evento Evento de clique.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    tratarCliqueEstrela(evento) {
        const pontuacao =
            this.obterPontuacaoDoEvento(
                evento,
            );

        if (pontuacao === null) {
            return;
        }

        this.pontuacaoSelecionada =
            pontuacao;

        this.pontuacaoAtual =
            pontuacao;

        this.campoPontuacao.value =
            String(pontuacao);

        this.atualizarEstrelas(
            pontuacao,
        );

        this.limparErroAvaliacao();
    }

    /**
     * Obtém a pontuação correspondente à posição do rato numa estrela.
     *
     * @param {MouseEvent} evento Evento do rato.
     * @returns {number|null}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    obterPontuacaoDoEvento(evento) {
        if (!(evento.target instanceof Element)) {
            return null;
        }

        const estrela =
            evento.target.closest(
                '[data-valor]',
            );

        if (
            !(estrela instanceof HTMLElement)
            || !this.contentorEstrelas.contains(
                estrela,
            )
        ) {
            return null;
        }

        const valorBase =
            Number.parseInt(
                estrela.dataset.valor
                ?? '',
                10,
            );

        if (!Number.isFinite(valorBase)) {
            return null;
        }

        const limites =
            estrela.getBoundingClientRect();

        const metadeEsquerda =
            evento.clientX - limites.left
            < limites.width / 2;

        return this.normalizarPontuacao(
            metadeEsquerda
                ? valorBase - 0.5
                : valorBase,
        );
    }

    /**
     * Atualiza a representação visual das estrelas.
     *
     * @param {number} pontuacao Valor da avaliação.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    atualizarEstrelas(pontuacao) {
        const estrelas =
            this.contentorEstrelas
                .querySelectorAll(
                    '[data-valor]',
                );

        const estrelasCompletas =
            Math.floor(
                pontuacao,
            );

        const temMeiaEstrela =
            pontuacao % 1 !== 0;

        estrelas.forEach(
            (estrela) => {
                const valorEstrela =
                    Number.parseInt(
                        estrela.dataset.valor
                        ?? '',
                        10,
                    );

                estrela.classList.remove(
                    'bi-star',
                    'bi-star-fill',
                    'bi-star-half',
                    'estrela-preenchida',
                );

                if (
                    valorEstrela
                    <= estrelasCompletas
                ) {
                    estrela.classList.add(
                        'bi-star-fill',
                        'estrela-preenchida',
                    );

                    return;
                }

                if (
                    temMeiaEstrela
                    && valorEstrela
                    === estrelasCompletas + 1
                ) {
                    estrela.classList.add(
                        'bi-star-half',
                        'estrela-preenchida',
                    );

                    return;
                }

                estrela.classList.add(
                    'bi-star',
                );
            },
        );

        if (
            this.elementoFeedback
            instanceof HTMLElement
        ) {
            this.elementoFeedback.textContent =
                pontuacao > 0
                    ? `A tua seleção: ${
                        this.formatarPontuacao(
                            pontuacao,
                        )
                    }/10`
                    : 'Clica numa estrela para avaliar.';
        }
    }

    /**
     * Submete o formulário de avaliação.
     *
     * @param {SubmitEvent} evento Evento de submissão.
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    async submeterFormulario(evento) {
        evento.preventDefault();

        if (
            this.emSubmissao
            || !(
                this.botaoAcionador
                instanceof HTMLElement
            )
        ) {
            return;
        }

        if (
            this.pontuacaoSelecionada
            <= 0
        ) {
            this.apresentarErroAvaliacao(
                'Seleciona uma avaliação antes de guardar.',
            );

            return;
        }

        this.emSubmissao =
            true;

        try {
            const tratadorFormulario =
                new TratadorFormularioAjax(
                    this.formulario.id,
                    this.formulario.action,
                    (dadosResposta) =>
                        this.atualizarResultado(
                            dadosResposta,
                        ),
                );

            await tratadorFormulario.submeter();
        } finally {
            this.emSubmissao =
                false;
        }
    }

    /**
     * Atualiza o botão acionador e o resumo das avaliações.
     *
     * @param {Record<string, unknown>} dados Dados devolvidos pelo servidor.
     *
     * @since 2.0.0
     * @version 3.0.0
     */
    atualizarResultado(dados) {
        if (
            !(
                this.botaoAcionador
                instanceof HTMLElement
            )
        ) {
            return;
        }

        const pontuacaoUtilizador =
            Number(
                dados.pontuacao_utilizador,
            );

        const mediaAvaliacoes =
            Number(
                dados.media_avaliacoes,
            );

        const numeroAvaliacoes =
            Number.parseInt(
                String(
                    dados.numero_avaliacoes
                    ?? '',
                ),
                10,
            );

        const conteudoIndicador =
            dados.conteudo_indicador_html;

        if (
            Number.isFinite(
                pontuacaoUtilizador,
            )
        ) {
            const textoBotao =
                this.botaoAcionador
                    .querySelector(
                        '[data-texto-avaliacao], span',
                    );

            if (
                textoBotao
                instanceof HTMLElement
            ) {
                textoBotao.textContent =
                    `A tua avaliação: ${
                        this.formatarPontuacao(
                            pontuacaoUtilizador,
                        )
                    }`;
            }

            this.botaoAcionador
                .dataset
                .pontuacaoUtilizador =
                    String(
                        pontuacaoUtilizador,
                    );
        }

        const contentorInteracoes =
            this.botaoAcionador.closest(
                '[data-contentor-interacoes]',
            );

        const apresentacaoAvaliacoes =
            contentorInteracoes?.querySelector(
                '.apresentacao-avaliacoes',
            );

        if (
            apresentacaoAvaliacoes
            instanceof HTMLElement
        ) {
            const elementoMedia =
                apresentacaoAvaliacoes
                    .querySelector(
                        '.media-avaliacoes',
                    );

            const elementoTotal =
                apresentacaoAvaliacoes
                    .querySelector(
                        '.quantidade-avaliacoes',
                    );

            if (
                elementoMedia instanceof HTMLElement
                && Number.isFinite(
                    mediaAvaliacoes,
                )
            ) {
                elementoMedia.textContent =
                    this.formatarPontuacao(
                        mediaAvaliacoes,
                    );
            }

            if (
                elementoTotal instanceof HTMLElement
                && Number.isInteger(
                    numeroAvaliacoes,
                )
                && numeroAvaliacoes >= 0
            ) {
                elementoTotal.textContent =
                    String(
                        numeroAvaliacoes,
                    );
            }

            if (
                typeof conteudoIndicador
                === 'string'
            ) {
                this.atualizarTooltip(
                    apresentacaoAvaliacoes,
                    conteudoIndicador,
                );
            }
        }

        Modal
            .getOrCreateInstance(
                this.modal,
            )
            .hide();
    }

    /**
     * Formata uma pontuação para apresentação em português.
     *
     * @param {number} pontuacao Pontuação recebida.
     * @returns {string}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    formatarPontuacao(pontuacao) {
        return pontuacao
            .toFixed(1)
            .replace(
                '.',
                ',',
            );
    }

    /**
     * Apresenta uma mensagem de erro associada à avaliação.
     *
     * @param {string} mensagem Mensagem a apresentar.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    apresentarErroAvaliacao(mensagem) {
        this.campoPontuacao.classList.add(
            'is-invalid',
        );

        this.campoPontuacao.setAttribute(
            'aria-invalid',
            'true',
        );

        if (
            this.elementoFeedback
            instanceof HTMLElement
        ) {
            this.elementoFeedback.textContent =
                mensagem;

            this.elementoFeedback.classList.add(
                'text-danger',
            );
        }
    }

    /**
     * Limpa a mensagem de erro associada à avaliação.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    limparErroAvaliacao() {
        this.campoPontuacao.classList.remove(
            'is-invalid',
        );

        this.campoPontuacao.removeAttribute(
            'aria-invalid',
        );

        if (
            this.elementoFeedback
            instanceof HTMLElement
        ) {
            this.elementoFeedback.classList.remove(
                'text-danger',
            );
        }
    }

    /**
     * Atualiza o tooltip do resumo das avaliações.
     *
     * @param {HTMLElement} elemento Elemento associado ao tooltip.
     * @param {string} conteudo Novo conteúdo do tooltip.
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
     * Repõe o estado interno quando a janela modal é fechada.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    reporEstado() {
        this.formulario.reset();

        this.pontuacaoAtual = 0;
        this.pontuacaoSelecionada = 0;
        this.botaoAcionador = null;
        this.campoPontuacao.value = '0';

        delete this.formulario
            .dataset
            .tipoAvaliavel;

        delete this.formulario
            .dataset
            .identificadorAvaliavel;

        this.atualizarEstrelas(0);
        this.limparErroAvaliacao();
    }

    /**
     * Remove os eventos associados ao gestor.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        if (!this.iniciado) {
            return;
        }

        this.modal.removeEventListener(
            'show.bs.modal',
            this.aoAbrirModal,
        );

        this.modal.removeEventListener(
            'hidden.bs.modal',
            this.aoFecharModal,
        );

        this.contentorEstrelas.removeEventListener(
            'mousemove',
            this.aoMoverRato,
        );

        this.contentorEstrelas.removeEventListener(
            'mouseleave',
            this.aoSairEstrelas,
        );

        this.contentorEstrelas.removeEventListener(
            'click',
            this.aoClicarEstrela,
        );

        this.formulario.removeEventListener(
            'submit',
            this.aoSubmeterFormulario,
        );

        this.iniciado = false;
    }
}

export default GestorModalAvaliacao;
