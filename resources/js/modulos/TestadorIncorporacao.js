/**
 * Gere o teste e a pré-visualização de ligações incorporáveis.
 *
 * @since 1.0.0
 * @version 3.0.0
 */
class TestadorIncorporacao {
    /**
     * Tipos de incorporação reconhecidos pela interface.
     *
     * @type {Readonly<Record<string, string>>}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    static TIPOS = Object.freeze({
        ligacao:
            'ligacao',

        videoYouTube:
            'video_youtube',

        listaReproducaoYouTube:
            'lista_reproducao_youtube',
    });

    /**
     * Hosts reconhecidos como pertencentes ao YouTube.
     *
     * @type {ReadonlyArray<string>}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    static HOSTS_YOUTUBE = Object.freeze([
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'music.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'youtu.be',
        'www.youtu.be',
    ]);

    /**
     * Segmentos que podem anteceder um identificador de vídeo.
     *
     * @type {ReadonlyArray<string>}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    static SEGMENTOS_VIDEO_YOUTUBE = Object.freeze([
        'embed',
        'shorts',
        'live',
    ]);

    /**
     * Cria um testador de incorporação para uma secção.
     *
     * @param {HTMLElement} elementoSeccao Elemento principal da secção.
     * @param {Array<object>} fornecedoresIncorporacao
     *     Definições de reconhecimento preparadas pelo servidor.
     *
     * @throws {TypeError} Quando as definições são inválidas.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    constructor(
        elementoSeccao,
        fornecedoresIncorporacao = [],
    ) {
        this.seccao =
            elementoSeccao instanceof HTMLElement
                ? elementoSeccao
                : null;

        this.campoLigacao =
            this.seccao?.querySelector(
                '.campo-ligacao',
            )
            ?? null;

        this.botaoTestar =
            this.seccao?.querySelector(
                '.botao-testar-incorporacao',
            )
            ?? null;

        this.contentorResultados =
            this.seccao?.querySelector(
                '.resultados-teste-incorporacao',
            )
            ?? null;

        this.campoTipoIncorporacao =
            this.seccao?.querySelector(
                '.campo-tipo-incorporacao',
            )
            ?? null;

        this.areaEstado =
            this.seccao?.querySelector(
                '.estado-teste-incorporacao',
            )
            ?? null;

        this.fornecedores =
            this.normalizarFornecedores(
                fornecedoresIncorporacao,
            );

        this.previsualizacoesApresentadas =
            new Set();

        this.iniciado =
            false;

        this.aoClicarTestar =
            () => this.testar();

        this.aoAlterarEscolha =
            (evento) => this.atualizarEscolha(evento);

        if (this.estaAtivo()) {
            this.iniciar();
        }
    }

    /**
     * Verifica se foram encontrados os elementos obrigatórios.
     *
     * @returns {boolean} Verdadeiro quando o testador pode funcionar.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    estaAtivo() {
        return this.seccao instanceof HTMLElement
            && this.campoLigacao instanceof HTMLInputElement
            && this.botaoTestar instanceof HTMLButtonElement
            && this.contentorResultados instanceof HTMLElement
            && this.campoTipoIncorporacao instanceof HTMLInputElement;
    }

    /**
     * Inicia os eventos do testador.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        if (!this.estaAtivo() || this.iniciado) {
            return;
        }

        this.botaoTestar.addEventListener(
            'click',
            this.aoClicarTestar,
        );

        this.contentorResultados.addEventListener(
            'change',
            this.aoAlterarEscolha,
        );

        this.iniciado =
            true;
    }

    /**
     * Testa a ligação indicada pelo utilizador.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    testar() {
        if (!this.estaAtivo()) {
            return;
        }

        const ligacao =
            this.campoLigacao.value.trim();

        this.repor();

        this.contentorResultados.hidden =
            false;

        if (ligacao === '') {
            this.apresentarEstado(
                'Indica uma ligação para testar.',
                'aviso',
            );

            return;
        }

        this.apresentarCarregamento();

        let encontrouIncorporacao =
            false;

        this.fornecedores.forEach((fornecedor) => {
            const identificador =
                this.detetarIdentificador(
                    fornecedor,
                    ligacao,
                );

            if (
                identificador !== null
                && this.apresentarPrevisualizacao(
                    fornecedor.tipo,
                    fornecedor.etiqueta,
                    identificador,
                )
            ) {
                encontrouIncorporacao =
                    true;
            }
        });

        this.detetarIncorporacoesYouTube(
            ligacao,
        ).forEach((incorporacao) => {
            if (
                this.apresentarPrevisualizacao(
                    incorporacao.tipo,
                    incorporacao.etiqueta,
                    incorporacao.identificador,
                )
            ) {
                encontrouIncorporacao =
                    true;
            }
        });

        this.apresentarEstado(
            encontrouIncorporacao
                ? 'Teste concluído. Confirma a opção correta.'
                : 'Não foi detetada uma incorporação automática. A ligação será guardada como ligação simples.',
            encontrouIncorporacao
                ? 'sucesso'
                : 'aviso',
        );
    }

    /**
     * Normaliza as definições recebidas do servidor.
     *
     * @param {unknown} fornecedores Definições recebidas.
     *
     * @returns {Array<{
     *     tipo: string,
     *     etiqueta: string,
     *     expressaoRegular: RegExp
     * }>} Definições normalizadas.
     *
     * @throws {TypeError} Quando alguma definição é inválida.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    normalizarFornecedores(fornecedores) {
        if (!Array.isArray(fornecedores)) {
            throw new TypeError(
                'As definições das incorporações devem ser uma lista.',
            );
        }

        return fornecedores.map((fornecedor) => {
            if (
                typeof fornecedor !== 'object'
                || fornecedor === null
                || Array.isArray(fornecedor)
                || typeof fornecedor.tipo !== 'string'
                || typeof fornecedor.etiqueta !== 'string'
                || typeof fornecedor.expressao_regular !== 'string'
            ) {
                throw new TypeError(
                    'Foi recebida uma definição de incorporação inválida.',
                );
            }

            const tipo =
                fornecedor.tipo.trim();

            const etiqueta =
                fornecedor.etiqueta.trim();

            const expressao =
                fornecedor.expressao_regular.trim();

            if (
                ![
                    TestadorIncorporacao.TIPOS.videoYouTube,
                    TestadorIncorporacao.TIPOS
                        .listaReproducaoYouTube,
                ].includes(tipo)
                || etiqueta === ''
                || expressao === ''
            ) {
                throw new TypeError(
                    'Foi recebida uma definição de incorporação não suportada.',
                );
            }

            try {
                return {
                    tipo,
                    etiqueta,
                    expressaoRegular:
                        new RegExp(expressao),
                };
            } catch {
                throw new TypeError(
                    `A expressão regular da incorporação "${etiqueta}" é inválida.`,
                );
            }
        });
    }

    /**
     * Obtém o identificador externo através de uma definição.
     *
     * @param {{expressaoRegular: RegExp}} fornecedor Definição utilizada.
     * @param {string} ligacao Ligação a testar.
     *
     * @returns {string|null} Identificador encontrado ou nulo.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    detetarIdentificador(
        fornecedor,
        ligacao,
    ) {
        const correspondencia =
            fornecedor.expressaoRegular.exec(
                ligacao,
            );

        if (
            correspondencia === null
            || typeof correspondencia[1] !== 'string'
            || correspondencia[1].trim() === ''
        ) {
            return null;
        }

        return correspondencia[1].trim();
    }

    /**
     * Deteta incorporações do YouTube através da estrutura do URL.
     *
     * Esta deteção complementa as expressões preparadas pelo servidor e cobre
     * todos os hosts reconhecidos pela aplicação, incluindo o YouTube Music.
     *
     * @param {string} ligacao Ligação a analisar.
     *
     * @returns {Array<{
     *     tipo: string,
     *     etiqueta: string,
     *     identificador: string
     * }>} Incorporações detetadas.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    detetarIncorporacoesYouTube(ligacao) {
        let url;

        try {
            url = new URL(
                ligacao,
            );
        } catch {
            return [];
        }

        const host =
            url.hostname
                .toLowerCase()
                .replace(
                    /\.$/,
                    '',
                );

        if (
            !['http:', 'https:'].includes(url.protocol)
            || !TestadorIncorporacao
                .HOSTS_YOUTUBE
                .includes(host)
        ) {
            return [];
        }

        const incorporacoes =
            [];

        const identificadorLista =
            url.searchParams.get(
                'list',
            );

        if (
            typeof identificadorLista === 'string'
            && identificadorLista.length >= 10
            && identificadorLista.length <= 150
            && /^[A-Za-z0-9_-]+$/.test(
                identificadorLista,
            )
        ) {
            incorporacoes.push({
                tipo:
                    TestadorIncorporacao
                        .TIPOS
                        .listaReproducaoYouTube,

                etiqueta:
                    'Lista de reprodução do YouTube',

                identificador:
                    identificadorLista,
            });
        }

        const segmentos =
            url.pathname
                .split('/')
                .filter(
                    (segmento) =>
                        segmento !== '',
                );

        let identificadorVideo;

        if (
            ['youtu.be', 'www.youtu.be']
                .includes(host)
        ) {
            identificadorVideo =
                segmentos[0]
                ?? null;
        } else {
            const identificadorParametro =
                url.searchParams.get(
                    'v',
                );

            if (identificadorParametro !== null) {
                identificadorVideo =
                    identificadorParametro;
            } else if (
                TestadorIncorporacao
                    .SEGMENTOS_VIDEO_YOUTUBE
                    .includes(
                        segmentos[0]
                        ?? '',
                    )
            ) {
                identificadorVideo =
                    segmentos[1]
                    ?? null;
            } else {
                identificadorVideo =
                    null;
            }
        }

        if (
            typeof identificadorVideo === 'string'
            && /^[A-Za-z0-9_-]{11}$/.test(
                identificadorVideo,
            )
        ) {
            incorporacoes.push({
                tipo:
                    TestadorIncorporacao
                        .TIPOS
                        .videoYouTube,

                etiqueta:
                    'Vídeo do YouTube',

                identificador:
                    identificadorVideo,
            });
        }

        return incorporacoes;
    }

    /**
     * Apresenta a pré-visualização de uma incorporação.
     *
     * @param {string} tipo Tipo da incorporação.
     * @param {string} etiqueta Etiqueta apresentada.
     * @param {string} identificador Identificador externo.
     *
     * @returns {boolean} Indica se a pré-visualização foi apresentada.
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    apresentarPrevisualizacao(
        tipo,
        etiqueta,
        identificador,
    ) {
        if (!(this.seccao instanceof HTMLElement)) {
            return false;
        }

        const urlIncorporacao =
            this.criarUrlIncorporacao(
                tipo,
                identificador,
            );

        if (urlIncorporacao === null) {
            return false;
        }

        const configuracaoVisual =
            this.obterConfiguracaoVisual(
                tipo,
            );

        if (configuracaoVisual === null) {
            return false;
        }

        const chavePrevisualizacao =
            `${tipo}:${identificador}`;

        if (
            this.previsualizacoesApresentadas.has(
                chavePrevisualizacao,
            )
        ) {
            return false;
        }

        const contentorOpcao =
            this.seccao.querySelector(
                configuracaoVisual.seletorOpcao,
            );

        const contentorPrevisualizacao =
            this.seccao.querySelector(
                configuracaoVisual
                    .seletorPrevisualizacao,
            );

        if (
            !(contentorOpcao instanceof HTMLElement)
            || !(contentorPrevisualizacao instanceof HTMLElement)
        ) {
            return false;
        }

        const iframe =
            document.createElement('iframe');

        iframe.className =
            'w-100 border-0';

        iframe.height =
            '200';

        iframe.src =
            urlIncorporacao;

        iframe.title =
            `Pré-visualização: ${etiqueta}`;

        iframe.loading =
            'lazy';

        iframe.referrerPolicy =
            'strict-origin-when-cross-origin';

        iframe.allow =
            'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';

        iframe.setAttribute(
            'sandbox',
            'allow-scripts allow-same-origin allow-presentation allow-popups',
        );

        iframe.allowFullscreen =
            true;

        contentorPrevisualizacao.replaceChildren(
            iframe,
        );

        contentorOpcao.hidden =
            false;

        this.previsualizacoesApresentadas.add(
            chavePrevisualizacao,
        );

        return true;
    }

    /**
     * Obtém os seletores visuais associados a um tipo.
     *
     * @param {string} tipo Tipo da incorporação.
     *
     * @returns {{
     *     seletorOpcao: string,
     *     seletorPrevisualizacao: string
     * }|null} Seletores ou nulo.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    obterConfiguracaoVisual(tipo) {
        switch (tipo) {
            case TestadorIncorporacao.TIPOS.videoYouTube:
                return {
                    seletorOpcao:
                        '.opcao-incorporacao-video',

                    seletorPrevisualizacao:
                        '.previsualizacao-video',
                };

            case TestadorIncorporacao.TIPOS
                .listaReproducaoYouTube:
                return {
                    seletorOpcao:
                        '.opcao-incorporacao-lista-reproducao',

                    seletorPrevisualizacao:
                        '.previsualizacao-lista-reproducao',
                };

            default:
                return null;
        }
    }

    /**
     * Cria o endereço utilizado no elemento de incorporação.
     *
     * @param {string} tipo Tipo da incorporação.
     * @param {string} identificador Identificador externo.
     *
     * @returns {string|null} Endereço da incorporação ou nulo.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    criarUrlIncorporacao(
        tipo,
        identificador,
    ) {
        if (
            typeof identificador !== 'string'
            || !/^[A-Za-z0-9_-]+$/.test(
                identificador,
            )
        ) {
            return null;
        }

        if (
            tipo
            === TestadorIncorporacao.TIPOS.videoYouTube
            && identificador.length === 11
        ) {
            return `https://www.youtube-nocookie.com/embed/${encodeURIComponent(
                identificador,
            )}?rel=0`;
        }

        if (
            tipo
            === TestadorIncorporacao.TIPOS
                .listaReproducaoYouTube
            && identificador.length >= 10
            && identificador.length <= 150
        ) {
            const url =
                new URL(
                    'https://www.youtube-nocookie.com/embed/videoseries',
                );

            url.searchParams.set(
                'list',
                identificador,
            );

            return url.toString();
        }

        return null;
    }

    /**
     * Atualiza o tipo de incorporação selecionado.
     *
     * @param {Event} evento Evento de alteração.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    atualizarEscolha(
        evento,
    ) {
        const campo =
            evento.target;

        if (
            !(campo instanceof HTMLInputElement)
            || !campo.classList.contains(
                'escolha-incorporacao',
            )
            || !(this.campoTipoIncorporacao instanceof HTMLInputElement)
        ) {
            return;
        }

        this.campoTipoIncorporacao.value =
            campo.value;
    }

    /**
     * Apresenta o estado de carregamento.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    apresentarCarregamento() {
        if (!(this.areaEstado instanceof HTMLElement)) {
            return;
        }

        const indicador =
            document.createElement('span');

        indicador.className =
            'spinner-border spinner-border-sm';

        indicador.setAttribute(
            'role',
            'status',
        );

        indicador.setAttribute(
            'aria-hidden',
            'true',
        );

        this.areaEstado.className =
            'estado-teste-incorporacao small mb-2';

        this.areaEstado.replaceChildren(
            indicador,
            document.createTextNode(
                ' A gerar pré-visualizações...',
            ),
        );
    }

    /**
     * Apresenta o resultado do teste.
     *
     * @param {string} mensagem Mensagem a apresentar.
     * @param {'sucesso'|'aviso'} tipo Tipo visual da mensagem.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    apresentarEstado(
        mensagem,
        tipo,
    ) {
        if (!(this.areaEstado instanceof HTMLElement)) {
            return;
        }

        this.areaEstado.textContent =
            mensagem;

        this.areaEstado.className = [
            'estado-teste-incorporacao',
            'small',
            'mb-2',
            tipo === 'sucesso'
                ? 'text-success'
                : 'text-warning',
        ].join(' ');
    }

    /**
     * Limpa as pré-visualizações e repõe a ligação simples.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 3.0.0
     */
    repor() {
        if (!(this.seccao instanceof HTMLElement)) {
            return;
        }

        if (this.contentorResultados instanceof HTMLElement) {
            this.contentorResultados.hidden =
                true;
        }

        [
            {
                seletorOpcao:
                    '.opcao-incorporacao-video',

                seletorPrevisualizacao:
                    '.previsualizacao-video',
            },
            {
                seletorOpcao:
                    '.opcao-incorporacao-lista-reproducao',

                seletorPrevisualizacao:
                    '.previsualizacao-lista-reproducao',
            },
        ].forEach((configuracao) => {
            const contentorOpcao =
                this.seccao.querySelector(
                    configuracao.seletorOpcao,
                );

            const contentorPrevisualizacao =
                this.seccao.querySelector(
                    configuracao.seletorPrevisualizacao,
                );

            if (contentorOpcao instanceof HTMLElement) {
                contentorOpcao.hidden =
                    true;
            }

            if (
                contentorPrevisualizacao
                instanceof HTMLElement
            ) {
                contentorPrevisualizacao.replaceChildren();
            }
        });

        if (this.areaEstado instanceof HTMLElement) {
            this.areaEstado.textContent =
                '';

            this.areaEstado.className =
                'estado-teste-incorporacao small mb-2';
        }

        const opcaoLigacao =
            this.seccao.querySelector(
                `.escolha-incorporacao[value="${TestadorIncorporacao.TIPOS.ligacao}"]`,
            );

        if (opcaoLigacao instanceof HTMLInputElement) {
            opcaoLigacao.checked =
                true;
        }

        if (
            this.campoTipoIncorporacao
            instanceof HTMLInputElement
        ) {
            this.campoTipoIncorporacao.value =
                TestadorIncorporacao.TIPOS.ligacao;
        }

        this.previsualizacoesApresentadas.clear();
    }

    /**
     * Remove os eventos associados ao testador.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        if (!this.iniciado) {
            return;
        }

        this.botaoTestar?.removeEventListener(
            'click',
            this.aoClicarTestar,
        );

        this.contentorResultados?.removeEventListener(
            'change',
            this.aoAlterarEscolha,
        );

        this.iniciado =
            false;
    }
}

export default TestadorIncorporacao;
