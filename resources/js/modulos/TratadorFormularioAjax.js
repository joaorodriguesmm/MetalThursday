import axios from 'axios';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

/**
 * Gere a submissão assíncrona de formulários.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class TratadorFormularioAjax {
    /**
     * Cria um tratador de formulário assíncrono.
     *
     * @param {string} idFormulario Identificador do formulário.
class TratadorFormularioAjax {
    /**
     * Cria um trat     * @param {string} url Endereço utilizado na submissão.
     * @param {((dados: unknown) => void|Promise<void>)|null} aoSucesso
     *     Função executada após uma submissão bem-sucedida.
     *
     * @throws {TypeError} Quando algum argumento é inválido.
     *
     * @since 1.0.0
     * @version 2.0.0
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

        this.formulario = document.getElementById(
            idFormulario,
        );

        this.url = url.trim();
        this.aoSucesso = aoSucesso;
        this.emSubmissao = false;

        this.botaoSubmissao =
            this.formulario?.querySelector(
                'button[type="submit"]',
            )
            ?? null;

        this.conteudoOriginalBotao =
            this.botaoSubmissao?.innerHTML
            ?? '';

        this.botaoOriginalmenteDesativado =
            this.botaoSubmissao?.disabled
            ?? false;
    }

    /**
     * Submete o formulário de forma assíncrona.
     *
     * @param {Event|null} evento Evento de submissão.
     *
     * @returns {Promise<void>}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    async submeter(
        evento = null,
    ) {
        evento?.preventDefault();

        if (
            !(this.formulario instanceof HTMLFormElement)
            || this.emSubmissao
        ) {
            return;
        }

        this.emSubmissao = true;

        this.limparErros();
        this.definirEstadoCarregamento(
            true,
        );

        try {
            const resposta =
                await axios.post(
                    this.url,
                    new FormData(
                        this.formulario,
                    ),
                    {
                        headers: {
                            Accept:
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                    },
                );

            document.dispatchEvent(
                new CustomEvent(
                    'formulario-ajax:sucesso',
                    {
                        detail: {
                            idFormulario:
                                this.formulario.id,

                            dadosResposta:
                                resposta.data,
                        },
                    },
                ),
            );

            if (this.aoSucesso !== null) {
                await this.aoSucesso(
                    resposta.data,
                );
            }

            this.mostrarMensagemSucesso();
            this.finalizarSubmissao();
        } catch (erro) {
            this.tratarErroSubmissao(
                erro,
            );
        } finally {
            this.definirEstadoCarregamento(
                false,
            );

            this.emSubmissao = false;
        }
    }

    /**
     * Trata um erro ocorrido durante a submissão.
     *
     * @param {unknown} erro Erro capturado.
     *
     * @since 2.0.0
     * @version 1.0.0
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

        const mensagem =
            this.formulario?.dataset.mensagemErro
            ?? 'Ocorreu um erro inesperado. Tenta novamente.';

        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: mensagem,
        });
    }

    /**
     * Verifica se um valor contém erros de validação.
     *
     * @param {unknown} erros Valor a verificar.
     *
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    eObjetoErrosValidacao(
        erros,
    ) {
        return typeof erros === 'object'
            && erros !== null
            && !Array.isArray(
                erros,
            );
    }

    /**
     * Apresenta os erros de validação nos campos correspondentes.
     *
     * @param {Record<string, string|string[]>} erros
     *     Erros devolvidos pelo servidor.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    tratarErrosValidacao(
        erros,
    ) {
        if (
            !(this.formulario instanceof HTMLFormElement)
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
                const campos =
                    this.obterCamposPorChave(
                        chave,
                    );

                const mensagem =
                    Array.isArray(
                        mensagens,
                    )
                        ? mensagens[0]
                        : mensagens;

                if (campos.length === 0) {
                    if (
                        primeiraMensagemSemCampo === null
                        && typeof mensagem === 'string'
                    ) {
                        primeiraMensagemSemCampo =
                            mensagem;
                    }

                    return;
                }

                campos.forEach(
                    (campo) => {
                        campo.classList.add(
                            'is-invalid',
                        );

                        campo.setAttribute(
                            'aria-invalid',
                            'true',
                        );
                    },
                );

                const elementoFeedback =
                    this.obterElementoFeedback(
                        campos[0],
                    );

                if (
                    elementoFeedback !== null
                    && typeof mensagem === 'string'
                ) {
                    elementoFeedback.textContent =
                        mensagem;

                    elementoFeedback.classList.add(
                        'd-block',
                    );
                }

                primeiroCampoInvalido ??=
                    campos.find(
                        (campo) =>
                            !campo.disabled
                            && !(
                                campo instanceof HTMLInputElement
                                && campo.type === 'hidden'
                            ),
                    )
                    ?? null;
            },
        );

        primeiroCampoInvalido?.focus();

        if (
            primeiroCampoInvalido === null
            && primeiraMensagemSemCampo !== null
        ) {
            Swal.fire({
                icon: 'error',
                title: 'Dados inválidos',
                text: primeiraMensagemSemCampo,
            });
        }
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
     * @version 1.0.0
     */
    obterCamposPorChave(
        chave,
    ) {
        if (
            !(this.formulario instanceof HTMLFormElement)
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
     * @returns {string}
     *
     * @since 2.0.0
     * @version 1.0.0
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
     * @param {
     *     HTMLInputElement
     *     |HTMLSelectElement
     *     |HTMLTextAreaElement
     * } campo Campo inválido.
     *
     * @returns {HTMLElement|null}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterElementoFeedback(
        campo,
    ) {
        const grupo =
            campo.closest(
                [
                    '[data-grupo-campo]',
                    '.form-field-group',
                    '.form-group',
                    '.mb-3',
                    '.flex-grow-1',
                ].join(
                    ', ',
                ),
            );

        return grupo?.querySelector(
            '.invalid-feedback',
        )
            ?? campo.parentElement?.querySelector(
                '.invalid-feedback',
            )
            ?? null;
    }

    /**
     * Limpa os erros de validação do formulário.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    limparErros() {
        if (
            !(this.formulario instanceof HTMLFormElement)
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
                },
            );
    }

    /**
     * Apresenta a mensagem de sucesso do formulário.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    mostrarMensagemSucesso() {
        const mensagem =
            this.formulario?.dataset.mensagemSucesso
            ?? 'Ação concluída com sucesso.';

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
     * Repõe o formulário e fecha a janela modal, quando aplicável.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    finalizarSubmissao() {
        if (
            !(this.formulario instanceof HTMLFormElement)
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
            elementoModal instanceof HTMLElement
        ) {
            Modal
                .getOrCreateInstance(
                    elementoModal,
                )
                .hide();
        }
    }

    /**
     * Define o estado de carregamento do botão de submissão.
     *
     * @param {boolean} emCarregamento Estado de carregamento.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    definirEstadoCarregamento(
        emCarregamento,
    ) {
        if (
            !(this.botaoSubmissao instanceof HTMLButtonElement)
        ) {
            return;
        }

        if (emCarregamento) {
            this.botaoSubmissao.disabled =
                true;

            this.botaoSubmissao.setAttribute(
                'aria-busy',
                'true',
            );

            this.botaoSubmissao.innerHTML = [
                '<span',
                'class="spinner-border spinner-border-sm"',
                'role="status"',
                'aria-hidden="true"',
                '></span>',
                '<span>A processar...</span>',
            ].join(
                ' ',
            );

            return;
        }

        this.botaoSubmissao.disabled =
            this.botaoOriginalmenteDesativado;

        this.botaoSubmissao.removeAttribute(
            'aria-busy',
        );

        this.botaoSubmissao.innerHTML =
            this.conteudoOriginalBotao;
    }
}

export default TratadorFormularioAjax;
