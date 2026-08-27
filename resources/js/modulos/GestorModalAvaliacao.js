import axios from 'axios';
import {
    Modal,
    Tooltip,
} from 'bootstrap';
import GestorAlertas from './GestorAlertas';

/**
 * Gere a interatividade da janela modal de avaliação.
 *
 * Suporta a seleção de meios valores através do rato e a seleção acessível
 * através do teclado.
 *
 * @since 1.0.0
 */
class GestorModalAvaliacao {
    /**
     * Seletor das estrelas disponíveis.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static SELETOR_ESTRELA = '[data-valor]';

    /**
     * Cria e inicializa o gestor da janela modal de avaliação.
     *
     * @since 1.0.0
     */
    constructor() {
        this.modal = document.getElementById(
            'modal-avaliacao',
        );

        this.formulario = this.modal?.querySelector(
            'form[data-formulario-avaliacao]',
        ) ?? null;

        this.contentorEstrelas = this.modal?.querySelector(
            '[data-estrelas-avaliacao]',
        ) ?? null;

        this.campoPontuacao = this.formulario?.querySelector(
            'input[name="pontuacao"]',
        ) ?? null;

        this.elementoFeedback = this.modal?.querySelector(
            '[data-feedback-avaliacao]',
        ) ?? null;

        this.elementoErro = this.modal?.querySelector(
            '#erro-pontuacao-avaliacao',
        ) ?? null;

        this.elementoNomeAvaliavel = this.modal?.querySelector(
            '[data-nome-avaliavel]',
        ) ?? null;

        this.botaoSubmissao = this.formulario?.querySelector(
            'button[type="submit"]',
        ) ?? null;

        this.botaoLimparAvaliacao = this.modal?.querySelector(
            '[data-limpar-avaliacao]',
        ) ?? null;

        this.estrelas = this.obterEstrelas();

        this.pontuacaoMaxima =
            this.obterPontuacaoMaxima();

        /**
         * Pontuação efetivamente selecionada.
         *
         * @type {number}
         *
         * @since 2.0.0
         */
        this.pontuacaoSelecionada = 0;

        /**
         * Pontuação atualmente representada visualmente pelas estrelas.
         *
         * Permite evitar alterações repetidas da DOM durante `mousemove`.
         *
         * @type {number|null}
         *
         * @since 2.0.0
         */
        this.pontuacaoApresentada = null;

        /**
         * Estrela que apresenta atualmente o tooltip da pontuação.
         *
         * @type {HTMLButtonElement|null}
         *
         * @since 2.0.0
         */
        this.estrelaTooltipAtiva = null;

        /**
         * Pontuação apresentada pelo tooltip atual.
         *
         * Evita reconstruir o tooltip em todos os eventos `mousemove` quando o
         * cursor continua sobre a mesma metade da mesma estrela.
         *
         * @type {number|null}
         *
         * @since 2.0.0
         */
        this.pontuacaoTooltipAtiva = null;

        /**
         * Botão que abriu a modal para a avaliação atual.
         *
         * @type {HTMLElement|null}
         *
         * @since 2.0.0
         */
        this.botaoAcionador = null;

        /**
         * Endereço validado utilizado pela avaliação atual.
         *
         * @type {string|null}
         *
         * @since 2.0.0
         */
        this.enderecoSubmissao = null;

        /**
         * Indica se existe uma submissão em curso.
         *
         * @type {boolean}
         *
         * @since 2.0.0
         */
        this.emSubmissao = false;

        this.formatadorPontuacao =
            new Intl.NumberFormat(
                'pt-PT',
                {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                },
            );

        this.formatadorPontuacaoMaxima =
            new Intl.NumberFormat(
                'pt-PT',
                {
                    maximumFractionDigits: 1,
                },
            );

        if (!this.estaAtivo()) {
            return;
        }

        this.prepararNavegacaoEstrelas();

        this.modal.addEventListener(
            'show.bs.modal',
            (evento) => this.configurarModal(evento),
        );

        this.modal.addEventListener(
            'hide.bs.modal',
            (evento) => this.tratarFechoModal(evento),
        );

        this.modal.addEventListener(
            'hidden.bs.modal',
            () => this.reporEstado(),
        );

        this.contentorEstrelas.addEventListener(
            'mousemove',
            (evento) => this.tratarMovimentoRato(evento),
        );

        this.contentorEstrelas.addEventListener(
            'mouseleave',
            () => this.tratarSaidaRato(),
        );

        this.contentorEstrelas.addEventListener(
            'click',
            (evento) => this.tratarCliqueEstrela(evento),
        );

        this.contentorEstrelas.addEventListener(
            'keydown',
            (evento) => this.tratarTeclaEstrela(evento),
        );

        this.formulario.addEventListener(
            'submit',
            (evento) => {
                void this.submeterFormulario(evento);
            },
        );

        this.botaoLimparAvaliacao.addEventListener(
            'click',
            () => {
                void this.limparAvaliacao();
            },
        );

        this.selecionarPontuacao(0);
    }

