/**
 * Gere a apresentação e a ocultação de campos de palavra-passe.
 *
 * Cada botão deve indicar o identificador do respetivo campo através do
 * atributo `data-alvo-palavra-passe`.
 *
 * @since 1.0.0
 * @version 2.1.0
 */
class AlternadorPalavraPasse {
    /**
     * Cria o alternador de palavras-passe.
     *
     * @param {string|Iterable<HTMLElement>} alternadoresOuSeletor Seletor
     *     CSS ou coleção de botões alternadores.
     *
     * @throws {TypeError} Quando o seletor ou os elementos não são válidos.
     *
     * @since 1.0.0
     * @version 2.1.0
     */
    constructor(
        alternadoresOuSeletor = '[data-alvo-palavra-passe]',
    ) {
        /**
         * Botões responsáveis pela alternância.
         *
         * @type {Array<HTMLButtonElement>}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.alternadores = this.obterAlternadores(
            alternadoresOuSeletor,
        );

        /**
         * Ligações entre botões, campos e manipuladores.
         *
         * @type {Map<
         *     HTMLButtonElement,
         *     {
         *         campo: HTMLInputElement,
         *         manipulador: (evento: MouseEvent) => void
         *     }
         * >}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.ligacoes = new Map();

        /**
         * Indica se os eventos já foram configurados.
         *
         * @type {boolean}
         *
         * @since 2.1.0
         * @version 1.0.0
         */
        this.iniciado = false;

        this.iniciar();
    }

    /**
     * Configura os alternadores encontrados.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.1.0
     */
    iniciar() {
        if (
            this.iniciado
            || this.alternadores.length === 0
        ) {
            return;
        }

        this.alternadores.forEach((alternador) => {
            const campo = this.obterCampoAlvo(
                alternador,
            );

            const manipulador = (evento) => {
                evento.preventDefault();

                this.alternarVisibilidade(
                    campo,
                    alternador,
                );
            };

            alternador.type = 'button';

            alternador.addEventListener(
                'click',
                manipulador,
            );

            this.ligacoes.set(
                alternador,
                {
                    campo,
                    manipulador,
                },
            );

            this.atualizarEstado(
                campo,
                alternador,
            );
        });

        this.iniciado = true;
    }

