import axios from './ClienteHttp';
import Modal from 'bootstrap/js/dist/modal';
import GestorAlertas from './GestorAlertas';

/**
 * Gere a submissão assíncrona de formulários.
 *
 * @since 1.0.0
 */
class TratadorFormularioAjax {
    /**
     * Cria um tratador de formulário assíncrono.
     *
     * @param {string} idFormulario Identificador do formulário.
     * @param {string} url Endereço utilizado na submissão.
     * @param {((dados: unknown) => void|Promise<void>)|null} aoSucesso
     *     Função executada após uma submissão bem-sucedida.
     * @param {((erro: unknown) => boolean|Promise<boolean>)|null} aoErro
     *     Função opcional executada perante um erro de submissão. Deve devolver
     *     verdadeiro quando o erro tiver sido tratado pelo consumidor.
     *
     * @throws {TypeError} Quando algum argumento é inválido.
     *
     * @since 1.0.0
     */
    constructor(
        idFormulario,
        url,
        aoSucesso = null,
        aoErro = null,
        obterContextoSubmissao = null,
    ) {
        if (
            typeof idFormulario !== 'string'
            || idFormulario.trim() === ''
        ) {
            throw new TypeError(
                'O identificador do formulário é obrigatório.',
            );
        }

        if (
            typeof url !== 'string'
            || url.trim() === ''
        ) {
            throw new TypeError(
                'O endereço de submissão é obrigatório.',
            );
        }

        if (
            aoSucesso !== null
            && typeof aoSucesso !== 'function'
        ) {
            throw new TypeError(
                'A função de sucesso indicada é inválida.',
            );
        }

        if (
            aoErro !== null
            && typeof aoErro !== 'function'
        ) {
            throw new TypeError(
                'A função de erro indicada é inválida.',
            );
        }

        if (
            obterContextoSubmissao !== null
            && typeof obterContextoSubmissao !== 'function'
        ) {
            throw new TypeError(
                'A função de contexto da submissão é inválida.',
            );
        }

        const identificadorFormulario =
            idFormulario.trim();

        this.formulario =
            document.getElementById(
                identificadorFormulario,
            );

        this.url =
            url.trim();

        this.aoSucesso =
            aoSucesso;

        this.aoErro =
            aoErro;

        this.obterContextoSubmissao =
            obterContextoSubmissao;

        this.emSubmissao =
            false;

        this.chaveSubmissao = null;
        this.conteudoSubmissao = null;

        const botaoSubmissao =
            this.formulario?.querySelector(
                'button[type="submit"]',
            )
            ?? null;

        this.botaoSubmissao =
            botaoSubmissao
            instanceof HTMLButtonElement
                ? botaoSubmissao
                : null;
    }

    /**
     * Gera um UUID v4 com aleatoriedade criptograficamente segura.
     *
     * @returns {string} Identificador da operação.
     */
    gerarChaveSubmissao() {
        const bytes = new Uint8Array(16);
        globalThis.crypto.getRandomValues(bytes);

        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;

        const texto = Array.from(
            bytes,
            (valor) => valor.toString(16).padStart(2, '0'),
        ).join('');

        return [
            texto.slice(0, 8),
            texto.slice(8, 12),
            texto.slice(12, 16),
            texto.slice(16, 20),
            texto.slice(20),
        ].join('-');
    }

    /**
     * Conserva a chave entre tentativas iguais após falhas HTTP.
     *
     * @returns {FormData} Dados a enviar ao servidor.
     */
    prepararDadosSubmissao() {
        const dados = new FormData(this.formulario);

        if (!this.formulario.hasAttribute('data-idempotencia-submissao')) {
            return dados;
        }

        const conteudo = dados.get('conteudo');

        if (typeof conteudo !== 'string') {
            throw new TypeError('O conteúdo da submissão é inválido.');
        }

        if (
            this.chaveSubmissao === null
            || this.conteudoSubmissao !== conteudo
        ) {
            this.chaveSubmissao = this.gerarChaveSubmissao();
            this.conteudoSubmissao = conteudo;
        }

        dados.set('chave_submissao', this.chaveSubmissao);

        return dados;
    }