    /**
     * Verifica se todos os elementos obrigatórios estão disponíveis.
     *
     * @returns {boolean} Verdadeiro quando o gestor pode funcionar.
     *
     * @since 2.0.0
     */
    estaAtivo() {
        return this.modal instanceof HTMLElement
            && this.formulario instanceof HTMLFormElement
            && this.formulario.id.trim() !== ''
            && this.contentorEstrelas instanceof HTMLElement
            && this.campoPontuacao instanceof HTMLInputElement
            && this.elementoFeedback instanceof HTMLElement
            && this.elementoErro instanceof HTMLElement
            && this.elementoNomeAvaliavel instanceof HTMLElement
            && this.botaoSubmissao instanceof HTMLButtonElement
            && this.botaoLimparAvaliacao instanceof HTMLButtonElement
            && this.estrelas.length > 0
            && this.pontuacaoMaxima > 0;
    }

    /**
     * Prepara uma única estrela para integrar o ciclo normal de tabulação.
     *
     * As restantes continuam acessíveis através das teclas direcionais.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    prepararNavegacaoEstrelas() {
        this.estrelas.forEach(
            (estrela, indice) => {
                estrela.tabIndex =
                    indice === 0
                        ? 0
                        : -1;
            },
        );
    }

    /**
     * Impede o fecho da modal durante uma submissão.
     *
     * @param {Event} evento Evento de fecho do Bootstrap.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarFechoModal(evento) {
        if (this.emSubmissao) {
            evento.preventDefault();
        }
    }

    /**
     * Configura a janela modal para o elemento selecionado.
     *
     * @param {Event} evento Evento de abertura da janela modal.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    configurarModal(evento) {
        const acionador = evento.relatedTarget;

        this.limparErroAvaliacao();

        if (!(acionador instanceof HTMLElement)) {
            this.invalidarConfiguracaoAtual();

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
            this.invalidarConfiguracaoAtual();

            this.apresentarErroAvaliacao(
                'Não foi possível preparar esta avaliação.',
            );

            return;
        }

        this.botaoAcionador = acionador;
        this.enderecoSubmissao =
            configuracao.endereco;

        this.formulario.action =
            configuracao.endereco;

        this.formulario.dataset.tipoAvaliavel =
            configuracao.tipo;

        this.formulario.dataset.identificadorAvaliavel =
            String(configuracao.identificador);

        this.elementoNomeAvaliavel.textContent =
            configuracao.nome;

        this.selecionarPontuacao(
            configuracao.pontuacaoUtilizador,
            {
                limparErro: true,
            },
        );

        this.atualizarDisponibilidadeLimpeza(
            configuracao.pontuacaoUtilizador,
        );
    }

    /**
     * Remove a configuração dinâmica da avaliação atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    invalidarConfiguracaoAtual() {
        this.botaoAcionador = null;
        this.enderecoSubmissao = null;

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
            'elemento selecionado';

        this.selecionarPontuacao(0);

        this.atualizarDisponibilidadeLimpeza(0);
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
     * @since 2.0.0
     */
    obterConfiguracaoAcionador(acionador) {
        const tipo =
            acionador.dataset.tipoAvaliavel?.trim()
            ?? '';

        const identificador = Number(
            acionador.dataset
                .identificadorAvaliavel
            ?? '',
        );

        const nome =
            acionador.dataset.nomeAvaliavel?.trim()
            ?? '';

        const endereco = this.normalizarEndereco(
            acionador.dataset.enderecoAvaliacao,
        );

        if (
            !/^[a-z0-9_-]+$/u.test(tipo)
            || !Number.isInteger(identificador)
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
     * Converte um valor numa pontuação válida.
     *
     * @param {unknown} valor Valor a normalizar.
     *
     * @returns {number} Pontuação entre zero e o máximo disponível.
     *
     * @since 2.0.0
     */
    normalizarPontuacao(valor) {
        const numero = Number(
            String(valor ?? '').trim(),
        );

        if (!Number.isFinite(numero)) {
            return 0;
        }

        const limitado = Math.min(
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
     * A representação visual e o tooltip utilizam exatamente a mesma pontuação,
     * incluindo os meios valores determinados pela metade da estrela sob o
     * cursor.
     *
     * @param {MouseEvent} evento Evento de movimento do rato.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    tratarMovimentoRato(evento) {
        if (this.emSubmissao) {
            return;
        }

        const estrela =
            this.obterEstrelaDoEvento(
                evento,
            );

        if (estrela === null) {
            return;
        }

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

        this.apresentarTooltipPontuacao(
            estrela,
            pontuacao,
        );
    }

    /**
     * Repõe visualmente a pontuação selecionada quando o rato sai.
     *
     * O tooltip da pré-visualização é também ocultado, porque deixa de existir
     * uma pontuação sob o cursor.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    tratarSaidaRato() {
        if (this.emSubmissao) {
            return;
        }

        this.ocultarTooltipPontuacao();

        this.atualizarEstrelas(
            this.pontuacaoSelecionada,
        );
    }

    /**
     * Apresenta a pontuação correspondente à posição atual do cursor.
     *
     * Quando o cursor muda entre as duas metades da mesma estrela, o conteúdo
     * visível do tooltip é atualizado diretamente, evitando reconstruir a
     * instância Bootstrap e provocar o desaparecimento momentâneo do indicador.
     *
     * Quando muda de estrela, o tooltip anterior é ocultado e a nova estrela
     * passa a apresentar a pontuação correspondente.
     *
     * @param {HTMLButtonElement} estrela Estrela sob o cursor.
     * @param {number} pontuacao Pontuação apresentada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarTooltipPontuacao(
        estrela,
        pontuacao,
    ) {
        const pontuacaoNormalizada =
            this.normalizarPontuacao(
                pontuacao,
            );

        if (
            this.estrelaTooltipAtiva === estrela
            && this.pontuacaoTooltipAtiva
                === pontuacaoNormalizada
        ) {
            return;
        }

        const conteudo =
            `${this.formatadorPontuacaoMaxima.format(
                pontuacaoNormalizada,
            )} em ${this.formatarPontuacaoMaxima()}`;

        /*
        * A estrela é a mesma e o tooltip já se encontra apresentado.
        *
        * Atualizar diretamente o conteúdo preserva a instância, o Popper e a
        * visibilidade. `Tooltip.setContent()` desmontaria a apresentação atual
        * e poderia fazê-la desaparecer durante a passagem entre meio valor e
        * valor inteiro da mesma estrela.
        */
        if (
            this.estrelaTooltipAtiva === estrela
        ) {
            const identificadorTooltip =
                estrela.getAttribute(
                    'aria-describedby',
                );

            if (
                identificadorTooltip !== null
                && identificadorTooltip.trim() !== ''
            ) {
                const tooltip =
                    document.getElementById(
                        identificadorTooltip,
                    );

                const conteudoTooltip =
                    tooltip?.querySelector(
                        '.tooltip-inner',
                    );

                if (
                    conteudoTooltip
                    instanceof HTMLElement
                ) {
                    conteudoTooltip.textContent =
                        conteudo;

                    this.pontuacaoTooltipAtiva =
                        pontuacaoNormalizada;

                    return;
                }
            }
        }

        if (
            this.estrelaTooltipAtiva !== null
            && this.estrelaTooltipAtiva !== estrela
        ) {
            Tooltip.getInstance(
                this.estrelaTooltipAtiva,
            )?.hide();
        }

        let instancia =
            Tooltip.getInstance(
                estrela,
            );

        if (instancia === null) {
            instancia = new Tooltip(
                estrela,
                {
                    trigger: 'manual',
                    placement: 'top',
                    animation: false,
                    title: conteudo,
                },
            );
        } else {
            instancia.setContent({
                '.tooltip-inner':
                    conteudo,
            });
        }

        instancia.show();

        this.estrelaTooltipAtiva =
            estrela;

        this.pontuacaoTooltipAtiva =
            pontuacaoNormalizada;
    }

    /**
     * Oculta o tooltip da pontuação atualmente apresentado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    ocultarTooltipPontuacao() {
        if (
            this.estrelaTooltipAtiva
            instanceof HTMLButtonElement
        ) {
            Tooltip.getInstance(
                this.estrelaTooltipAtiva,
            )?.hide();
        }

        this.estrelaTooltipAtiva =
            null;

        this.pontuacaoTooltipAtiva =
            null;
    }

    /**
     * Guarda a pontuação correspondente à posição selecionada.
     *
     * @param {MouseEvent} evento Evento de clique.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    tratarCliqueEstrela(evento) {
        if (this.emSubmissao) {
            return;
        }

        const pontuacao =
            this.obterPontuacaoDoRato(evento);

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
     * @since 2.0.0
     */
    tratarTeclaEstrela(evento) {
        if (this.emSubmissao) {
            return;
        }

        const estrela =
            this.obterEstrelaDoEvento(evento);

        if (estrela === null) {
            return;
        }

        const valorEstrela =
            this.obterValorEstrela(estrela);

        if (valorEstrela === null) {
            return;
        }

        let novaPontuacao;

        switch (evento.key) {
            case 'Enter':
            case ' ':
                novaPontuacao = valorEstrela;
                break;

            case 'ArrowLeft':
            case 'ArrowDown':
                novaPontuacao = Math.max(
                    0.5,
                    this.pontuacaoSelecionada - 0.5,
                );
                break;

            case 'ArrowRight':
            case 'ArrowUp':
                novaPontuacao = Math.min(
                    this.pontuacaoMaxima,
                    Math.max(
                        0.5,
                        this.pontuacaoSelecionada + 0.5,
                    ),
                );
                break;

            case 'Home':
                novaPontuacao = 0.5;
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
     * @since 2.0.0
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
            String(pontuacaoNormalizada);

        this.atualizarEstrelas(
            pontuacaoNormalizada,
        );

        this.atualizarFeedbackPontuacao(
            pontuacaoNormalizada,
        );

        this.atualizarEstrelaTabulavel(
            pontuacaoNormalizada,
        );

        if (limparErro) {
            this.limparErroAvaliacao();
        }
    }

    /**
     * Mostra ou oculta a ação de limpeza consoante exista uma avaliação.
     *
     * @param {unknown} pontuacao Pontuação atual do utilizador.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarDisponibilidadeLimpeza(pontuacao) {
        const pontuacaoNormalizada =
            this.normalizarPontuacao(
                pontuacao,
            );

        this.botaoLimparAvaliacao.hidden =
            pontuacaoNormalizada <= 0;
    }

    /**
     * Atualiza a mensagem que descreve a pontuação selecionada.
     *
     * @param {number} pontuacao Pontuação selecionada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarFeedbackPontuacao(pontuacao) {
        this.elementoFeedback.textContent =
            pontuacao > 0
                ? `A tua seleção: ${
                    this.formatarPontuacao(
                        pontuacao,
                    )
                }/${
                    this.formatarPontuacaoMaxima()
                }`
                : 'Seleciona uma estrela para avaliar.';
    }

    /**
     * Obtém a pontuação correspondente à posição do rato.
     *
     * @param {MouseEvent} evento Evento do rato.
     *
     * @returns {number|null} Pontuação encontrada ou nulo.
     *
     * @since 1.0.0
     */
    obterPontuacaoDoRato(evento) {
        const estrela =
            this.obterEstrelaDoEvento(evento);

        if (estrela === null) {
            return null;
        }

        const valorBase =
            this.obterValorEstrela(estrela);

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
     * @returns {HTMLButtonElement|null} Estrela encontrada ou nulo.
     *
     * @since 2.0.0
     */
    obterEstrelaDoEvento(evento) {
        if (!(evento.target instanceof Element)) {
            return null;
        }

        const estrela = evento.target.closest(
            GestorModalAvaliacao.SELETOR_ESTRELA,
        );

        if (
            !(estrela instanceof HTMLButtonElement)
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
     * @since 2.0.0
     */
    obterValorEstrela(estrela) {
        const valor = Number(
            estrela.dataset.valor ?? '',
        );

        return Number.isFinite(valor)
            && valor > 0
            ? valor
            : null;
    }

    /**
     * Obtém as estrelas existentes na janela modal.
     *
     * @returns {Array<HTMLButtonElement>} Estrelas encontradas.
     *
     * @since 2.0.0
     */
    obterEstrelas() {
        if (
            !(this.contentorEstrelas
                instanceof HTMLElement)
        ) {
            return [];
        }

        return Array.from(
            this.contentorEstrelas.querySelectorAll(
                GestorModalAvaliacao.SELETOR_ESTRELA,
            ),
        ).filter(
            (estrela) =>
                estrela instanceof HTMLButtonElement,
        );
    }

    /**
     * Obtém a pontuação máxima representada pelas estrelas.
     *
     * @returns {number} Pontuação máxima ou zero.
     *
     * @since 2.0.0
     */
    obterPontuacaoMaxima() {
        const valores = this.estrelas
            .map(
                (estrela) =>
                    this.obterValorEstrela(
                        estrela,
                    ),
            )
            .filter(
                (valor) => valor !== null,
            );

        return valores.length > 0
            ? Math.max(...valores)
            : 0;
    }

    /**
     * Atualiza a estrela que integra o ciclo normal de tabulação.
     *
     * @param {number} pontuacao Pontuação atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarEstrelaTabulavel(pontuacao) {
        const valorProcurado =
            pontuacao > 0
                ? Math.ceil(pontuacao)
                : this.obterValorEstrela(
                    this.estrelas[0],
                );

        const estrelaTabulavel =
            this.estrelas.find(
                (estrela) =>
                    this.obterValorEstrela(estrela)
                    === valorProcurado,
            )
            ?? this.estrelas[0];

        this.estrelas.forEach((estrela) => {
            estrela.tabIndex =
                estrela === estrelaTabulavel
                    ? 0
                    : -1;
        });
    }

    /**
     * Move o foco para a estrela correspondente à pontuação.
     *
     * @param {number} pontuacao Pontuação selecionada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    focarEstrelaCorrespondente(pontuacao) {
        const valorProcurado =
            Math.ceil(pontuacao);

        const estrela = this.estrelas.find(
            (elemento) =>
                this.obterValorEstrela(elemento)
                === valorProcurado,
        );

        estrela?.focus();
    }

    /**
     * Atualiza a representação visual das estrelas.
     *
     * Não altera a DOM quando a pontuação visual já corresponde ao valor
     * pretendido.
     *
     * @param {number} pontuacao Valor da avaliação.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    atualizarEstrelas(pontuacao) {
        const pontuacaoNormalizada =
            this.normalizarPontuacao(
                pontuacao,
            );

        if (
            this.pontuacaoApresentada
            === pontuacaoNormalizada
        ) {
            return;
        }

        const estrelasCompletas = Math.floor(
            pontuacaoNormalizada,
        );

        const temMeiaEstrela =
            pontuacaoNormalizada % 1 !== 0;

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
                estrela.classList.add('bi-star');

                return;
            }

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

            estrela.classList.add('bi-star');
        });

        this.pontuacaoApresentada =
            pontuacaoNormalizada;
    }

    /**
     * Submete o formulário de avaliação.
     *
     * @param {SubmitEvent} evento Evento de submissão.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     */
    async submeterFormulario(evento) {
        evento.preventDefault();

        if (
            this.emSubmissao
            || !(this.botaoAcionador
                instanceof HTMLElement)
            || this.enderecoSubmissao === null
        ) {
            return;
        }

        if (this.pontuacaoSelecionada <= 0) {
            this.apresentarErroAvaliacao(
                'Seleciona uma avaliação antes de guardar.',
            );

            this.estrelas[0]?.focus();

            return;
        }

        const botaoAcionador =
            this.botaoAcionador;

        const endereco =
            this.enderecoSubmissao;

        const estadoSubmissao =
            this.iniciarEstadoOperacao(
                this.botaoSubmissao,
                'A guardar...',
            );

        this.emSubmissao = true;

        let sucesso = false;

        try {
            const resposta = await axios.post(
                endereco,
                new FormData(
                    this.formulario,
                ),
            );

            if (
                !this.atualizarResultado(
                    botaoAcionador,
                    resposta.data,
                )
            ) {
                throw new Error(
                    'A resposta da avaliação é inválida.',
                );
            }

            this.despacharEventoSucesso(
                resposta.data,
            );

            sucesso = true;
        } catch (erro) {
            this.tratarErroSubmissao(erro);
        } finally {
            this.restaurarEstadoOperacao(
                estadoSubmissao,
            );

            this.emSubmissao = false;
        }

        if (!sucesso) {
            return;
        }

        Modal
            .getOrCreateInstance(
                this.modal,
            )
            .hide();

        void this.mostrarMensagemSucesso();
    }

    /**
     * Elimina a avaliação atual do utilizador.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    async limparAvaliacao() {
        if (
            this.emSubmissao
            || !(this.botaoAcionador
                instanceof HTMLElement)
            || this.enderecoSubmissao === null
        ) {
            return;
        }

        const pontuacaoAtual =
            this.normalizarPontuacao(
                this.botaoAcionador
                    .dataset
                    .pontuacaoUtilizador,
            );

        if (pontuacaoAtual <= 0) {
            this.atualizarDisponibilidadeLimpeza(
                0,
            );

            return;
        }

        const confirmado =
            await GestorAlertas.confirmar({
                titulo:
                    'Limpar avaliação?',

                mensagem:
                    'A tua avaliação será removida. Podes voltar a avaliar mais tarde.',

                textoConfirmar:
                    'Limpar avaliação',

                textoCancelar:
                    'Cancelar',
            });

        if (!confirmado) {
            return;
        }

        const botaoAcionador =
            this.botaoAcionador;

        const endereco =
            this.enderecoSubmissao;

        const estadoOperacao =
            this.iniciarEstadoOperacao(
                this.botaoLimparAvaliacao,
                'A limpar...',
            );

        this.emSubmissao = true;

        let dadosResposta = null;

        try {
            const resposta =
                await axios.delete(
                    endereco,
                );

            if (
                Number(
                    resposta
                        .data
                        ?.pontuacao_utilizador,
                ) !== 0
                || !this.atualizarResultado(
                    botaoAcionador,
                    resposta.data,
                )
            ) {
                throw new Error(
                    'A resposta da limpeza da avaliação é inválida.',
                );
            }

            dadosResposta =
                resposta.data;
        } catch (erro) {
            const mensagemResposta =
                axios.isAxiosError(erro)
                && typeof erro.response
                    ?.data
                    ?.mensagem === 'string'
                    ? erro.response
                        .data
                        .mensagem
                        .trim()
                    : '';

            await GestorAlertas.mostrarErro(
                mensagemResposta
                || 'Não foi possível limpar a avaliação.',
                'Erro ao limpar avaliação',
            );
        } finally {
            this.restaurarEstadoOperacao(
                estadoOperacao,
            );

            this.emSubmissao = false;
        }

        if (dadosResposta === null) {
            return;
        }

        Modal
            .getOrCreateInstance(
                this.modal,
            )
            .hide();

        const mensagemSucesso =
            typeof dadosResposta.mensagem === 'string'
                ? dadosResposta.mensagem.trim()
                : '';

        void GestorAlertas.mostrarSucesso(
            mensagemSucesso
            || 'Avaliação limpa com sucesso.',
        );
    }

    /**
     * Aplica o estado visual de uma operação e guarda os valores anteriores.
     *
     * @param {HTMLButtonElement} botaoOperacao Botão associado à operação.
     * @param {string} textoProgresso Texto apresentado durante a operação.
     *
     * @returns {{
     *     ariaBusy: string|null,
     *     botaoOperacao: HTMLButtonElement,
     *     conteudoBotaoOperacao: string,
     *     estadosBotoes: Map<HTMLButtonElement, boolean>
     * }} Estado anterior da interface.
     *
     * @since 2.0.0
     */
    iniciarEstadoOperacao(
        botaoOperacao,
        textoProgresso,
    ) {
        const estadosBotoes = new Map();

        this.modal.querySelectorAll('button')
            .forEach((botao) => {
                if (
                    !(botao
                        instanceof HTMLButtonElement)
                ) {
                    return;
                }

                estadosBotoes.set(
                    botao,
                    botao.disabled,
                );

                botao.disabled = true;
            });

        const estado = {
            ariaBusy:
                this.formulario.getAttribute(
                    'aria-busy',
                ),

            botaoOperacao,

            conteudoBotaoOperacao:
                botaoOperacao.innerHTML,

            estadosBotoes,
        };

        this.formulario.setAttribute(
            'aria-busy',
            'true',
        );

        botaoOperacao.innerHTML = [
            '<span class="spinner-border spinner-border-sm"',
            'role="status" aria-hidden="true"></span>',
            `<span>${textoProgresso}</span>`,
        ].join(' ');

        return estado;
    }

    /**
     * Repõe a interface depois de uma operação.
     *
     * @param {{
     *     ariaBusy: string|null,
     *     botaoOperacao: HTMLButtonElement,
     *     conteudoBotaoOperacao: string,
     *     estadosBotoes: Map<HTMLButtonElement, boolean>
     * }} estado Estado anterior.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    restaurarEstadoOperacao(estado) {
        estado.estadosBotoes.forEach(
            (desativado, botao) => {
                botao.disabled = desativado;
            },
        );

        estado.botaoOperacao.innerHTML =
            estado.conteudoBotaoOperacao;

        if (estado.ariaBusy === null) {
            this.formulario.removeAttribute(
                'aria-busy',
            );

            return;
        }

        this.formulario.setAttribute(
            'aria-busy',
            estado.ariaBusy,
        );
    }

    /**
     * Trata um erro de submissão.
     *
     * @param {unknown} erro Erro recebido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarErroSubmissao(erro) {
        const mensagemValidacao =
            axios.isAxiosError(erro)
            && erro.response?.status === 422
                ? this.obterPrimeiraMensagem(
                    erro.response
                        .data
                        ?.errors
                        ?.pontuacao,
                )
                : null;

        const mensagemResposta =
            axios.isAxiosError(erro)
            && typeof erro.response
                ?.data
                ?.mensagem === 'string'
                ? erro.response.data
                    .mensagem
                    .trim()
                : '';

        const mensagemConfigurada =
            this.formulario
                .dataset
                .mensagemErro
                ?.trim()
            ?? '';

        this.apresentarErroAvaliacao(
            mensagemValidacao
            || mensagemResposta
            || mensagemConfigurada
            || 'Não foi possível guardar a avaliação.',
        );

        this.focarEstrelaCorrespondente(
            this.pontuacaoSelecionada > 0
                ? this.pontuacaoSelecionada
                : 0.5,
        );
    }

    /**
     * Obtém a primeira mensagem de uma resposta de validação.
     *
     * @param {unknown} mensagens Mensagens recebidas.
     *
     * @returns {string|null} Primeira mensagem válida ou nulo.
     *
     * @since 2.0.0
     */
    obterPrimeiraMensagem(mensagens) {
        const mensagem =
            Array.isArray(mensagens)
                ? mensagens[0]
                : mensagens;

        return typeof mensagem === 'string'
            && mensagem.trim() !== ''
            ? mensagem.trim()
            : null;
    }

    /**
     * Mantém o evento global anteriormente emitido pelo tratador AJAX.
     *
     * @param {unknown} dadosResposta Dados devolvidos.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    despacharEventoSucesso(dadosResposta) {
        document.dispatchEvent(
            new CustomEvent(
                'formulario-ajax:sucesso',
                {
                    detail: {
                        idFormulario:
                            this.formulario.id,

                        dadosResposta,
                    },
                },
            ),
        );
    }

    /**
     * Atualiza o botão acionador e o resumo das avaliações.
     *
     * @param {HTMLElement} botaoAcionador Botão que originou a avaliação.
     * @param {unknown} dados Dados devolvidos pelo servidor.
     *
     * @returns {boolean} Indica se a resposta pôde ser aplicada.
     *
     * @since 2.0.0
     */
    atualizarResultado(
        botaoAcionador,
        dados,
    ) {
        if (
            !(botaoAcionador instanceof HTMLElement)
            || !this.eObjeto(dados)
        ) {
            return false;
        }

        const pontuacaoUtilizador = Number(
            dados.pontuacao_utilizador,
        );

        const mediaAvaliacoes = Number(
            dados.media_avaliacoes,
        );

        const numeroAvaliacoes = Number(
            dados.numero_avaliacoes,
        );

        if (
            !Number.isFinite(
                pontuacaoUtilizador,
            )
            || pontuacaoUtilizador < 0
            || pontuacaoUtilizador
                > this.pontuacaoMaxima
            || !Number.isFinite(
                mediaAvaliacoes,
            )
            || mediaAvaliacoes < 0
            || mediaAvaliacoes
                > this.pontuacaoMaxima
            || !Number.isInteger(
                numeroAvaliacoes,
            )
            || numeroAvaliacoes < 0
        ) {
            return false;
        }

        const textoSemAvaliacao =
            botaoAcionador
                .dataset
                .textoSemAvaliacao
                ?.trim()
            ?? '';

        if (
            pontuacaoUtilizador === 0
            && textoSemAvaliacao === ''
        ) {
            return false;
        }

        const textoBotao =
            botaoAcionador.querySelector(
                '[data-texto-avaliacao]',
            );

        if (textoBotao instanceof HTMLElement) {
            textoBotao.textContent =
                pontuacaoUtilizador > 0
                    ? `A tua avaliação: ${
                        this.formatarPontuacao(
                            pontuacaoUtilizador,
                        )
                    }`
                    : textoSemAvaliacao;
        }

        botaoAcionador
            .dataset
            .pontuacaoUtilizador =
                String(pontuacaoUtilizador);

        this.atualizarDisponibilidadeLimpeza(
            pontuacaoUtilizador,
        );

        const contentorInteracoes =
            botaoAcionador.closest(
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
                dados.conteudo_indicador_html,
            );
        }

        return true;
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
     * @since 2.0.0
     */
    atualizarResumoAvaliacoes(
        apresentacao,
        media,
        quantidade,
        conteudoIndicador,
    ) {
        apresentacao.setAttribute(
            'aria-label',
            `Consultar detalhes das avaliações. Média: ${
                this.formatarPontuacao(
                    media,
                )
            }. Total: ${quantidade}.`,
        );

        const elementoMedia =
            apresentacao.querySelector(
                '.media-avaliacoes',
            );

        const elementoQuantidade =
            apresentacao.querySelector(
                '.quantidade-avaliacoes',
            );

        if (elementoMedia instanceof HTMLElement) {
            elementoMedia.textContent =
                this.formatarPontuacao(media);
        }

        if (
            elementoQuantidade
            instanceof HTMLElement
        ) {
            elementoQuantidade.textContent =
                String(quantidade);
        }

        if (
            typeof conteudoIndicador === 'string'
            && conteudoIndicador.trim() !== ''
        ) {
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
     * @since 2.0.0
     */
    formatarPontuacao(pontuacao) {
        return this.formatadorPontuacao.format(
            pontuacao,
        );
    }

    /**
     * Formata a pontuação máxima.
     *
     * @returns {string} Pontuação máxima formatada.
     *
     * @since 2.0.0
     */
    formatarPontuacaoMaxima() {
        return this.formatadorPontuacaoMaxima
            .format(
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
     */
    apresentarErroAvaliacao(mensagem) {
        const texto = mensagem.trim();

        if (texto === '') {
            return;
        }

        this.campoPontuacao.classList.add(
            'is-invalid',
        );

        this.campoPontuacao.setAttribute(
            'aria-invalid',
            'true',
        );

        this.elementoErro.textContent = texto;

        this.elementoErro.classList.add(
            'd-block',
        );
    }

    /**
     * Limpa a mensagem de erro associada à avaliação.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    limparErroAvaliacao() {
        this.campoPontuacao.classList.remove(
            'is-invalid',
        );

        this.campoPontuacao.removeAttribute(
            'aria-invalid',
        );

        this.elementoErro.textContent = '';

        this.elementoErro.classList.remove(
            'd-block',
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
     */
    atualizarTooltip(
        elemento,
        conteudo,
    ) {
        elemento.setAttribute(
            'data-bs-title',
            conteudo,
        );

        const instancia =
            Tooltip.getOrCreateInstance(
                elemento,
            );

        instancia.setContent({
            '.tooltip-inner': conteudo,
        });
    }

    /**
     * Apresenta a mensagem de sucesso configurada para o formulário.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    async mostrarMensagemSucesso() {
        const mensagem =
            this.formulario
                .dataset
                .mensagemSucesso
                ?.trim()
            ?? '';

        if (mensagem === '') {
            return;
        }

        await GestorAlertas.mostrarSucesso(
            mensagem,
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
     * Repõe o estado interno quando a janela modal é fechada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    reporEstado() {
        this.ocultarTooltipPontuacao();

        this.formulario.reset();

        this.botaoAcionador = null;
        this.enderecoSubmissao = null;

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
            'elemento selecionado';

        this.pontuacaoApresentada = null;

        this.selecionarPontuacao(
            0,
            {
                limparErro: true,
            },
        );

        this.atualizarDisponibilidadeLimpeza(0);
    }
}

export default GestorModalAvaliacao;
