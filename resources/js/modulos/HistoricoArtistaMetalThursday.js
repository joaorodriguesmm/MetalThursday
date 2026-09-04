/**
 * Gere a apresentação contextual das aparições anteriores de artistas nos
 * formulários de criação e edição de MetalThursdays.
 *
 * O histórico é obtido a partir do endpoint definido no próprio formulário.
 * Na edição, a MetalThursday atual é enviada como exclusão para impedir que a
 * publicação em edição seja apresentada como uma aparição anterior.
 *
 * @since 2.0.0
 */
class HistoricoArtistaMetalThursday {
    /**
     * Marcador utilizado no endereço preparado pelo servidor.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static MARCADOR_IDENTIFICADOR_ARTISTA =
        '__IDENTIFICADOR_ARTISTA__';

    /**
     * Cria o gestor de histórico contextual.
     *
     * @param {HTMLFormElement} formulario Formulário principal.
     *
     * @throws {TypeError} Quando a configuração do formulário é inválida.
     *
     * @since 2.0.0
     */
    constructor(formulario) {
        if (!(formulario instanceof HTMLFormElement)) {
            throw new TypeError(
                'O formulário do histórico de artistas é inválido.',
            );
        }

        this.formulario = formulario;

        this.modeloEndereco =
            this.obterModeloEndereco();

        this.identificadorMetalThursday =
            this.obterIdentificadorMetalThursday();

        /**
         * Pedidos atualmente associados às secções.
         *
         * O WeakMap permite libertar automaticamente as referências quando uma
         * secção é removida da página.
         *
         * @type {WeakMap<HTMLElement, AbortController>}
         */
        this.controladoresPedidos =
            new WeakMap();

        this.configurarEventos();

        this.inicializarSecoesExistentes();
    }

    /**
     * Obtém e valida o modelo de endereço fornecido pelo servidor.
     *
     * @returns {string} Endereço contendo o marcador do artista.
     *
     * @throws {TypeError} Quando o endereço é inválido.
     *
     * @since 2.0.0
     */
    obterModeloEndereco() {
        const endereco =
            this.formulario
                .dataset
                .enderecoHistoricoArtista;

        if (
            typeof endereco !== 'string'
            || !endereco.includes(
                HistoricoArtistaMetalThursday
                    .MARCADOR_IDENTIFICADOR_ARTISTA,
            )
        ) {
            throw new TypeError(
                'O endereço do histórico de artistas é inválido.',
            );
        }

        const url = new URL(
            endereco,
            window.location.origin,
        );

        if (
            !['http:', 'https:'].includes(
                url.protocol,
            )
            || url.origin !== window.location.origin
        ) {
            throw new TypeError(
                'O endereço do histórico de artistas não pertence à aplicação.',
            );
        }

        return url.href;
    }

    /**
     * Obtém o identificador da MetalThursday atualmente editada.
     *
     * Na criação o atributo não existe e é devolvido nulo.
     *
     * @returns {number|null} Identificador persistido ou nulo.
     *
     * @throws {TypeError} Quando existe um valor inválido.
     *
     * @since 2.0.0
     */
    obterIdentificadorMetalThursday() {
        const valor =
            this.formulario
                .dataset
                .metalThursdayId;

        if (
            typeof valor === 'undefined'
            || valor === ''
        ) {
            return null;
        }

        if (!/^[1-9]\d*$/u.test(valor)) {
            throw new TypeError(
                'O identificador da MetalThursday em edição é inválido.',
            );
        }

        const identificador =
            Number(valor);

        if (
            !Number.isSafeInteger(
                identificador,
            )
            || identificador < 1
        ) {
            throw new TypeError(
                'O identificador da MetalThursday em edição é inválido.',
            );
        }

        return identificador;
    }

