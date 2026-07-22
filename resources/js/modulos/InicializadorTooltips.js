import { Tooltip } from 'bootstrap';

/**
 * Gere os tooltips fornecidos pelo Bootstrap.
 *
 * O inicializador reutiliza instâncias existentes, impedindo a associação de
 * vários tooltips ao mesmo elemento.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class InicializadorTooltips {
    /**
     * Cria o inicializador de tooltips.
     *
     * @param {string|HTMLElement|Iterable<HTMLElement>}
     * elementosOuSeletor - Seletor, elemento ou coleção de elementos.
     * @param {Object} opcoes - Opções transmitidas ao Bootstrap.
     *
     * @throws {TypeError} Quando os elementos ou as opções não são válidos.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor(
        elementosOuSeletor = '[data-bs-toggle="tooltip"]',
        opcoes = {},
    ) {
        /**
         * Origem utilizada para localizar os elementos.
         *
         * @type {string|HTMLElement|Iterable<HTMLElement>}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.elementosOuSeletor = elementosOuSeletor;

        /**
         * Opções transmitidas ao Bootstrap.
         *
         * @type {Readonly<Object>}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.opcoes = this.normalizarOpcoes(
            opcoes,
        );

        /**
         * Instâncias associadas aos respetivos elementos.
         *
         * @type {Map<HTMLElement, Tooltip>}
         *
         * @since 2.0.0
         * @version 1.0.0
         */
        this.instancias = new Map();

        this.iniciar();
    }

    /**
     * Inicializa os tooltips disponíveis.
     *
     * Elementos já inicializados são ignorados.
     *
     * @return {number} Número total de instâncias geridas.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        this.removerInstanciasDesligadas();

        const elementos = this.obterElementos(
            this.elementosOuSeletor,
        );

        elementos.forEach((elemento) => {
            this.inicializarElemento(elemento);
        });

        return this.instancias.size;
    }

    /**
     * Procura novamente os elementos e inicializa os que foram adicionados.
     *
     * Este método pode ser utilizado depois de introduzir conteúdo dinâmico
     * na página.
     *
     * @return {number} Número total de instâncias geridas.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    atualizar() {
        return this.iniciar();
    }

    /**
     * Obtém a instância associada a um elemento.
     *
     * @param {HTMLElement} elemento - Elemento procurado.
     *
     * @return {Tooltip|null} Instância encontrada ou nulo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterInstancia(elemento) {
        if (!(elemento instanceof HTMLElement)) {
            return null;
        }

        return this.instancias.get(elemento)
            ?? Tooltip.getInstance(elemento)
            ?? null;
    }

    /**
     * Destrói todas as instâncias geridas.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        this.instancias.forEach((instancia) => {
            instancia.dispose();
        });

        this.instancias.clear();
    }

    /**
     * Inicializa o tooltip de um elemento.
     *
     * @param {HTMLElement} elemento - Elemento configurado.
     *
     * @return {Tooltip} Instância criada ou reutilizada.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    inicializarElemento(elemento) {
        const instanciaGerida =
            this.instancias.get(elemento);

        if (instanciaGerida instanceof Tooltip) {
            return instanciaGerida;
        }

        const instancia = Tooltip.getOrCreateInstance(
            elemento,
            this.opcoes,
        );

        this.instancias.set(
            elemento,
            instancia,
        );

        return instancia;
    }

    /**
     * Remove as instâncias cujos elementos já não pertencem ao documento.
     *
     * @return {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    removerInstanciasDesligadas() {
        this.instancias.forEach(
            (
                instancia,
                elemento,
            ) => {
                if (elemento.isConnected) {
                    return;
                }

                instancia.dispose();

                this.instancias.delete(
                    elemento,
                );
            },
        );
    }

    /**
     * Obtém os elementos que devem possuir tooltip.
     *
     * Uma pesquisa sem resultados é válida e produz uma lista vazia.
     *
     * @param {string|HTMLElement|Iterable<HTMLElement>}
     * elementosOuSeletor - Origem recebida.
     *
     * @return {Array<HTMLElement>} Elementos únicos encontrados.
     *
     * @throws {TypeError} Quando a origem ou algum elemento não são válidos.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterElementos(elementosOuSeletor) {
        let elementos;

        if (typeof elementosOuSeletor === 'string') {
            const seletor = elementosOuSeletor.trim();

            if (seletor === '') {
                throw new TypeError(
                    'O seletor dos tooltips não pode estar vazio.',
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
            elementosOuSeletor instanceof HTMLElement
        ) {
            elementos = [
                elementosOuSeletor,
            ];
        } else if (
            elementosOuSeletor !== null
            && typeof elementosOuSeletor[
                Symbol.iterator
            ] === 'function'
        ) {
            elementos = Array.from(
                elementosOuSeletor,
            );
        } else {
            throw new TypeError(
                'Os tooltips devem ser indicados através de um seletor, elemento ou coleção.',
            );
        }

        if (
            elementos.some(
                (elemento) =>
                    !(elemento instanceof HTMLElement),
            )
        ) {
            throw new TypeError(
                'Todos os elementos dos tooltips devem ser elementos HTML válidos.',
            );
        }

        return Array.from(
            new Set(elementos),
        );
    }

    /**
     * Normaliza as opções transmitidas ao Bootstrap.
     *
     * @param {Object} opcoes - Opções recebidas.
     *
     * @return {Readonly<Object>} Opções normalizadas.
     *
     * @throws {TypeError} Quando as opções não formam um objeto.
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
                'As opções dos tooltips devem ser apresentadas num objeto.',
            );
        }

        return Object.freeze({
            ...opcoes,
        });
    }
}

export default InicializadorTooltips;
