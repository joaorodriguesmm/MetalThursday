/**
 * Gere a criação e a remoção dinâmica de filtros de pesquisa.
 *
 * @since 1.0.0
 */
class GestorFiltrosDinamicos {
    /**
     * Cria e inicia um gestor de filtros dinâmicos.
     *
     * Cada filtro disponível deve possuir:
     *
     * - `parametro`: nome utilizado no endereço;
     * - `tipo`: `selecao`, `data` ou `sim_nao`;
     * - `rotulo`: texto apresentado ao utilizador;
     * - `chaveDados`: chave dos dados da seleção, quando aplicável.
     *
     * @param {object} opcoes Opções de configuração.
     * @param {string} opcoes.seletorListaFiltros
     *     Seletor da lista utilizada para adicionar filtros.
     * @param {string} opcoes.seletorContentorFiltros
     *     Seletor do contentor dos filtros ativos.
     * @param {Record<string, Array<object>>} opcoes.dadosFiltros
     *     Dados utilizados pelos campos de seleção.
     * @param {Record<string, object>} opcoes.filtrosDisponiveis
     *     Configuração dos filtros disponíveis.
     *
     * @throws {TypeError} Quando as opções ou os elementos são inválidos.
     *
     * @since 1.0.0
     */
    constructor({
        seletorListaFiltros,
        seletorContentorFiltros,
        dadosFiltros = {},
        filtrosDisponiveis = {},
    } = {}) {
        this.listaAdicionarFiltro = this.obterElemento(
            seletorListaFiltros,
            HTMLSelectElement,
            'a lista de filtros',
        );

        this.areaFiltrosAtivos = this.obterElemento(
            seletorContentorFiltros,
            HTMLElement,
            'o contentor de filtros',
        );

        this.validarObjeto(
            dadosFiltros,
            'Os dados dos filtros devem ser um objeto.',
        );

        this.dadosFiltros = dadosFiltros;

        this.filtrosDisponiveis =
            this.normalizarFiltrosDisponiveis(
                filtrosDisponiveis,
            );

        /**
         * Componentes atualmente apresentados, indexados pelo nome do campo.
         *
         * @type {Map<string, HTMLDivElement>}
         *
         * @since 2.0.0
         */
        this.componentesAtivos = new Map();

        /**
         * Instâncias Tom Select associadas aos filtros ativos.
         *
         * @type {Map<string, object>}
         *
         * @since 2.0.0
         */
        this.instanciasTomSelect = new Map();

        /**
         * Promessa partilhada do carregamento assíncrono do Tom Select.
         *
         * @type {Promise<Function>|null}
         *
         * @since 2.0.0
         */
        this.promessaTomSelect = null;

        this.inicializarAPartirDoUrl();

        this.listaAdicionarFiltro.addEventListener(
            'change',
            (evento) => this.tratarAdicaoFiltro(evento),
        );

        this.areaFiltrosAtivos.addEventListener(
            'click',
            (evento) => this.tratarRemocaoFiltro(evento),
        );
    }