    /**
     * Configura os eventos delegados do formulário.
     *
     * A delegação permite tratar também secções adicionadas dinamicamente sem
     * criar um observador específico para cada novo item.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    configurarEventos() {
        this.formulario.addEventListener(
            'change',
            (evento) => {
                const selecao =
                    evento.target;

                if (
                    !(selecao instanceof HTMLSelectElement)
                    || !selecao.classList.contains(
                        'tom-select-artistas',
                    )
                ) {
                    return;
                }

                this.atualizarHistoricoSelecao(
                    selecao,
                );
            },
        );
    }

    /**
     * Inicializa os campos de artista já renderizados pelo servidor.
     *
     * Isto permite apresentar imediatamente o histórico de secções existentes
     * no formulário de edição.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    inicializarSecoesExistentes() {
        this.formulario
            .querySelectorAll(
                '.tom-select-artistas',
            )
            .forEach((selecao) => {
                if (
                    selecao
                    instanceof HTMLSelectElement
                    && selecao.value !== ''
                ) {
                    this.atualizarHistoricoSelecao(
                        selecao,
                    );
                }
            });
    }

    /**
     * Atualiza o histórico correspondente a uma seleção de artista.
     *
     * @param {HTMLSelectElement} selecao Campo do artista.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    async atualizarHistoricoSelecao(
        selecao,
    ) {
        const seccao = selecao.closest(
            '.item-seccao',
        );

        if (
            !(seccao instanceof HTMLElement)
            || !this.formulario.contains(
                seccao,
            )
        ) {
            return;
        }

        this.cancelarPedidoAnterior(
            seccao,
        );

        const identificadorArtista =
            this.normalizarIdentificadorArtista(
                selecao.value,
            );

        if (identificadorArtista === null) {
            this.ocultarHistorico(
                seccao,
            );

            return;
        }

        const controlador =
            new AbortController();

        this.controladoresPedidos.set(
            seccao,
            controlador,
        );

        this.apresentarCarregamento(
            seccao,
        );

        try {
            const aparicoes =
                await this.obterAparicoes(
                    identificadorArtista,
                    controlador.signal,
                );

            if (
                controlador.signal.aborted
                || !this.formulario.contains(
                    seccao,
                )
            ) {
                return;
            }

            this.apresentarAparicoes(
                seccao,
                aparicoes,
            );
        } catch (erro) {
            if (
                controlador.signal.aborted
                || (
                    erro instanceof DOMException
                    && erro.name === 'AbortError'
                )
            ) {
                return;
            }

            this.apresentarErro(
                seccao,
            );
        } finally {
            if (
                this.controladoresPedidos.get(
                    seccao,
                ) === controlador
            ) {
                this.controladoresPedidos.delete(
                    seccao,
                );
            }
        }
    }

    /**
     * Normaliza o identificador selecionado.
     *
     * @param {string} valor Valor do campo.
     *
     * @returns {number|null} Identificador ou nulo quando não existe seleção.
     *
     * @throws {TypeError} Quando o valor não representa um identificador
     *     positivo.
     *
     * @since 2.0.0
     */
    normalizarIdentificadorArtista(valor) {
        const valorNormalizado =
            valor.trim();

        if (valorNormalizado === '') {
            return null;
        }

        if (!/^[1-9]\d*$/u.test(
            valorNormalizado,
        )) {
            throw new TypeError(
                'O identificador do artista selecionado é inválido.',
            );
        }

        const identificador =
            Number(valorNormalizado);

        if (
            !Number.isSafeInteger(
                identificador,
            )
            || identificador < 1
        ) {
            throw new TypeError(
                'O identificador do artista selecionado é inválido.',
            );
        }

        return identificador;
    }

    /**
     * Cancela um pedido anterior pertencente à mesma secção.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    cancelarPedidoAnterior(seccao) {
        this.controladoresPedidos
            .get(
                seccao,
            )
            ?.abort();

        this.controladoresPedidos.delete(
            seccao,
        );
    }

    /**
     * Obtém as aparições publicadas de um artista.
     *
     * @param {number} identificadorArtista Identificador do artista.
     * @param {AbortSignal} sinal Sinal utilizado para cancelamento.
     *
     * @returns {Promise<Array<object>>} Aparições normalizadas.
     *
     * @throws {Error} Quando o pedido ou a resposta são inválidos.
     *
     * @since 2.0.0
     */
    async obterAparicoes(
        identificadorArtista,
        sinal,
    ) {
        const endereco =
            this.criarEnderecoPedido(
                identificadorArtista,
            );

        const resposta = await fetch(
            endereco,
            {
                method: 'GET',

                headers: {
                    Accept: 'application/json',

                    'X-Requested-With':
                        'XMLHttpRequest',
                },

                credentials: 'same-origin',

                signal: sinal,
            },
        );

        if (!resposta.ok) {
            throw new Error(
                `Não foi possível obter o histórico do artista (${resposta.status}).`,
            );
        }

        const dados =
            await resposta.json();

        if (
            typeof dados !== 'object'
            || dados === null
            || !Array.isArray(
                dados.aparicoes,
            )
        ) {
            throw new Error(
                'A resposta do histórico do artista é inválida.',
            );
        }

        return dados.aparicoes.map(
            (aparicao) =>
                this.normalizarAparicao(
                    aparicao,
                ),
        );
    }

