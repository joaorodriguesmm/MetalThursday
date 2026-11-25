import axios from './ClienteHttp';
import GestorAlertas from './GestorAlertas';
import ValidadorFormulario from './ValidadorFormulario';

/**
 * Gere os formulários de denúncia de comentários, incluindo os carregados
 * posteriormente por AJAX. A autorização é sempre revalidada no servidor.
 */
export default class DenunciadorComentarios {
    /** @param {Document|HTMLElement} contentor Elemento de delegação dos eventos. */
    constructor(contentor = document) {
        this.submissoesAtivas = new WeakSet();
        this.validadores = new WeakMap();

        contentor.addEventListener('click', (evento) => this.tratarClique(evento));
        contentor.addEventListener('change', (evento) => this.tratarMotivo(evento));
    }

    /** @param {Event} evento Clique num controlo de denúncia. */
    tratarClique(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botao = evento.target.closest('[data-abrir-denuncia-comentario]');

        if (!(botao instanceof HTMLButtonElement)) {
            return;
        }

        const identificador = botao.getAttribute('aria-controls');
        const contentor = identificador
            ? document.getElementById(identificador)
            : null;
        const comentario = botao.closest('.comentario');

        if (
            !(contentor instanceof HTMLElement)
            || !(comentario instanceof HTMLElement)
            || !comentario.contains(contentor)
        ) {
            return;
        }

        evento.preventDefault();
        contentor.hidden = !contentor.hidden;
        botao.setAttribute('aria-expanded', String(!contentor.hidden));

        if (!contentor.hidden) {
            const formulario = contentor.querySelector(
                '[data-formulario-denuncia-comentario]',
            );

            if (formulario instanceof HTMLFormElement) {
                this.inicializarValidacao(formulario);
            }

            contentor.querySelector('select[name="motivo"]')?.focus();
        }
    }

    /** @param {Event} evento Alteração ao motivo selecionado. */
    tratarMotivo(evento) {
        const seletor = evento.target;

        if (
            !(seletor instanceof HTMLSelectElement)
            || seletor.name !== 'motivo'
            || !seletor.closest('[data-formulario-denuncia-comentario]')
        ) {
            return;
        }

        const formulario = seletor.closest('form');
        const detalhes = formulario?.querySelector('textarea[name="detalhes"]');

        if (detalhes instanceof HTMLTextAreaElement) {
            detalhes.required = seletor.value === 'outro';
        }

        if (formulario instanceof HTMLFormElement) {
            this.validadores.get(formulario)?.atualizarConfiguracao({
                regras: this.obterRegrasValidacao(seletor.value),
                mensagens: this.obterMensagensValidacao(),
            });
        }
    }

    /**
     * Aplica o validador comum apenas quando o formulário é aberto.
     * Permite validar comentários acrescentados posteriormente por AJAX.
     *
     * @param {HTMLFormElement} formulario Formulário de denúncia.
     * @returns {void}
     */
    inicializarValidacao(formulario) {
        if (this.validadores.has(formulario)) {
            return;
        }

        const motivo = formulario.querySelector('select[name="motivo"]');

        const validador = new ValidadorFormulario(formulario, {
            regras: this.obterRegrasValidacao(motivo?.value ?? ''),
            mensagens: this.obterMensagensValidacao(),
            aoSucesso: () => {
                if (!this.submissoesAtivas.has(formulario)) {
                    void this.enviar(formulario);
                }
            },
        });

        this.validadores.set(formulario, validador);
    }

    /**
     * Define os limites do servidor e torna os detalhes obrigatórios para
     * o motivo «Outro».
     *
     * @param {string} motivo Motivo selecionado.
     * @returns {Record<string, Array<string|Function>>} Regras da validação.
     */
    obterRegrasValidacao(motivo) {
        return {
            motivo: [
                'obrigatorio',
                ({ valor }) => [
                    'spam', 'ofensivo', 'assedio', 'outro',
                ].includes(valor) ? null : 'Seleciona um motivo válido.',
            ],
            detalhes: motivo === 'outro'
                ? ['obrigatorio', 'maximo:1000']
                : ['maximo:1000'],
        };
    }

    /** @returns {Record<string, Record<string, string>>} Mensagens dos campos. */
    obterMensagensValidacao() {
        return {
            motivo: {
                obrigatorio: 'Seleciona o motivo da denúncia.',
            },
            detalhes: {
                obrigatorio: 'Indica os detalhes da denúncia.',
                maximo: 'Os detalhes não podem ultrapassar 1000 caracteres.',
            },
        };
    }

    /**
     * @param {HTMLFormElement} formulario Formulário validado no navegador.
     * @returns {Promise<void>}
     */
    async enviar(formulario) {
        const motivo = formulario.querySelector('select[name="motivo"]');
        const detalhes = formulario.querySelector('textarea[name="detalhes"]');
        const token = formulario.querySelector('input[name="_token"]');
        const botao = formulario.querySelector('button[type="submit"]');

        if (
            !(motivo instanceof HTMLSelectElement)
            || !(detalhes instanceof HTMLTextAreaElement)
            || !(token instanceof HTMLInputElement)
            || !(botao instanceof HTMLButtonElement)
        ) {
            return;
        }

        this.submissoesAtivas.add(formulario);
        botao.disabled = true;
        let sucesso = false;

        try {
            const resposta = await axios.post(formulario.action, {
                _token: token.value,
                motivo: motivo.value,
                detalhes: detalhes.value.trim(),
            });

            if (!formulario.isConnected) {
                return;
            }

            sucesso = true;
            const denunciaNova = resposta.data?.nova === true;
            const contentor = formulario.closest('[data-contentor-denuncia-comentario]');
            const comentario = formulario.closest('.comentario');
            const acionador = comentario?.querySelector('[data-abrir-denuncia-comentario]');

            if (contentor instanceof HTMLElement) {
                contentor.hidden = true;
            }

            if (acionador instanceof HTMLButtonElement) {
                const indicador = document.createElement('span');
                indicador.className = 'text-muted';
                indicador.dataset.denunciaJaEnviada = '';
                indicador.textContent = denunciaNova
                    ? 'Denúncia enviada'
                    : 'Já denunciaste este comentário';
                acionador.replaceWith(indicador);
            }

            const confirmacao = comentario?.querySelector(
                '[data-confirmacao-denuncia-comentario]',
            );

            if (denunciaNova && confirmacao instanceof HTMLElement) {
                confirmacao.classList.remove('d-none');
            }

            if (denunciaNova) {
                void GestorAlertas.mostrarSucesso(
                    resposta.data?.mensagem ?? 'Denúncia registada com sucesso.',
                );
            }
        } catch (erro) {
            if (formulario.isConnected) {
                const errosServidor = erro?.response?.status === 422
                    ? erro.response.data?.errors
                    : null;
                const validador = this.validadores.get(formulario);
                let quantidadeErros = 0;

                if (validador && errosServidor && typeof errosServidor === 'object') {
                    for (const campo of ['motivo', 'detalhes']) {
                        const mensagem = errosServidor[campo]?.[0];

                        if (typeof mensagem === 'string' && mensagem.trim() !== '') {
                            validador.definirErro(campo, mensagem);
                            quantidadeErros += 1;
                        }
                    }
                }

                if (quantidadeErros === 0) {
                    void GestorAlertas.mostrarErro(
                        'Não foi possível enviar a denúncia. Tenta novamente.',
                    );
                }
            }
        } finally {
            this.submissoesAtivas.delete(formulario);

            if (!sucesso && formulario.isConnected) {
                botao.disabled = false;
            }
        }
    }
}
