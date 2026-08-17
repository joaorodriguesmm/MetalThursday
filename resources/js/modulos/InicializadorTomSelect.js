import TomSelect from 'tom-select/base';
import TomSelectClearButton
    from 'tom-select/plugins/clear_button/plugin.js';
import TomSelectRemoveButton
    from 'tom-select/plugins/remove_button/plugin.js';

TomSelect.define(
    'clear_button',
    TomSelectClearButton,
);

TomSelect.define(
    'remove_button',
    TomSelectRemoveButton,
);

/**
 * Gere as instâncias de Tom Select da aplicação.
 *
 * Os nomes dos plugins e das opções permanecem em inglês por corresponderem
 * à API disponibilizada pela biblioteca Tom Select.
 *
 * @since 1.0.0
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
     */
    constructor(
        seletor = 'select.tom-select-unico, select.tom-select-multiplo',
    ) {
        if (
            typeof seletor !== 'string'
            || seletor.trim() === ''
        ) {
            throw new TypeError(
                'O seletor dos campos Tom Select é obrigatório.',
            );
        }

        const seletorNormalizado =
            seletor.trim();

        try {
            document.querySelectorAll(
                seletorNormalizado,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletorNormalizado}" é inválido.`,
            );
        }

        /**
         * Seletor dos campos geridos.
         *
         * @type {string}
         *
         * @since 2.0.0
         */
        this.seletor =
            seletorNormalizado;

        /**
         * Instâncias conhecidas pelo inicializador.
         *
         * Cada instância remove-se automaticamente deste registo quando
         * recebe o evento `destroy`.
         *
         * @type {Set<TomSelect>}
         *
         * @since 2.0.0
         */
        this.instancias =
            new Set();

        this.iniciarTodos();
    }

    /**
     * Inicializa todos os campos que correspondem ao seletor configurado.
     *
     * @param {Document|Element} raiz
     *     Elemento a partir do qual os campos serão procurados.
     *
     * @returns {Array<TomSelect>} Instâncias encontradas ou criadas.
     *
     * @throws {TypeError} Quando a raiz de pesquisa é inválida.
     *
     * @since 1.0.0
     */
    iniciarTodos(
        raiz = document,
    ) {
        if (
            !(raiz instanceof Document)
            && !(raiz instanceof Element)
        ) {
            throw new TypeError(
                'A raiz de pesquisa dos campos Tom Select é inválida.',
            );
        }

        return Array.from(
            raiz.querySelectorAll(
                this.seletor,
            ),
        )
            .filter(
                (elemento) =>
                    elemento
                    instanceof HTMLSelectElement,
            )
            .map(
                (elemento) =>
                    this.iniciar(
                        elemento,
                    ),
            )
            .filter(
                (instancia) =>
                    instancia !== null,
            );
    }

    /**
     * Inicializa uma instância Tom Select num elemento.
     *
     * Quando o elemento já possui uma instância, esta é reutilizada e
     * registada sem ser recriada.
     *
     * @param {HTMLSelectElement} elemento Campo a inicializar.
     *
     * @returns {TomSelect|null} Instância disponível para o campo.
     *
     * @since 1.0.0
     */
    iniciar(elemento) {
        if (
            !(elemento
                instanceof HTMLSelectElement)
        ) {
            return null;
        }

        const instanciaExistente =
            elemento.tomselect;

        if (instanciaExistente) {
            this.registarInstancia(
                instanciaExistente,
            );

            return instanciaExistente;
        }

        const instancia =
            new TomSelect(
                elemento,
                this.criarConfiguracao(
                    elemento,
                ),
            );

        this.registarInstancia(
            instancia,
        );

        return instancia;
    }

    /**
     * Cria a configuração de uma instância Tom Select.
     *
     * Os campos múltiplos permitem remover opções individualmente e limpar
     * toda a seleção. Os campos simples disponibilizam apenas a limpeza da
     * seleção.
     *
     * @param {HTMLSelectElement} elemento Campo configurado.
     *
     * @returns {object} Configuração da instância.
     *
     * @since 2.0.0
     */
    criarConfiguracao(elemento) {
        const plugins =
            elemento.multiple
                ? {
                    remove_button: {
                        title:
                            'Remover esta opção',
                    },

                    clear_button: {
                        title:
                            'Limpar seleção',
                    },
                }
                : {
                    clear_button: {
                        title:
                            'Limpar seleção',
                    },
                };

        return {
            plugins,

            render: {
                no_results: (
                    dados,
                    escapar,
                ) => [
                    '<div class="no-results">',
                    `Nenhum resultado para "${escapar(
                        dados.input,
                    )}".`,
                    '</div>',
                ].join(''),
            },
        };
    }

    /**
     * Regista uma instância e acompanha a respetiva destruição.
     *
     * O evento `destroy` permite que instâncias destruídas por outros
     * componentes sejam igualmente removidas deste registo.
     *
     * @param {TomSelect} instancia Instância registada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    registarInstancia(instancia) {
        if (
            this.instancias.has(
                instancia,
            )
        ) {
            return;
        }

        this.instancias.add(
            instancia,
        );

        instancia.on(
            'destroy',
            () => {
                this.instancias.delete(
                    instancia,
                );
            },
        );
    }

    /**
     * Obtém uma instância Tom Select através do identificador do campo.
     *
     * A pesquisa é feita sempre no DOM atual, evitando manter um segundo
     * registo por identificador que possa ficar desatualizado.
     *
     * @param {string} identificador Identificador HTML do campo.
     *
     * @returns {TomSelect|null} Instância encontrada ou criada.
     *
     * @since 1.0.0
     */
    obterInstancia(identificador) {
        if (
            typeof identificador !== 'string'
            || identificador.trim() === ''
        ) {
            return null;
        }

        const elemento =
            document.getElementById(
                identificador.trim(),
            );

        if (
            !(elemento
                instanceof HTMLSelectElement)
        ) {
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
     */
    destruirInstancia(referencia) {
        let elemento;

        if (typeof referencia === 'string') {
            const identificador =
                referencia.trim();

            if (identificador === '') {
                return false;
            }

            elemento =
                document.getElementById(
                    identificador,
                );
        } else {
            elemento =
                referencia;
        }

        if (
            !(elemento
                instanceof HTMLSelectElement)
        ) {
            return false;
        }

        const instancia =
            elemento.tomselect;

        if (!instancia) {
            return false;
        }

        instancia.destroy();

        /*
         * O evento `destroy` remove normalmente a instância do registo.
         * A eliminação explícita mantém o método robusto caso a instância
         * recebida não emita o evento esperado.
         */
        this.instancias.delete(
            instancia,
        );

        return true;
    }

    /**
     * Destrói todas as instâncias registadas.
     *
     * É utilizada uma cópia do conjunto porque cada `destroy()` remove a
     * própria instância do registo.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    destruirTodas() {
        [
            ...this.instancias,
        ].forEach((instancia) => {
            if (
                typeof instancia.destroy
                === 'function'
            ) {
                instancia.destroy();
            }
        });

        this.instancias.clear();
    }
}

export default InicializadorTomSelect;