    /**
     * Obtém os valores dos campos e as referências dos ficheiros.
     *
     * @returns {object} Estado atual dos campos do formulário.
     *
     * @since 2.0.0
     */
    obterEstadoCamposFormulario() {
        return {
            conteudo: JSON.stringify(
                Array.from(
                    new FormData(this.formulario),
                    ([nome, valor]) => [
                        nome,
                        typeof valor === 'string'
                            ? valor
                            : null,
                    ],
                ),
            ),

            ficheiros: Array.from(
                this.formulario.querySelectorAll(
                    'input[type="file"]',
                ),
                (campo) => ({
                    campo,
                    selecionados: Array.from(
                        campo.files ?? [],
                    ),
                }),
            ),
        };
    }

    /**
     * Confirma que os campos não mudaram após a submissão.
     *
     * @param {object} estadoAnterior Estado anterior ao pedido.
     *
     * @returns {boolean} Verdadeiro quando os campos não mudaram.
     *
     * @since 2.0.0
     */
    camposFormularioMantidos(estadoAnterior) {
        const estadoAtual =
            this.obterEstadoCamposFormulario();

        return estadoAnterior.conteudo
            === estadoAtual.conteudo
            && estadoAnterior.ficheiros.length
                === estadoAtual.ficheiros.length
            && estadoAnterior.ficheiros.every(
                (entrada, indice) => {
                    const atual =
                        estadoAtual.ficheiros[indice];

                    return entrada.campo === atual.campo
                        && entrada.selecionados.length
                            === atual.selecionados.length
                        && entrada.selecionados.every(
                            (ficheiro, posicao) =>
                                ficheiro
                                === atual.selecionados[posicao],
                        );
                },
            );
    }

    /**
     * Submete o formulário de forma assíncrona.
     *
     * @param {Event|null} evento Evento de submissão.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     */
    async submeter(
        evento = null,
    ) {
        evento?.preventDefault();

        if (
            !(this.formulario
                instanceof HTMLFormElement)
            || !this.formulario.isConnected
            || this.emSubmissao
        ) {
            return;
        }

        this.emSubmissao =
            true;

        const estadoBotao =
            this.obterEstadoBotaoSubmissao();

        this.limparErros();
        this.apresentarEstadoCarregamento();

        const eventosReposicao = [];

        const observarReposicao = (evento) => {
            eventosReposicao.push(evento);
        };

        this.formulario.addEventListener(
            'reset',
            observarReposicao,
        );

        try {
            let resposta;
            let estadoCamposSubmetidos;
            let contextoSubmissao = null;

            try {
                const dadosSubmissao =
                    this.prepararDadosSubmissao();

                contextoSubmissao =
                    this.obterContextoSubmissao === null
                        ? null
                        : this.obterContextoSubmissao();

                estadoCamposSubmetidos =
                    this.obterEstadoCamposFormulario();

                resposta =
                    await axios.post(
                        this.url,
                        dadosSubmissao,
                    );
            } catch (erro) {
                if (!this.formulario.isConnected) {
                    return;
                }

                const erroTratado =
                    await this.executarAcaoErro(
                        erro,
                    );

                if (!this.formulario.isConnected) {
                    return;
                }

                if (!erroTratado) {
                    this.tratarErroSubmissao(
                        erro,
                    );
                }

                return;
            }

            // A resposta confirmou a operação; o próximo envio é novo.
            this.chaveSubmissao = null;
            this.conteudoSubmissao = null;

            this.emitirEventoSucesso(
                resposta.data,
            );

            if (!this.formulario.isConnected) {
                return;
            }

            const acaoSucessoConcluida =
                await this.executarAcaoSucesso(
                    resposta.data,
                    contextoSubmissao,
                );

            if (!this.formulario.isConnected) {
                return;
            }

            if (acaoSucessoConcluida) {
                this.mostrarMensagemSucesso();
            } else {
                this.mostrarAvisoAtualizacaoInterface();
            }

            if (
                eventosReposicao.every(
                    (evento) => evento.defaultPrevented,
                )
                && this.camposFormularioMantidos(
                    estadoCamposSubmetidos,
                )
            ) {
                this.finalizarSubmissao();
            } else {
                this.limparErros();
            }
        } finally {
            this.formulario.removeEventListener(
                'reset',
                observarReposicao,
            );

            this.reporEstadoBotaoSubmissao(
                estadoBotao,
            );

            this.emSubmissao =
                false;
        }
    }

