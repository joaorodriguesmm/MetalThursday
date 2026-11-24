/**
 * Protege os formulários de MetalThursday contra a perda de alterações.
 *
 * O estado é calculado a partir dos campos efetivamente submetidos, incluindo
 * secções e ligações dinâmicas. Não guarda dados sem intervenção do utilizador.
 *
 * @since 2.0.0
 */
class GestorAlteracoesMetalThursday {
    /** @type {WeakMap<HTMLFormElement, GestorAlteracoesMetalThursday>} */
    static instancias = new WeakMap();

    /**
     * @param {HTMLFormElement} formulario Formulário de criação ou edição.
     * @returns {GestorAlteracoesMetalThursday} Gestor idempotente.
     */
    constructor(formulario) {
        if (!(formulario instanceof HTMLFormElement)) {
            throw new TypeError('O formulário de MetalThursday é inválido.');
        }

        const existente = GestorAlteracoesMetalThursday.instancias.get(formulario);
        if (existente) {
            return existente;
        }

        const estado = formulario.querySelector('[data-estado-alteracoes-metal-thursday]');
        if (!(estado instanceof HTMLElement)) {
            throw new TypeError('Não foi encontrado o estado das alterações da MetalThursday.');
        }

        this.formulario = formulario;
        this.estado = estado;
        this.assinaturaInicial = this.obterAssinatura();
        this.emSubmissao = false;
        this.atualizacaoPendente = false;

        const atualizar = () => this.atualizarEstado();
        formulario.addEventListener('input', atualizar);
        formulario.addEventListener('change', atualizar);

        formulario.addEventListener('submit', (evento) => {
            // O validador principal já processou este evento.
            if (evento.defaultPrevented) {
                return;
            }

            this.emSubmissao = true;
            this.apresentarEstado('A guardar…', false);

            // Um listener posterior ainda pode impedir a submissão.
            queueMicrotask(() => {
                if (evento.defaultPrevented) {
                    this.emSubmissao = false;
                    this.atualizarEstado();
                }
            });
        });

        formulario.addEventListener('reset', () => {
            queueMicrotask(() => this.atualizarEstado());
        });

        const contentor = formulario.querySelector('#contentor-seccoes');
        if (contentor instanceof HTMLElement) {
            this.observador = new MutationObserver(() => this.agendarAtualizacao());
            this.observador.observe(contentor, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['name', 'disabled'],
            });
        }

        window.addEventListener('beforeunload', (evento) => {
            if (!formulario.isConnected || this.emSubmissao || !this.possuiAlteracoes()) {
                return;
            }

            evento.preventDefault();
            evento.returnValue = '';
        });

        window.addEventListener('pageshow', (evento) => {
            if (evento.persisted && formulario.isConnected) {
                this.emSubmissao = false;
                this.atualizarEstado();
            }
        });

        GestorAlteracoesMetalThursday.instancias.set(formulario, this);
        this.atualizarEstado();
    }

    /**
     * Obtém uma representação estável e ordenada dos campos submetidos.
     * Ignora botões e campos desativados; preserva a ordem das secções.
     *
     * @returns {string} Assinatura do conteúdo do formulário.
     */
    obterAssinatura() {
        const entradas = [];

        for (const campo of this.formulario.elements) {
            if (
                !(
                    campo instanceof HTMLInputElement
                    || campo instanceof HTMLSelectElement
                    || campo instanceof HTMLTextAreaElement
                )
                || !campo.name
                || campo.matches(':disabled')
                || ['_token', '_method'].includes(campo.name)
            ) {
                continue;
            }

            if (campo instanceof HTMLInputElement) {
                if (['button', 'submit', 'reset', 'image'].includes(campo.type)) {
                    continue;
                }

                if (['checkbox', 'radio'].includes(campo.type) && !campo.checked) {
                    continue;
                }

                if (campo.type === 'file') {
                    entradas.push([
                        campo.name,
                        Array.from(campo.files ?? [], (ficheiro) => [
                            ficheiro.name,
                            ficheiro.size,
                            ficheiro.lastModified,
                        ]),
                    ]);
                    continue;
                }
            }

            if (campo instanceof HTMLSelectElement && campo.multiple) {
                entradas.push([
                    campo.name,
                    Array.from(campo.selectedOptions, (opcao) => opcao.value),
                ]);
                continue;
            }

            entradas.push([campo.name, campo.value]);
        }

        return JSON.stringify(entradas);
    }

    /** @returns {boolean} Indica se o conteúdo diverge do estado inicial. */
    possuiAlteracoes() {
        return this.obterAssinatura() !== this.assinaturaInicial;
    }

    /**
     * Atualiza o texto sem apresentar falsos estados de guardamento.
     *
     * @param {string} mensagem Texto acessível.
     * @param {boolean} alterado Se existem alterações por guardar.
     * @returns {void}
     */
    apresentarEstado(mensagem, alterado) {
        if (this.estado.textContent !== mensagem) {
            this.estado.textContent = mensagem;
        }
        this.estado.classList.toggle('text-warning', alterado);
        this.estado.classList.toggle('text-muted', !alterado);
    }

    /** @returns {void} Atualiza o indicador visual. */
    atualizarEstado() {
        if (this.emSubmissao) {
            return;
        }

        const alterado = this.possuiAlteracoes();
        this.apresentarEstado(
            alterado ? 'Alterações por guardar' : 'Sem alterações por guardar',
            alterado,
        );
    }

    /**
     * Agrupa várias alterações estruturais numa única atualização.
     *
     * @returns {void}
     */
    agendarAtualizacao() {
        if (this.atualizacaoPendente) {
            return;
        }

        this.atualizacaoPendente = true;
        queueMicrotask(() => {
            this.atualizacaoPendente = false;
            if (this.formulario.isConnected) {
                this.atualizarEstado();
            }
        });
    }
}

export default GestorAlteracoesMetalThursday;