    /**
     * Cria os filtros presentes nos parâmetros do endereço atual.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    inicializarAPartirDoUrl() {
        const parametrosUrl = new URLSearchParams(
            window.location.search,
        );

        Object.entries(this.filtrosDisponiveis).forEach(
            ([chaveFiltro, configuracao]) => {
                const nomeParametro =
                    `filtro_${configuracao.parametro}`;

                const valores = parametrosUrl.getAll(
                    nomeParametro,
                );

                if (valores.length === 0) {
                    return;
                }

                const valorAtual =
                    valores[valores.length - 1];

                if (
                    valorAtual === ''
                    || !this.eValorFiltroValido(
                        configuracao,
                        valorAtual,
                    )
                ) {
                    return;
                }

                this.renderizar(
                    chaveFiltro,
                    valorAtual,
                );
            },
        );
    }

    /**
     * Trata a seleção de um novo filtro.
     *
     * @param {Event} evento Evento de alteração.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    tratarAdicaoFiltro(evento) {
        const lista = evento.currentTarget;

        if (!(lista instanceof HTMLSelectElement)) {
            return;
        }

        const chaveFiltro = lista.value.trim();

        if (chaveFiltro === '') {
            return;
        }

        this.renderizar(chaveFiltro);

        lista.value = '';
    }

    /**
     * Trata o pedido de remoção de um filtro.
     *
     * @param {MouseEvent} evento Evento de clique.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    tratarRemocaoFiltro(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botaoRemover = evento.target.closest(
            '[data-remover-filtro]',
        );

        if (!(botaoRemover instanceof HTMLButtonElement)) {
            return;
        }

        const nomeFiltro =
            botaoRemover.dataset.removerFiltro?.trim();

        if (!nomeFiltro) {
            return;
        }

        this.removerFiltro(nomeFiltro);
    }

    /**
     * Renderiza um novo filtro.
     *
     * @param {string} chaveFiltro Chave da configuração do filtro.
     * @param {string} valorAtual Valor atual do filtro.
     *
     * @returns {boolean} Indica se o filtro foi criado.
     *
     * @since 1.0.0
     */
    renderizar(
        chaveFiltro,
        valorAtual = '',
    ) {
        if (typeof chaveFiltro !== 'string') {
            return false;
        }

        const configuracao =
            this.filtrosDisponiveis[chaveFiltro];

        if (!configuracao) {
            return false;
        }

        const nomeFiltro =
            `filtro_${configuracao.parametro}`;

        if (this.componentesAtivos.has(nomeFiltro)) {
            return false;
        }

        const identificadorCampo =
            this.criarIdentificadorCampo(nomeFiltro);

        const campo = this.criarCampoFiltro(
            configuracao,
            nomeFiltro,
            identificadorCampo,
            String(valorAtual),
        );

        const componente = this.criarComponenteFiltro(
            configuracao.rotulo,
            nomeFiltro,
            identificadorCampo,
            campo,
        );

        this.componentesAtivos.set(
            nomeFiltro,
            componente,
        );

        this.areaFiltrosAtivos.append(componente);

        if (
            configuracao.tipo === 'selecao'
            && campo instanceof HTMLSelectElement
        ) {
            this.inicializarTomSelect(
                nomeFiltro,
                campo,
            );
        }

        return true;
    }

    /**
     * Remove um filtro ativo.
     *
     * @param {string} nomeFiltro Nome HTML do filtro.
     *
     * @returns {boolean} Indica se o filtro foi removido.
     *
     * @since 1.0.0
     */
    removerFiltro(nomeFiltro) {
        const componente =
            this.componentesAtivos.get(nomeFiltro);

        if (!componente) {
            return false;
        }

        const instanciaTomSelect =
            this.instanciasTomSelect.get(nomeFiltro);

        if (instanciaTomSelect) {
            instanciaTomSelect.destroy();

            this.instanciasTomSelect.delete(
                nomeFiltro,
            );
        }

        componente.remove();

        this.componentesAtivos.delete(nomeFiltro);

        return true;
    }

    /**
     * Cria o campo adequado ao tipo de filtro.
     *
     * @param {object} configuracao Configuração do filtro.
     * @param {string} nomeFiltro Nome HTML do campo.
     * @param {string} identificadorCampo Identificador HTML do campo.
     * @param {string} valorAtual Valor atual do filtro.
     *
     * @returns {HTMLInputElement|HTMLSelectElement} Campo criado.
     *
     * @throws {Error} Quando é recebido um tipo de filtro desconhecido.
     *
     * @since 2.0.0
     */
    criarCampoFiltro(
        configuracao,
        nomeFiltro,
        identificadorCampo,
        valorAtual,
    ) {
        switch (configuracao.tipo) {
            case 'selecao':
                return this.criarCampoSelecao(
                    configuracao,
                    nomeFiltro,
                    identificadorCampo,
                    valorAtual,
                );

            case 'data':
                return this.criarCampoData(
                    nomeFiltro,
                    identificadorCampo,
                    valorAtual,
                );

            case 'sim_nao':
                return this.criarCampoSimNao(
                    nomeFiltro,
                    identificadorCampo,
                    valorAtual,
                );

            default:
                throw new Error(
                    `O tipo de filtro "${configuracao.tipo}" não é suportado.`,
                );
        }
    }