    /**
     * Emite o evento global de submissão AJAX bem-sucedida.
     *
     * O evento representa o sucesso da operação no servidor e é emitido antes
     * do pós-processamento específico configurado pelo consumidor.
     *
     * @param {unknown} dadosResposta Dados devolvidos pelo servidor.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    emitirEventoSucesso(
        dadosResposta,
    ) {
        if (
            !(this.formulario
                instanceof HTMLFormElement)
        ) {
            return;
        }

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
     * Executa a ação configurada após o sucesso da operação no servidor.
     *
     * Uma falha nesta fase não transforma a operação persistida numa falha
     * HTTP. O chamador recebe essa distinção para evitar uma nova submissão
     * potencialmente duplicada.
     *
     * @param {unknown} dadosResposta Dados devolvidos pelo servidor.
     *
     * @returns {Promise<boolean>}
     *     Verdadeiro quando o pós-processamento foi concluído.
     *
     * @since 2.0.0
     */
    async executarAcaoSucesso(
        dadosResposta,
        contextoSubmissao = null,
    ) {
        if (this.aoSucesso === null) {
            return true;
        }

        try {
            if (this.obterContextoSubmissao === null) {
                await this.aoSucesso(
                    dadosResposta,
                );
            } else {
                await this.aoSucesso(
                    dadosResposta,
                    contextoSubmissao,
                );
            }

            return true;
        } catch {
            return false;
        }
    }

    /**
     * Executa a ação específica configurada para um erro de submissão.
     *
     * O consumidor deve devolver verdadeiro apenas quando assumir integralmente
     * o tratamento do erro. Se não existir ação configurada, se esta devolver
     * outro valor ou se ocorrer uma exceção, mantém-se o tratamento genérico.
     *
     * @param {unknown} erro Erro capturado durante a submissão.
     *
     * @returns {Promise<boolean>}
     *     Verdadeiro quando o erro foi tratado pelo consumidor.
     *
     * @since 2.0.0
     */
    async executarAcaoErro(
        erro,
    ) {
        if (this.aoErro === null) {
            return false;
        }

        try {
            return (
                await this.aoErro(
                    erro,
                )
            ) === true;
        } catch {
            return false;
        }
    }

    /**
     * Trata um erro ocorrido durante o pedido de submissão.
     *
     * @param {unknown} erro Erro capturado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarErroSubmissao(
        erro,
    ) {
        if (
            axios.isAxiosError(
                erro,
            )
            && erro.response?.status === 422
            && this.eObjetoErrosValidacao(
                erro.response.data?.errors,
            )
        ) {
            this.tratarErrosValidacao(
                erro.response.data.errors,
            );

            return;
        }

        const mensagemConfigurada =
            this.formulario
                ?.dataset
                .mensagemErro
                ?.trim()
            ?? '';

        void GestorAlertas.mostrarErro(
            mensagemConfigurada !== ''
                ? mensagemConfigurada
                : 'Ocorreu um erro inesperado. Tenta novamente.',
        );
    }

    /**
     * Verifica se um valor contém erros de validação utilizáveis.
     *
     * @param {unknown} erros Valor a verificar.
     *
     * @returns {boolean} Verdadeiro quando existem mensagens válidas.
     *
     * @since 2.0.0
     */
    eObjetoErrosValidacao(
        erros,
    ) {
        if (
            typeof erros !== 'object'
            || erros === null
            || Array.isArray(
                erros,
            )
        ) {
            return false;
        }

        const entradas =
            Object.entries(
                erros,
            );

        return entradas.length > 0
            && entradas.every(
                ([, mensagens]) =>
                    this.obterMensagemValidacao(
                        mensagens,
                    ) !== null,
            );
    }