    /**
     * Alterna a apresentação de um campo.
     *
     * @param {HTMLInputElement} campo Campo de palavra-passe.
     * @param {HTMLButtonElement} alternador Botão acionado.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    alternarVisibilidade(
        campo,
        alternador,
    ) {
        const inicioSelecao =
            campo.selectionStart;

        const fimSelecao =
            campo.selectionEnd;

        campo.type = campo.type === 'password'
            ? 'text'
            : 'password';

        this.atualizarEstado(
            campo,
            alternador,
        );

        campo.focus();

        if (
            inicioSelecao !== null
            && fimSelecao !== null
        ) {
            try {
                campo.setSelectionRange(
                    inicioSelecao,
                    fimSelecao,
                );
            } catch {
                /*
                 * Alguns navegadores não permitem restaurar a seleção depois
                 * da mudança do tipo do campo. A alternância continua válida.
                 */
            }
        }
    }

    /**
     * Atualiza o estado visual e acessível do botão.
     *
     * @param {HTMLInputElement} campo Campo associado.
     * @param {HTMLButtonElement} alternador Botão associado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    atualizarEstado(
        campo,
        alternador,
    ) {
        const palavraPasseVisivel =
            campo.type === 'text';

        const descricaoCampo =
            this.obterDescricaoCampo(
                campo,
                alternador,
            );

        alternador.setAttribute(
            'aria-controls',
            campo.id,
        );

        alternador.setAttribute(
            'aria-pressed',
            palavraPasseVisivel
                ? 'true'
                : 'false',
        );

        const descricaoAcao =
            palavraPasseVisivel
                ? `Ocultar ${descricaoCampo}`
                : `Mostrar ${descricaoCampo}`;

        alternador.setAttribute(
            'aria-label',
            descricaoAcao,
        );

        alternador.title =
            descricaoAcao;

        this.atualizarIcone(
            alternador,
            palavraPasseVisivel,
        );
    }

    /**
     * Atualiza o ícone do botão.
     *
     * O ícone representa a ação disponível: um olho quando o campo pode ser
     * mostrado e um olho cortado quando pode ser ocultado.
     *
     * @param {HTMLButtonElement} alternador Botão atualizado.
     * @param {boolean} palavraPasseVisivel Estado atual do campo.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    atualizarIcone(
        alternador,
        palavraPasseVisivel,
    ) {
        const icone = alternador.querySelector(
            '[data-icone-palavra-passe], i',
        );

        if (!(icone instanceof HTMLElement)) {
            return;
        }

        icone.classList.remove(
            'bi-eye',
            'bi-eye-fill',
            'bi-eye-slash',
            'bi-eye-slash-fill',
        );

        icone.classList.add(
            palavraPasseVisivel
                ? 'bi-eye-slash-fill'
                : 'bi-eye-fill',
        );
    }

    /**
     * Remove os eventos configurados pelo módulo.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    destruir() {
        if (!this.iniciado) {
            return;
        }

        this.ligacoes.forEach(
            (
                ligacao,
                alternador,
            ) => {
                alternador.removeEventListener(
                    'click',
                    ligacao.manipulador,
                );
            },
        );

        this.ligacoes.clear();
        this.iniciado = false;
    }

    /**
     * Obtém os botões alternadores.
     *
     * A ausência de botões é válida em páginas que não possuam campos de
     * palavra-passe.
     *
     * @param {string|Iterable<HTMLElement>} alternadoresOuSeletor Seletor
     *     ou coleção recebida.
     *
     * @returns {Array<HTMLButtonElement>} Botões encontrados.
     *
     * @throws {TypeError} Quando os elementos não são botões válidos.
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    obterAlternadores(
        alternadoresOuSeletor,
    ) {
        let elementos;

        if (
            typeof alternadoresOuSeletor
            === 'string'
        ) {
            const seletor =
                alternadoresOuSeletor.trim();

            if (seletor === '') {
                throw new TypeError(
                    'O seletor dos alternadores não pode estar vazio.',
                );
            }

            try {
                elementos = Array.from(
                    document.querySelectorAll(
                        seletor,
                    ),
                );
            } catch {
                throw new TypeError(
                    `O seletor "${seletor}" não é válido.`,
                );
            }
        } else if (
            alternadoresOuSeletor !== null
            && alternadoresOuSeletor !== undefined
            && typeof alternadoresOuSeletor[
                Symbol.iterator
            ] === 'function'
        ) {
            elementos = Array.from(
                alternadoresOuSeletor,
            );
        } else {
            throw new TypeError(
                'Os alternadores devem ser indicados através de um seletor ou coleção.',
            );
        }

        const elementosUnicos = [
            ...new Set(
                elementos,
            ),
        ];

        const alternadores =
            elementosUnicos.filter(
                (elemento) =>
                    elemento instanceof HTMLButtonElement,
            );

        if (
            alternadores.length
            !== elementosUnicos.length
        ) {
            throw new TypeError(
                'Todos os alternadores de palavra-passe devem ser botões HTML.',
            );
        }

        return alternadores;
    }

    /**
     * Obtém o campo associado a um botão.
     *
     * @param {HTMLButtonElement} alternador Botão configurado.
     *
     * @returns {HTMLInputElement} Campo de palavra-passe.
     *
     * @throws {TypeError} Quando o atributo ou o campo não são válidos.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterCampoAlvo(
        alternador,
    ) {
        const identificador = alternador
            .dataset
            .alvoPalavraPasse
            ?.trim();

        if (!identificador) {
            throw new TypeError(
                'O botão deve indicar um campo através de data-alvo-palavra-passe.',
            );
        }

        const campo = document.getElementById(
            identificador,
        );

        if (
            !(campo instanceof HTMLInputElement)
            || ![
                'password',
                'text',
            ].includes(
                campo.type,
            )
        ) {
            throw new TypeError(
                `O alvo "${identificador}" não é um campo de palavra-passe válido.`,
            );
        }

        return campo;
    }

    /**
     * Obtém uma descrição legível do campo.
     *
     * O botão pode definir uma descrição explícita através de
     * `data-descricao-palavra-passe`. Caso contrário, é utilizada a etiqueta
     * associada ao campo.
     *
     * @param {HTMLInputElement} campo Campo associado.
     * @param {HTMLButtonElement} alternador Botão associado.
     *
     * @returns {string} Descrição do campo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterDescricaoCampo(
        campo,
        alternador,
    ) {
        const descricaoExplicita = alternador
            .dataset
            .descricaoPalavraPasse
            ?.trim();

        if (descricaoExplicita) {
            return descricaoExplicita;
        }

        const etiqueta =
            campo.labels?.item(0);

        if (
            etiqueta
            instanceof HTMLLabelElement
        ) {
            const copiaEtiqueta =
                etiqueta.cloneNode(
                    true,
                );

            copiaEtiqueta
                .querySelectorAll(
                    '.text-danger, [aria-hidden="true"]',
                )
                .forEach(
                    (elemento) =>
                        elemento.remove(),
                );

            const texto = copiaEtiqueta
                .textContent
                ?.trim();

            if (texto) {
                return texto.toLocaleLowerCase(
                    'pt-PT',
                );
            }
        }

        return 'palavra-passe';
    }
}

export default AlternadorPalavraPasse;