    /**
     * Cria um campo de seleção.
     *
     * @param {object} configuracao Configuração do filtro.
     * @param {string} nomeFiltro Nome HTML do campo.
     * @param {string} identificadorCampo Identificador HTML do campo.
     * @param {string} valorAtual Valor atual do filtro.
     *
     * @returns {HTMLSelectElement} Campo criado.
     *
     * @since 2.0.0
     */
    criarCampoSelecao(
        configuracao,
        nomeFiltro,
        identificadorCampo,
        valorAtual,
    ) {
        const selecao = document.createElement('select');

        selecao.id = identificadorCampo;
        selecao.name = nomeFiltro;
        selecao.className =
            'form-select bg-secondary text-white border-secondary';

        const opcaoInicial =
            document.createElement('option');

        opcaoInicial.value = '';
        opcaoInicial.textContent = 'Seleciona uma opção';
        opcaoInicial.selected = valorAtual === '';

        selecao.append(opcaoInicial);

        this.obterOpcoesFiltro(configuracao)
            .forEach((opcao) => {
                if (!this.eOpcaoFiltroValida(opcao)) {
                    return;
                }

                const elementoOpcao =
                    document.createElement('option');

                elementoOpcao.value =
                    String(opcao.identificador);

                elementoOpcao.textContent =
                    opcao.nome.trim();

                elementoOpcao.selected =
                    elementoOpcao.value === valorAtual;

                selecao.append(elementoOpcao);
            });

        return selecao;
    }

    /**
     * Cria um campo de data.
     *
     * @param {string} nomeFiltro Nome HTML do campo.
     * @param {string} identificadorCampo Identificador HTML do campo.
     * @param {string} valorAtual Valor atual do filtro.
     *
     * @returns {HTMLInputElement} Campo criado.
     *
     * @since 2.0.0
     */
    criarCampoData(
        nomeFiltro,
        identificadorCampo,
        valorAtual,
    ) {
        const campo = document.createElement('input');

        campo.id = identificadorCampo;
        campo.type = 'date';
        campo.name = nomeFiltro;
        campo.value = valorAtual;
        campo.className =
            'form-control bg-dark text-white border-secondary';

        return campo;
    }

    /**
     * Cria um campo de escolha entre sim e não.
     *
     * @param {string} nomeFiltro Nome HTML do campo.
     * @param {string} identificadorCampo Identificador HTML do campo.
     * @param {string} valorAtual Valor atual do filtro.
     *
     * @returns {HTMLSelectElement} Campo criado.
     *
     * @since 2.0.0
     */
    criarCampoSimNao(
        nomeFiltro,
        identificadorCampo,
        valorAtual,
    ) {
        const selecao = document.createElement('select');

        selecao.id = identificadorCampo;
        selecao.name = nomeFiltro;
        selecao.className =
            'form-select bg-secondary text-white border-secondary';

        const valorSelecionado =
            ['sim', 'nao'].includes(valorAtual)
                ? valorAtual
                : 'sim';

        [
            {
                valor: 'sim',
                rotulo: 'Sim',
            },
            {
                valor: 'nao',
                rotulo: 'Não',
            },
        ].forEach(({ valor, rotulo }) => {
            const opcao = document.createElement('option');

            opcao.value = valor;
            opcao.textContent = rotulo;
            opcao.selected = valor === valorSelecionado;

            selecao.append(opcao);
        });

        return selecao;
    }

    /**
     * Cria o componente visual de um filtro.
     *
     * @param {string} rotulo Texto apresentado.
     * @param {string} nomeFiltro Nome HTML do filtro.
     * @param {string} identificadorCampo Identificador HTML do campo.
     * @param {HTMLElement} campo Campo do filtro.
     *
     * @returns {HTMLDivElement} Componente criado.
     *
     * @since 2.0.0
     */
    criarComponenteFiltro(
        rotulo,
        nomeFiltro,
        identificadorCampo,
        campo,
    ) {
        const coluna = document.createElement('div');

        coluna.className = 'col-md-4 mb-3';

        const cartao = document.createElement('div');

        cartao.className = 'card bg-secondary h-100';

        const corpo = document.createElement('div');

        corpo.className = 'card-body p-2';

        const cabecalho = document.createElement('div');

        cabecalho.className =
            'd-flex justify-content-between align-items-center mb-2';

        const etiqueta = document.createElement('label');

        etiqueta.className = 'small text-white';
        etiqueta.htmlFor = identificadorCampo;
        etiqueta.textContent = rotulo;

        const botaoRemover =
            document.createElement('button');

        botaoRemover.type = 'button';
        botaoRemover.className =
            'btn-close btn-close-white';

        botaoRemover.dataset.removerFiltro =
            nomeFiltro;

        botaoRemover.setAttribute(
            'aria-label',
            `Remover o filtro ${rotulo}`,
        );

        cabecalho.append(
            etiqueta,
            botaoRemover,
        );

        corpo.append(
            cabecalho,
            campo,
        );

        cartao.append(corpo);
        coluna.append(cartao);

        return coluna;
    }