    /**
     * Obtém a primeira mensagem válida de uma entrada de validação.
     *
     * @param {unknown} mensagens Mensagem ou lista de mensagens.
     *
     * @returns {string|null} Mensagem normalizada ou nulo.
     *
     * @since 2.0.0
     */
    obterMensagemValidacao(
        mensagens,
    ) {
        const candidatas =
            Array.isArray(
                mensagens,
            )
                ? mensagens
                : [
                    mensagens,
                ];

        for (const candidata of candidatas) {
            if (
                typeof candidata
                !== 'string'
            ) {
                continue;
            }

            const mensagem =
                candidata.trim();

            if (mensagem !== '') {
                return mensagem;
            }
        }

        return null;
    }

    /**
     * Apresenta os erros de validação nos campos correspondentes.
     *
     * @param {Record<string, string|string[]>} erros
     *     Erros devolvidos pelo servidor.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    tratarErrosValidacao(
        erros,
    ) {
        if (
            !(this.formulario
                instanceof HTMLFormElement)
        ) {
            return;
        }

        let primeiroCampoInvalido =
            null;

        let primeiraMensagemSemCampo =
            null;

        Object.entries(
            erros,
        ).forEach(
            ([chave, mensagens]) => {
                const mensagem =
                    this.obterMensagemValidacao(
                        mensagens,
                    );

                if (mensagem === null) {
                    return;
                }

                const campos =
                    this.obterCamposPorChave(
                        chave,
                    );

                if (campos.length === 0) {
                    primeiraMensagemSemCampo ??=
                        mensagem;

                    return;
                }

                campos.forEach(
                    (campo) => {
                        this.definirCampoInvalido(
                            campo,
                        );
                    },
                );

                const elementoFeedback =
                    this.obterElementoFeedback(
                        campos[0],
                    );

                if (
                    elementoFeedback
                    instanceof HTMLElement
                ) {
                    elementoFeedback.textContent =
                        mensagem;

                    elementoFeedback.classList.add(
                        'd-block',
                    );

                    elementoFeedback.removeAttribute(
                        'hidden',
                    );

                    elementoFeedback.style
                        .removeProperty(
                            'display',
                        );
                }

                primeiroCampoInvalido ??=
                    campos.find(
                        (campo) =>
                            this.eCampoFocavel(
                                campo,
                            ),
                    )
                    ?? null;
            },
        );

        if (primeiroCampoInvalido !== null) {
            this.focarCampo(
                primeiroCampoInvalido,
            );

            return;
        }

        if (
            primeiraMensagemSemCampo
            !== null
        ) {
            void GestorAlertas.mostrarErro(
                primeiraMensagemSemCampo,
                'Dados inválidos',
            );
        }
    }

    /**
     * Aplica o estado inválido a um campo e ao respetivo componente visual.
     *
     * Quando um `<select>` é gerido pelo Tom Select, a classe visual deve ser
     * aplicada também ao wrapper apresentado ao utilizador.
     *
     * @param {
     *     HTMLInputElement
     *     |HTMLSelectElement
     *     |HTMLTextAreaElement
     * } campo Campo inválido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    definirCampoInvalido(
        campo,
    ) {
        campo.classList.add(
            'is-invalid',
        );

        campo.setAttribute(
            'aria-invalid',
            'true',
        );

        if (
            !(campo
                instanceof HTMLSelectElement)
        ) {
            return;
        }

        const wrapper =
            campo.tomselect
                ?.wrapper;

        if (
            wrapper
            instanceof HTMLElement
        ) {
            wrapper.classList.add(
                'is-invalid',
            );
        }
    }

    /**
     * Determina se um campo pode receber foco de forma útil.
     *
     * @param {
     *     HTMLInputElement
     *     |HTMLSelectElement
     *     |HTMLTextAreaElement
     * } campo Campo recebido.
     *
     * @returns {boolean} Verdadeiro quando o campo pode receber foco.
     *
     * @since 2.0.0
     */
    eCampoFocavel(
        campo,
    ) {
        if (
            campo.disabled
            || (
                campo
                instanceof HTMLInputElement
                && campo.type === 'hidden'
            )
            || campo.closest(
                '[hidden]',
            ) !== null
        ) {
            return false;
        }

        if (
            campo
            instanceof HTMLSelectElement
            && campo.tomselect
            && typeof campo.tomselect.focus
                === 'function'
        ) {
            return true;
        }

        return true;
    }