    /**
     * Constrói o endereço do pedido.
     *
     * @param {number} identificadorArtista Identificador do artista.
     *
     * @returns {string} Endereço absoluto validado.
     *
     * @since 2.0.0
     */
    criarEnderecoPedido(
        identificadorArtista,
    ) {
        const endereco =
            this.modeloEndereco.replace(
                HistoricoArtistaMetalThursday
                    .MARCADOR_IDENTIFICADOR_ARTISTA,
                String(
                    identificadorArtista,
                ),
            );

        const url =
            new URL(endereco);

        if (
            this.identificadorMetalThursday
            !== null
        ) {
            url.searchParams.set(
                'metal_thursday_excluida',
                String(
                    this.identificadorMetalThursday,
                ),
            );
        }

        return url.href;
    }

    /**
     * Normaliza uma aparição recebida do servidor.
     *
     * @param {unknown} aparicao Dados recebidos.
     *
     * @returns {{
     *     identificador: number,
     *     tipo: string,
     *     titulo: string|null,
     *     ano: number|null,
     *     autor: string,
     *     data: string,
     *     enderecoMetalThursday: string
     * }} Aparição normalizada.
     *
     * @throws {Error} Quando os dados são inválidos.
     *
     * @since 2.0.0
     */
    normalizarAparicao(aparicao) {
        if (
            typeof aparicao !== 'object'
            || aparicao === null
        ) {
            throw new Error(
                'Foi recebida uma aparição inválida.',
            );
        }

        const {
            identificador,
            tipo,
            titulo,
            ano,
            autor,
            data,
            endereco_metal_thursday:
                enderecoMetalThursday,
        } = aparicao;

        if (
            !Number.isSafeInteger(
                identificador,
            )
            || identificador < 1
            || typeof tipo !== 'string'
            || tipo.trim() === ''
            || (
                titulo !== null
                && typeof titulo !== 'string'
            )
            || (
                ano !== null
                && (
                    !Number.isSafeInteger(
                        ano,
                    )
                    || ano < 1
                )
            )
            || typeof autor !== 'string'
            || autor.trim() === ''
            || typeof data !== 'string'
            || !/^\d{4}-\d{2}-\d{2}$/u.test(
                data,
            )
            || typeof enderecoMetalThursday
                !== 'string'
        ) {
            throw new Error(
                'Foi recebida uma aparição com dados inválidos.',
            );
        }

        const url =
            new URL(
                enderecoMetalThursday,
                window.location.origin,
            );

        if (
            !['http:', 'https:'].includes(
                url.protocol,
            )
            || url.origin !== window.location.origin
        ) {
            throw new Error(
                'A ligação da MetalThursday é inválida.',
            );
        }

        return {
            identificador,

            tipo:
                tipo.trim(),

            titulo:
                titulo === null
                    ? null
                    : titulo.trim(),

            ano,

            autor:
                autor.trim(),

            data,

            enderecoMetalThursday:
                url.href,
        };
    }

