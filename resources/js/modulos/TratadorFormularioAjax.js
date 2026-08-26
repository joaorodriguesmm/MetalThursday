import axios from 'axios';
import { Modal } from 'bootstrap';
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
     *
     * @throws {TypeError} Quando algum argumento é inválido.
     *
     * @since 1.0.0
     */
    constructor(
        idFormulario,
        url,
        aoSucesso = null,
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

        this.emSubmissao =
            false;

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

        try {
            let resposta;

            try {
                resposta =
                    await axios.post(
                        this.url,
                        new FormData(
                            this.formulario,
                        ),
                    );
            } catch (erro) {
                this.tratarErroSubmissao(
                    erro,
                );

                return;
            }

            this.emitirEventoSucesso(
                resposta.data,
            );

            const acaoSucessoConcluida =
                await this.executarAcaoSucesso(
                    resposta.data,
                );

            if (acaoSucessoConcluida) {
                this.mostrarMensagemSucesso();
            } else {
                this.mostrarAvisoAtualizacaoInterface();
            }

            this.finalizarSubmissao();
        } finally {
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
    ) {
        if (this.aoSucesso === null) {
            return true;
        }

        try {
            await this.aoSucesso(
                dadosResposta,
            );

            return true;
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

        this.formulario.reset();
        this.limparErros();

        const elementoModal =
            this.formulario.closest(
                '.modal',
            );

        if (
            elementoModal
            instanceof HTMLElement
        ) {
            Modal
                .getOrCreateInstance(
                    elementoModal,
                )
                .hide();
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
