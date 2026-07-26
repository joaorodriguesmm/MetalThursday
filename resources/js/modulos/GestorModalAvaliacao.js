import { Tooltip } from 'bootstrap';
import TratadorFormularioAjax from './TratadorFormularioAjax';

/**
 * Gere a interatividade da janela modal de avaliação.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class GestorModalAvaliacao {
    /**
     * Cria um gestor da janela modal de avaliação.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor() {
        this.modal = document.getElementById('ratingModal');
        this.formulario = document.getElementById('rating-form');
        this.contentorEstrelas = document.getElementById(
            'interactive-stars',
        );
        this.campoAvaliacao = document.getElementById(
            'rating-value-hidden',
        );
        this.elementoFeedback = document.getElementById(
            'rating-live-feedback',
        );
        this.elementoNomeAvaliado = document.getElementById(
            'rateable-name',
        );
        this.campoTipoAvaliado = document.getElementById(
            'rateable-type-hidden',
        );
        this.campoIdAvaliado = document.getElementById(
            'rateable-id-hidden',
        );

        this.avaliacaoAtual = 0;
        this.avaliacaoSelecionada = 0;
        this.botaoAcionador = null;
        this.emSubmissao = false;
        this.iniciado = false;

        this.aoAbrirModal = (evento) => this.configurarModal(evento);
        this.aoFecharModal = () => this.reporEstado();
        this.aoMoverRato = (evento) => this.tratarMovimentoRato(evento);
        this.aoSairEstrelas = () => this.tratarSaidaRato();
        this.aoClicarEstrela = (evento) => {
            this.tratarCliqueEstrela(evento);
        };
        this.aoSubmeterFormulario = (evento) => {
            this.submeterFormulario(evento);
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
        return this.modal instanceof HTMLElement
            && this.formulario instanceof HTMLFormElement
            && this.contentorEstrelas instanceof HTMLElement
            && this.campoAvaliacao instanceof HTMLInputElement
            && this.elementoNomeAvaliado instanceof HTMLElement
            && this.campoTipoAvaliado instanceof HTMLInputElement
            && this.campoIdAvaliado instanceof HTMLInputElement;
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
     * @version 2.0.0
     */
    configurarModal(evento) {
        const acionador = evento.relatedTarget;

        if (!(acionador instanceof HTMLElement)) {
            return;
        }

        const tipoAvaliado =
            acionador.dataset.rateableType?.trim()
            ?? '';

        const idAvaliado =
            acionador.dataset.rateableId?.trim()
            ?? '';

        const nomeAvaliado =
            acionador.dataset.rateableName?.trim()
            ?? '';

        const avaliacaoUtilizador =
            this.normalizarAvaliacao(
                acionador.dataset.userRating,
            );

        if (tipoAvaliado === '' || idAvaliado === '') {
            return;
        }

        this.botaoAcionador = acionador;
        this.campoTipoAvaliado.value = tipoAvaliado;
        this.campoIdAvaliado.value = idAvaliado;
        this.formulario.action =
            `/${tipoAvaliado}/${idAvaliado}/rate`;

        this.elementoNomeAvaliado.textContent =
            nomeAvaliado;

        this.avaliacaoSelecionada =
            avaliacaoUtilizador;

        this.avaliacaoAtual =
            avaliacaoUtilizador;

        this.campoAvaliacao.value =
            String(avaliacaoUtilizador);

        this.atualizarEstrelas(
            avaliacaoUtilizador,
        );

        this.limparErroAvaliacao();
    }

    /**
     * Converte um valor numa avaliação válida entre zero e dez.
     *
     * @param {unknown} valor Valor a normalizar.
     *
     * @returns {number}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    normalizarAvaliacao(valor) {
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
        const avaliacao =
            this.obterAvaliacaoDoEvento(
                evento,
            );

        if (avaliacao === null) {
            return;
        }

        this.avaliacaoAtual =
            avaliacao;

        this.atualizarEstrelas(
            avaliacao,
        );
    }

    /**
     * Repõe a avaliação selecionada quando o rato sai das estrelas.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    tratarSaidaRato() {
        this.avaliacaoAtual =
            this.avaliacaoSelecionada;

        this.atualizarEstrelas(
            this.avaliacaoSelecionada,
        );
    }

    /**
     * Guarda a avaliação correspondente à estrela selecionada.
     *
     * @param {MouseEvent} evento Evento de clique.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    tratarCliqueEstrela(evento) {
        const avaliacao =
            this.obterAvaliacaoDoEvento(
                evento,
            );

        if (avaliacao === null) {
            return;
        }

        this.avaliacaoSelecionada =
            avaliacao;

        this.avaliacaoAtual =
            avaliacao;

        this.campoAvaliacao.value =
            String(avaliacao);

        this.atualizarEstrelas(
            avaliacao,
        );

        this.limparErroAvaliacao();
    }

    /**
     * Obtém a avaliação correspondente à posição do rato numa estrela.
     *
     * @param {MouseEvent} evento Evento do rato.
     *
     * @returns {number|null}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    obterAvaliacaoDoEvento(evento) {
        if (!(evento.target instanceof Element)) {
            return null;
        }

        const estrela =
            evento.target.closest(
                'i[data-value]',
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
                estrela.dataset.value ?? '',
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

        return this.normalizarAvaliacao(
            metadeEsquerda
                ? valorBase - 0.5
                : valorBase,
        );
    }

    /**
     * Atualiza a representação visual das estrelas.
     *
     * @param {number} avaliacao Valor da avaliação.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    atualizarEstrelas(avaliacao) {
        const estrelas =
            this.contentorEstrelas.querySelectorAll(
                'i[data-value]',
            );

        const estrelasCompletas =
            Math.floor(avaliacao);

        const temMeiaEstrela =
            avaliacao % 1 !== 0;

        estrelas.forEach((estrela) => {
            const valorEstrela =
                Number.parseInt(
                    estrela.dataset.value ?? '',
                    10,
                );

            estrela.classList.remove(
                'bi-star',
                'bi-star-fill',
                'bi-star-half',
                'star-filled',
            );

            if (
                valorEstrela
                <= estrelasCompletas
            ) {
                estrela.classList.add(
                    'bi-star-fill',
                    'star-filled',
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
                    'star-filled',
                );

                return;
            }

            estrela.classList.add(
                'bi-star',
            );
        });

        if (
            this.elementoFeedback
            instanceof HTMLElement
        ) {
            this.elementoFeedback.textContent =
                avaliacao > 0
                    ? `A tua seleção: ${avaliacao.toFixed(1)}/10`
                    : 'Clica numa estrela para avaliar.';
        }
    }

    /**
     * Submete o formulário de avaliação.
     *
     * @param {SubmitEvent} evento Evento de submissão.
     *
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
            this.avaliacaoSelecionada
            <= 0
        ) {
            this.apresentarErroAvaliacao(
                'Seleciona uma avaliação antes de guardar.',
            );

            return;
        }

        this.emSubmissao = true;

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
            this.emSubmissao = false;
        }
    }

    /**
     * Atualiza o botão acionador e o resumo das avaliações.
     *
     * As chaves da resposta permanecem temporariamente inalteradas até à
     * revisão do controlador responsável pela avaliação.
     *
     * @param {Record<string, unknown>} dados Dados devolvidos pelo servidor.
     *
     * @since 2.0.0
     * @version 1.0.0
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

        const avaliacaoUtilizador =
            Number(
                dados.user_rating,
            );

        const avaliacaoMedia =
            dados.average_rating;

        const totalAvaliacoes =
            dados.ratings_count;

        const conteudoTooltip =
            dados.tooltip_html;

        if (
            Number.isFinite(
                avaliacaoUtilizador,
            )
        ) {
            const textoBotao =
                this.botaoAcionador.querySelector(
                    '[data-rating-button-text], span',
                );

            if (
                textoBotao
                instanceof HTMLElement
            ) {
                textoBotao.textContent =
                    `A tua avaliação: ${avaliacaoUtilizador.toFixed(1)}`;
            }

            this.botaoAcionador.dataset.userRating =
                String(
                    avaliacaoUtilizador,
                );

            this.botaoAcionador.classList.remove(
                'btn-dark',
                'btn-outline-warning',
            );

            this.botaoAcionador.classList.add(
                'btn-warning',
            );
        }

        const apresentacaoAvaliacao =
            this.botaoAcionador
                .closest('.d-flex')
                ?.querySelector(
                    '.rating-display',
                );

        if (
            !(
                apresentacaoAvaliacao
                instanceof HTMLElement
            )
        ) {
            return;
        }

        const elementoMedia =
            apresentacaoAvaliacao.querySelector(
                '.average-rating',
            );

        const elementoTotal =
            apresentacaoAvaliacao.querySelector(
                '.ratings-count',
            );

        if (
            elementoMedia
            instanceof HTMLElement
            && avaliacaoMedia !== undefined
            && avaliacaoMedia !== null
        ) {
            elementoMedia.textContent =
                String(
                    avaliacaoMedia,
                );
        }

        if (
            elementoTotal
            instanceof HTMLElement
            && totalAvaliacoes !== undefined
            && totalAvaliacoes !== null
        ) {
            elementoTotal.textContent =
                String(
                    totalAvaliacoes,
                );
        }

        if (
            typeof conteudoTooltip
            === 'string'
        ) {
            this.atualizarTooltip(
                apresentacaoAvaliacao,
                conteudoTooltip,
            );
        }
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
        this.campoAvaliacao.classList.add(
            'is-invalid',
        );

        this.campoAvaliacao.setAttribute(
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
        this.campoAvaliacao.classList.remove(
            'is-invalid',
        );

        this.campoAvaliacao.removeAttribute(
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
     * @version 1.0.0
     */
    reporEstado() {
        this.formulario.reset();

        this.avaliacaoAtual = 0;
        this.avaliacaoSelecionada = 0;
        this.botaoAcionador = null;
        this.campoAvaliacao.value = '0';

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
