/**
 * Gere a validação de apoio dos formulários no navegador.
 *
 * A validação executada por este módulo melhora a experiência do utilizador,
 * mas não substitui as regras, autorizações e validações aplicadas pelo
 * servidor.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class ValidadorFormulario {
    /**
     * Cria o validador.
     *
     * @param {string|HTMLFormElement} formularioOuSeletor - Formulário ou
     * respetivo seletor CSS.
     * @param {Object} configuracao - Configuração do validador.
     * @param {Object<string, Array<string|Function>>} configuracao.regras -
     * Regras organizadas pelo atributo `name` dos campos.
     * @param {Object<string, Object<string, string>>}
     * [configuracao.mensagens={}] - Mensagens personalizadas.
     * @param {Function|null} [configuracao.aoSucesso=null] - Função executada
     * quando o formulário é válido.
     * @param {Function|null} [configuracao.validadorPersonalizado=null] -
     * Validação adicional do formulário.
     * @param {Array<string>} [configuracao.eventosTempoReal] - Eventos usados
     * depois da primeira tentativa de submissão.
     *
     * @throws {TypeError} Quando o formulário ou a configuração são inválidos.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor(
        formularioOuSeletor,
        configuracao = {},
    ) {
        /**
         * Formulário gerido.
         *
         * @type {HTMLFormElement}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.formulario = this.obterFormulario(
            formularioOuSeletor,
        );

        /**
         * Configuração normalizada.
         *
         * @type {Readonly<Object>}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.configuracao = this.normalizarConfiguracao(
            configuracao,
        );

        /**
         * Regras de validação.
         *
         * @type {Readonly<Object<string, ReadonlyArray<string|Function>>>}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.regras = this.configuracao.regras;

        /**
         * Mensagens personalizadas.
         *
         * @type {Readonly<Object<string, Object<string, string>>>}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.mensagens = this.configuracao.mensagens;

        /**
         * Erros atualmente registados.
         *
         * @type {Map<string, Array<string>>}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.erros = new Map();

        /**
         * Indica se já ocorreu uma tentativa de submissão.
         *
         * @type {boolean}
         *
         * @since 1.0.0
         * @version 2.0.0
         */
        this.tentouSubmeter = false;

        /**
         * Referências estáveis dos manipuladores.
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.manipularSubmissao =
            this.manipularSubmissao.bind(this);

        this.manipularInteracao =
            this.manipularInteracao.bind(this);

        this.manipularReposicao =
            this.manipularReposicao.bind(this);

        this.formulario.setAttribute(
            'novalidate',
            '',
        );

        this.configurarEventos();
    }

    /**
     * Valida todos os campos configurados.
     *
     * @param {Object} opcoes - Opções da validação.
     * @param {boolean} [opcoes.focarPrimeiroErro=true] - Indica se o primeiro
     * campo inválido deve receber foco.
     *
     * @return {boolean} Verdadeiro quando todos os campos são válidos.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    validarTudo({
        focarPrimeiroErro = true,
    } = {}) {
        let formularioValido = true;
        let primeiroCampoInvalido = null;

        Object.keys(this.regras).forEach(
            (nomeCampo) => {
                const campoValido =
                    this.validarCampoPorNome(
                        nomeCampo,
                        this.regras[nomeCampo],
                        this.mensagens[nomeCampo] ?? {},
                    );

                if (campoValido) {
                    return;
                }

                formularioValido = false;

                if (primeiroCampoInvalido === null) {
                    primeiroCampoInvalido =
                        this.obterCampos(nomeCampo)[0] ?? null;
                }
            },
        );

        if (
            !formularioValido
            && focarPrimeiroErro
            && primeiroCampoInvalido instanceof HTMLElement
        ) {
            primeiroCampoInvalido.focus();
        }

        return formularioValido;
    }

    /**
     * Valida o campo indicado.
     *
     * @param {string|HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement}
     * campoOuNome - Campo ou respetivo nome.
     *
     * @return {boolean} Verdadeiro quando o campo é válido.
     *
     * @throws {TypeError} Quando não é possível determinar o campo.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    validarCampo(campoOuNome) {
        const nomeCampo = this.obterNomeCampo(
            campoOuNome,
        );

        return this.validarCampoPorNome(
            nomeCampo,
            this.regras[nomeCampo] ?? [],
            this.mensagens[nomeCampo] ?? {},
        );
    }

    /**
     * Valida um campo com regras e mensagens específicas.
     *
     * @param {string|HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement}
     * campoOuNome - Campo ou respetivo nome.
     * @param {Array<string|Function>} regras - Regras temporárias.
     * @param {Object<string, string>} [mensagens={}] - Mensagens temporárias.
     *
     * @return {boolean} Verdadeiro quando o campo é válido.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    validarCampoComRegras(
        campoOuNome,
        regras,
        mensagens = {},
    ) {
        const nomeCampo = this.obterNomeCampo(
            campoOuNome,
        );

        this.validarListaRegras(
            nomeCampo,
            regras,
        );

        return this.validarCampoPorNome(
            nomeCampo,
            regras,
            mensagens,
        );
    }

    /**
     * Regista manualmente um erro num campo.
     *
     * Pode ser utilizado por validadores personalizados.
     *
     * @param {string} nomeCampo - Nome do campo.
     * @param {string} mensagem - Mensagem apresentada.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    definirErro(
        nomeCampo,
        mensagem,
    ) {
        const mensagemNormalizada = String(
            mensagem,
        ).trim();

        if (mensagemNormalizada === '') {
            throw new TypeError(
                'A mensagem de erro não pode estar vazia.',
            );
        }

        const campos = this.obterCampos(nomeCampo);

        this.erros.set(
            nomeCampo,
            [mensagemNormalizada],
        );

        this.apresentarErros(
            campos,
            [mensagemNormalizada],
        );
    }

    /**
     * Limpa o erro de um campo.
     *
     * @param {string} nomeCampo - Nome do campo.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    limparErroCampo(nomeCampo) {
        const campos = this.obterCampos(nomeCampo);

        this.erros.delete(nomeCampo);

        this.apresentarErros(
            campos,
            [],
        );
    }

    /**
     * Obtém uma cópia dos erros atuais.
     *
     * @return {Object<string, Array<string>>} Erros por campo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterErros() {
        return Object.fromEntries(
            Array.from(
                this.erros.entries(),
                ([nomeCampo, mensagens]) => [
                    nomeCampo,
                    [...mensagens],
                ],
            ),
        );
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
        this.formulario.removeEventListener(
            'submit',
            this.manipularSubmissao,
        );

        this.formulario.removeEventListener(
            'reset',
            this.manipularReposicao,
        );

        this.configuracao.eventosTempoReal.forEach(
            (tipoEvento) => {
                this.formulario.removeEventListener(
                    tipoEvento,
                    this.manipularInteracao,
                );
            },
        );
    }

    /**
     * Valida um campo pelo respetivo nome.
     *
     * @param {string} nomeCampo - Nome do campo.
     * @param {Array<string|Function>} regras - Regras aplicáveis.
     * @param {Object<string, string>} mensagens - Mensagens personalizadas.
     *
     * @return {boolean} Verdadeiro quando o campo é válido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    validarCampoPorNome(
        nomeCampo,
        regras,
        mensagens,
    ) {
        const campos = this.obterCampos(nomeCampo);

        if (campos.length === 0) {
            throw new Error(
                `Não foi encontrado nenhum campo com o nome "${nomeCampo}".`,
            );
        }

        const valor = this.obterValor(campos);

        const campoObrigatorio = regras.some(
            (regra) => (
                typeof regra === 'string'
                && this.analisarRegra(regra).nome
                    === 'obrigatorio'
            ),
        );

        if (
            !campoObrigatorio
            && this.estaVazio(valor)
        ) {
            this.erros.delete(nomeCampo);

            this.apresentarErros(
                campos,
                [],
            );

            return true;
        }

        const errosCampo = [];

        for (const regra of regras) {
            const mensagem = typeof regra === 'function'
                ? this.executarRegraPersonalizada(
                    regra,
                    nomeCampo,
                    campos,
                    valor,
                )
                : this.validarRegraTextual(
                    nomeCampo,
                    campos,
                    valor,
                    regra,
                    mensagens,
                );

            if (mensagem !== null) {
                errosCampo.push(mensagem);

                break;
            }
        }

        if (errosCampo.length > 0) {
            this.erros.set(
                nomeCampo,
                errosCampo,
            );
        } else {
            this.erros.delete(nomeCampo);
        }

        this.apresentarErros(
            campos,
            errosCampo,
        );

        return errosCampo.length === 0;
    }

    /**
     * Valida uma regra textual.
     *
     * @param {string} nomeCampo - Nome do campo.
     * @param {Array<HTMLElement>} campos - Campos associados.
     * @param {mixed} valor - Valor recebido.
     * @param {string} regra - Regra textual.
     * @param {Object<string, string>} mensagens - Mensagens personalizadas.
     *
     * @return {string|null} Mensagem de erro ou nulo.
     *
     * @throws {Error} Quando a regra não é reconhecida.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    validarRegraTextual(
        nomeCampo,
        campos,
        valor,
        regra,
        mensagens,
    ) {
        const {
            nome,
            parametro,
        } = this.analisarRegra(regra);

        let valido;

        switch (nome) {
            case 'obrigatorio':
                valido = !this.estaVazio(valor);
                break;

            case 'email':
                valido = this.validarEmail(valor);
                break;

            case 'minimo':
                valido = this.obterComprimento(valor)
                    >= this.obterParametroNumerico(
                        nome,
                        parametro,
                    );
                break;

            case 'maximo':
                valido = this.obterComprimento(valor)
                    <= this.obterParametroNumerico(
                        nome,
                        parametro,
                    );
                break;

            case 'confirmado':
                valido = this.valoresIguais(
                    valor,
                    this.obterValor(
                        this.obterCamposObrigatorios(
                            parametro,
                            nome,
                        ),
                    ),
                );
                break;

            case 'diferente':
                valido = !this.valoresIguais(
                    valor,
                    this.obterValor(
                        this.obterCamposObrigatorios(
                            parametro,
                            nome,
                        ),
                    ),
                );
                break;

            case 'data':
                valido = this.criarData(valor) !== null;
                break;

            case 'posterior_ou_igual': {
                const outraData = this.criarData(
                    this.obterValor(
                        this.obterCamposObrigatorios(
                            parametro,
                            nome,
                        ),
                    ),
                );

                const dataAtual = this.criarData(valor);

                valido = (
                    dataAtual === null
                    || outraData === null
                    || dataAtual.getTime()
                        >= outraData.getTime()
                );

                break;
            }

            case 'maiuscula':
                valido = (
                    typeof valor === 'string'
                    && /\p{Lu}/u.test(valor)
                );
                break;

            case 'minuscula':
                valido = (
                    typeof valor === 'string'
                    && /\p{Ll}/u.test(valor)
                );
                break;

            case 'numero':
                valido = (
                    typeof valor === 'string'
                    && /\p{N}/u.test(valor)
                );
                break;

            case 'simbolo':
                valido = (
                    typeof valor === 'string'
                    && /[^\p{L}\p{N}\s]/u.test(valor)
                );
                break;

            case 'inteiro':
                valido = (
                    typeof valor === 'string'
                    && /^-?\d+$/.test(valor.trim())
                );
                break;

            default:
                throw new Error(
                    `A regra de validação "${nome}" não é reconhecida.`,
                );
        }

        if (valido) {
            return null;
        }

        return mensagens[nome]
            ?? this.criarMensagemPadrao(
                nomeCampo,
                campos,
                nome,
                parametro,
            );
    }

    /**
     * Executa uma regra personalizada.
     *
     * A regra pode devolver `true`, `null` ou `undefined` para indicar sucesso,
     * `false` para uma mensagem genérica, ou uma string para uma mensagem
     * específica.
     *
     * @param {Function} regra - Regra personalizada.
     * @param {string} nomeCampo - Nome do campo.
     * @param {Array<HTMLElement>} campos - Campos associados.
     * @param {mixed} valor - Valor recebido.
     *
     * @return {string|null} Mensagem de erro ou nulo.
     *
     * @throws {TypeError} Quando o resultado não é reconhecido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    executarRegraPersonalizada(
        regra,
        nomeCampo,
        campos,
        valor,
    ) {
        const resultado = regra({
            nomeCampo,
            campos: [...campos],
            valor,
            formulario: this.formulario,
            validador: this,
        });

        if (
            resultado === true
            || resultado === null
            || resultado === undefined
        ) {
            return null;
        }

        if (resultado === false) {
            return `O campo '${this.obterNomeAmigavel(campos, nomeCampo)}' é inválido.`;
        }

        if (typeof resultado === 'string') {
            return resultado;
        }

        throw new TypeError(
            'Uma regra personalizada deve devolver uma mensagem, um booleano ou nulo.',
        );
    }

    /**
     * Apresenta ou remove os erros de um conjunto de campos.
     *
     * @param {Array<HTMLElement>} campos - Campos associados.
     * @param {Array<string>} erros - Mensagens de erro.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    apresentarErros(
        campos,
        erros,
    ) {
        const possuiErro = erros.length > 0;

        campos.forEach((campo) => {
            campo.classList.toggle(
                'is-invalid',
                possuiErro,
            );

            if (possuiErro) {
                campo.setAttribute(
                    'aria-invalid',
                    'true',
                );
            } else {
                campo.removeAttribute(
                    'aria-invalid',
                );
            }
        });

        const grupos = Array.from(
            new Set(
                campos
                    .map(
                        (campo) => campo.closest(
                            '.form-field-group',
                        ),
                    )
                    .filter(
                        (grupo) => grupo instanceof HTMLElement,
                    ),
            ),
        );

        grupos.forEach((grupo) => {
            const elementoErro = grupo.querySelector(
                '.invalid-feedback',
            );

            if (!(elementoErro instanceof HTMLElement)) {
                return;
            }

            elementoErro.setAttribute(
                'role',
                'alert',
            );

            elementoErro.setAttribute(
                'aria-live',
                'polite',
            );

            elementoErro.textContent =
                possuiErro ? erros[0] : '';

            elementoErro.classList.toggle(
                'd-block',
                possuiErro,
            );

            if (possuiErro) {
                elementoErro.removeAttribute(
                    'hidden',
                );
            } else {
                elementoErro.setAttribute(
                    'hidden',
                    '',
                );
            }
        });
    }

    /**
     * Processa a submissão do formulário.
     *
     * @param {SubmitEvent} evento - Evento de submissão.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    manipularSubmissao(evento) {
        this.tentouSubmeter = true;

        const validacaoEstatica = this.validarTudo({
            focarPrimeiroErro: false,
        });

        let validacaoPersonalizada = true;

        if (
            validacaoEstatica
            && typeof this.configuracao
                .validadorPersonalizado === 'function'
        ) {
            const resultado = this.configuracao
                .validadorPersonalizado(
                    this,
                    this.formulario,
                );

            if (resultado instanceof Promise) {
                throw new TypeError(
                    'O validador personalizado não pode ser assíncrono.',
                );
            }

            validacaoPersonalizada = resultado !== false;
        }

        if (
            !validacaoEstatica
            || !validacaoPersonalizada
        ) {
            evento.preventDefault();

            this.focarPrimeiroCampoInvalido();

            return;
        }

        if (
            typeof this.configuracao.aoSucesso
            !== 'function'
        ) {
            return;
        }

        evento.preventDefault();

        const resultado = this.configuracao.aoSucesso(
            this.formulario,
            this,
        );

        if (resultado instanceof Promise) {
            throw new TypeError(
                'O callback de sucesso não pode ser assíncrono.',
            );
        }
    }

    /**
     * Processa eventos de validação em tempo real.
     *
     * @param {Event} evento - Evento ocorrido no formulário.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    manipularInteracao(evento) {
        if (!this.tentouSubmeter) {
            return;
        }

        const campo = evento.target;

        if (!this.eCampoValidavel(campo)) {
            return;
        }

        if (!Object.hasOwn(this.regras, campo.name)) {
            return;
        }

        this.validarCampo(campo);
    }

    /**
     * Processa a reposição do formulário.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    manipularReposicao() {
        window.setTimeout(
            () => {
                this.tentouSubmeter = false;

                Object.keys(this.regras).forEach(
                    (nomeCampo) => {
                        this.limparErroCampo(nomeCampo);
                    },
                );
            },
            0,
        );
    }

    /**
     * Configura os eventos do formulário.
     *
     * @return {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    configurarEventos() {
        this.formulario.addEventListener(
            'submit',
            this.manipularSubmissao,
        );

        this.formulario.addEventListener(
            'reset',
            this.manipularReposicao,
        );

        this.configuracao.eventosTempoReal.forEach(
            (tipoEvento) => {
                this.formulario.addEventListener(
                    tipoEvento,
                    this.manipularInteracao,
                );
            },
        );
    }

    /**
     * Foca o primeiro campo atualmente inválido.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    focarPrimeiroCampoInvalido() {
        for (const nomeCampo of this.erros.keys()) {
            const campo = this.obterCampos(nomeCampo)
                .find(
                    (elemento) => (
                        elemento instanceof HTMLElement
                        && !elemento.hasAttribute('hidden')
                    ),
                );

            if (campo instanceof HTMLElement) {
                campo.focus();

                return;
            }
        }
    }

    /**
     * Obtém os campos associados a um nome.
     *
     * @param {string} nomeCampo - Nome procurado.
     *
     * @return {Array<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>}
     * Campos encontrados.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterCampos(nomeCampo) {
        return Array.from(
            this.formulario.elements,
        ).filter(
            (elemento) => (
                this.eCampoValidavel(elemento)
                && elemento.name === nomeCampo
                && !elemento.disabled
            ),
        );
    }

    /**
     * Obtém obrigatoriamente os campos associados.
     *
     * @param {string|null} nomeCampo - Nome procurado.
     * @param {string} regra - Regra que exige o campo.
     *
     * @return {Array<HTMLElement>} Campos encontrados.
     *
     * @throws {Error} Quando o parâmetro ou o campo não existem.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterCamposObrigatorios(
        nomeCampo,
        regra,
    ) {
        if (
            typeof nomeCampo !== 'string'
            || nomeCampo.trim() === ''
        ) {
            throw new Error(
                `A regra "${regra}" exige o nome de outro campo.`,
            );
        }

        const campos = this.obterCampos(
            nomeCampo,
        );

        if (campos.length === 0) {
            throw new Error(
                `A regra "${regra}" referencia o campo inexistente "${nomeCampo}".`,
            );
        }

        return campos;
    }

    /**
     * Obtém o valor de um conjunto de campos.
     *
     * @param {Array<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>}
     * campos - Campos associados.
     *
     * @return {mixed} Valor normalizado.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterValor(campos) {
        const primeiroCampo = campos[0];

        if (primeiroCampo instanceof HTMLInputElement) {
            if (primeiroCampo.type === 'checkbox') {
                return campos
                    .filter(
                        (campo) => (
                            campo instanceof HTMLInputElement
                            && campo.checked
                        ),
                    )
                    .map((campo) => campo.value);
            }

            if (primeiroCampo.type === 'radio') {
                const selecionado = campos.find(
                    (campo) => (
                        campo instanceof HTMLInputElement
                        && campo.checked
                    ),
                );

                return selecionado instanceof HTMLInputElement
                    ? selecionado.value
                    : '';
            }

            if (primeiroCampo.type === 'file') {
                return primeiroCampo.files === null
                    ? []
                    : Array.from(primeiroCampo.files);
            }
        }

        if (
            primeiroCampo instanceof HTMLSelectElement
            && primeiroCampo.multiple
        ) {
            return Array.from(
                primeiroCampo.selectedOptions,
                (opcao) => opcao.value,
            );
        }

        return primeiroCampo.value;
    }

    /**
     * Determina se um valor está vazio.
     *
     * @param {mixed} valor - Valor recebido.
     *
     * @return {boolean} Verdadeiro quando não contém informação.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    estaVazio(valor) {
        if (valor === null || valor === undefined) {
            return true;
        }

        if (Array.isArray(valor)) {
            return valor.length === 0;
        }

        if (typeof valor === 'string') {
            return valor.trim() === '';
        }

        return false;
    }

    /**
     * Obtém o comprimento de um valor.
     *
     * @param {mixed} valor - Valor recebido.
     *
     * @return {number} Comprimento obtido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterComprimento(valor) {
        if (
            typeof valor === 'string'
            || Array.isArray(valor)
        ) {
            return valor.length;
        }

        return String(valor ?? '').length;
    }

    /**
     * Valida um endereço de e-mail.
     *
     * @param {mixed} valor - Valor recebido.
     *
     * @return {boolean} Verdadeiro quando o formato é plausível.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    validarEmail(valor) {
        return (
            typeof valor === 'string'
            && /^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(
                valor.trim(),
            )
        );
    }

    /**
     * Cria uma data válida.
     *
     * @param {mixed} valor - Valor recebido.
     *
     * @return {Date|null} Data ou nulo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    criarData(valor) {
        if (
            typeof valor !== 'string'
            || valor.trim() === ''
        ) {
            return null;
        }

        const valorNormalizado = valor.trim();
        const correspondencia = valorNormalizado.match(
            /^(\d{4})-(\d{2})-(\d{2})$/,
        );

        if (correspondencia !== null) {
            const ano = Number(correspondencia[1]);
            const mes = Number(correspondencia[2]);
            const dia = Number(correspondencia[3]);

            const data = new Date(
                Date.UTC(
                    ano,
                    mes - 1,
                    dia,
                ),
            );

            if (
                data.getUTCFullYear() !== ano
                || data.getUTCMonth() !== mes - 1
                || data.getUTCDate() !== dia
            ) {
                return null;
            }

            return data;
        }

        const instante = Date.parse(
            valorNormalizado,
        );

        return Number.isNaN(instante)
            ? null
            : new Date(instante);
    }

    /**
     * Determina se dois valores são iguais.
     *
     * @param {mixed} primeiroValor - Primeiro valor.
     * @param {mixed} segundoValor - Segundo valor.
     *
     * @return {boolean} Verdadeiro quando coincidem.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    valoresIguais(
        primeiroValor,
        segundoValor,
    ) {
        if (
            Array.isArray(primeiroValor)
            || Array.isArray(segundoValor)
        ) {
            return JSON.stringify(primeiroValor)
                === JSON.stringify(segundoValor);
        }

        return String(primeiroValor ?? '')
            === String(segundoValor ?? '');
    }

    /**
     * Analisa uma regra textual.
     *
     * @param {string} regra - Regra recebida.
     *
     * @return {{nome: string, parametro: string|null}} Regra analisada.
     *
     * @throws {TypeError} Quando a regra é inválida.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    analisarRegra(regra) {
        if (
            typeof regra !== 'string'
            || regra.trim() === ''
        ) {
            throw new TypeError(
                'Cada regra deve ser uma sequência de caracteres não vazia.',
            );
        }

        const [
            nome,
            ...partesParametro
        ] = regra.trim().split(':');

        return {
            nome,
            parametro: partesParametro.length > 0
                ? partesParametro.join(':').trim()
                : null,
        };
    }

    /**
     * Obtém um parâmetro numérico de uma regra.
     *
     * @param {string} regra - Nome da regra.
     * @param {string|null} parametro - Parâmetro recebido.
     *
     * @return {number} Parâmetro inteiro não negativo.
     *
     * @throws {Error} Quando o parâmetro não é válido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterParametroNumerico(
        regra,
        parametro,
    ) {
        const numero = Number(parametro);

        if (
            !Number.isInteger(numero)
            || numero < 0
        ) {
            throw new Error(
                `A regra "${regra}" exige um número inteiro não negativo.`,
            );
        }

        return numero;
    }

    /**
     * Cria a mensagem padrão de uma regra.
     *
     * @param {string} nomeCampo - Nome técnico do campo.
     * @param {Array<HTMLElement>} campos - Campos associados.
     * @param {string} regra - Regra aplicada.
     * @param {string|null} parametro - Parâmetro da regra.
     *
     * @return {string} Mensagem de erro.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    criarMensagemPadrao(
        nomeCampo,
        campos,
        regra,
        parametro,
    ) {
        const nomeAmigavel =
            this.obterNomeAmigavel(
                campos,
                nomeCampo,
            );

        const mensagens = {
            obrigatorio:
                `O campo '${nomeAmigavel}' é obrigatório.`,

            email:
                'Por favor, insere um endereço de e-mail válido.',

            minimo:
                `O campo '${nomeAmigavel}' deve ter, pelo menos, ${parametro} caracteres.`,

            maximo:
                `O campo '${nomeAmigavel}' não pode ter mais de ${parametro} caracteres.`,

            confirmado:
                'A confirmação não coincide.',

            diferente:
                `O campo '${nomeAmigavel}' deve ser diferente.`,

            data:
                `O campo '${nomeAmigavel}' deve conter uma data válida.`,

            posterior_ou_igual:
                `O campo '${nomeAmigavel}' deve conter uma data igual ou posterior a '${this.obterNomeAmigavel(
                    this.obterCamposObrigatorios(
                        parametro,
                        regra,
                    ),
                    parametro,
                )}'.`,

            maiuscula:
                `O campo '${nomeAmigavel}' deve conter uma letra maiúscula.`,

            minuscula:
                `O campo '${nomeAmigavel}' deve conter uma letra minúscula.`,

            numero:
                `O campo '${nomeAmigavel}' deve conter um número.`,

            simbolo:
                `O campo '${nomeAmigavel}' deve conter um símbolo.`,

            inteiro:
                `O campo '${nomeAmigavel}' deve conter um número inteiro.`,
        };

        return mensagens[regra]
            ?? `O campo '${nomeAmigavel}' é inválido.`;
    }

    /**
     * Obtém o nome legível de um campo.
     *
     * @param {Array<HTMLElement>} campos - Campos associados.
     * @param {string} nomeAlternativo - Nome usado como alternativa.
     *
     * @return {string} Nome legível.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    obterNomeAmigavel(
        campos,
        nomeAlternativo,
    ) {
        const primeiroCampo = campos[0];

        if (
            !this.eCampoValidavel(primeiroCampo)
            || !primeiroCampo.labels
            || primeiroCampo.labels.length === 0
        ) {
            return nomeAlternativo;
        }

        const etiqueta = primeiroCampo.labels[0]
            .cloneNode(true);

        etiqueta
            .querySelectorAll(
                '.text-danger, [aria-hidden="true"]',
            )
            .forEach(
                (elemento) => elemento.remove(),
            );

        const texto = etiqueta.textContent?.trim();

        return texto !== ''
            ? texto
            : nomeAlternativo;
    }

    /**
     * Obtém o nome técnico de um campo.
     *
     * @param {string|HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement}
     * campoOuNome - Campo ou nome.
     *
     * @return {string} Nome do campo.
     *
     * @throws {TypeError} Quando o valor não é válido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterNomeCampo(campoOuNome) {
        if (
            typeof campoOuNome === 'string'
            && campoOuNome.trim() !== ''
        ) {
            return campoOuNome;
        }

        if (
            this.eCampoValidavel(campoOuNome)
            && campoOuNome.name !== ''
        ) {
            return campoOuNome.name;
        }

        throw new TypeError(
            'Não foi possível determinar o nome do campo.',
        );
    }

    /**
     * Obtém o formulário configurado.
     *
     * @param {string|HTMLFormElement} formularioOuSeletor - Valor recebido.
     *
     * @return {HTMLFormElement} Formulário encontrado.
     *
     * @throws {TypeError} Quando o formulário não existe.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterFormulario(formularioOuSeletor) {
        if (formularioOuSeletor instanceof HTMLFormElement) {
            return formularioOuSeletor;
        }

        if (
            typeof formularioOuSeletor !== 'string'
            || formularioOuSeletor.trim() === ''
        ) {
            throw new TypeError(
                'É necessário indicar um formulário ou seletor válido.',
            );
        }

        const formulario = document.querySelector(
            formularioOuSeletor,
        );

        if (!(formulario instanceof HTMLFormElement)) {
            throw new TypeError(
                `Não foi encontrado um formulário para o seletor "${formularioOuSeletor}".`,
            );
        }

        return formulario;
    }

    /**
     * Normaliza a configuração do validador.
     *
     * @param {Object} configuracao - Configuração recebida.
     *
     * @return {Readonly<Object>} Configuração normalizada.
     *
     * @throws {TypeError} Quando a configuração é inválida.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    normalizarConfiguracao(configuracao) {
        if (
            configuracao === null
            || typeof configuracao !== 'object'
            || Array.isArray(configuracao)
        ) {
            throw new TypeError(
                'A configuração do validador deve ser um objeto.',
            );
        }

        const configuracaoNormalizada = {
            regras: {},
            mensagens: {},
            aoSucesso: null,
            validadorPersonalizado: null,
            eventosTempoReal: [
                'input',
                'change',
                'focusout',
            ],
            ...configuracao,
        };

        if (
            configuracaoNormalizada.regras === null
            || typeof configuracaoNormalizada.regras
                !== 'object'
            || Array.isArray(
                configuracaoNormalizada.regras,
            )
        ) {
            throw new TypeError(
                'As regras devem ser apresentadas num objeto.',
            );
        }

        const regrasNormalizadas = {};

        Object.entries(
            configuracaoNormalizada.regras,
        ).forEach(
            ([nomeCampo, regras]) => {
                this.validarListaRegras(
                    nomeCampo,
                    regras,
                );

                regrasNormalizadas[nomeCampo] =
                    Object.freeze([...regras]);
            },
        );

        if (
            configuracaoNormalizada.mensagens === null
            || typeof configuracaoNormalizada.mensagens
                !== 'object'
            || Array.isArray(
                configuracaoNormalizada.mensagens,
            )
        ) {
            throw new TypeError(
                'As mensagens devem ser apresentadas num objeto.',
            );
        }

        [
            'aoSucesso',
            'validadorPersonalizado',
        ].forEach(
            (nomeCallback) => {
                const callback =
                    configuracaoNormalizada[nomeCallback];

                if (
                    callback !== null
                    && typeof callback !== 'function'
                ) {
                    throw new TypeError(
                        `A opção "${nomeCallback}" deve ser uma função ou nula.`,
                    );
                }
            },
        );

        if (
            !Array.isArray(
                configuracaoNormalizada.eventosTempoReal,
            )
        ) {
            throw new TypeError(
                'Os eventos de validação devem ser apresentados numa lista.',
            );
        }

        const eventosTempoReal = Array.from(
            new Set(
                configuracaoNormalizada
                    .eventosTempoReal
                    .map((evento) => {
                        if (
                            typeof evento !== 'string'
                            || evento.trim() === ''
                        ) {
                            throw new TypeError(
                                'Cada evento deve ter um nome válido.',
                            );
                        }

                        return evento.trim();
                    }),
            ),
        );

        return Object.freeze({
            regras: Object.freeze(
                regrasNormalizadas,
            ),

            mensagens: Object.freeze({
                ...configuracaoNormalizada.mensagens,
            }),

            aoSucesso:
                configuracaoNormalizada.aoSucesso,

            validadorPersonalizado:
                configuracaoNormalizada
                    .validadorPersonalizado,

            eventosTempoReal: Object.freeze(
                eventosTempoReal,
            ),
        });
    }

    /**
     * Valida uma lista de regras.
     *
     * @param {string} nomeCampo - Nome do campo.
     * @param {mixed} regras - Regras recebidas.
     *
     * @return {void}
     *
     * @throws {TypeError} Quando a configuração é inválida.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    validarListaRegras(
        nomeCampo,
        regras,
    ) {
        if (
            typeof nomeCampo !== 'string'
            || nomeCampo.trim() === ''
        ) {
            throw new TypeError(
                'Cada conjunto de regras deve possuir um nome de campo válido.',
            );
        }

        if (!Array.isArray(regras)) {
            throw new TypeError(
                `As regras do campo "${nomeCampo}" devem ser apresentadas numa lista.`,
            );
        }

        regras.forEach((regra) => {
            if (
                typeof regra !== 'string'
                && typeof regra !== 'function'
            ) {
                throw new TypeError(
                    `O campo "${nomeCampo}" contém uma regra inválida.`,
                );
            }
        });
    }

    /**
     * Determina se um elemento pode ser validado.
     *
     * @param {mixed} elemento - Elemento recebido.
     *
     * @return {boolean} Verdadeiro para campos suportados.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    eCampoValidavel(elemento) {
        return (
            elemento instanceof HTMLInputElement
            || elemento instanceof HTMLSelectElement
            || elemento instanceof HTMLTextAreaElement
        );
    }
}

export default ValidadorFormulario;