    /**
     * Coloca o foco num campo inválido.
     *
     * Os campos Tom Select utilizam a API do componente para que o foco seja
     * colocado no controlo visual em vez do `<select>` original.
     *
     * @param {
     *     HTMLInputElement
     *     |HTMLSelectElement
     *     |HTMLTextAreaElement
     * } campo Campo a focar.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    focarCampo(
        campo,
    ) {
        if (
            campo
            instanceof HTMLSelectElement
            && campo.tomselect
            && typeof campo.tomselect.focus
                === 'function'
        ) {
            campo.tomselect.focus();

            return;
        }

        campo.focus();
    }

    /**
     * Obtém os campos associados a uma chave de validação.
     *
     * Suporta nomes como `generos[]` e `musicas[0][titulo]`.
     *
     * @param {string} chave Chave devolvida pelo servidor.
     *
     * @returns {Array<
     *     HTMLInputElement
     *     |HTMLSelectElement
     *     |HTMLTextAreaElement
     * >}
     *
     * @since 2.0.0
     */
    obterCamposPorChave(
        chave,
    ) {
        if (
            !(this.formulario
                instanceof HTMLFormElement)
        ) {
            return [];
        }

        return Array.from(
            this.formulario.querySelectorAll(
                'input[name], select[name], textarea[name]',
            ),
        ).filter(
            (campo) => {
                const nomeNormalizado =
                    this.normalizarNomeCampo(
                        campo.name,
                    );

                return nomeNormalizado === chave
                    || chave.startsWith(
                        `${nomeNormalizado}.`,
                    )
                    || nomeNormalizado.startsWith(
                        `${chave}.`,
                    );
            },
        );
    }

