import { Tooltip } from 'bootstrap';

/**
 * Gere os tooltips fornecidos pelo Bootstrap.
 *
 * O inicializador reutiliza instâncias existentes, impedindo a associação de
 * vários tooltips ao mesmo elemento.
 *
 * @since 1.0.0
 * @version 2.1.0
 */
class InicializadorTooltips {
    /**
     * Cria o inicializador de tooltips.
     *
     * @param {string|HTMLElement|Iterable<HTMLElement>} elementosOuSeletor
     *     Seletor, elemento ou coleção de elementos.
     * @param {Record<string, unknown>} opcoes
     *     Opções transmitidas ao Bootstrap.
     *
     * @throws {TypeError} Quando os elementos ou as opções não são válidos.
     *
     * @since 1.0.0
     * @version 2.1.0
     */
    constructor(
        elementosOuSeletor = '[data-bs-toggle="tooltip"]',
        opcoes = {},
    ) {
        this.elementosOuSeletor = elementosOuSeletor;
        this.opcoes = this.normalizarOpcoes(opcoes);
        this.instancias = new Map();

        this.iniciar();
    }

    /**
     * Inicializa os tooltips disponíveis.
     *
     * Elementos já inicializados são reutilizados.
     *
     * @returns {number} Número total de instâncias geridas.
     *
     * @since 1.0.0
     * @version 2.1.0
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
     * @returns {number} Número total de instâncias geridas.
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
     * @param {HTMLElement} elemento Elemento procurado.
     *
     * @returns {Tooltip|null} Instância encontrada ou nulo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    obterInstancia(elemento) {
        if (!(elemento instanceof HTMLElement)) {
            return null;
        }

        return this.instancias.get(elemento)?.instancia
            ?? Tooltip.getInstance(elemento)
            ?? null;
    }

    /**
     * Destrói as instâncias criadas por este inicializador.
     *
     * Instâncias que já existiam antes da inicialização são apenas removidas
     * do registo interno e permanecem ativas.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    destruir() {
        this.instancias.forEach(
            (
                registo,
                elemento,
            ) => {
                if (
                    registo.criadaPeloInicializador
                    && Tooltip.getInstance(elemento)
                    === registo.instancia
                ) {
                    registo.instancia.dispose();
                }
            },
        );

        this.instancias.clear();
    }

    /**
     * Inicializa o tooltip de um elemento.
     *
     * @param {HTMLElement} elemento Elemento configurado.
     *
     * @returns {Tooltip} Instância criada ou reutilizada.
     *
     * @throws {TypeError} Quando o elemento não é válido.
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    inicializarElemento(elemento) {
        if (!(elemento instanceof HTMLElement)) {
            throw new TypeError(
                'O elemento do tooltip deve ser um elemento HTML válido.',
            );
        }

        const registoAtual =
            this.instancias.get(elemento);

        const instanciaAtual =
            Tooltip.getInstance(elemento);

        if (
            registoAtual
            && instanciaAtual === registoAtual.instancia
        ) {
            return registoAtual.instancia;
        }

        if (registoAtual) {
            this.instancias.delete(elemento);
        }

        if (instanciaAtual) {
            this.instancias.set(
                elemento,
                {
                    instancia: instanciaAtual,
                    criadaPeloInicializador: false,
                },
            );

            return instanciaAtual;
        }

        const instancia = new Tooltip(
            elemento,
            this.opcoes,
        );

        this.instancias.set(
            elemento,
            {
                instancia,
                criadaPeloInicializador: true,
            },
        );

        return instancia;
    }

    /**
     * Remove os registos cujos elementos já não pertencem ao documento.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.1.0
     */
    removerInstanciasDesligadas() {
        this.instancias.forEach(
            (
                registo,
                elemento,
            ) => {
                if (elemento.isConnected) {
                    return;
                }

                if (
                    registo.criadaPeloInicializador
                    && Tooltip.getInstance(elemento)
                    === registo.instancia
                ) {
                    registo.instancia.dispose();
                }

                this.instancias.delete(elemento);
            },
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
     * @version 1.0.0
     */
    obterElementos(elementosOuSeletor) {
        let elementos;

        if (typeof elementosOuSeletor === 'string') {
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
                    !(
                        elemento
                        instanceof HTMLElement
                    ),
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
     * @param {Record<string, unknown>} opcoes Opções recebidas.
     *
     * @returns {Readonly<Record<string, unknown>>} Opções normalizadas.
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
