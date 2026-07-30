import {
    Modal,
    Tooltip,
} from 'bootstrap';

import TratadorFormularioAjax
    from './TratadorFormularioAjax';

/**
 * Gere a interatividade da janela modal de avaliação.
 *
 * Suporta a seleção de meios valores através do rato e a seleção acessível
 * através do teclado.
 *
 * @since 1.0.0
 * @version 4.0.0
 */
class GestorModalAvaliacao {
    /**
     * Seletor das estrelas disponíveis.
     *
     * @type {string}
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    static SELETOR_ESTRELA =
        '[data-valor]';

    /**
     * Cria um gestor da janela modal de avaliação.
     *
     * @since 1.0.0
     * @version 4.0.0
     */
    constructor() {
        this.modal =
            document.getElementById(
                'modal-avaliacao',
            );

        this.formulario =
            this.modal?.querySelector(
                'form[data-formulario-avaliacao]',
            )
            ?? null;

        this.contentorEstrelas =
            this.modal?.querySelector(
                '[data-estrelas-avaliacao]',
            )
            ?? null;

        this.campoPontuacao =
            this.formulario?.querySelector(
                'input[name="pontuacao"]',
            )
            ?? null;

        this.elementoFeedback =
            this.modal?.querySelector(
                '[data-feedback-avaliacao]',
            )
            ?? null;

        this.elementoNomeAvaliavel =
            this.modal?.querySelector(
                '[data-nome-avaliavel]',
            )
            ?? null;

        this.estrelas =
            this.obterEstrelas();

        this.pontuacaoMaxima =
            this.obterPontuacaoMaxima();

        this.pontuacaoSelecionada =
            0;

        this.botaoAcionador =
            null;

        this.emSubmissao =
            false;

        this.iniciado =
            false;

        this.aoAbrirModal = (evento) => {
            this.configurarModal(
                evento,
            );
        };

        this.aoFecharModal = () => {
            this.reporEstado();
        };

        this.aoMoverRato = (evento) => {
            this.tratarMovimentoRato(
                evento,
            );
        };

        this.aoSairEstrelas = () => {
            this.tratarSaidaRato();
        };

        this.aoClicarEstrela = (evento) => {
            this.tratarCliqueEstrela(
                evento,
            );
        };

        this.aoPremirTecla = (evento) => {
            this.tratarTeclaEstrela(
                evento,
            );
        };

        this.aoSubmeterFormulario = (evento) => {
            this.submeterFormulario(
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
     * @version 3.0.0
     */
    estaAtivo() {
        return this.modal instanceof HTMLElement
            && this.formulario instanceof HTMLFormElement
            && this.formulario.id.trim() !== ''
            && this.contentorEstrelas instanceof HTMLElement
            && this.campoPontuacao instanceof HTMLInputElement
            && this.elementoFeedback instanceof HTMLElement
            && this.elementoNomeAvaliavel instanceof HTMLElement
            && this.estrelas.length > 0
            && this.pontuacaoMaxima > 0;
    }

    /**
     * Inicia os eventos da janela modal.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    iniciar() {
        if (!this.estaAtivo() || this.iniciado) {
            return;
        }

        this.prepararAcessibilidadeEstrelas();

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

        this.contentorEstrelas.addEventListener(
            'keydown',
            this.aoPremirTecla,
        );

        this.formulario.addEventListener(
            'submit',
            this.aoSubmeterFormulario,
        );

        this.iniciado =
            true;
    }

    /**
     * Prepara as estrelas para utilização através do teclado.
     *
     * @returns {void}
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    prepararAcessibilidadeEstrelas() {
        this.estrelas.forEach((estrela) => {
            estrela.setAttribute(
                'role',
                'button',
            );

            estrela.setAttribute(
                'tabindex',
                '0',
            );

            estrela.removeAttribute(
                'aria-hidden',
            );
        });
    }

    /**
     * Configura a janela modal para o elemento selecionado.
     *
     * @param {Event} evento Evento de abertura da janela modal.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 4.0.0
     */
    configurarModal(evento) {
        const acionador =
            evento.relatedTarget;

        if (!(acionador instanceof HTMLElement)) {
            this.apresentarErroAvaliacao(
                'Não foi possível identificar o elemento a avaliar.',
            );

            return;
        }

        const configuracao =
            this.obterConfiguracaoAcionador(
                acionador,
            );

        if (configuracao === null) {
            this.botaoAcionador =
                null;

            this.formulario.removeAttribute(
                'action',
            );

            this.apresentarErroAvaliacao(
                'Não foi possível preparar esta avaliação.',
            );

            return;
        }

        this.botaoAcionador =
            acionador;

        this.formulario.action =
            configuracao.endereco;

        this.formulario.dataset.tipoAvaliavel =
            configuracao.tipo;

        this.formulario.dataset.identificadorAvaliavel =
            String(
                configuracao.identificador,
            );

        this.elementoNomeAvaliavel.textContent =
            configuracao.nome;

        this.selecionarPontuacao(
            configuracao.pontuacaoUtilizador,
            {
                limparErro: true,
            },
        );
    }

    /**
     * Obtém e valida a configuração de um acionador.
     *
     * @param {HTMLElement} acionador Elemento que abriu a janela modal.
     *
     * @returns {{
     *     tipo: string,
     *     identificador: number,
     *     nome: string,
     *     endereco: string,
     *     pontuacaoUtilizador: number
     * }|null} Configuração validada ou nulo.
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    obterConfiguracaoAcionador(acionador) {
        const tipo =
            acionador.dataset.tipoAvaliavel?.trim()
            ?? '';

        const identificador =
            Number.parseInt(
                acionador.dataset
                    .identificadorAvaliavel
                ?? '',
                10,
            );

        const nome =
            acionador.dataset.nomeAvaliavel?.trim()
            ?? '';

        const endereco =
            this.normalizarEndereco(
                acionador.dataset.enderecoAvaliacao,
            );

        if (
            !/^[a-z0-9_-]+$/.test(
                tipo,
            )
            || !Number.isInteger(
                identificador,
            )
            || identificador <= 0
            || nome === ''
            || endereco === null
        ) {
            return null;
        }

        return {
            tipo,
            identificador,
            nome,
            endereco,

            pontuacaoUtilizador:
                this.normalizarPontuacao(
                    acionador.dataset
                        .pontuacaoUtilizador,
                ),
        };
    }

    /**
     * Normaliza e valida o endereço da avaliação.
     *
     * Apenas são aceites endereços HTTP ou HTTPS da origem atual.
     *
     * @param {unknown} endereco Endereço recebido.
     *
     * @returns {string|null} Endereço normalizado ou nulo.
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
                !['http:', 'https:'].includes(
                    url.protocol,
                )
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
     * Converte um valor numa pontuação válida.
     *
     * @param {unknown} valor Valor a normalizar.
     *
     * @returns {number} Pontuação entre zero e o máximo disponível.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    normalizarPontuacao(valor) {
        const numero =
            Number.parseFloat(
                String(
                    valor
                    ?? '',
                ),
            );

        if (!Number.isFinite(numero)) {
            return 0;
        }

        const limitado =
            Math.min(
                this.pontuacaoMaxima,
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
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    tratarMovimentoRato(evento) {
        const pontuacao =
            this.obterPontuacaoDoRato(
                evento,
            );

        if (pontuacao === null) {
            return;
        }

        this.atualizarEstrelas(
            pontuacao,
        );
    }

    /**
     * Repõe a pontuação selecionada quando o rato sai das estrelas.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    tratarSaidaRato() {
        this.atualizarEstrelas(
            this.pontuacaoSelecionada,
        );
    }

    /**
     * Guarda a pontuação correspondente à posição selecionada.
     *
     * @param {MouseEvent} evento Evento de clique.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    tratarCliqueEstrela(evento) {
        const pontuacao =
            this.obterPontuacaoDoRato(
                evento,
            );

        if (pontuacao === null) {
            return;
        }

        evento.preventDefault();

        this.selecionarPontuacao(
            pontuacao,
            {
                limparErro: true,
            },
        );
    }

    /**
     * Trata a seleção através do teclado.
     *
     * Enter e espaço escolhem o valor integral da estrela focada. As setas
     * alteram a seleção em incrementos de meio ponto.
     *
     * @param {KeyboardEvent} evento Evento de teclado.
     *
     * @returns {void}
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    tratarTeclaEstrela(evento) {
        const estrela =
            this.obterEstrelaDoEvento(
                evento,
            );

        if (estrela === null) {
            return;
        }

        const valorEstrela =
            this.obterValorEstrela(
                estrela,
            );

        if (valorEstrela === null) {
            return;
        }

        let novaPontuacao;

        switch (evento.key) {
            case 'Enter':
            case ' ':
                novaPontuacao =
                    valorEstrela;
                break;

            case 'ArrowLeft':
            case 'ArrowDown':
                novaPontuacao =
                    Math.max(
                        0.5,
                        this.pontuacaoSelecionada - 0.5,
                    );
                break;

            case 'ArrowRight':
            case 'ArrowUp':
                novaPontuacao =
                    Math.min(
                        this.pontuacaoMaxima,
                        Math.max(
                            0.5,
                            this.pontuacaoSelecionada + 0.5,
                        ),
                    );
                break;

            case 'Home':
                novaPontuacao =
                    0.5;
                break;

            case 'End':
                novaPontuacao =
                    this.pontuacaoMaxima;
                break;

            default:
                return;
        }

        evento.preventDefault();

        this.selecionarPontuacao(
            novaPontuacao,
            {
                limparErro: true,
            },
        );

        this.focarEstrelaCorrespondente(
            novaPontuacao,
        );
    }

    /**
     * Seleciona uma pontuação e atualiza o campo e a interface.
     *
     * @param {unknown} pontuacao Pontuação recebida.
     * @param {object} opcoes Opções da atualização.
     * @param {boolean} [opcoes.limparErro=false]
     *     Indica se o erro atual deve ser removido.
     *
     * @returns {void}
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    selecionarPontuacao(
        pontuacao,
        {
            limparErro = false,
        } = {},
    ) {
        const pontuacaoNormalizada =
            this.normalizarPontuacao(
                pontuacao,
            );

        this.pontuacaoSelecionada =
            pontuacaoNormalizada;

        this.campoPontuacao.value =
            String(
                pontuacaoNormalizada,
            );

        this.atualizarEstrelas(
            pontuacaoNormalizada,
        );

        if (limparErro) {
            this.limparErroAvaliacao();
        }
    }

    /**
     * Obtém a pontuação correspondente à posição do rato.
     *
     * @param {MouseEvent} evento Evento do rato.
     *
     * @returns {number|null} Pontuação encontrada ou nulo.
     *
     * @since 1.0.0
     * @version 4.0.0
     */
    obterPontuacaoDoRato(evento) {
        const estrela =
            this.obterEstrelaDoEvento(
                evento,
            );

        if (estrela === null) {
            return null;
        }

        const valorBase =
            this.obterValorEstrela(
                estrela,
            );

        if (valorBase === null) {
            return null;
        }

        const limites =
            estrela.getBoundingClientRect();

        if (limites.width <= 0) {
            return null;
        }

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
     * Obtém a estrela associada a um evento delegado.
     *
     * @param {Event} evento Evento recebido.
     *
     * @returns {HTMLElement|null} Estrela encontrada ou nulo.
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    obterEstrelaDoEvento(evento) {
        if (!(evento.target instanceof Element)) {
            return null;
        }

        const estrela =
            evento.target.closest(
                GestorModalAvaliacao
                    .SELETOR_ESTRELA,
            );

        if (
            !(estrela instanceof HTMLElement)
            || !this.contentorEstrelas.contains(
                estrela,
            )
        ) {
            return null;
        }

        return estrela;
    }

    /**
     * Obtém o valor numérico de uma estrela.
     *
     * @param {HTMLElement} estrela Estrela recebida.
     *
     * @returns {number|null} Valor positivo ou nulo.
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    obterValorEstrela(estrela) {
        const valor =
            Number.parseFloat(
                estrela.dataset.valor
                ?? '',
            );

        return Number.isFinite(valor)
            && valor > 0
            ? valor
            : null;
    }

    /**
     * Obtém as estrelas existentes na janela modal.
     *
     * @returns {Array<HTMLElement>} Estrelas encontradas.
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    obterEstrelas() {
        if (
            !(
                this.contentorEstrelas
                instanceof HTMLElement
            )
        ) {
            return [];
        }

        return Array.from(
            this.contentorEstrelas.querySelectorAll(
                GestorModalAvaliacao
                    .SELETOR_ESTRELA,
            ),
        ).filter(
            (estrela) =>
                estrela instanceof HTMLElement,
        );
    }

    /**
     * Obtém a pontuação máxima representada pelas estrelas.
     *
     * @returns {number} Pontuação máxima ou zero.
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    obterPontuacaoMaxima() {
        const valores =
            this.estrelas
                .map(
                    (estrela) =>
                        this.obterValorEstrela(
                            estrela,
                        ),
                )
                .filter(
                    (valor) =>
                        valor !== null,
                );

        return valores.length > 0
            ? Math.max(
                ...valores,
            )
            : 0;
    }

    /**
     * Move o foco para a estrela correspondente à pontuação.
     *
     * @param {number} pontuacao Pontuação selecionada.
     *
     * @returns {void}
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    focarEstrelaCorrespondente(pontuacao) {
        const valorProcurado =
            Math.ceil(
                pontuacao,
            );

        const estrela =
            this.estrelas.find(
                (elemento) =>
                    this.obterValorEstrela(
                        elemento,
                    ) === valorProcurado,
            );

        estrela?.focus();
    }

    /**
     * Atualiza a representação visual das estrelas.
     *
     * @param {number} pontuacao Valor da avaliação.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 4.0.0
     */
    atualizarEstrelas(pontuacao) {
        const estrelasCompletas =
            Math.floor(
                pontuacao,
            );

        const temMeiaEstrela =
            pontuacao % 1 !== 0;

        this.estrelas.forEach((estrela) => {
            const valorEstrela =
                this.obterValorEstrela(
                    estrela,
                );

            estrela.classList.remove(
                'bi-star',
                'bi-star-fill',
                'bi-star-half',
                'estrela-preenchida',
            );

            if (valorEstrela === null) {
                estrela.classList.add(
                    'bi-star',
                );

                return;
            }

            if (valorEstrela <= estrelasCompletas) {
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
        });

        this.elementoFeedback.textContent =
            pontuacao > 0
                ? `A tua seleção: ${
                    this.formatarPontuacao(
                        pontuacao,
                    )
                }/${this.formatarPontuacaoMaxima()}`
                : 'Clica numa estrela para avaliar.';
    }

    /**
     * Submete o formulário de avaliação.
     *
     * @param {SubmitEvent} evento Evento de submissão.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 3.0.0
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

        if (this.pontuacaoSelecionada <= 0) {
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
                    (dadosResposta) => {
                        this.atualizarResultado(
                            dadosResposta,
                        );
                    },
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
     * @returns {void}
     *
     * @since 2.0.0
     * @version 4.0.0
     */
    atualizarResultado(dados) {
        if (
            !(
                this.botaoAcionador
                instanceof HTMLElement
            )
            || typeof dados !== 'object'
            || dados === null
            || Array.isArray(dados)
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
                this.botaoAcionador.querySelector(
                    '[data-texto-avaliacao]',
                );

            if (textoBotao instanceof HTMLElement) {
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
            this.atualizarResumoAvaliacoes(
                apresentacaoAvaliacoes,
                mediaAvaliacoes,
                numeroAvaliacoes,
                conteudoIndicador,
            );
        }

        Modal
            .getOrCreateInstance(
                this.modal,
            )
            .hide();
    }

    /**
     * Atualiza os valores apresentados no resumo das avaliações.
     *
     * @param {HTMLElement} apresentacao Contentor do resumo.
     * @param {number} media Média recebida.
     * @param {number} quantidade Quantidade recebida.
     * @param {unknown} conteudoIndicador Conteúdo do tooltip.
     *
     * @returns {void}
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    atualizarResumoAvaliacoes(
        apresentacao,
        media,
        quantidade,
        conteudoIndicador,
    ) {
        const elementoMedia =
            apresentacao.querySelector(
                '.media-avaliacoes',
            );

        const elementoQuantidade =
            apresentacao.querySelector(
                '.quantidade-avaliacoes',
            );

        if (
            elementoMedia instanceof HTMLElement
            && Number.isFinite(media)
        ) {
            elementoMedia.textContent =
                this.formatarPontuacao(
                    media,
                );
        }

        if (
            elementoQuantidade instanceof HTMLElement
            && Number.isInteger(quantidade)
            && quantidade >= 0
        ) {
            elementoQuantidade.textContent =
                String(
                    quantidade,
                );
        }

        if (typeof conteudoIndicador === 'string') {
            this.atualizarTooltip(
                apresentacao,
                conteudoIndicador,
            );
        }
    }

    /**
     * Formata uma pontuação para apresentação em português.
     *
     * @param {number} pontuacao Pontuação recebida.
     *
     * @returns {string} Pontuação com uma casa decimal.
     *
     * @since 3.0.0
     * @version 2.0.0
     */
    formatarPontuacao(pontuacao) {
        return new Intl.NumberFormat(
            'pt-PT',
            {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1,
            },
        ).format(
            pontuacao,
        );
    }

    /**
     * Formata a pontuação máxima sem casas decimais desnecessárias.
     *
     * @returns {string} Pontuação máxima formatada.
     *
     * @since 4.0.0
     * @version 1.0.0
     */
    formatarPontuacaoMaxima() {
        return new Intl.NumberFormat(
            'pt-PT',
            {
                maximumFractionDigits: 1,
            },
        ).format(
            this.pontuacaoMaxima,
        );
    }

    /**
     * Apresenta uma mensagem de erro associada à avaliação.
     *
     * @param {string} mensagem Mensagem a apresentar.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    apresentarErroAvaliacao(mensagem) {
        this.campoPontuacao.classList.add(
            'is-invalid',
        );

        this.campoPontuacao.setAttribute(
            'aria-invalid',
            'true',
        );

        this.elementoFeedback.textContent =
            mensagem;

        this.elementoFeedback.classList.add(
            'text-danger',
        );
    }

    /**
     * Limpa a mensagem de erro associada à avaliação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    limparErroAvaliacao() {
        this.campoPontuacao.classList.remove(
            'is-invalid',
        );

        this.campoPontuacao.removeAttribute(
            'aria-invalid',
        );

        this.elementoFeedback.classList.remove(
            'text-danger',
        );
    }

    /**
     * Atualiza o tooltip do resumo das avaliações.
     *
     * @param {HTMLElement} elemento Elemento associado ao tooltip.
     * @param {string} conteudo Novo conteúdo do tooltip.
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
     * Repõe o estado interno quando a janela modal é fechada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 3.0.0
     */
    reporEstado() {
        this.formulario.reset();

        this.pontuacaoSelecionada =
            0;

        this.botaoAcionador =
            null;

        this.campoPontuacao.value =
            '0';

        this.formulario.removeAttribute(
            'action',
        );

        delete this.formulario
            .dataset
            .tipoAvaliavel;

        delete this.formulario
            .dataset
            .identificadorAvaliavel;

        this.elementoNomeAvaliavel.textContent =
            '';

        this.atualizarEstrelas(
            0,
        );

        this.limparErroAvaliacao();
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

        this.contentorEstrelas.removeEventListener(
            'keydown',
            this.aoPremirTecla,
        );

        this.formulario.removeEventListener(
            'submit',
            this.aoSubmeterFormulario,
        );

        this.iniciado =
            false;
    }
}

export default GestorModalAvaliacao;
