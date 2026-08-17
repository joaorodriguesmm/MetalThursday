/**
 * Valida um único ficheiro selecionado num campo HTML.
 *
 * Este módulo fornece apenas validação de apoio no navegador. A validação
 * definitiva do tipo, conteúdo e tamanho do ficheiro deve ser executada pelo
 * servidor.
 *
 * @since 1.0.0
 */
class ValidadorFicheiro {
    /**
     * Cria e inicializa o validador.
     *
     * @param {HTMLInputElement} campoFicheiro Campo de ficheiro.
     * @param {object} opcoes Configuração do validador.
     * @param {Array<string>} [opcoes.tiposPermitidos=[]]
     *     Tipos MIME permitidos. São aceites tipos exatos e padrões como
     *     `image/*`.
     * @param {number} [opcoes.tamanhoMaximo=0]
     *     Tamanho máximo em bytes. O valor zero desativa esta validação.
     * @param {string|null} [opcoes.seletorMensagemErro=null]
     *     Seletor do elemento que apresenta os erros.
     * @param {string|null} [opcoes.seletorTextoFicheiro=null]
     *     Seletor do elemento que apresenta o texto da seleção.
     * @param {string} [opcoes.textoPadrao='Selecionar ficheiro']
     *     Texto apresentado quando não existe seleção.
     * @param {string} [opcoes.textoSelecionado='Alterar ficheiro']
     *     Texto apresentado quando existe um ficheiro válido.
     * @param {((ficheiro: File, resultado: object) => void)|null}
     *     [opcoes.aoFicheiroInvalido=null]
     *     Função executada quando o ficheiro é inválido.
     * @param {((ficheiro: File) => void)|null}
     *     [opcoes.aoFicheiroValido=null]
     *     Função executada quando o ficheiro é válido.
     * @param {(() => void)|null} [opcoes.aoLimparSelecao=null]
     *     Função executada quando a seleção é removida.
     *
     * @throws {TypeError} Quando o campo ou alguma opção são inválidos.
     *
     * @since 1.0.0
     */
    constructor(
        campoFicheiro,
        opcoes = {},
    ) {
        if (
            !(campoFicheiro instanceof HTMLInputElement)
            || campoFicheiro.type !== 'file'
            || campoFicheiro.multiple
        ) {
            throw new TypeError(
                'O validador requer um campo HTML de ficheiro único.',
            );
        }

        this.campoFicheiro =
            campoFicheiro;

        this.opcoes =
            this.normalizarOpcoes(
                opcoes,
            );

        this.elementoMensagemErro =
            this.obterElementoOpcional(
                this.opcoes.seletorMensagemErro,
                'mensagem de erro',
            );

        this.elementoTextoFicheiro =
            this.obterElementoOpcional(
                this.opcoes.seletorTextoFicheiro,
                'texto do ficheiro',
            );

        this.configurarAcessibilidade();

        this.campoFicheiro.addEventListener(
            'change',
            () => {
                this.tratarAlteracao();
            },
        );

        const formulario =
            this.campoFicheiro.form;

        if (formulario instanceof HTMLFormElement) {
            formulario.addEventListener(
                'reset',
                () => {
                    this.tratarReposicao();
                },
            );
        }

        this.atualizarTextoSelecao();
    }