    /**
     * Converte um nome HTML para a notação utilizada pelo Laravel.
     *
     * @param {string} nome Nome HTML do campo.
     *
     * @returns {string} Nome normalizado.
     *
     * @since 2.0.0
     */
    normalizarNomeCampo(
        nome,
    ) {
        return nome
            .replace(
                /\[\]/g,
                '',
            )
            .replace(
                /\[([^\]]+)]/g,
                '.$1',
            );
    }

    /**
     * Obtém o elemento destinado à mensagem de validação.
     *
     * Dá prioridade aos contratos explícitos do campo antes de recorrer ao
     * primeiro feedback existente no respetivo grupo.
     *
     * @param {
     *     HTMLInputElement
     *     |HTMLSelectElement
     *     |HTMLTextAreaElement
     * } campo Campo inválido.
     *
     * @returns {HTMLElement|null} Elemento de feedback encontrado.
     *
     * @since 2.0.0
     */
    obterElementoFeedback(
        campo,
    ) {
        if (
            !(this.formulario
                instanceof HTMLFormElement)
        ) {
            return null;
        }

        const identificadoresDescritos =
            (
                campo.getAttribute(
                    'aria-describedby',
                )
                ?? ''
            )
                .split(
                    /\s+/u,
                )
                .filter(
                    (identificador) =>
                        identificador !== '',
                );

        for (
            const identificador
            of identificadoresDescritos
        ) {
            const elemento =
                document.getElementById(
                    identificador,
                );

            if (
                elemento
                    instanceof HTMLElement
                && this.formulario.contains(
                    elemento,
                )
                && elemento.classList.contains(
                    'invalid-feedback',
                )
            ) {
                return elemento;
            }
        }

        const identificadorCampo =
            campo.id.trim();

        if (identificadorCampo !== '') {
            const elemento =
                document.getElementById(
                    `erro-${identificadorCampo}`,
                );

            if (
                elemento
                    instanceof HTMLElement
                && this.formulario.contains(
                    elemento,
                )
                && elemento.classList.contains(
                    'invalid-feedback',
                )
            ) {
                return elemento;
            }
        }

        const grupo =
            campo.closest(
                [
                    '[data-grupo-campo]',
                    '.grupo-campo-formulario',
                ].join(
                    ', ',
                ),
            );

        const feedbackGrupo =
            grupo?.querySelector(
                '.invalid-feedback',
            );

        if (
            feedbackGrupo
            instanceof HTMLElement
        ) {
            return feedbackGrupo;
        }

        const feedbackAdjacente =
            campo.parentElement
                ?.querySelector(
                    '.invalid-feedback',
                );

        return feedbackAdjacente
            instanceof HTMLElement
                ? feedbackAdjacente
                : null;
    }

    /**
     * Limpa os erros de validação do formulário.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    limparErros() {
        if (
            !(this.formulario
                instanceof HTMLFormElement)
        ) {
            return;
        }

        this.formulario
            .querySelectorAll(
                '.is-invalid',
            )
            .forEach(
                (elemento) => {
                    elemento.classList.remove(
                        'is-invalid',
                    );

                    elemento.removeAttribute(
                        'aria-invalid',
                    );
                },
            );

        this.formulario
            .querySelectorAll(
                '.invalid-feedback',
            )
            .forEach(
                (elemento) => {
                    elemento.textContent =
                        '';

                    elemento.classList.remove(
                        'd-block',
                    );

                    elemento.style.removeProperty(
                        'display',
                    );

                    elemento.setAttribute(
                        'hidden',
                        '',
                    );
                },
            );
    }

    /**
     * Apresenta a mensagem de sucesso do formulário.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    mostrarMensagemSucesso() {
        const mensagemConfigurada =
            this.formulario
                ?.dataset
                .mensagemSucesso
                ?.trim()
            ?? '';

        void GestorAlertas.mostrarSucesso(
            mensagemConfigurada !== ''
                ? mensagemConfigurada
                : 'Ação concluída com sucesso.',
        );
    }

    /**
     * Informa que a operação foi concluída, mas a interface não conseguiu
     * executar o respetivo pós-processamento.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    mostrarAvisoAtualizacaoInterface() {
        void GestorAlertas.mostrarAviso(
            'A operação foi concluída, mas não foi possível atualizar a interface. Recarrega a página para veres os dados mais recentes.',
            'Operação concluída',
        );
    }

    /**
     * Repõe o formulário e fecha a janela modal, quando aplicável.
     *
     * Num modal, só repõe os campos se o Bootstrap aceitar o fecho.
     * Um fecho cancelado ou ignorado mantém os dados apresentados.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    finalizarSubmissao() {
        if (
            !(this.formulario
                instanceof HTMLFormElement)
        ) {
            return;
        }

        const elementoModal =
            this.formulario.closest(
                '.modal',
            );

        if (!(elementoModal instanceof HTMLElement)) {
            this.formulario.reset();
            this.limparErros();

            return;
        }

        let eventoFecho = null;
        let eventoReposicao = null;

        const observarFecho = (evento) => {
            eventoFecho = evento;
        };

        const observarReposicao = (evento) => {
            eventoReposicao = evento;
        };

        const finalizarOcultacao = (evento) => {
            if (evento.target !== elementoModal) {
                return;
            }

            elementoModal.removeEventListener(
                'hidden.bs.modal',
                finalizarOcultacao,
            );

            queueMicrotask(() => {
                try {
                    if (!this.formulario.isConnected) {
                        return;
                    }

                    if (eventoReposicao === null) {
                        this.formulario.reset();
                    }

                    if (
                        eventoReposicao !== null
                        && !eventoReposicao.defaultPrevented
                    ) {
                        this.limparErros();
                    }
                } finally {
                    this.formulario.removeEventListener(
                        'reset',
                        observarReposicao,
                    );
                }
            });
        };

        this.formulario.addEventListener(
            'reset',
            observarReposicao,
        );

        elementoModal.addEventListener(
            'hidden.bs.modal',
            finalizarOcultacao,
        );

        elementoModal.addEventListener(
            'hide.bs.modal',
            observarFecho,
        );

        let fechoAceite = false;

        try {
            Modal
                .getOrCreateInstance(
                    elementoModal,
                )
                .hide();

            fechoAceite =
                eventoFecho !== null
                && !eventoFecho.defaultPrevented;
        } finally {
            elementoModal.removeEventListener(
                'hide.bs.modal',
                observarFecho,
            );

            if (!fechoAceite) {
                elementoModal.removeEventListener(
                    'hidden.bs.modal',
                    finalizarOcultacao,
                );

                this.formulario.removeEventListener(
                    'reset',
                    observarReposicao,
                );
            }
        }
    }

    /**
     * Obtém o estado atual do botão de submissão.
     *
     * Os nós originais são preservados para serem recolocados sem recriar o
     * conteúdo HTML nem perder eventuais listeners associados aos descendentes.
     *
     * @returns {{
     *     desativado: boolean,
     *     ariaBusy: string|null,
     *     conteudo: Array<Node>
     * }|null} Estado atual ou nulo quando não existe botão.
     *
     * @since 2.0.0
     */
    obterEstadoBotaoSubmissao() {
        if (
            !(this.botaoSubmissao
                instanceof HTMLButtonElement)
        ) {
            return null;
        }

        return {
            desativado:
                this.botaoSubmissao.disabled,

            ariaBusy:
                this.botaoSubmissao.getAttribute(
                    'aria-busy',
                ),

            conteudo:
                Array.from(
                    this.botaoSubmissao.childNodes,
                ),
        };
    }

    /**
     * Apresenta o estado de carregamento no botão de submissão.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    apresentarEstadoCarregamento() {
        if (
            !(this.botaoSubmissao
                instanceof HTMLButtonElement)
        ) {
            return;
        }

        this.botaoSubmissao.disabled =
            true;

        this.botaoSubmissao.setAttribute(
            'aria-busy',
            'true',
        );

        const indicador =
            document.createElement(
                'span',
            );

        indicador.className =
            'spinner-border spinner-border-sm me-2';

        indicador.setAttribute(
            'aria-hidden',
            'true',
        );

        this.botaoSubmissao.replaceChildren(
            indicador,
            document.createTextNode(
                'A processar...',
            ),
        );
    }

    /**
     * Repõe o estado do botão existente antes da submissão.
     *
     * @param {{
     *     desativado: boolean,
     *     ariaBusy: string|null,
     *     conteudo: Array<Node>
     * }|null} estado Estado anteriormente guardado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    reporEstadoBotaoSubmissao(
        estado,
    ) {
        if (
            !(this.botaoSubmissao
                instanceof HTMLButtonElement)
            || estado === null
        ) {
            return;
        }

        this.botaoSubmissao.disabled =
            estado.desativado;

        if (estado.ariaBusy === null) {
            this.botaoSubmissao.removeAttribute(
                'aria-busy',
            );
        } else {
            this.botaoSubmissao.setAttribute(
                'aria-busy',
                estado.ariaBusy,
            );
        }

        this.botaoSubmissao.replaceChildren(
            ...estado.conteudo,
        );
    }
}

export default TratadorFormularioAjax;
