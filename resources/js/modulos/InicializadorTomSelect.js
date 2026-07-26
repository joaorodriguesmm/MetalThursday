import TomSelect from 'tom-select';

/**
 * Gere as instâncias de Tom Select da aplicação.
 *
 * Os nomes dos plugins e das opções permanecem em inglês por corresponderem
 * à API disponibilizada pela biblioteca Tom Select.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class InicializadorTomSelect {
    /**
     * Cria um inicializador de campos Tom Select.
     *
     * @param {string} seletor
     *     Seletor CSS dos elementos `<select>` a inicializar.
     *
     * @throws {TypeError} Quando o seletor é inválido.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor(
        seletor = 'select.tom-select-single, select.tom-select-multiple',
    ) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                'O seletor dos campos Tom Select é obrigatório.',
            );
        }

        try {
            document.querySelectorAll(seletor);
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletor}" é inválido.`,
            );
        }

        this.seletor = seletor;
        this.instancias = new Set();
        this.instanciasPorId = new Map();

        this.iniciarTodos();
    }

    /**
     * Inicializa todos os campos que correspondem ao seletor configurado.
     *
     * @param {Document|Element} raiz
     *     Elemento a partir do qual os campos serão procurados.
     *
     * @returns {TomSelect[]} Instâncias encontradas ou criadas.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciarTodos(raiz = document) {
        if (
            !(raiz instanceof Document)
            && !(raiz instanceof Element)
        ) {
            throw new TypeError(
                'A raiz de pesquisa dos campos Tom Select é inválida.',
            );
        }

        return Array.from(
            raiz.querySelectorAll(this.seletor),
        )
            .filter(
                (elemento) =>
                    elemento instanceof HTMLSelectElement,
            )
            .map(
                (elemento) =>
                    this.iniciar(elemento),
            )
            .filter(
                (instancia) =>
                    instancia !== null,
            );
    }

    /**
     * Inicializa uma instância de Tom Select num elemento.
     *
     * Quando o elemento já possui uma instância, essa instância é registada
     * e devolvida sem ser recriada.
     *
     * @param {HTMLSelectElement} elemento Campo a inicializar.
     *
     * @returns {TomSelect|null} Instância disponível para o campo.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar(elemento) {
        if (!(elemento instanceof HTMLSelectElement)) {
            return null;
        }

        const instanciaExistente =
            elemento.tomselect;

        if (instanciaExistente) {
            this.registarInstancia(
                elemento,
                instanciaExistente,
            );

            return instanciaExistente;
        }

        const instancia =
            new TomSelect(
                elemento,
                this.criarConfiguracao(elemento),
            );

        this.registarInstancia(
            elemento,
            instancia,
        );

        return instancia;
    }

    /**
     * Cria a configuração de uma instância Tom Select.
     *
     * @param {HTMLSelectElement} elemento Campo a configurar.
     *
     * @returns {object} Configuração da instância.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    criarConfiguracao(elemento) {
        return {
            plugins: elemento.multiple
                ? [
                    'remove_button',
                    'clear_button',
                ]
                : [
                    'clear_button',
                ],

            render: {
                no_results: (
                    dados,
                    escapar,
                ) => [
                    '<div class="no-results">',
                    `Nenhum resultado para "${escapar(dados.input)}".`,
                    '</div>',
                ].join(''),
            },
        };
    }

    /**
     * Regista uma instância para reutilização posterior.
     *
     * @param {HTMLSelectElement} elemento Campo associado.
     * @param {TomSelect} instancia Instância a registar.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    registarInstancia(
        elemento,
        instancia,
    ) {
        this.instancias.add(
            instancia,
        );

        if (elemento.id !== '') {
            this.instanciasPorId.set(
                elemento.id,
                instancia,
            );
        }
    }

    /**
     * Obtém uma instância Tom Select através do identificador do campo.
     *
     * @param {string} id Identificador HTML do campo.
     *
     * @returns {TomSelect|null} Instância encontrada.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    obterInstancia(id) {
        if (
            typeof id !== 'string'
            || id.trim() === ''
        ) {
            return null;
        }

        const identificador =
            id.trim();

        const instanciaRegistada =
            this.instanciasPorId.get(
                identificador,
            );

        if (instanciaRegistada) {
            return instanciaRegistada;
        }

        const elemento =
            document.getElementById(
                identificador,
            );

        if (!(elemento instanceof HTMLSelectElement)) {
            return null;
        }

        return this.iniciar(
            elemento,
        );
    }

    /**
     * Destrói a instância associada a um campo.
     *
     * @param {string|HTMLSelectElement} referencia
     *     Identificador ou elemento associado à instância.
     *
     * @returns {boolean} Indica se foi destruída uma instância.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruirInstancia(referencia) {
        const elemento =
            typeof referencia === 'string'
                ? document.getElementById(
                    referencia,
                )
                : referencia;

        if (!(elemento instanceof HTMLSelectElement)) {
            return false;
        }

        const instancia =
            elemento.tomselect;

        if (!instancia) {
            return false;
        }

        instancia.destroy();

        this.instancias.delete(
            instancia,
        );

        if (elemento.id !== '') {
            this.instanciasPorId.delete(
                elemento.id,
            );
        }

        return true;
    }

    /**
     * Destrói todas as instâncias registadas.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruirTodas() {
        this.instancias.forEach(
            (instancia) => {
                if (
                    typeof instancia.destroy
                    === 'function'
                ) {
                    instancia.destroy();
                }
            },
        );

        this.instancias.clear();
        this.instanciasPorId.clear();
    }
}

export default InicializadorTomSelect;
