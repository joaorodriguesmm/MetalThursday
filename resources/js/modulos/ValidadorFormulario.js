/**
 * Gere a validação de apoio dos formulários no navegador.
 *
 * A validação executada por este módulo melhora a experiência do utilizador,
 * mas não substitui as regras, autorizações e validações aplicadas pelo
 * servidor.
 *
 * @since 1.0.0
 */
class ValidadorFormulario {
    /**
     * Cria e inicializa o validador.
     *
     * @param {string|HTMLFormElement} formularioOuSeletor Formulário ou seletor.
     * @param {object} configuracao Configuração do validador.
     *
     * @throws {TypeError} Quando o formulário ou a configuração são inválidos.
     *
     * @since 1.0.0
     */
    constructor(formularioOuSeletor, configuracao = {}) {
        this.formulario = this.obterFormulario(formularioOuSeletor);
        this.configuracao = this.normalizarConfiguracao(configuracao);
        this.regras = this.configuracao.regras;
        this.mensagens = this.configuracao.mensagens;
        this.erros = new Map();
        this.tentouSubmeter = false;
        this.dependencias = this.criarMapaDependencias(this.regras);

        this.formulario.setAttribute('novalidate', '');

        this.formulario.addEventListener('submit', (evento) => {
            this.manipularSubmissao(evento);
        });

        this.formulario.addEventListener('reset', () => {
            this.manipularReposicao();
        });

        this.configuracao.eventosTempoReal.forEach((tipoEvento) => {
            this.formulario.addEventListener(tipoEvento, (evento) => {
                this.manipularInteracao(evento);
            });
        });
    }

    /**
     * Valida todos os campos configurados.
     *
     * @param {object} opcoes Opções da validação.
     * @param {boolean} [opcoes.focarPrimeiroErro=true]
     *     Indica se o primeiro campo inválido deve receber foco.
     *
     * @returns {boolean} Verdadeiro quando todos os campos são válidos.
     *
     * @since 1.0.0
     */
    validarTudo({ focarPrimeiroErro = true } = {}) {
        let formularioValido = true;

        Object.keys(this.regras).forEach((nomeCampo) => {
            const campoValido = this.validarCampoPorNome(
                nomeCampo,
                this.regras[nomeCampo],
                this.mensagens[nomeCampo] ?? {},
            );

            if (!campoValido) {
                formularioValido = false;
            }
        });

        if (!formularioValido && focarPrimeiroErro) {
            this.focarPrimeiroCampoInvalido();
        }

        return formularioValido;
    }

