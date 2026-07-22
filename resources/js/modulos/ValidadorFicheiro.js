/**
 * Valida um único ficheiro selecionado num campo HTML.
 *
 * Este módulo fornece apenas validação de apoio no navegador. A validação
 * definitiva do tipo, conteúdo e tamanho do ficheiro deve ser executada pelo
 * servidor.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class ValidadorFicheiro {
    /**
     * Cria o validador.
     *
     * @param {HTMLInputElement} campoFicheiro - Campo de ficheiro.
     * @param {Object} opcoes - Configuração do validador.
     * @param {Array<string>} [opcoes.tiposPermitidos=[]] - Tipos MIME
     * permitidos.
     * @param {number} [opcoes.tamanhoMaximo=0] - Tamanho máximo em bytes. O
     * valor zero desativa esta validação.
     * @param {string|null} [opcoes.seletorMensagemErro=null] - Seletor do
     * elemento que apresenta os erros.
     * @param {string|null} [opcoes.seletorTextoFicheiro=null] - Seletor do
     * elemento que apresenta o texto da seleção.
     * @param {string} [opcoes.textoPadrao='Selecionar ficheiro'] - Texto
     * apresentado quando não existe seleção.
     * @param {string} [opcoes.textoSelecionado='Alterar ficheiro'] - Texto
     * apresentado quando existe um ficheiro válido.
     * @param {Function|null} [opcoes.aoFicheiroInvalido=null] - Callback
     * executado quando o ficheiro é inválido.
     * @param {Function|null} [opcoes.aoFicheiroValido=null] - Callback
     * executado quando o ficheiro é válido.
     * @param {Function|null} [opcoes.aoLimparSelecao=null] - Callback executado
     * quando a seleção é removida.
     *
     * @throws {TypeError} Quando o campo ou alguma opção são inválidos.
     *
     * @since 1.0.0
     * @version 2.0.0
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

        /**
         * Campo de ficheiro gerido.
         *
         * @type {HTMLInputElement}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.campoFicheiro = campoFicheiro;

        /**
         * Opções normalizadas do validador.
         *
         * @type {Readonly<Object>}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.opcoes = this.normalizarOpcoes(opcoes);

        /**
         * Elemento utilizado para apresentar mensagens de erro.
         *
         * @type {HTMLElement|null}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.elementoMensagemErro = this.obterElementoOpcional(
            this.opcoes.seletorMensagemErro,
        );

        /**
         * Elemento utilizado para apresentar o estado da seleção.
         *
         * @type {HTMLElement|null}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.elementoTextoFicheiro = this.obterElementoOpcional(
            this.opcoes.seletorTextoFicheiro,
        );

        /**
         * Referência estável do manipulador de alteração.
         *
         * @type {(evento: Event) => void}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.manipularAlteracao =
            this.manipularAlteracao.bind(this);

        this.configurarAcessibilidade();
        this.configurarEventos();
        this.atualizarTextoSelecao(null);
    }

    /**
     * Normaliza e valida as opções recebidas.
     *
     * @param {Object} opcoes - Opções recebidas.
     *
     * @return {Readonly<Object>} Opções normalizadas.
     *
     * @throws {TypeError} Quando alguma opção é inválida.
     *
     * @since 2.0.0
     * @version 1.0.0
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
                        'Cada tipo permitido deve ser uma sequência de caracteres.',
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

        return Object.freeze({
            ...opcoesNormalizadas,
            tiposPermitidos: Object.freeze(
                Array.from(new Set(tiposPermitidos)),
            ),
            tamanhoMaximo: Math.floor(
                opcoesNormalizadas.tamanhoMaximo,
            ),
            textoPadrao: String(
                opcoesNormalizadas.textoPadrao,
            ),
            textoSelecionado: String(
                opcoesNormalizadas.textoSelecionado,
            ),
        });
    }

    /**
     * Configura atributos de acessibilidade.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    configurarAcessibilidade() {
        if (!(this.elementoMensagemErro instanceof HTMLElement)) {
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
    }

    /**
     * Configura os eventos do campo.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    configurarEventos() {
        this.campoFicheiro.addEventListener(
            'change',
            this.manipularAlteracao,
        );
    }

    /**
     * Processa uma alteração na seleção.
     *
     * @param {Event} evento - Evento de alteração.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    manipularAlteracao(evento) {
        if (evento.target !== this.campoFicheiro) {
            return;
        }

        this.limparErro();

        const ficheiro = this.campoFicheiro
            .files
            ?.item(0);

        if (!(ficheiro instanceof File)) {
            this.atualizarTextoSelecao(null);
            this.executarCallback(
                this.opcoes.aoLimparSelecao,
            );

            return;
        }

        const resultado = this.validar(ficheiro);

        if (!resultado.valido) {
            this.apresentarErro(resultado.mensagem);
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
     * @param {File} ficheiro - Ficheiro selecionado.
     *
     * @return {{valido: boolean, codigo: string|null, mensagem: string}}
     * Resultado da validação.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    validar(ficheiro) {
        if (!(ficheiro instanceof File)) {
            return {
                valido: false,
                codigo: 'ficheiro_invalido',
                mensagem: 'O ficheiro selecionado não é válido.',
            };
        }

        const tipoFicheiro = ficheiro.type
            .trim()
            .toLowerCase();

        if (
            this.opcoes.tiposPermitidos.length > 0
            && !this.opcoes.tiposPermitidos.includes(
                tipoFicheiro,
            )
        ) {
            return {
                valido: false,
                codigo: 'tipo_nao_permitido',
                mensagem: this.criarMensagemTipoNaoPermitido(
                    tipoFicheiro,
                ),
            };
        }

        if (
            this.opcoes.tamanhoMaximo > 0
            && ficheiro.size > this.opcoes.tamanhoMaximo
        ) {
            return {
                valido: false,
                codigo: 'tamanho_excedido',
                mensagem:
                    `O ficheiro é demasiado grande. O tamanho máximo permitido é ${this.formatarBytes(this.opcoes.tamanhoMaximo)}.`,
            };
        }

        return {
            valido: true,
            codigo: null,
            mensagem: '',
        };
    }

    /**
     * Apresenta uma mensagem de erro.
     *
     * @param {string} mensagem - Mensagem apresentada.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    apresentarErro(mensagem) {
        this.campoFicheiro.classList.add(
            'is-invalid',
        );

        this.campoFicheiro.setAttribute(
            'aria-invalid',
            'true',
        );

        if (!(this.elementoMensagemErro instanceof HTMLElement)) {
            return;
        }

        this.elementoMensagemErro.textContent = mensagem;

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
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    limparErro() {
        this.campoFicheiro.classList.remove(
            'is-invalid',
        );

        this.campoFicheiro.removeAttribute(
            'aria-invalid',
        );

        if (!(this.elementoMensagemErro instanceof HTMLElement)) {
            return;
        }

        this.elementoMensagemErro.textContent = '';

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
     * @param {string|null} texto - Texto apresentado ou nulo para utilizar o
     * valor padrão.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    atualizarTextoSelecao(texto = null) {
        if (!(this.elementoTextoFicheiro instanceof HTMLElement)) {
            return;
        }

        this.elementoTextoFicheiro.textContent =
            texto ?? this.opcoes.textoPadrao;
    }

    /**
     * Remove os eventos configurados pelo módulo.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        this.campoFicheiro.removeEventListener(
            'change',
            this.manipularAlteracao,
        );
    }

    /**
     * Cria a mensagem para um tipo não permitido.
     *
     * @param {string} tipoRecebido - Tipo MIME recebido.
     *
     * @return {string} Mensagem de erro.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    criarMensagemTipoNaoPermitido(tipoRecebido) {
        const tipoApresentado = tipoRecebido !== ''
            ? tipoRecebido
            : 'desconhecido';

        const formatosPermitidos = this.formatarLista(
            this.opcoes.tiposPermitidos.map(
                (tipo) => this.formatarTipoMime(tipo),
            ),
        );

        return `O tipo de ficheiro "${tipoApresentado}" não é permitido. Formatos permitidos: ${formatosPermitidos}.`;
    }

    /**
     * Converte um tipo MIME numa designação legível.
     *
     * @param {string} tipoMime - Tipo MIME.
     *
     * @return {string} Designação do formato.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    formatarTipoMime(tipoMime) {
        const partes = tipoMime.split('/');

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
     * @param {Array<string>} elementos - Elementos apresentados.
     *
     * @return {string} Lista formatada.
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

        return `${elementos.slice(0, -1).join(', ')} e ${elementos.at(-1)}`;
    }

    /**
     * Formata um tamanho em bytes.
     *
     * @param {number} bytes - Tamanho em bytes.
     * @param {number} casasDecimais - Número máximo de casas decimais.
     *
     * @return {string} Tamanho formatado.
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
                Math.log(bytes) / Math.log(1024),
            ),
            unidades.length - 1,
        );

        const valor = bytes / (1024 ** indice);

        const formatador = new Intl.NumberFormat(
            'pt-PT',
            {
                maximumFractionDigits: Math.max(
                    0,
                    Math.floor(casasDecimais),
                ),
            },
        );

        return `${formatador.format(valor)} ${unidades[indice]}`;
    }

    /**
     * Obtém um elemento associado a um seletor opcional.
     *
     * @param {string|null} seletor - Seletor recebido.
     *
     * @return {HTMLElement|null} Elemento encontrado ou nulo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterElementoOpcional(seletor) {
        if (seletor === null) {
            return null;
        }

        const elemento = document.querySelector(seletor);

        return elemento instanceof HTMLElement
            ? elemento
            : null;
    }

    /**
     * Valida um seletor opcional.
     *
     * @param {mixed} seletor - Valor recebido.
     * @param {string} nome - Nome legível da opção.
     *
     * @return {void}
     *
     * @throws {TypeError} Quando o seletor não é válido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    validarSeletorOpcional(
        seletor,
        nome,
    ) {
        if (
            seletor !== null
            && (
                typeof seletor !== 'string'
                || seletor.trim() === ''
            )
        ) {
            throw new TypeError(
                `O seletor de ${nome} não é válido.`,
            );
        }
    }

    /**
     * Valida um callback opcional.
     *
     * @param {mixed} callback - Callback recebido.
     * @param {string} nome - Nome da opção.
     *
     * @return {void}
     *
     * @throws {TypeError} Quando o callback não é válido.
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
     * Executa um callback quando este está configurado.
     *
     * @param {Function|null} callback - Callback configurado.
     * @param {...mixed} argumentos - Argumentos enviados.
     *
     * @return {void}
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
}

export default ValidadorFicheiro;