    /**
     * Normaliza e valida as opções recebidas.
     *
     * @param {object} opcoes Opções recebidas.
     *
     * @returns {Readonly<object>} Opções normalizadas.
     *
     * @throws {TypeError} Quando alguma opção é inválida.
     *
     * @since 2.0.0
     */
    normalizarOpcoes(opcoes) {
        if (
            opcoes === null
            || typeof opcoes !== 'object'
            || Array.isArray(opcoes)
        ) {
            throw new TypeError(
                'As opções do validador devem ser um objeto.',
            );
        }

        const configuracao = {
            tiposPermitidos: [],
            tamanhoMaximo: 0,
            seletorMensagemErro: null,
            seletorTextoFicheiro: null,
            textoPadrao: 'Selecionar ficheiro',
            textoSelecionado: 'Alterar ficheiro',
            aoFicheiroInvalido: null,
            aoFicheiroValido: null,
            aoLimparSelecao: null,
            ...opcoes,
        };

        if (
            !Array.isArray(
                configuracao.tiposPermitidos,
            )
        ) {
            throw new TypeError(
                'Os tipos permitidos devem ser apresentados numa lista.',
            );
        }

        const tiposPermitidos =
            configuracao.tiposPermitidos.map(
                (tipo) => {
                    if (typeof tipo !== 'string') {
                        throw new TypeError(
                            'Cada tipo permitido deve ser uma cadeia de caracteres.',
                        );
                    }

                    const tipoNormalizado =
                        tipo.trim().toLowerCase();

                    const partes =
                        tipoNormalizado.split('/');

                    if (
                        tipoNormalizado === ''
                        || partes.length !== 2
                        || partes[0] === ''
                        || partes[0] === '*'
                        || partes[1] === ''
                        || /\s/u.test(
                            partes[0],
                        )
                        || /\s/u.test(
                            partes[1],
                        )
                    ) {
                        throw new TypeError(
                            `O tipo MIME "${tipo}" não é válido.`,
                        );
                    }

                    return tipoNormalizado;
                },
            );

        if (
            !Number.isSafeInteger(
                configuracao.tamanhoMaximo,
            )
            || configuracao.tamanhoMaximo < 0
        ) {
            throw new TypeError(
                'O tamanho máximo deve ser um número inteiro não negativo.',
            );
        }

        const seletorMensagemErro =
            this.normalizarSeletorOpcional(
                configuracao.seletorMensagemErro,
                'mensagem de erro',
            );

        const seletorTextoFicheiro =
            this.normalizarSeletorOpcional(
                configuracao.seletorTextoFicheiro,
                'texto do ficheiro',
            );

        this.validarCallbackOpcional(
            configuracao.aoFicheiroInvalido,
            'aoFicheiroInvalido',
        );

        this.validarCallbackOpcional(
            configuracao.aoFicheiroValido,
            'aoFicheiroValido',
        );

        this.validarCallbackOpcional(
            configuracao.aoLimparSelecao,
            'aoLimparSelecao',
        );

        if (
            typeof configuracao.textoPadrao !== 'string'
            || configuracao.textoPadrao.trim() === ''
            || typeof configuracao.textoSelecionado !== 'string'
            || configuracao.textoSelecionado.trim() === ''
        ) {
            throw new TypeError(
                'Os textos do campo de ficheiro devem ser cadeias de caracteres não vazias.',
            );
        }

        return Object.freeze({
            tiposPermitidos:
                Object.freeze(
                    Array.from(
                        new Set(
                            tiposPermitidos,
                        ),
                    ),
                ),

            tamanhoMaximo:
                configuracao.tamanhoMaximo,

            seletorMensagemErro,
            seletorTextoFicheiro,

            textoPadrao:
                configuracao.textoPadrao.trim(),

            textoSelecionado:
                configuracao.textoSelecionado.trim(),

            aoFicheiroInvalido:
                configuracao.aoFicheiroInvalido,

            aoFicheiroValido:
                configuracao.aoFicheiroValido,

            aoLimparSelecao:
                configuracao.aoLimparSelecao,
        });
    }