    /**
     * Valida um campo com as regras configuradas.
     *
     * @param {string|HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement}
     *     campoOuNome Campo ou respetivo nome.
     *
     * @returns {boolean} Verdadeiro quando o campo é válido.
     *
     * @since 1.0.0
     */
    validarCampo(campoOuNome) {
        const nomeCampo = this.obterNomeCampo(campoOuNome);

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
     *     campoOuNome Campo ou respetivo nome.
     * @param {Array<string|Function>} regras Regras temporárias.
     * @param {Record<string, string>} [mensagens={}] Mensagens temporárias.
     *
     * @returns {boolean} Verdadeiro quando o campo é válido.
     *
     * @since 1.0.0
     */
    validarCampoComRegras(campoOuNome, regras, mensagens = {}) {
        const nomeCampo = this.obterNomeCampo(campoOuNome);

        this.validarListaRegras(nomeCampo, regras);
        this.validarMensagensCampo(nomeCampo, mensagens);

        return this.validarCampoPorNome(
            nomeCampo,
            regras,
            mensagens,
        );
    }

    /**
     * Regista manualmente um erro num campo.
     *
     * @param {string} nomeCampo Nome do campo.
     * @param {string} mensagem Mensagem apresentada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    definirErro(nomeCampo, mensagem) {
        const nomeNormalizado = this.normalizarNomeCampo(nomeCampo);

        if (typeof mensagem !== 'string' || mensagem.trim() === '') {
            throw new TypeError(
                'A mensagem de erro deve ser uma cadeia de caracteres não vazia.',
            );
        }

        const mensagemNormalizada = mensagem.trim();
        const campos = this.obterCampos(nomeNormalizado, true);

        this.erros.set(nomeNormalizado, [mensagemNormalizada]);
        this.apresentarErros(campos, [mensagemNormalizada]);
    }

    /**
     * Limpa o erro de um campo.
     *
     * @param {string} nomeCampo Nome do campo.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    limparErroCampo(nomeCampo) {
        const nomeNormalizado = this.normalizarNomeCampo(nomeCampo);
        const campos = this.obterCampos(nomeNormalizado, true);

        this.erros.delete(nomeNormalizado);
        this.apresentarErros(campos, []);
    }

    /**
     * Obtém uma cópia dos erros atuais.
     *
     * @returns {Record<string, Array<string>>} Erros por campo.
     *
     * @since 2.0.0
     */
    obterErros() {
        return Object.fromEntries(
            Array.from(this.erros.entries(), ([nomeCampo, mensagens]) => [
                nomeCampo,
                [...mensagens],
            ]),
        );
    }

    /**
     * Valida um campo pelo respetivo nome.
     *
     * @param {string} nomeCampo Nome do campo.
     * @param {Array<string|Function>} regras Regras aplicáveis.
     * @param {Record<string, string>} mensagens Mensagens personalizadas.
     *
     * @returns {boolean} Verdadeiro quando o campo é válido.
     *
     * @since 2.0.0
     */
    validarCampoPorNome(nomeCampo, regras, mensagens) {
        const campos = this.obterCampos(nomeCampo);

        if (campos.length === 0) {
            throw new Error(
                `Não foi encontrado nenhum campo ativo com o nome "${nomeCampo}".`,
            );
        }

        const valor = this.obterValor(campos);
        const campoObrigatorio = regras.some((regra) => (
            typeof regra === 'string'
            && this.analisarRegra(regra).nome === 'obrigatorio'
        ));

        if (!campoObrigatorio && this.estaVazio(valor)) {
            this.erros.delete(nomeCampo);
            this.apresentarErros(campos, []);

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
            this.erros.set(nomeCampo, errosCampo);
        } else {
            this.erros.delete(nomeCampo);
        }

        this.apresentarErros(campos, errosCampo);

        return errosCampo.length === 0;
    }

    /**
     * Valida uma regra textual.
     *
     * @returns {string|null} Mensagem de erro ou nulo.
     *
     * @since 2.0.0
     */
    validarRegraTextual(nomeCampo, campos, valor, regra, mensagens) {
        const { nome, parametro } = this.analisarRegra(regra);
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
                    >= this.obterParametroNumerico(nome, parametro);
                break;

            case 'maximo':
                valido = this.obterComprimento(valor)
                    <= this.obterParametroNumerico(nome, parametro);
                break;

            case 'confirmado':
                valido = this.valoresIguais(
                    valor,
                    this.obterValor(
                        this.obterCamposObrigatorios(parametro, nome),
                    ),
                );
                break;

            case 'diferente':
                valido = !this.valoresIguais(
                    valor,
                    this.obterValor(
                        this.obterCamposObrigatorios(parametro, nome),
                    ),
                );
                break;

            case 'data':
                valido = this.criarData(valor) !== null;
                break;

            case 'posterior_ou_igual': {
                const outraData = this.criarData(
                    this.obterValor(
                        this.obterCamposObrigatorios(parametro, nome),
                    ),
                );
                const dataAtual = this.criarData(valor);

                valido = dataAtual === null
                    || outraData === null
                    || dataAtual.getTime() >= outraData.getTime();
                break;
            }

            case 'maiuscula':
                valido = typeof valor === 'string' && /\p{Lu}/u.test(valor);
                break;

            case 'minuscula':
                valido = typeof valor === 'string' && /\p{Ll}/u.test(valor);
                break;

            case 'numero':
                valido = typeof valor === 'string' && /\p{N}/u.test(valor);
                break;

            case 'simbolo':
                valido = typeof valor === 'string'
                    && /[^\p{L}\p{N}\s]/u.test(valor);
                break;

            case 'inteiro':
                valido = typeof valor === 'string'
                    && /^-?\d+$/u.test(valor.trim());
                break;

            default:
                throw new Error(
                    `A regra de validação "${nome}" não é reconhecida.`,
                );
        }

        return valido
            ? null
            : mensagens[nome] ?? this.criarMensagemPadrao(
                nomeCampo,
                campos,
                nome,
                parametro,
            );
    }

