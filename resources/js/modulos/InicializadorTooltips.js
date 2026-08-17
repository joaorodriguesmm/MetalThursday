import { Tooltip } from 'bootstrap';

/**
 * Inicializa os tooltips fornecidos pelo Bootstrap.
 *
 * Instâncias já associadas aos elementos são reutilizadas através da API do
 * próprio Bootstrap, evitando registos paralelos e duplicação de instâncias.
 *
 * @since 1.0.0
 */
class InicializadorTooltips {
    /**
     * Cria o inicializador e prepara os elementos recebidos.
     *
     * @param {string|HTMLElement|Iterable<HTMLElement>} elementosOuSeletor
     *     Seletor, elemento ou coleção de elementos.
     * @param {Record<string, unknown>} opcoes
     *     Opções transmitidas ao Bootstrap.
     *
     * @throws {TypeError} Quando os elementos ou as opções são inválidos.
     *
     * @since 1.0.0
     */
    constructor(
        elementosOuSeletor = '[data-bs-toggle="tooltip"]',
        opcoes = {},
    ) {
        /**
         * Opções utilizadas na criação de novas instâncias.
         *
         * @type {Readonly<Record<string, unknown>>}
         *
         * @since 2.0.0
         */
        this.opcoes =
            this.normalizarOpcoes(
                opcoes,
            );

        this.inicializar(
            elementosOuSeletor,
        );
    }

    /**
     * Inicializa os tooltips indicados.
     *
     * Elementos que já possuem uma instância Bootstrap reutilizam-na sem
     * criar uma segunda instância.
     *
     * @param {string|HTMLElement|Iterable<HTMLElement>} elementosOuSeletor
     *     Seletor, elemento ou coleção de elementos.
     *
     * @returns {number} Número de elementos processados.
     *
     * @since 1.0.0
     */
    inicializar(elementosOuSeletor) {
        const elementos =
            this.obterElementos(
                elementosOuSeletor,
            );

        elementos.forEach(
            (elemento) => {
                this.inicializarElemento(
                    elemento,
                );
            },
        );

        return elementos.length;
    }

    /**
     * Inicializa ou reutiliza o tooltip de um elemento.
     *
     * @param {HTMLElement} elemento Elemento configurado.
     *
     * @returns {Tooltip} Instância disponível.
     *
     * @throws {TypeError} Quando o elemento não é válido.
     *
     * @since 2.0.0
     */
    inicializarElemento(elemento) {
        if (
            !(elemento
                instanceof HTMLElement)
        ) {
            throw new TypeError(
                'O elemento do tooltip deve ser um elemento HTML válido.',
            );
        }

        return Tooltip.getOrCreateInstance(
            elemento,
            this.opcoes,
        );
    }

    /**
     * Obtém os elementos que devem possuir tooltip.
     *
     * Uma pesquisa sem resultados é válida e produz uma lista vazia.
     *
     * @param {string|HTMLElement|Iterable<HTMLElement>} elementosOuSeletor
     *     Origem recebida.
     *
     * @returns {Array<HTMLElement>} Elementos únicos encontrados.
     *
     * @throws {TypeError} Quando a origem ou algum elemento não são válidos.
     *
     * @since 2.0.0
     */
    obterElementos(elementosOuSeletor) {
        let elementos;

        if (
            typeof elementosOuSeletor
            === 'string'
        ) {
            const seletor =
                elementosOuSeletor.trim();

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
            elementosOuSeletor
            instanceof HTMLElement
        ) {
            elementos = [
                elementosOuSeletor,
            ];
        } else if (
            elementosOuSeletor !== null
            && elementosOuSeletor !== undefined
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
                    !(elemento
                        instanceof HTMLElement),
            )
        ) {
            throw new TypeError(
                'Todos os elementos dos tooltips devem ser elementos HTML válidos.',
            );
        }

        return Array.from(
            new Set(
                elementos,
            ),
        );
    }

    /**
     * Normaliza as opções transmitidas ao Bootstrap.
     *
     * @param {Record<string, unknown>} opcoes Opções recebidas.
     *
     * @returns {Readonly<Record<string, unknown>>} Opções normalizadas.
     *
     * @throws {TypeError} Quando as opções não formam um objeto.
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
                'As opções dos tooltips devem ser apresentadas num objeto.',
            );
        }

        return Object.freeze({
            ...opcoes,
        });
    }
}

export default InicializadorTooltips;