    /**
     * Normaliza um seletor CSS opcional.
     *
     * @param {unknown} seletor Seletor recebido.
     * @param {string} descricao Descrição utilizada em mensagens de erro.
     *
     * @returns {string|null} Seletor normalizado ou nulo.
     *
     * @throws {TypeError} Quando o seletor não é válido.
     *
     * @since 2.0.0
     */
    normalizarSeletorOpcional(
        seletor,
        descricao,
    ) {
        if (seletor === null) {
            return null;
        }

        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                `O seletor de ${descricao} não é válido.`,
            );
        }

        return seletor.trim();
    }

    /**
     * Configura os atributos de acessibilidade da mensagem de erro.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    configurarAcessibilidade() {
        if (
            !(this.elementoMensagemErro
                instanceof HTMLElement)
        ) {
            return;
        }

        if (
            !this.elementoMensagemErro.hasAttribute(
                'aria-live',
            )
        ) {
            this.elementoMensagemErro.setAttribute(
                'aria-live',
                'polite',
            );
        }

        if (
            !this.elementoMensagemErro.hasAttribute(
                'aria-atomic',
            )
        ) {
            this.elementoMensagemErro.setAttribute(
                'aria-atomic',
                'true',
            );
        }

        const identificadorErro =
            this.elementoMensagemErro.id.trim();

        if (identificadorErro === '') {
            return;
        }

        const descricoesAtuais =
            (
                this.campoFicheiro.getAttribute(
                    'aria-describedby',
                )
                ?? ''
            )
                .split(/\s+/u)
                .filter(
                    (identificador) =>
                        identificador !== '',
                );

        this.campoFicheiro.setAttribute(
            'aria-describedby',
            Array.from(
                new Set([
                    ...descricoesAtuais,
                    identificadorErro,
                ]),
            ).join(' '),
        );
    }

    /**
     * Processa uma alteração na seleção.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    tratarAlteracao() {
        this.limparErro();

        const ficheiro =
            this.campoFicheiro.files?.item(
                0,
            )
            ?? null;

        if (!(ficheiro instanceof File)) {
            this.atualizarTextoSelecao();

            this.opcoes
                .aoLimparSelecao?.();

            return;
        }

        const resultado =
            this.validar(
                ficheiro,
            );

        if (!resultado.valido) {
            this.campoFicheiro.value =
                '';

            this.apresentarErro(
                resultado.mensagem,
            );

            this.atualizarTextoSelecao();

            this.opcoes
                .aoFicheiroInvalido?.(
                    ficheiro,
                    resultado,
                );

            return;
        }

        this.atualizarTextoSelecao(
            this.opcoes.textoSelecionado,
        );

        this.opcoes
            .aoFicheiroValido?.(
                ficheiro,
            );
    }

    /**
     * Sincroniza o estado visual depois da reposição do formulário.
     *
     * O navegador conclui a reposição dos controlos depois do evento `reset`,
     * pelo que a verificação é adiada para a tarefa seguinte.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarReposicao() {
        window.setTimeout(
            () => {
                const ficheiro =
                    this.campoFicheiro.files?.item(
                        0,
                    )
                    ?? null;

                /*
                 * Se o reset tiver sido cancelado por outro componente,
                 * a seleção mantém-se e não deve ser alterada por este módulo.
                 */
                if (ficheiro instanceof File) {
                    return;
                }

                this.limparErro();
                this.atualizarTextoSelecao();

                this.opcoes
                    .aoLimparSelecao?.();
            },
            0,
        );
    }

    /**
     * Valida um ficheiro.
     *
     * Um tipo MIME vazio não é rejeitado no navegador, porque esse valor pode
     * significar apenas que o agente do utilizador não conseguiu determinar o
     * tipo. A validação definitiva permanece no servidor.
     *
     * @param {File} ficheiro Ficheiro selecionado.
     *
     * @returns {{valido: boolean, codigo: string|null, mensagem: string}}
     *     Resultado da validação.
     *
     * @since 2.0.0
     */
    validar(ficheiro) {
        if (!(ficheiro instanceof File)) {
            return {
                valido: false,
                codigo:
                    'ficheiro_invalido',

                mensagem:
                    'O ficheiro selecionado não é válido.',
            };
        }

        const tipoFicheiro =
            ficheiro.type
                .trim()
                .toLowerCase();

        if (
            tipoFicheiro !== ''
            && this.opcoes.tiposPermitidos.length > 0
            && !this.tipoEPermitido(
                tipoFicheiro,
            )
        ) {
            return {
                valido: false,
                codigo:
                    'tipo_nao_permitido',

                mensagem:
                    this.criarMensagemTipoNaoPermitido(
                        tipoFicheiro,
                    ),
            };
        }

        if (
            this.opcoes.tamanhoMaximo > 0
            && ficheiro.size
                > this.opcoes.tamanhoMaximo
        ) {
            return {
                valido: false,
                codigo:
                    'tamanho_excedido',

                mensagem:
                    `O ficheiro é demasiado grande. O tamanho máximo permitido é ${this.formatarBytes(
                        this.opcoes.tamanhoMaximo,
                    )}.`,
            };
        }

        return {
            valido: true,
            codigo: null,
            mensagem: '',
        };
    }

    /**
     * Verifica se um tipo MIME pertence à lista permitida.
     *
     * @param {string} tipoFicheiro Tipo MIME do ficheiro.
     *
     * @returns {boolean} Verdadeiro quando o tipo é permitido.
     *
     * @since 2.0.0
     */
    tipoEPermitido(tipoFicheiro) {
        return this.opcoes.tiposPermitidos.some(
            (tipoPermitido) => {
                if (
                    tipoPermitido.endsWith(
                        '/*',
                    )
                ) {
                    return tipoFicheiro.startsWith(
                        tipoPermitido.slice(
                            0,
                            -1,
                        ),
                    );
                }

                return tipoFicheiro
                    === tipoPermitido;
            },
        );
    }

    /**
     * Apresenta uma mensagem de erro.
     *
     * A seleção inválida é removida do campo antes desta apresentação. Não é
     * mantida uma validade personalizada que possa bloquear posteriormente um
     * campo opcional já vazio.
     *
     * @param {string} mensagem Mensagem apresentada.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    apresentarErro(mensagem) {
        this.campoFicheiro.classList.add(
            'is-invalid',
        );

        this.campoFicheiro.setAttribute(
            'aria-invalid',
            'true',
        );

        if (
            !(this.elementoMensagemErro
                instanceof HTMLElement)
        ) {
            return;
        }

        this.elementoMensagemErro.textContent =
            mensagem;

        this.elementoMensagemErro.classList.add(
            'd-block',
        );

        this.elementoMensagemErro.removeAttribute(
            'hidden',
        );

        this.elementoMensagemErro.style
            .removeProperty(
                'display',
            );
    }

    /**
     * Limpa a mensagem de erro apresentada pelo módulo.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    limparErro() {
        this.campoFicheiro.classList.remove(
            'is-invalid',
        );

        this.campoFicheiro.removeAttribute(
            'aria-invalid',
        );

        /*
         * Garante a limpeza de qualquer validade personalizada que possa ter
         * sido aplicada anteriormente ao mesmo campo.
         */
        this.campoFicheiro.setCustomValidity(
            '',
        );

        if (
            !(this.elementoMensagemErro
                instanceof HTMLElement)
        ) {
            return;
        }

        this.elementoMensagemErro.textContent =
            '';

        this.elementoMensagemErro.classList.remove(
            'd-block',
        );

        this.elementoMensagemErro.style
            .removeProperty(
                'display',
            );

        this.elementoMensagemErro.setAttribute(
            'hidden',
            '',
        );
    }

    /**
     * Atualiza o texto associado ao campo.
     *
     * @param {string|null} texto
     *     Texto apresentado ou nulo para utilizar o valor padrão.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    atualizarTextoSelecao(texto = null) {
        if (
            !(this.elementoTextoFicheiro
                instanceof HTMLElement)
        ) {
            return;
        }

        this.elementoTextoFicheiro.textContent =
            texto
            ?? this.opcoes.textoPadrao;
    }

    /**
     * Cria a mensagem para um tipo não permitido.
     *
     * @param {string} tipoRecebido Tipo MIME recebido.
     *
     * @returns {string} Mensagem de erro.
     *
     * @since 2.0.0
     */
    criarMensagemTipoNaoPermitido(
        tipoRecebido,
    ) {
        const formatosPermitidos =
            this.formatarLista(
                this.opcoes.tiposPermitidos.map(
                    (tipo) =>
                        this.formatarTipoMime(
                            tipo,
                        ),
                ),
            );

        return `O tipo de ficheiro "${tipoRecebido}" não é permitido. Formatos permitidos: ${formatosPermitidos}.`;
    }

    /**
     * Converte um tipo MIME numa designação legível.
     *
     * @param {string} tipoMime Tipo MIME.
     *
     * @returns {string} Designação do formato.
     *
     * @since 2.0.0
     */
    formatarTipoMime(tipoMime) {
        if (
            tipoMime.endsWith(
                '/*',
            )
        ) {
            return `${tipoMime
                .slice(
                    0,
                    -2,
                )
                .toUpperCase()}/*`;
        }

        const subtipo =
            tipoMime
                .split('/')
                .slice(1)
                .join('/')
                .replace(
                    '+xml',
                    '',
                )
                .toUpperCase();

        return subtipo === 'JPEG'
            ? 'JPG/JPEG'
            : subtipo;
    }

    /**
     * Formata uma lista em português.
     *
     * @param {Array<string>} elementos Elementos apresentados.
     *
     * @returns {string} Lista formatada.
     *
     * @since 2.0.0
     */
    formatarLista(elementos) {
        if (elementos.length === 0) {
            return '';
        }

        if (elementos.length === 1) {
            return elementos[0];
        }

        return `${elementos
            .slice(
                0,
                -1,
            )
            .join(', ')} e ${elementos.at(-1)}`;
    }

    /**
     * Formata um tamanho em bytes utilizando unidades binárias.
     *
     * @param {number} bytes Tamanho em bytes.
     *
     * @returns {string} Tamanho formatado.
     *
     * @since 1.0.0
     */
    formatarBytes(bytes) {
        if (
            !Number.isFinite(bytes)
            || bytes <= 0
        ) {
            return '0 bytes';
        }

        const unidades = [
            'bytes',
            'KiB',
            'MiB',
            'GiB',
            'TiB',
        ];

        const indice = Math.min(
            Math.floor(
                Math.log(bytes)
                / Math.log(1024),
            ),
            unidades.length - 1,
        );

        const valor =
            bytes / (1024 ** indice);

        const formatador =
            new Intl.NumberFormat(
                'pt-PT',
                {
                    maximumFractionDigits:
                        2,
                },
            );

        return `${formatador.format(
            valor,
        )} ${unidades[indice]}`;
    }

    /**
     * Obtém um elemento associado a um seletor opcional.
     *
     * Quando um seletor é fornecido, este deve encontrar um elemento HTML.
     *
     * @param {string|null} seletor Seletor recebido.
     * @param {string} descricao Descrição utilizada em mensagens de erro.
     *
     * @returns {HTMLElement|null} Elemento encontrado ou nulo.
     *
     * @throws {TypeError} Quando o seletor ou o elemento são inválidos.
     *
     * @since 2.0.0
     */
    obterElementoOpcional(
        seletor,
        descricao,
    ) {
        if (seletor === null) {
            return null;
        }

        let elemento;

        try {
            elemento =
                document.querySelector(
                    seletor,
                );
        } catch {
            throw new TypeError(
                `O seletor CSS de ${descricao} não é válido.`,
            );
        }

        if (!(elemento instanceof HTMLElement)) {
            throw new TypeError(
                `Não foi encontrado um elemento válido para ${descricao}.`,
            );
        }

        return elemento;
    }

    /**
     * Valida uma função opcional.
     *
     * @param {unknown} callback Função recebida.
     * @param {string} nome Nome da opção.
     *
     * @returns {void}
     *
     * @throws {TypeError} Quando a função não é válida.
     *
     * @since 2.0.0
     */
    validarCallbackOpcional(
        callback,
        nome,
    ) {
        if (
            callback !== null
            && typeof callback !== 'function'
        ) {
            throw new TypeError(
                `A opção "${nome}" deve ser uma função ou nula.`,
            );
        }
    }
}

export default ValidadorFicheiro;