    /**
     * Executa uma regra personalizada.
     *
     * @returns {string|null} Mensagem de erro ou nulo.
     *
     * @since 2.0.0
     */
    executarRegraPersonalizada(regra, nomeCampo, campos, valor) {
        const resultado = regra({
            nomeCampo,
            campos: [...campos],
            valor,
            formulario: this.formulario,
            validador: this,
        });

        if (resultado instanceof Promise) {
            throw new TypeError(
                'Uma regra personalizada não pode ser assíncrona.',
            );
        }

        if (
            resultado === true
            || resultado === null
            || resultado === undefined
        ) {
            return null;
        }

        if (resultado === false) {
            return `O campo '${this.obterNomeAmigavel(
                campos,
                nomeCampo,
            )}' é inválido.`;
        }

        if (typeof resultado === 'string' && resultado.trim() !== '') {
            return resultado.trim();
        }

        throw new TypeError(
            'Uma regra personalizada deve devolver uma mensagem não vazia, um booleano ou nulo.',
        );
    }

    /**
     * Apresenta ou remove os erros de um conjunto de campos.
     *
     * @param {Array<HTMLElement>} campos Campos associados.
     * @param {Array<string>} erros Mensagens de erro.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    apresentarErros(campos, erros) {
        const possuiErro = erros.length > 0;

        campos.forEach((campo) => {
            this.definirEstadoCampo(campo, possuiErro);
        });

        this.obterElementosFeedback(campos).forEach((elementoFeedback) => {
            elementoFeedback.textContent = possuiErro ? erros[0] : '';
            elementoFeedback.classList.toggle('d-block', possuiErro);
            elementoFeedback.style.removeProperty('display');

            if (possuiErro) {
                elementoFeedback.removeAttribute('hidden');
            } else {
                elementoFeedback.setAttribute('hidden', '');
            }
        });
    }

    /**
     * Atualiza o estado inválido de um campo e do respetivo Tom Select.
     *
     * @since 2.0.0
     */
    definirEstadoCampo(campo, possuiErro) {
        campo.classList.toggle('is-invalid', possuiErro);

        if (possuiErro) {
            campo.setAttribute('aria-invalid', 'true');
        } else {
            campo.removeAttribute('aria-invalid');
        }

        if (!(campo instanceof HTMLSelectElement) || !campo.tomselect) {
            return;
        }

        const wrapper = campo.tomselect.wrapper;
        const controlo = campo.tomselect.control_input;

        if (wrapper instanceof HTMLElement) {
            wrapper.classList.toggle('is-invalid', possuiErro);
        }

        if (controlo instanceof HTMLElement) {
            if (possuiErro) {
                controlo.setAttribute('aria-invalid', 'true');
            } else {
                controlo.removeAttribute('aria-invalid');
            }
        }
    }

    /**
     * Obtém os elementos de feedback associados aos campos.
     *
     * @returns {Array<HTMLElement>} Elementos únicos encontrados.
     *
     * @since 2.0.0
     */
    obterElementosFeedback(campos) {
        return Array.from(new Set(
            campos
                .map((campo) => this.obterElementoFeedback(campo))
                .filter((elemento) => elemento instanceof HTMLElement),
        ));
    }

