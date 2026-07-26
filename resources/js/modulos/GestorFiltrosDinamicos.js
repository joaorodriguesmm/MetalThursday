import TomSelect from 'tom-select';

/**
 * Gere a criação e a remoção dinâmica de filtros de pesquisa.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class GestorFiltrosDinamicos {
    /**
     * Cria um gestor de filtros dinâmicos.
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
     * @throws {TypeError} Quando as opções são inválidas.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor({
        seletorListaFiltros,
        seletorContentorFiltros,
        dadosFiltros = {},
        filtrosDisponiveis = {},
    }) {
        this.validarSeletor(
            seletorListaFiltros,
            'O seletor da lista de filtros é obrigatório.',
        );

        this.validarSeletor(
            seletorContentorFiltros,
            'O seletor do contentor de filtros é obrigatório.',
        );

        this.validarObjeto(
            dadosFiltros,
            'Os dados dos filtros devem ser um objeto.',
        );

        this.validarObjeto(
            filtrosDisponiveis,
            'A configuração dos filtros deve ser um objeto.',
        );

        this.listaAdicionarFiltro = this.obterElemento(
            seletorListaFiltros,
        );

        this.areaFiltrosAtivos = this.obterElemento(
            seletorContentorFiltros,
        );

        this.dadosFiltros = dadosFiltros;
        this.filtrosDisponiveis = filtrosDisponiveis;
        this.instanciasTomSelect = new Map();
        this.iniciado = false;

        this.aoAlterarListaFiltros = (evento) => {
            this.tratarAdicaoFiltro(evento);
        };

        this.aoClicarAreaFiltros = (evento) => {
            this.tratarRemocaoFiltro(evento);
        };

        if (this.estaAtivo()) {
            this.iniciar();
        }
    }

    /**
     * Verifica se o gestor encontrou os elementos necessários.
     *
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    estaAtivo() {
        return this.listaAdicionarFiltro instanceof HTMLSelectElement
            && this.areaFiltrosAtivos instanceof HTMLElement;
    }

    /**
     * Inicia o gestor de filtros.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        if (!this.estaAtivo() || this.iniciado) {
            return;
        }

        this.inicializarAPartirDoUrl();

        this.listaAdicionarFiltro.addEventListener(
            'change',
            this.aoAlterarListaFiltros,
        );

        this.areaFiltrosAtivos.addEventListener(
            'click',
            this.aoClicarAreaFiltros,
        );

        this.iniciado = true;
    }

    /**
     * Cria os filtros presentes nos parâmetros do endereço atual.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    inicializarAPartirDoUrl() {
        const parametrosUrl = new URLSearchParams(
            window.location.search,
        );

        Object.entries(this.filtrosDisponiveis).forEach(
            ([chaveFiltro, configuracao]) => {
                if (
                    !this.eConfiguracaoValida(configuracao)
                ) {
                    return;
                }

                const nomeParametro =
                    `filtro_${configuracao.parametro}`;

                if (!parametrosUrl.has(nomeParametro)) {
                    return;
                }

                this.renderizar(
                    chaveFiltro,
                    parametrosUrl.get(nomeParametro) ?? '',
                );
            },
        );
    }

    /**
     * Trata a seleção de um novo filtro.
     *
     * @param {Event} evento Evento de alteração.
     *
     * @since 1.0.0
     * @version 2.0.0
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
     * @since 1.0.0
     * @version 2.0.0
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
            botaoRemover.dataset.removerFiltro;

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
     * @version 2.0.0
     */
    renderizar(
        chaveFiltro,
        valorAtual = '',
    ) {
        if (!this.estaAtivo()) {
            return false;
        }

        const configuracao =
            this.filtrosDisponiveis[chaveFiltro];

        if (!this.eConfiguracaoValida(configuracao)) {
            return false;
        }

        const nomeFiltro =
            `filtro_${configuracao.parametro}`;

        if (this.filtroEstaAtivo(nomeFiltro)) {
            return false;
        }

        const identificadorCampo =
            this.criarIdentificadorCampo(nomeFiltro);

        const campo =
            this.criarCampoFiltro(
                configuracao,
                nomeFiltro,
                identificadorCampo,
                String(valorAtual),
            );

        if (campo === null) {
            return false;
        }

        const componente =
            this.criarComponenteFiltro(
                configuracao.rotulo,
                nomeFiltro,
                identificadorCampo,
                campo,
            );

        this.areaFiltrosAtivos.append(
            componente,
        );

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
     * @since 1.0.0
     * @version 2.0.0
     */
    removerFiltro(nomeFiltro) {
        const instanciaTomSelect =
            this.instanciasTomSelect.get(nomeFiltro);

        if (instanciaTomSelect) {
            instanciaTomSelect.destroy();
            this.instanciasTomSelect.delete(nomeFiltro);
        }

        const componente = Array.from(
            this.areaFiltrosAtivos.children,
        ).find(
            (elemento) =>
                elemento instanceof HTMLElement
                && elemento.dataset.nomeFiltro === nomeFiltro,
        );

        componente?.remove();
    }

    /**
     * Verifica se um filtro já está apresentado.
     *
     * @param {string} nomeFiltro Nome HTML do filtro.
     *
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    filtroEstaAtivo(nomeFiltro) {
        return Array.from(
            this.areaFiltrosAtivos.children,
        ).some(
            (elemento) =>
                elemento instanceof HTMLElement
                && elemento.dataset.nomeFiltro === nomeFiltro,
        );
    }

    /**
     * Cria o campo adequado ao tipo de filtro.
     *
     * @param {object} configuracao Configuração do filtro.
     * @param {string} nomeFiltro Nome HTML do campo.
     * @param {string} identificadorCampo Identificador HTML do campo.
     * @param {string} valorAtual Valor atual do filtro.
     *
     * @returns {
     *     HTMLInputElement
     *     |HTMLSelectElement
     *     |null
     * }
     *
     * @since 2.0.0
     * @version 1.0.0
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
                return null;
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
     * @returns {HTMLSelectElement}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    criarCampoSelecao(
        configuracao,
        nomeFiltro,
        identificadorCampo,
        valorAtual,
    ) {
        const selecao =
            document.createElement('select');

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

        const opcoes =
            this.obterOpcoesFiltro(configuracao);

        opcoes.forEach((opcao) => {
            if (
                !this.eObjeto(opcao)
                || !Object.hasOwn(opcao, 'id')
                || !Object.hasOwn(opcao, 'nome')
            ) {
                return;
            }

            const elementoOpcao =
                document.createElement('option');

            elementoOpcao.value =
                String(opcao.id);

            elementoOpcao.textContent =
                String(opcao.nome);

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
     * @returns {HTMLInputElement}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    criarCampoData(
        nomeFiltro,
        identificadorCampo,
        valorAtual,
    ) {
        const campo =
            document.createElement('input');

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
     * @returns {HTMLSelectElement}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    criarCampoSimNao(
        nomeFiltro,
        identificadorCampo,
        valorAtual,
    ) {
        const selecao =
            document.createElement('select');

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
            const opcao =
                document.createElement('option');

            opcao.value = valor;
            opcao.textContent = rotulo;
            opcao.selected =
                valor === valorSelecionado;

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
     * @returns {HTMLDivElement}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    criarComponenteFiltro(
        rotulo,
        nomeFiltro,
        identificadorCampo,
        campo,
    ) {
        const coluna =
            document.createElement('div');

        coluna.className =
            'col-md-4 mb-3';

        coluna.dataset.nomeFiltro =
            nomeFiltro;

        const cartao =
            document.createElement('div');

        cartao.className =
            'card bg-secondary h-100';

        const corpo =
            document.createElement('div');

        corpo.className =
            'card-body p-2';

        const cabecalho =
            document.createElement('div');

        cabecalho.className =
            'd-flex justify-content-between align-items-center mb-2';

        const etiqueta =
            document.createElement('label');

        etiqueta.className =
            'small text-white';

        etiqueta.htmlFor =
            identificadorCampo;

        etiqueta.textContent =
            rotulo;

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
     * @returns {Array<object>}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterOpcoesFiltro(configuracao) {
        if (
            typeof configuracao.chaveDados !== 'string'
            || configuracao.chaveDados.trim() === ''
        ) {
            return [];
        }

        const opcoes =
            this.dadosFiltros[configuracao.chaveDados];

        return Array.isArray(opcoes)
            ? opcoes
            : [];
    }

    /**
     * Inicializa o Tom Select num campo.
     *
     * @param {string} nomeFiltro Nome HTML do filtro.
     * @param {HTMLSelectElement} campo Campo de seleção.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    inicializarTomSelect(
        nomeFiltro,
        campo,
    ) {
        const instanciaExistente =
            this.instanciasTomSelect.get(nomeFiltro);

        instanciaExistente?.destroy();

        const instancia =
            new TomSelect(
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
     * Cria um identificador HTML seguro.
     *
     * @param {string} nomeFiltro Nome HTML do filtro.
     *
     * @returns {string}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    criarIdentificadorCampo(nomeFiltro) {
        return nomeFiltro
            .replace(
                /[^a-zA-Z0-9_-]/g,
                '-',
            );
    }

    /**
     * Verifica se uma configuração de filtro é válida.
     *
     * @param {unknown} configuracao Configuração a verificar.
     *
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    eConfiguracaoValida(configuracao) {
        if (!this.eObjeto(configuracao)) {
            return false;
        }

        return typeof configuracao.parametro === 'string'
            && configuracao.parametro.trim() !== ''
            && typeof configuracao.tipo === 'string'
            && ['selecao', 'data', 'sim_nao'].includes(
                configuracao.tipo,
            )
            && typeof configuracao.rotulo === 'string'
            && configuracao.rotulo.trim() !== '';
    }

    /**
     * Verifica se um valor é um objeto não nulo.
     *
     * @param {unknown} valor Valor a verificar.
     *
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    eObjeto(valor) {
        return typeof valor === 'object'
            && valor !== null
            && !Array.isArray(valor);
    }

    /**
     * Valida uma opção que deve ser um objeto.
     *
     * @param {unknown} valor Valor a validar.
     * @param {string} mensagem Mensagem de erro.
     *
     * @throws {TypeError} Quando o valor é inválido.
     *
     * @since 2.0.0
     * @version 1.0.0
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
     * Valida um seletor CSS obrigatório.
     *
     * @param {unknown} seletor Seletor a validar.
     * @param {string} mensagem Mensagem de erro.
     *
     * @throws {TypeError} Quando o seletor é inválido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    validarSeletor(
        seletor,
        mensagem,
    ) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(mensagem);
        }
    }

    /**
     * Obtém um elemento através de um seletor CSS.
     *
     * @param {string} seletor Seletor CSS.
     *
     * @returns {Element|null}
     *
     * @throws {TypeError} Quando o seletor CSS é inválido.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterElemento(seletor) {
        try {
            return document.querySelector(
                seletor,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletor}" é inválido.`,
            );
        }
    }

    /**
     * Destrói o gestor e as instâncias associadas.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        if (this.iniciado) {
            this.listaAdicionarFiltro?.removeEventListener(
                'change',
                this.aoAlterarListaFiltros,
            );

            this.areaFiltrosAtivos?.removeEventListener(
                'click',
                this.aoClicarAreaFiltros,
            );
        }

        this.instanciasTomSelect.forEach(
            (instancia) => {
                instancia.destroy();
            },
        );

        this.instanciasTomSelect.clear();
        this.iniciado = false;
    }
}

export default GestorFiltrosDinamicos;