    /**
     * Obtém as opções associadas a um filtro de seleção.
     *
     * @param {object} configuracao Configuração do filtro.
     *
     * @returns {Array<object>} Opções disponíveis.
     *
     * @since 2.0.0
     */
    obterOpcoesFiltro(configuracao) {
        const opcoes = this.dadosFiltros[
            configuracao.chaveDados
        ];

        return Array.isArray(opcoes)
            ? opcoes
            : [];
    }

    /**
     * Inicializa o Tom Select num campo de seleção.
     *
     * O módulo é carregado apenas quando existe efetivamente um filtro de
     * seleção. Se o carregamento falhar, o campo nativo permanece funcional.
     *
     * @param {string} nomeFiltro Nome HTML do filtro.
     * @param {HTMLSelectElement} campo Campo de seleção.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    async inicializarTomSelect(
        nomeFiltro,
        campo,
    ) {
        let TomSelect;

        try {
            TomSelect = await this.carregarTomSelect();
        } catch {
            return;
        }

        const componente =
            this.componentesAtivos.get(nomeFiltro);

        if (
            !componente
            || !campo.isConnected
            || !componente.contains(campo)
        ) {
            return;
        }

        const instancia = new TomSelect(
            campo,
            {
                allowEmptyOption: true,
                create: false,
            },
        );

        this.instanciasTomSelect.set(
            nomeFiltro,
            instancia,
        );
    }

    /**
     * Carrega a versão base do Tom Select.
     *
     * @returns {Promise<Function>} Construtor do Tom Select.
     *
     * @since 2.0.0
     */
    carregarTomSelect() {
        if (this.promessaTomSelect === null) {
            this.promessaTomSelect = import(
                'tom-select/base'
            )
                .then((modulo) => modulo.default)
                .catch((erro) => {
                    this.promessaTomSelect = null;

                    throw erro;
                });
        }

        return this.promessaTomSelect;
    }

    /**
     * Verifica se um valor é válido para um filtro configurado.
     *
     * @param {object} configuracao Configuração do filtro.
     * @param {string} valor Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando o valor é válido.
     *
     * @since 2.0.0
     */
    eValorFiltroValido(
        configuracao,
        valor,
    ) {
        switch (configuracao.tipo) {
            case 'selecao':
                return this.obterOpcoesFiltro(
                    configuracao,
                ).some(
                    (opcao) =>
                        this.eOpcaoFiltroValida(opcao)
                        && String(opcao.identificador)
                            === valor,
                );

            case 'data':
                return this.eDataValida(valor);

            case 'sim_nao':
                return ['sim', 'nao'].includes(valor);

            default:
                return false;
        }
    }

    /**
     * Verifica se uma opção de um filtro de seleção é válida.
     *
     * @param {unknown} opcao Opção recebida.
     *
     * @returns {boolean} Verdadeiro quando a opção é válida.
     *
     * @since 2.0.0
     */
    eOpcaoFiltroValida(opcao) {
        return this.eObjeto(opcao)
            && Number.isInteger(opcao.identificador)
            && opcao.identificador > 0
            && typeof opcao.nome === 'string'
            && opcao.nome.trim() !== '';
    }

    /**
     * Verifica se um valor respeita o formato de um campo HTML de data.
     *
     * @param {string} valor Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando a data é válida.
     *
     * @since 2.0.0
     */
    eDataValida(valor) {
        const campo = document.createElement('input');

        campo.type = 'date';
        campo.value = valor;

        return campo.value === valor;
    }

    /**
     * Cria um identificador HTML seguro.
     *
     * @param {string} nomeFiltro Nome HTML do filtro.
     *
     * @returns {string} Identificador normalizado.
     *
     * @since 2.0.0
     */
    criarIdentificadorCampo(nomeFiltro) {
        return nomeFiltro.replace(
            /[^a-zA-Z0-9_-]/g,
            '-',
        );
    }