    /**
     * Obtém o feedback correspondente a um campo.
     *
     * @returns {HTMLElement|null} Elemento encontrado ou nulo.
     *
     * @since 2.0.0
     */
    obterElementoFeedback(campo) {
        const identificadores = (campo.getAttribute('aria-describedby') ?? '')
            .split(/\s+/u)
            .filter((identificador) => identificador !== '');

        for (const identificador of identificadores) {
            const elemento = document.getElementById(identificador);

            if (
                elemento instanceof HTMLElement
                && this.formulario.contains(elemento)
                && elemento.classList.contains('invalid-feedback')
            ) {
                return elemento;
            }
        }

        if (campo.id.trim() !== '') {
            const elemento = document.getElementById(`erro-${campo.id.trim()}`);

            if (
                elemento instanceof HTMLElement
                && this.formulario.contains(elemento)
                && elemento.classList.contains('invalid-feedback')
            ) {
                return elemento;
            }
        }

        const grupo = campo.closest('.grupo-campo-formulario');
        const feedback = grupo?.querySelector('.invalid-feedback');

        return feedback instanceof HTMLElement ? feedback : null;
    }

    /**
     * Processa a submissão do formulário.
     *
     * @since 1.0.0
     */
    manipularSubmissao(evento) {
        this.tentouSubmeter = true;

        const validacaoEstatica = this.validarTudo({
            focarPrimeiroErro: false,
        });
        let validacaoPersonalizada = true;

        if (
            validacaoEstatica
            && typeof this.configuracao.validadorPersonalizado === 'function'
        ) {
            const resultado = this.configuracao.validadorPersonalizado(
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

        if (!validacaoEstatica || !validacaoPersonalizada) {
            evento.preventDefault();
            this.focarPrimeiroCampoInvalido();
            return;
        }

        if (typeof this.configuracao.aoSucesso !== 'function') {
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
     * @since 1.0.0
     */
    manipularInteracao(evento) {
        if (!this.tentouSubmeter || !this.eCampoValidavel(evento.target)) {
            return;
        }

        const nomeCampo = evento.target.name.trim();

        if (nomeCampo === '') {
            return;
        }

        if (Object.hasOwn(this.regras, nomeCampo)) {
            this.validarCampo(nomeCampo);
        }

        this.validarCamposDependentes(nomeCampo);
    }

    /**
     * Revalida os campos cujo resultado depende do campo alterado.
     *
     * @since 2.0.0
     */
    validarCamposDependentes(nomeCampo) {
        const dependentes = this.dependencias.get(nomeCampo);

        dependentes?.forEach((nomeDependente) => {
            if (this.obterCampos(nomeDependente).length === 0) {
                this.limparErroCampo(nomeDependente);
                return;
            }

            this.validarCampo(nomeDependente);
        });
    }

    /**
     * Processa a reposição do formulário.
     *
     * @since 2.0.0
     */
    manipularReposicao() {
        window.setTimeout(() => {
            this.tentouSubmeter = false;
            this.limparTodosErros();
        }, 0);
    }

    /**
     * Limpa todos os estados de validação existentes no formulário.
     *
     * @since 2.0.0
     */
    limparTodosErros() {
        this.erros.clear();

        Array.from(this.formulario.elements).forEach((elemento) => {
            if (this.eCampoValidavel(elemento)) {
                this.definirEstadoCampo(elemento, false);
            }
        });

        this.formulario.querySelectorAll('.invalid-feedback')
            .forEach((elemento) => {
                if (!(elemento instanceof HTMLElement)) {
                    return;
                }

                elemento.textContent = '';
                elemento.classList.remove('d-block');
                elemento.style.removeProperty('display');
                elemento.setAttribute('hidden', '');
            });
    }

    /**
     * Foca o primeiro campo inválido de acordo com a ordem da DOM.
     *
     * @since 2.0.0
     */
    focarPrimeiroCampoInvalido() {
        const campo = Array.from(this.formulario.elements).find((elemento) => (
            this.eCampoValidavel(elemento)
            && elemento.getAttribute('aria-invalid') === 'true'
            && this.eCampoFocavel(elemento)
        ));

        if (this.eCampoValidavel(campo)) {
            this.focarCampo(campo);
        }
    }

    /**
     * Determina se um campo pode receber foco de forma útil.
     *
     * @returns {boolean} Verdadeiro quando o campo é focável.
     *
     * @since 2.0.0
     */
    eCampoFocavel(campo) {
        return !campo.disabled
            && !(campo instanceof HTMLInputElement && campo.type === 'hidden')
            && campo.closest('[hidden], .d-none') === null;
    }

    /**
     * Coloca o foco num campo inválido.
     *
     * @since 2.0.0
     */
    focarCampo(campo) {
        if (
            campo instanceof HTMLSelectElement
            && campo.tomselect
            && typeof campo.tomselect.focus === 'function'
        ) {
            campo.tomselect.focus();
            return;
        }

        campo.focus();
    }

    /**
     * Obtém os campos associados a um nome.
     *
     * @param {string} nomeCampo Nome procurado.
     * @param {boolean} [incluirDesativados=false]
     *     Indica se os campos desativados devem ser incluídos.
     *
     * @returns {Array<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>}
     *     Campos encontrados.
     *
     * @since 2.0.0
     */
    obterCampos(nomeCampo, incluirDesativados = false) {
        return Array.from(this.formulario.elements).filter((elemento) => (
            this.eCampoValidavel(elemento)
            && elemento.name === nomeCampo
            && (incluirDesativados || !elemento.disabled)
        ));
    }

    /**
     * Obtém obrigatoriamente os campos associados.
     *
     * @since 2.0.0
     */
    obterCamposObrigatorios(nomeCampo, regra) {
        if (typeof nomeCampo !== 'string' || nomeCampo.trim() === '') {
            throw new Error(
                `A regra "${regra}" exige o nome de outro campo.`,
            );
        }

        const nomeNormalizado = nomeCampo.trim();
        const campos = this.obterCampos(nomeNormalizado);

        if (campos.length === 0) {
            throw new Error(
                `A regra "${regra}" referencia o campo inexistente ou desativado "${nomeNormalizado}".`,
            );
        }

        return campos;
    }

    /**
     * Obtém o valor de um conjunto de campos.
     *
     * @since 2.0.0
     */
    obterValor(campos) {
        const primeiroCampo = campos[0];

        if (primeiroCampo instanceof HTMLInputElement) {
            if (primeiroCampo.type === 'checkbox') {
                return campos
                    .filter((campo) => (
                        campo instanceof HTMLInputElement && campo.checked
                    ))
                    .map((campo) => campo.value);
            }

            if (primeiroCampo.type === 'radio') {
                return campos.find((campo) => (
                    campo instanceof HTMLInputElement && campo.checked
                ))?.value ?? '';
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

        return primeiroCampo?.value ?? '';
    }

    /**
     * Determina se um valor está vazio.
     *
     * @since 2.0.0
     */
    estaVazio(valor) {
        if (valor === null || valor === undefined) {
            return true;
        }

        if (Array.isArray(valor)) {
            return valor.length === 0;
        }

        return typeof valor === 'string' ? valor.trim() === '' : false;
    }

    /**
     * Obtém o comprimento de um valor.
     *
     * @since 2.0.0
     */
    obterComprimento(valor) {
        if (typeof valor === 'string' || Array.isArray(valor)) {
            return valor.length;
        }

        return String(valor ?? '').length;
    }

    /**
     * Valida um endereço de e-mail.
     *
     * @since 2.0.0
     */
    validarEmail(valor) {
        return typeof valor === 'string'
            && /^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(valor.trim());
    }

    /**
     * Cria uma data válida.
     *
     * @since 2.0.0
     */
    criarData(valor) {
        if (typeof valor !== 'string' || valor.trim() === '') {
            return null;
        }

        const valorNormalizado = valor.trim();
        const correspondencia = valorNormalizado.match(
            /^(\d{4})-(\d{2})-(\d{2})$/u,
        );

        if (correspondencia !== null) {
            const ano = Number(correspondencia[1]);
            const mes = Number(correspondencia[2]);
            const dia = Number(correspondencia[3]);
            const data = new Date(Date.UTC(ano, mes - 1, dia));

            if (
                data.getUTCFullYear() !== ano
                || data.getUTCMonth() !== mes - 1
                || data.getUTCDate() !== dia
            ) {
                return null;
            }

            return data;
        }

        const instante = Date.parse(valorNormalizado);

        return Number.isNaN(instante) ? null : new Date(instante);
    }

    /**
     * Determina se dois valores são iguais.
     *
     * @since 2.0.0
     */
    valoresIguais(primeiroValor, segundoValor) {
        if (Array.isArray(primeiroValor) || Array.isArray(segundoValor)) {
            return JSON.stringify(primeiroValor)
                === JSON.stringify(segundoValor);
        }

        return String(primeiroValor ?? '') === String(segundoValor ?? '');
    }

    /**
     * Analisa uma regra textual.
     *
     * @since 2.0.0
     */
    analisarRegra(regra) {
        if (typeof regra !== 'string' || regra.trim() === '') {
            throw new TypeError(
                'Cada regra deve ser uma cadeia de caracteres não vazia.',
            );
        }

        const [nomeRecebido, ...partesParametro] = regra.trim().split(':');
        const nome = nomeRecebido.trim();

        if (nome === '') {
            throw new TypeError('Cada regra deve possuir um nome válido.');
        }

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
     * @since 2.0.0
     */
    obterParametroNumerico(regra, parametro) {
        if (typeof parametro !== 'string' || parametro.trim() === '') {
            throw new Error(
                `A regra "${regra}" exige um número inteiro não negativo.`,
            );
        }

        const numero = Number(parametro);

        if (!Number.isInteger(numero) || numero < 0) {
            throw new Error(
                `A regra "${regra}" exige um número inteiro não negativo.`,
            );
        }

        return numero;
    }

    /**
     * Cria a mensagem padrão de uma regra.
     *
     * @since 2.0.0
     */
    criarMensagemPadrao(nomeCampo, campos, regra, parametro) {
        const nomeAmigavel = this.obterNomeAmigavel(campos, nomeCampo);
        const mensagens = {
            obrigatorio: `O campo '${nomeAmigavel}' é obrigatório.`,
            email: 'Por favor, insere um endereço de e-mail válido.',
            minimo:
                `O campo '${nomeAmigavel}' deve ter, pelo menos, ${parametro} caracteres.`,
            maximo:
                `O campo '${nomeAmigavel}' não pode ter mais de ${parametro} caracteres.`,
            confirmado: 'A confirmação não coincide.',
            diferente: `O campo '${nomeAmigavel}' deve ser diferente.`,
            data: `O campo '${nomeAmigavel}' deve conter uma data válida.`,
            posterior_ou_igual:
                `O campo '${nomeAmigavel}' deve conter uma data igual ou posterior a '${this.obterNomeAmigavel(
                    this.obterCamposObrigatorios(parametro, regra),
                    parametro ?? '',
                )}'.`,
            maiuscula:
                `O campo '${nomeAmigavel}' deve conter uma letra maiúscula.`,
            minuscula:
                `O campo '${nomeAmigavel}' deve conter uma letra minúscula.`,
            numero: `O campo '${nomeAmigavel}' deve conter um número.`,
            simbolo: `O campo '${nomeAmigavel}' deve conter um símbolo.`,
            inteiro: `O campo '${nomeAmigavel}' deve conter um número inteiro.`,
        };

        return mensagens[regra] ?? `O campo '${nomeAmigavel}' é inválido.`;
    }

    /**
     * Obtém o nome legível de um campo.
     *
     * @since 1.0.0
     */
    obterNomeAmigavel(campos, nomeAlternativo) {
        const primeiroCampo = campos[0];

        if (
            !this.eCampoValidavel(primeiroCampo)
            || !primeiroCampo.labels
            || primeiroCampo.labels.length === 0
        ) {
            return nomeAlternativo;
        }

        const etiqueta = primeiroCampo.labels[0].cloneNode(true);

        etiqueta.querySelectorAll('.text-danger, [aria-hidden="true"]')
            .forEach((elemento) => {
                elemento.remove();
            });

        return etiqueta.textContent?.trim() || nomeAlternativo;
    }

    /**
     * Obtém o nome técnico de um campo.
     *
     * @since 2.0.0
     */
    obterNomeCampo(campoOuNome) {
        if (typeof campoOuNome === 'string') {
            return this.normalizarNomeCampo(campoOuNome);
        }

        if (
            this.eCampoValidavel(campoOuNome)
            && campoOuNome.name.trim() !== ''
        ) {
            return campoOuNome.name.trim();
        }

        throw new TypeError('Não foi possível determinar o nome do campo.');
    }

    /**
     * Normaliza um nome de campo.
     *
     * @since 2.0.0
     */
    normalizarNomeCampo(nomeCampo) {
        if (typeof nomeCampo !== 'string' || nomeCampo.trim() === '') {
            throw new TypeError(
                'O nome do campo deve ser uma cadeia de caracteres não vazia.',
            );
        }

        return nomeCampo.trim();
    }

    /**
     * Obtém o formulário configurado.
     *
     * @since 2.0.0
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

        const seletor = formularioOuSeletor.trim();
        let formulario;

        try {
            formulario = document.querySelector(seletor);
        } catch {
            throw new TypeError(`O seletor CSS "${seletor}" é inválido.`);
        }

        if (!(formulario instanceof HTMLFormElement)) {
            throw new TypeError(
                `Não foi encontrado um formulário para o seletor "${seletor}".`,
            );
        }

        return formulario;
    }

    /**
     * Normaliza a configuração do validador.
     *
     * @since 2.0.0
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

        const normalizada = {
            regras: {},
            mensagens: {},
            aoSucesso: null,
            validadorPersonalizado: null,
            eventosTempoReal: ['input', 'change', 'focusout'],
            ...configuracao,
        };

        if (
            normalizada.regras === null
            || typeof normalizada.regras !== 'object'
            || Array.isArray(normalizada.regras)
        ) {
            throw new TypeError('As regras devem ser apresentadas num objeto.');
        }

        const regrasNormalizadas = {};

        Object.entries(normalizada.regras).forEach(([nomeCampo, regras]) => {
            const nomeNormalizado = this.normalizarNomeCampo(nomeCampo);

            if (Object.hasOwn(regrasNormalizadas, nomeNormalizado)) {
                throw new TypeError(
                    `O campo "${nomeNormalizado}" possui regras duplicadas.`,
                );
            }

            this.validarListaRegras(nomeNormalizado, regras);
            regrasNormalizadas[nomeNormalizado] = Object.freeze([...regras]);
        });

        if (
            normalizada.mensagens === null
            || typeof normalizada.mensagens !== 'object'
            || Array.isArray(normalizada.mensagens)
        ) {
            throw new TypeError(
                'As mensagens devem ser apresentadas num objeto.',
            );
        }

        const mensagensNormalizadas = {};

        Object.entries(normalizada.mensagens)
            .forEach(([nomeCampo, mensagensCampo]) => {
                const nomeNormalizado = this.normalizarNomeCampo(nomeCampo);
                this.validarMensagensCampo(nomeNormalizado, mensagensCampo);
                mensagensNormalizadas[nomeNormalizado] = Object.freeze({
                    ...mensagensCampo,
                });
            });

        ['aoSucesso', 'validadorPersonalizado'].forEach((nomeCallback) => {
            const callback = normalizada[nomeCallback];

            if (callback !== null && typeof callback !== 'function') {
                throw new TypeError(
                    `A opção "${nomeCallback}" deve ser uma função ou nula.`,
                );
            }
        });

        if (!Array.isArray(normalizada.eventosTempoReal)) {
            throw new TypeError(
                'Os eventos de validação devem ser apresentados numa lista.',
            );
        }

        const eventosTempoReal = Array.from(new Set(
            normalizada.eventosTempoReal.map((evento) => {
                if (typeof evento !== 'string' || evento.trim() === '') {
                    throw new TypeError('Cada evento deve ter um nome válido.');
                }

                return evento.trim();
            }),
        ));

        return Object.freeze({
            regras: Object.freeze(regrasNormalizadas),
            mensagens: Object.freeze(mensagensNormalizadas),
            aoSucesso: normalizada.aoSucesso,
            validadorPersonalizado: normalizada.validadorPersonalizado,
            eventosTempoReal: Object.freeze(eventosTempoReal),
        });
    }

    /**
     * Valida uma lista de regras.
     *
     * @since 2.0.0
     */
    validarListaRegras(nomeCampo, regras) {
        if (!Array.isArray(regras)) {
            throw new TypeError(
                `As regras do campo "${nomeCampo}" devem ser apresentadas numa lista.`,
            );
        }

        regras.forEach((regra) => {
            if (
                typeof regra !== 'function'
                && (typeof regra !== 'string' || regra.trim() === '')
            ) {
                throw new TypeError(
                    `O campo "${nomeCampo}" contém uma regra inválida.`,
                );
            }
        });
    }

    /**
     * Valida as mensagens de um campo.
     *
     * @since 2.0.0
     */
    validarMensagensCampo(nomeCampo, mensagens) {
        if (
            mensagens === null
            || typeof mensagens !== 'object'
            || Array.isArray(mensagens)
        ) {
            throw new TypeError(
                `As mensagens do campo "${nomeCampo}" devem ser apresentadas num objeto.`,
            );
        }

        Object.entries(mensagens).forEach(([nomeRegra, mensagem]) => {
            if (
                nomeRegra.trim() === ''
                || typeof mensagem !== 'string'
                || mensagem.trim() === ''
            ) {
                throw new TypeError(
                    `O campo "${nomeCampo}" contém uma mensagem de validação inválida.`,
                );
            }
        });
    }

    /**
     * Cria o mapa das dependências entre campos configurados.
     *
     * @since 2.0.0
     */
    criarMapaDependencias(regras) {
        const dependencias = new Map();
        const regrasDependentes = new Set([
            'confirmado',
            'diferente',
            'posterior_ou_igual',
        ]);

        Object.entries(regras).forEach(([nomeCampo, regrasCampo]) => {
            regrasCampo.forEach((regra) => {
                if (typeof regra !== 'string') {
                    return;
                }

                const { nome, parametro } = this.analisarRegra(regra);

                if (
                    !regrasDependentes.has(nome)
                    || typeof parametro !== 'string'
                    || parametro === ''
                ) {
                    return;
                }

                if (!dependencias.has(parametro)) {
                    dependencias.set(parametro, new Set());
                }

                dependencias.get(parametro).add(nomeCampo);
            });
        });

        return dependencias;
    }

    /**
     * Determina se um elemento pode ser validado.
     *
     * @since 2.0.0
     */
    eCampoValidavel(elemento) {
        return elemento instanceof HTMLInputElement
            || elemento instanceof HTMLSelectElement
            || elemento instanceof HTMLTextAreaElement;
    }
}

export default ValidadorFormulario;
