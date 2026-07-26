/**
 * Valida um único ficheiro selecionado num campo HTML.
 *
 * Este módulo fornece apenas validação de apoio no navegador. A validação
 * definitiva do tipo, conteúdo e tamanho do ficheiro deve ser executada pelo
 * servidor.
 *
 * @since 1.0.0
 * @version 2.1.0
 */
class ValidadorFicheiro {
    /**
     * Cria o validador.
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
     * @version 2.1.0
     */
    constructor(
        campoFicheiro,
        opcoes = {},
    ) {
        if (
            !(campoFicheiro instanceof HTMLInputElement)
            || campoFicheiro.type !== 'file'
        ) {
            throw new TypeError(
                'O validador requer um campo HTML do tipo ficheiro.',
            );
        }

        this.campoFicheiro = campoFicheiro;

        this.opcoes = this.normalizarOpcoes(
            opcoes,
        );

        this.elementoMensagemErro = this.obterElementoOpcional(
            this.opcoes.seletorMensagemErro,
        );

        this.elementoTextoFicheiro = this.obterElementoOpcional(
            this.opcoes.seletorTextoFicheiro,
        );

        this.iniciado = false;

        this.manipularAlteracao =
            this.manipularAlteracao.bind(this);

        this.configurarAcessibilidade();
        this.configurarEventos();
        this.atualizarTextoSelecao(null);
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
     * @version 1.1.0
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

        const opcoesNormalizadas = {
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

        if (!Array.isArray(opcoesNormalizadas.tiposPermitidos)) {
            throw new TypeError(
                'Os tipos permitidos devem ser apresentados numa lista.',
            );
        }

        const tiposPermitidos = opcoesNormalizadas
            .tiposPermitidos
            .map((tipo) => {
                if (typeof tipo !== 'string') {
                    throw new TypeError(
                        'Cada tipo permitido deve ser uma cadeia de caracteres.',
                    );
                }

                return tipo.trim().toLowerCase();
            })
            .filter((tipo) => tipo !== '');

        if (
            !Number.isFinite(opcoesNormalizadas.tamanhoMaximo)
            || opcoesNormalizadas.tamanhoMaximo < 0
        ) {
            throw new TypeError(
                'O tamanho máximo deve ser um número não negativo.',
            );
        }

        this.validarSeletorOpcional(
            opcoesNormalizadas.seletorMensagemErro,
            'mensagem de erro',
        );

        this.validarSeletorOpcional(
            opcoesNormalizadas.seletorTextoFicheiro,
            'texto do ficheiro',
        );

        this.validarCallbackOpcional(
            opcoesNormalizadas.aoFicheiroInvalido,
            'aoFicheiroInvalido',
        );

        this.validarCallbackOpcional(
            opcoesNormalizadas.aoFicheiroValido,
            'aoFicheiroValido',
        );

        this.validarCallbackOpcional(
            opcoesNormalizadas.aoLimparSelecao,
            'aoLimparSelecao',
        );

        const textoPadrao = String(
            opcoesNormalizadas.textoPadrao,
        ).trim();

        const textoSelecionado = String(
            opcoesNormalizadas.textoSelecionado,
        ).trim();

        if (
            textoPadrao === ''
            || textoSelecionado === ''
        ) {
            throw new TypeError(
                'Os textos do campo de ficheiro não podem estar vazios.',
            );
        }

        return Object.freeze({
            ...opcoesNormalizadas,

            tiposPermitidos: Object.freeze(
                Array.from(
                    new Set(tiposPermitidos),
                ),
            ),

            tamanhoMaximo: Math.floor(
                opcoesNormalizadas.tamanhoMaximo,
            ),

            textoPadrao,
            textoSelecionado,
        });
    }

    /**
     * Configura os atributos de acessibilidade.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    configurarAcessibilidade() {
        if (
            !(
                this.elementoMensagemErro
                instanceof HTMLElement
            )
        ) {
            return;
        }

        this.elementoMensagemErro.setAttribute(
            'aria-live',
            'polite',
        );

        this.elementoMensagemErro.setAttribute(
            'role',
            'alert',
        );

        if (this.elementoMensagemErro.id === '') {
            return;
        }

        const descricoesAtuais = (
            this.campoFicheiro.getAttribute(
                'aria-describedby',
            )
            ?? ''
        )
            .split(/\s+/)
            .filter(Boolean);

        const descricoes = Array.from(
            new Set([
                ...descricoesAtuais,
                this.elementoMensagemErro.id,
            ]),
        );

        this.campoFicheiro.setAttribute(
            'aria-describedby',
            descricoes.join(' '),
        );
    }

    /**
     * Configura os eventos do campo.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.1.0
     */
    configurarEventos() {
        if (this.iniciado) {
            return;
        }

        this.campoFicheiro.addEventListener(
            'change',
            this.manipularAlteracao,
        );

        this.iniciado = true;
    }

    /**
     * Processa uma alteração na seleção.
     *
     * @param {Event} evento Evento de alteração.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.1.0
     */
    manipularAlteracao(evento) {
        if (
            evento.currentTarget
            !== this.campoFicheiro
        ) {
            return;
        }

        this.limparErro();

        const ficheiro =
            this.campoFicheiro.files?.item(0)
            ?? null;

        if (!(ficheiro instanceof File)) {
            this.atualizarTextoSelecao(null);

            this.executarCallback(
                this.opcoes.aoLimparSelecao,
            );

            return;
        }

        const resultado =
            this.validar(ficheiro);

        if (!resultado.valido) {
            this.apresentarErro(
                resultado.mensagem,
            );

            this.campoFicheiro.value = '';

            this.atualizarTextoSelecao(null);

            this.executarCallback(
                this.opcoes.aoFicheiroInvalido,
                ficheiro,
                resultado,
            );

            return;
        }

        this.atualizarTextoSelecao(
            this.opcoes.textoSelecionado,
        );

        this.executarCallback(
            this.opcoes.aoFicheiroValido,
            ficheiro,
        );
    }

    /**
     * Valida um ficheiro.
     *
     * @param {File} ficheiro Ficheiro selecionado.
     *
     * @returns {{valido: boolean, codigo: string|null, mensagem: string}}
     *     Resultado da validação.
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    validar(ficheiro) {
        if (!(ficheiro instanceof File)) {
            return {
                valido: false,
                codigo: 'ficheiro_invalido',
                mensagem:
                    'O ficheiro selecionado não é válido.',
            };
        }

        const tipoFicheiro = ficheiro.type
            .trim()
            .toLowerCase();

        if (
            this.opcoes.tiposPermitidos.length > 0
            && !this.tipoEPermitido(tipoFicheiro)
        ) {
            return {
                valido: false,
                codigo: 'tipo_nao_permitido',

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
                codigo: 'tamanho_excedido',

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
     * @returns {boolean}
     *
     * @since 2.1.0
     * @version 1.0.0
     */
    tipoEPermitido(tipoFicheiro) {
        return this.opcoes.tiposPermitidos.some(
            (tipoPermitido) => {
                if (
                    tipoPermitido.endsWith('/*')
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
     * @param {string} mensagem Mensagem apresentada.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.1.0
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
            !(
                this.elementoMensagemErro
                instanceof HTMLElement
            )
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
    }

    /**
     * Limpa a mensagem de erro apresentada pelo módulo.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.1.0
     */
    limparErro() {
        this.campoFicheiro.classList.remove(
            'is-invalid',
        );

        this.campoFicheiro.removeAttribute(
            'aria-invalid',
        );

        if (
            !(
                this.elementoMensagemErro
                instanceof HTMLElement
            )
        ) {
            return;
        }

        this.elementoMensagemErro.textContent =
            '';

        this.elementoMensagemErro.classList.remove(
            'd-block',
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
     * @version 2.0.0
     */
    atualizarTextoSelecao(texto = null) {
        if (
            !(
                this.elementoTextoFicheiro
                instanceof HTMLElement
            )
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
     * @version 1.0.0
     */
    criarMensagemTipoNaoPermitido(
        tipoRecebido,
    ) {
        const tipoApresentado =
            tipoRecebido !== ''
                ? tipoRecebido
                : 'desconhecido';

        const formatosPermitidos =
            this.formatarLista(
                this.opcoes.tiposPermitidos.map(
                    (tipo) =>
                        this.formatarTipoMime(
                            tipo,
                        ),
                ),
            );

        return `O tipo de ficheiro "${tipoApresentado}" não é permitido. Formatos permitidos: ${formatosPermitidos}.`;
    }

    /**
     * Converte um tipo MIME numa designação legível.
     *
     * @param {string} tipoMime Tipo MIME.
     *
     * @returns {string} Designação do formato.
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    formatarTipoMime(tipoMime) {
        if (tipoMime.endsWith('/*')) {
            return `${tipoMime
                .slice(0, -2)
                .toUpperCase()}/*`;
        }

        const partes =
            tipoMime.split('/');

        if (partes.length < 2) {
            return tipoMime.toUpperCase();
        }

        const subtipo = partes
            .slice(1)
            .join('/')
            .replace('+xml', '')
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
     * @version 1.0.0
     */
    formatarLista(elementos) {
        if (elementos.length === 0) {
            return '';
        }

        if (elementos.length === 1) {
            return elementos[0];
        }

        return `${elementos
            .slice(0, -1)
            .join(', ')} e ${elementos.at(-1)}`;
    }

    /**
     * Formata um tamanho em bytes.
     *
     * @param {number} bytes Tamanho em bytes.
     * @param {number} casasDecimais
     *     Número máximo de casas decimais.
     *
     * @returns {string} Tamanho formatado.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    formatarBytes(
        bytes,
        casasDecimais = 2,
    ) {
        if (
            !Number.isFinite(bytes)
            || bytes <= 0
        ) {
            return '0 bytes';
        }

        const unidades = [
            'bytes',
            'KB',
            'MB',
            'GB',
            'TB',
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
                        Math.max(
                            0,
                            Math.floor(
                                casasDecimais,
                            ),
                        ),
                },
            );

        return `${formatador.format(
            valor,
        )} ${unidades[indice]}`;
    }

    /**
     * Obtém um elemento associado a um seletor opcional.
     *
     * @param {string|null} seletor Seletor recebido.
     *
     * @returns {HTMLElement|null} Elemento encontrado ou nulo.
     *
     * @throws {TypeError} Quando o seletor CSS é inválido.
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    obterElementoOpcional(seletor) {
        if (seletor === null) {
            return null;
        }

        try {
            const elemento =
                document.querySelector(
                    seletor,
                );

            return elemento
                instanceof HTMLElement
                ? elemento
                : null;
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletor}" é inválido.`,
            );
        }
    }

    /**
     * Valida um seletor opcional.
     *
     * @param {unknown} seletor Valor recebido.
     * @param {string} nome Nome legível da opção.
     *
     * @returns {void}
     *
     * @throws {TypeError} Quando o seletor não é válido.
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    validarSeletorOpcional(
        seletor,
        nome,
    ) {
        if (seletor === null) {
            return;
        }

        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                `O seletor de ${nome} não é válido.`,
            );
        }

        try {
            document.querySelector(
                seletor,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS de ${nome} não é válido.`,
            );
        }
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
     * @version 1.0.0
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

    /**
     * Executa uma função quando esta está configurada.
     *
     * @param {Function|null} callback Função configurada.
     * @param {...unknown} argumentos Argumentos enviados.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    executarCallback(
        callback,
        ...argumentos
    ) {
        if (typeof callback === 'function') {
            callback(...argumentos);
        }
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

        this.campoFicheiro.removeEventListener(
            'change',
            this.manipularAlteracao,
        );

        this.iniciado = false;
    }
}

export default ValidadorFicheiro;
