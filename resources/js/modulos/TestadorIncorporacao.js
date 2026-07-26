/**
 * Gere o teste e a pré-visualização de ligações incorporáveis.
 *
 * Os valores `link`, `youtube_video` e `youtube_playlist` permanecem
 * inalterados por corresponderem aos identificadores utilizados pela
 * aplicação.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class TestadorIncorporacao {
    /**
     * Cria um testador de incorporação para uma secção.
     *
     * @param {HTMLElement} elementoSeccao Elemento principal da secção.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor(elementoSeccao) {
        this.seccao =
            elementoSeccao instanceof HTMLElement
                ? elementoSeccao
                : null;

        this.campoLigacao =
            this.seccao?.querySelector('.link-input')
            ?? null;

        this.botaoTestar =
            this.seccao?.querySelector('.link-test-btn')
            ?? null;

        this.contentorResultados =
            this.seccao?.querySelector('.link-test-results')
            ?? null;

        this.campoTipoIncorporacao =
            this.seccao?.querySelector('.embed-type-input')
            ?? null;

        this.areaEstado =
            this.seccao?.querySelector('.test-status')
            ?? null;

        this.provedores =
            Array.isArray(window.embedProviders)
                ? window.embedProviders
                : [];

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
     * @returns {boolean}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    estaAtivo() {
        return this.seccao instanceof HTMLElement
            && this.campoLigacao instanceof HTMLInputElement
            && this.botaoTestar instanceof HTMLButtonElement
            && this.contentorResultados instanceof HTMLElement;
    }

    /**
     * Inicia os eventos do testador.
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
     * @since 1.0.0
     * @version 2.0.0
     */
    testar() {
        if (!this.estaAtivo()) {
            return;
        }

        const ligacao =
            this.campoLigacao.value.trim();

        this.repor();

        this.contentorResultados.style.display =
            'block';

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

        this.provedores.forEach((provedor) => {
            const identificador =
                this.detetarIdentificador(
                    provedor,
                    ligacao,
                );

            if (
                identificador !== null
                && this.apresentarPrevisualizacao(
                    provedor.type,
                    identificador,
                )
            ) {
                encontrouIncorporacao =
                    true;
            }
        });

        const identificadorYoutubeMusic =
            this.detetarVideoYoutubeMusic(
                ligacao,
            );

        if (
            identificadorYoutubeMusic !== null
            && this.apresentarPrevisualizacao(
                'youtube_video',
                identificadorYoutubeMusic,
            )
        ) {
            encontrouIncorporacao =
                true;
        }

        this.apresentarEstado(
            encontrouIncorporacao
                ? 'Teste concluído. Confirma a opção correta.'
                : 'Não foi detetada nenhuma incorporação automática. A ligação será guardada como uma ligação simples.',
            encontrouIncorporacao
                ? 'sucesso'
                : 'aviso',
        );
    }

    /**
     * Obtém o identificador através da configuração de um provedor.
     *
     * @param {unknown} provedor Configuração do provedor.
     * @param {string} ligacao Ligação a testar.
     *
     * @returns {string|null}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    detetarIdentificador(
        provedor,
        ligacao,
    ) {
        if (
            typeof provedor !== 'object'
            || provedor === null
            || typeof provedor.regex !== 'string'
            || typeof provedor.type !== 'string'
        ) {
            return null;
        }

        try {
            const expressao =
                new RegExp(
                    provedor.regex,
                );

            const correspondencia =
                expressao.exec(
                    ligacao,
                );

            if (
                correspondencia
                && typeof correspondencia[1] === 'string'
                && correspondencia[1].trim() !== ''
            ) {
                return correspondencia[1].trim();
            }
        } catch {
            return null;
        }

        return null;
    }

    /**
     * Deteta o identificador de um vídeo numa ligação do YouTube Music.
     *
     * Uma ligação `music.youtube.com/watch?v=...` representa um vídeo e não
     * uma lista de reprodução.
     *
     * @param {string} ligacao Ligação a testar.
     *
     * @returns {string|null}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    detetarVideoYoutubeMusic(
        ligacao,
    ) {
        try {
            const url =
                new URL(
                    ligacao,
                );

            if (
                url.hostname !== 'music.youtube.com'
                || url.pathname !== '/watch'
                || url.searchParams.has('list')
            ) {
                return null;
            }

            const identificador =
                url.searchParams.get('v');

            return identificador?.trim()
                || null;
        } catch {
            return null;
        }
    }

    /**
     * Apresenta a pré-visualização de uma incorporação.
     *
     * @param {string} tipo Tipo da incorporação.
     * @param {string} identificador Identificador externo.
     *
     * @returns {boolean} Indica se a pré-visualização foi apresentada.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    apresentarPrevisualizacao(
        tipo,
        identificador,
    ) {
        if (
            !(this.seccao instanceof HTMLElement)
        ) {
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

        const chavePrevisualizacao =
            `${tipo}:${identificador}`;

        if (
            this.previsualizacoesApresentadas.has(
                chavePrevisualizacao,
            )
        ) {
            return false;
        }

        const nomeBase =
            tipo.replace(
                /^youtube_/,
                '',
            );

        const contentorOpcao =
            this.seccao.querySelector(
                `.${nomeBase}-option`,
            );

        const contentorPrevisualizacao =
            this.seccao.querySelector(
                `.${nomeBase}-preview-container`,
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
            'embed-responsive-item w-100';

        iframe.height =
            '200';

        iframe.src =
            urlIncorporacao;

        iframe.title =
            'Pré-visualização da incorporação';

        iframe.loading =
            'lazy';

        iframe.referrerPolicy =
            'strict-origin-when-cross-origin';

        iframe.allow =
            'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';

        iframe.allowFullscreen =
            true;

        contentorPrevisualizacao.replaceChildren(
            iframe,
        );

        contentorOpcao.style.display =
            'block';

        this.previsualizacoesApresentadas.add(
            chavePrevisualizacao,
        );

        return true;
    }

    /**
     * Cria o endereço utilizado no elemento de incorporação.
     *
     * @param {string} tipo Tipo da incorporação.
     * @param {string} identificador Identificador externo.
     *
     * @returns {string|null}
     *
     * @since 2.0.0
     * @version 1.0.0
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

        if (tipo === 'youtube_video') {
            return `https://www.youtube.com/embed/${encodeURIComponent(
                identificador,
            )}`;
        }

        if (tipo === 'youtube_playlist') {
            const url =
                new URL(
                    'https://www.youtube.com/embed/videoseries',
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
     * @since 1.0.0
     * @version 2.0.0
     */
    atualizarEscolha(
        evento,
    ) {
        const campo =
            evento.target;

        if (
            !(campo instanceof HTMLInputElement)
            || !campo.classList.contains(
                'embed-choice-radio',
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
     * @since 2.0.0
     * @version 1.0.0
     */
    apresentarCarregamento() {
        if (
            !(this.areaEstado instanceof HTMLElement)
        ) {
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
            'test-status small mt-2';

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
     * @since 2.0.0
     * @version 1.0.0
     */
    apresentarEstado(
        mensagem,
        tipo,
    ) {
        if (
            !(this.areaEstado instanceof HTMLElement)
        ) {
            return;
        }

        this.areaEstado.textContent =
            mensagem;

        this.areaEstado.className = [
            'test-status',
            'small',
            'mt-2',
            tipo === 'sucesso'
                ? 'text-success'
                : 'text-warning',
        ].join(' ');
    }

    /**
     * Limpa as pré-visualizações e repõe a escolha predefinida.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    repor() {
        if (
            !(this.seccao instanceof HTMLElement)
        ) {
            return;
        }

        if (
            this.contentorResultados instanceof HTMLElement
        ) {
            this.contentorResultados.style.display =
                'none';
        }

        [
            'video',
            'playlist',
        ].forEach((nomeBase) => {
            const contentorOpcao =
                this.seccao.querySelector(
                    `.${nomeBase}-option`,
                );

            const contentorPrevisualizacao =
                this.seccao.querySelector(
                    `.${nomeBase}-preview-container`,
                );

            if (
                contentorOpcao instanceof HTMLElement
            ) {
                contentorOpcao.style.display =
                    'none';
            }

            if (
                contentorPrevisualizacao instanceof HTMLElement
            ) {
                contentorPrevisualizacao.replaceChildren();
            }
        });

        if (
            this.areaEstado instanceof HTMLElement
        ) {
            this.areaEstado.textContent =
                '';

            this.areaEstado.className =
                'test-status small mt-2';
        }

        const opcaoLigacao =
            this.seccao.querySelector(
                '.embed-choice-radio[value="link"], input[value="link"]',
            );

        if (
            opcaoLigacao instanceof HTMLInputElement
        ) {
            opcaoLigacao.checked =
                true;
        }

        if (
            this.campoTipoIncorporacao instanceof HTMLInputElement
        ) {
            this.campoTipoIncorporacao.value =
                'link';
        }

        this.previsualizacoesApresentadas.clear();
    }

    /**
     * Remove os eventos associados ao testador.
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