    /**
     * Apresenta o estado de carregamento.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarCarregamento(seccao) {
        const elementos =
            this.obterElementosHistorico(
                seccao,
            );

        elementos.lista.replaceChildren();

        elementos.estado.textContent =
            'A carregar aparições anteriores…';

        elementos.contentor.hidden =
            false;
    }

    /**
     * Apresenta as aparições recebidas.
     *
     * @param {HTMLElement} seccao Secção atual.
     * @param {Array<object>} aparicoes Aparições normalizadas.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarAparicoes(
        seccao,
        aparicoes,
    ) {
        const elementos =
            this.obterElementosHistorico(
                seccao,
            );

        elementos.lista.replaceChildren();

        if (aparicoes.length === 0) {
            elementos.estado.textContent =
                'Este artista ainda não tem aparições anteriores publicadas.';

            elementos.contentor.hidden =
                false;

            return;
        }

        elementos.estado.textContent = '';

        const itens =
            aparicoes.map(
                (aparicao) =>
                    this.criarElementoAparicao(
                        aparicao,
                    ),
            );

        elementos.lista.append(
            ...itens,
        );

        elementos.contentor.hidden =
            false;
    }

    /**
     * Cria um elemento visual para uma aparição.
     *
     * Todo o conteúdo textual é inserido através de `textContent`.
     * A ligação para a MetalThursday abre num novo contexto de navegação para
     * preservar os dados ainda não guardados no formulário atual.
     *
     * @param {object} aparicao Aparição normalizada.
     *
     * @returns {HTMLElement} Elemento criado.
     *
     * @since 2.0.0
     */
    criarElementoAparicao(aparicao) {
        const item =
            document.createElement(
                'div',
            );

        item.className =
            'border rounded p-2 mb-2';

        const titulo =
            document.createElement(
                'div',
            );

        titulo.className =
            'fw-semibold';

        const tituloAparicao =
            aparicao.titulo !== null
            && aparicao.titulo !== ''
                ? aparicao.titulo
                : 'Sem título';

        titulo.textContent =
            `${aparicao.tipo}: ${tituloAparicao}`;

        const detalhes =
            document.createElement(
                'div',
            );

        detalhes.className =
            'small text-muted mt-1';

        const partesDetalhes = [
            aparicao.ano !== null
                ? `Ano: ${aparicao.ano}`
                : 'Ano não indicado',

            `Autor: ${aparicao.autor}`,

            this.formatarData(
                aparicao.data,
            ),
        ];

        detalhes.textContent =
            partesDetalhes.join(
                ' · ',
            );

        const ligacao =
            document.createElement(
                'a',
            );

        ligacao.className =
            'small d-inline-block mt-1';

        ligacao.href =
            aparicao.enderecoMetalThursday;

        ligacao.target =
            '_blank';

        ligacao.rel =
            'noopener noreferrer';

        ligacao.textContent =
            'Ver MetalThursday';

        item.append(
            titulo,
            detalhes,
            ligacao,
        );

        return item;
    }

    /**
     * Formata uma data ISO curta sem aplicar conversões de fuso horário.
     *
     * @param {string} data Data no formato AAAA-MM-DD.
     *
     * @returns {string} Data no formato DD/MM/AAAA.
     *
     * @since 2.0.0
     */
    formatarData(data) {
        const [
            ano,
            mes,
            dia,
        ] = data.split('-');

        return `${dia}/${mes}/${ano}`;
    }

    /**
     * Apresenta uma falha ao obter o histórico.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarErro(seccao) {
        if (!this.formulario.contains(
            seccao,
        )) {
            return;
        }

        const elementos =
            this.obterElementosHistorico(
                seccao,
            );

        elementos.lista.replaceChildren();

        elementos.estado.textContent =
            'Não foi possível carregar as aparições anteriores deste artista.';

        elementos.contentor.hidden =
            false;
    }

    /**
     * Oculta e limpa o histórico de uma secção.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    ocultarHistorico(seccao) {
        const elementos =
            this.obterElementosHistorico(
                seccao,
            );

        elementos.estado.textContent = '';

        elementos.lista.replaceChildren();

        elementos.contentor.hidden =
            true;
    }

    /**
     * Obtém os elementos necessários à apresentação do histórico.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {{
     *     contentor: HTMLElement,
     *     estado: HTMLElement,
     *     lista: HTMLElement
     * }} Elementos encontrados.
     *
     * @throws {Error} Quando a estrutura HTML está incompleta.
     *
     * @since 2.0.0
     */
    obterElementosHistorico(seccao) {
        const contentor =
            seccao.querySelector(
                '.historico-artista-metal-thursday',
            );

        const estado =
            seccao.querySelector(
                '.estado-historico-artista-metal-thursday',
            );

        const lista =
            seccao.querySelector(
                '.lista-historico-artista-metal-thursday',
            );

        if (
            !(contentor instanceof HTMLElement)
            || !(estado instanceof HTMLElement)
            || !(lista instanceof HTMLElement)
        ) {
            throw new Error(
                'A estrutura do histórico do artista está incompleta.',
            );
        }

        return {
            contentor,
            estado,
            lista,
        };
    }
}

export default HistoricoArtistaMetalThursday;