    /**
     * Valida e normaliza a configuração dos filtros disponíveis.
     *
     * @param {unknown} filtrosDisponiveis Configuração recebida.
     *
     * @returns {Readonly<Record<string, object>>}
     *     Configuração normalizada.
     *
     * @throws {TypeError} Quando a configuração é inválida.
     *
     * @since 2.0.0
     */
    normalizarFiltrosDisponiveis(
        filtrosDisponiveis,
    ) {
        this.validarObjeto(
            filtrosDisponiveis,
            'A configuração dos filtros deve ser um objeto.',
        );

        const filtrosNormalizados = {};
        const parametros = new Set();

        Object.entries(filtrosDisponiveis).forEach(
            ([chaveFiltro, configuracao]) => {
                if (
                    chaveFiltro.trim() === ''
                    || !this.eObjeto(configuracao)
                    || typeof configuracao.parametro
                        !== 'string'
                    || typeof configuracao.tipo
                        !== 'string'
                    || typeof configuracao.rotulo
                        !== 'string'
                ) {
                    throw new TypeError(
                        `A configuração do filtro "${chaveFiltro}" é inválida.`,
                    );
                }

                const parametro =
                    configuracao.parametro.trim();

                const tipo =
                    configuracao.tipo.trim();

                const rotulo =
                    configuracao.rotulo.trim();

                if (
                    parametro === ''
                    || rotulo === ''
                    || ![
                        'selecao',
                        'data',
                        'sim_nao',
                    ].includes(tipo)
                ) {
                    throw new TypeError(
                        `A configuração do filtro "${chaveFiltro}" é inválida.`,
                    );
                }

                if (parametros.has(parametro)) {
                    throw new TypeError(
                        `O parâmetro de filtro "${parametro}" está configurado mais do que uma vez.`,
                    );
                }

                parametros.add(parametro);

                let chaveDados = null;

                if (tipo === 'selecao') {
                    if (
                        typeof configuracao.chaveDados
                            !== 'string'
                        || configuracao.chaveDados.trim()
                            === ''
                    ) {
                        throw new TypeError(
                            `O filtro de seleção "${chaveFiltro}" não possui uma chave de dados válida.`,
                        );
                    }

                    chaveDados =
                        configuracao.chaveDados.trim();

                    if (
                        !Array.isArray(
                            this.dadosFiltros[chaveDados],
                        )
                    ) {
                        throw new TypeError(
                            `Não existem dados válidos para o filtro "${chaveFiltro}".`,
                        );
                    }
                }

                filtrosNormalizados[chaveFiltro] =
                    Object.freeze({
                        parametro,
                        tipo,
                        rotulo,
                        chaveDados,
                    });
            },
        );

        return Object.freeze(filtrosNormalizados);
    }

    /**
     * Verifica se um valor é um objeto não nulo.
     *
     * @param {unknown} valor Valor a verificar.
     *
     * @returns {boolean} Verdadeiro quando o valor é um objeto simples.
     *
     * @since 2.0.0
     */
    eObjeto(valor) {
        return typeof valor === 'object'
            && valor !== null
            && !Array.isArray(valor);
    }

    /**
     * Valida um valor que deve ser um objeto.
     *
     * @param {unknown} valor Valor a validar.
     * @param {string} mensagem Mensagem de erro.
     *
     * @returns {void}
     *
     * @throws {TypeError} Quando o valor é inválido.
     *
     * @since 2.0.0
     */
    validarObjeto(
        valor,
        mensagem,
    ) {
        if (!this.eObjeto(valor)) {
            throw new TypeError(mensagem);
        }
    }

    /**
     * Obtém e valida um elemento através de um seletor CSS.
     *
     * @param {unknown} seletor Seletor CSS.
     * @param {Function} tipoElemento Tipo de elemento esperado.
     * @param {string} descricaoElemento Descrição do elemento esperado.
     *
     * @returns {Element} Elemento encontrado.
     *
     * @throws {TypeError} Quando o seletor ou o elemento são inválidos.
     *
     * @since 2.0.0
     */
    obterElemento(
        seletor,
        tipoElemento,
        descricaoElemento,
    ) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                `O seletor para ${descricaoElemento} é obrigatório.`,
            );
        }

        const seletorNormalizado = seletor.trim();

        let elemento;

        try {
            elemento = document.querySelector(
                seletorNormalizado,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletorNormalizado}" é inválido.`,
            );
        }

        if (!(elemento instanceof tipoElemento)) {
            throw new TypeError(
                `Não foi possível encontrar ${descricaoElemento} através de "${seletorNormalizado}".`,
            );
        }

        return elemento;
    }
}

export default GestorFiltrosDinamicos;
