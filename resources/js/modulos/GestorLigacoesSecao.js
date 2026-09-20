import GestorSeccoes from './GestorSeccoes';

/**
 * Gere as ligações públicas associadas a uma secção musical.
 *
 * A plataforma apresentada no navegador é apenas informativa. O servidor
 * continua a detetar e a persistir a plataforma de forma autoritativa.
 *
 * @since 2.0.0
 */
class GestorLigacoesSecao {
    /**
     * Marcador utilizado pelo modelo HTML para o índice de uma ligação.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static MARCADOR_INDICE = '__INDICE_LIGACAO__';

    /**
     * Plataformas reconhecidas pelo editor.
     *
     * @type {Readonly<Record<string, Readonly<{
     *     valor: string,
     *     etiqueta: string,
     *     suportaIncorporacao: boolean
     * }>>>}
     *
     * @since 2.0.0
     */
    static PLATAFORMAS = Object.freeze({
        spotify: Object.freeze({
            valor: 'spotify',
            etiqueta: 'Spotify',
            suportaIncorporacao: true,
        }),

        appleMusic: Object.freeze({
            valor: 'apple_music',
            etiqueta: 'Apple Music',
            suportaIncorporacao: true,
        }),

        youtube: Object.freeze({
            valor: 'youtube',
            etiqueta: 'YouTube',
            suportaIncorporacao: true,
        }),

        outro: Object.freeze({
            valor: 'outro',
            etiqueta: 'Outro',
            suportaIncorporacao: false,
        }),
    });

    /**
     * Cria e inicializa o gestor.
     *
     * @param {HTMLElement} seccao Secção gerida.
     *
     * @throws {TypeError} Quando a estrutura obrigatória não existe.
     *
     * @since 2.0.0
     */
    constructor(seccao) {
        if (!(seccao instanceof HTMLElement)) {
            throw new TypeError(
                'A secção das ligações deve ser um elemento HTML válido.',
            );
        }

        this.seccao = seccao;

        this.editor = seccao.querySelector(
            '[data-editor-ligacoes-seccao]',
        );

        this.lista = seccao.querySelector(
            '[data-lista-ligacoes-seccao]',
        );

        this.modelo = seccao.querySelector(
            '[data-modelo-ligacao-seccao]',
        );

        this.botaoAdicionar = seccao.querySelector(
            '[data-acao-ligacao-seccao="adicionar"]',
        );

        this.estado = seccao.querySelector(
            '[data-estado-ligacoes-seccao]',
        );

        if (
            !(this.editor instanceof HTMLElement)
            || !(this.lista instanceof HTMLElement)
            || !(this.modelo instanceof HTMLTemplateElement)
            || !(this.botaoAdicionar instanceof HTMLButtonElement)
            || !(this.estado instanceof HTMLElement)
        ) {
            throw new TypeError(
                'A estrutura do editor de ligações da secção é inválida.',
            );
        }

        this.nomeBaseCampo =
            this.normalizarNomeBaseCampo(
                this.editor.dataset.nomeBaseCampo,
            );

        this.maximoLigacoes =
            this.normalizarMaximoLigacoes(
                this.editor.dataset.maximoLigacoes,
            );

        this.editor.addEventListener(
            'click',
            (evento) => {
                this.tratarClique(
                    evento,
                );
            },
        );

        this.editor.addEventListener(
            'input',
            (evento) => {
                this.tratarAlteracaoUrl(
                    evento,
                );
            },
        );

        this.seccao.addEventListener(
            GestorSeccoes.EVENTO_ESTADO_ATUALIZADO,
            () => {
                this.normalizar();
            },
        );

        this.normalizar();
    }

    /**
     * Deteta a plataforma de um URL absoluto HTTP ou HTTPS.
     *
     * @param {unknown} valor URL recebido.
     * @returns {{
     *     valor: string,
     *     etiqueta: string,
     *     suportaIncorporacao: boolean
     * }|null} Plataforma detetada ou nulo quando o URL ainda não é válido.
     *
     * @since 2.0.0
     */
    static detetarPlataforma(valor) {
        if (
            typeof valor !== 'string'
            || valor.trim() === ''
        ) {
            return null;
        }

        let url;

        try {
            url = new URL(
                valor.trim(),
            );
        } catch {
            return null;
        }

        if (
            !['http:', 'https:'].includes(
                url.protocol,
            )
        ) {
            return null;
        }

        const host =
            url.hostname
                .toLowerCase()
                .replace(
                    /\.$/u,
                    '',
                );

        if (
            GestorLigacoesSecao.dominioCorresponde(
                host,
                'spotify.com',
            )
            || GestorLigacoesSecao.dominioCorresponde(
                host,
                'spotify.link',
            )
        ) {
            return {
                ...GestorLigacoesSecao
                    .PLATAFORMAS
                    .spotify,
            };
        }

        if (
            GestorLigacoesSecao.dominioCorresponde(
                host,
                'music.apple.com',
            )
        ) {
            return {
                ...GestorLigacoesSecao
                    .PLATAFORMAS
                    .appleMusic,
            };
        }

        if (
            GestorLigacoesSecao.dominioCorresponde(
                host,
                'youtube.com',
            )
            || GestorLigacoesSecao.dominioCorresponde(
                host,
                'youtu.be',
            )
        ) {
            return {
                ...GestorLigacoesSecao
                    .PLATAFORMAS
                    .youtube,
            };
        }

        return {
            ...GestorLigacoesSecao
                .PLATAFORMAS
                .outro,
        };
    }

    /**
     * Verifica se um host corresponde a um domínio ou respetivo subdomínio.
     *
     * @param {string} host Host normalizado.
     * @param {string} dominio Domínio base.
     * @returns {boolean} Verdadeiro quando existe correspondência segura.
     *
     * @since 2.0.0
     */
    static dominioCorresponde(
        host,
        dominio,
    ) {
        return host === dominio
            || host.endsWith(
                `.${dominio}`,
            );
    }

    /**
     * Trata os cliques nos controlos do editor.
     *
     * @param {MouseEvent} evento Evento recebido.
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarClique(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botao = evento.target.closest(
            '[data-acao-ligacao-seccao]',
        );

        if (!(botao instanceof HTMLButtonElement)) {
            return;
        }

        const acao =
            botao.dataset.acaoLigacaoSeccao
            ?? '';

        switch (acao) {
            case 'adicionar':
                this.adicionar();
                break;

            case 'remover':
                this.remover(
                    botao,
                );
                break;

            case 'subir':
                this.mover(
                    botao,
                    -1,
                );
                break;

            case 'descer':
                this.mover(
                    botao,
                    1,
                );
                break;

            default:
                break;
        }
    }

    /**
     * Atualiza uma linha quando o respetivo URL é alterado.
     *
     * @param {Event} evento Evento recebido.
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarAlteracaoUrl(evento) {
        const campo = evento.target;

        if (
            !(campo instanceof HTMLInputElement)
            || !campo.matches(
                '[data-campo-url-ligacao]',
            )
        ) {
            return;
        }

        const linha = campo.closest(
            '[data-ligacao-seccao]',
        );

        if (linha instanceof HTMLElement) {
            this.atualizarEstadoLinha(
                linha,
            );
        }
    }

    /**
     * Adiciona uma nova ligação vazia.
     *
     * @returns {HTMLElement|null} Linha criada ou nulo se o limite foi atingido.
     *
     * @since 2.0.0
     */
    adicionar() {
        const linhas =
            this.obterLinhas();

        if (
            linhas.length
            >= this.maximoLigacoes
        ) {
            this.atualizarEstadoGeral();

            return null;
        }

        const conteudo =
            this.modelo.innerHTML
                .replaceAll(
                    GestorLigacoesSecao
                        .MARCADOR_INDICE,
                    String(
                        linhas.length,
                    ),
                )
                .trim();

        const modeloTemporario =
            document.createElement(
                'template',
            );

        modeloTemporario.innerHTML =
            conteudo;

        const elementos =
            Array.from(
                modeloTemporario
                    .content
                    .children,
            );

        if (
            elementos.length !== 1
            || !(elementos[0] instanceof HTMLElement)
            || !elementos[0].matches(
                '[data-ligacao-seccao]',
            )
        ) {
            throw new Error(
                'O modelo deve produzir exatamente uma ligação válida.',
            );
        }

        const linha =
            elementos[0];

        this.lista.append(
            linha,
        );

        this.normalizar();

        const campoUrl = linha.querySelector(
            '[data-campo-url-ligacao]',
        );

        if (campoUrl instanceof HTMLInputElement) {
            campoUrl.focus();
        }

        return linha;
    }

    /**
     * Remove uma ligação.
     *
     * @param {HTMLButtonElement} botao Botão utilizado.
     * @returns {boolean} Verdadeiro quando a linha foi removida.
     *
     * @since 2.0.0
     */
    remover(botao) {
        const linha = botao.closest(
            '[data-ligacao-seccao]',
        );

        if (
            !(linha instanceof HTMLElement)
            || !this.lista.contains(
                linha,
            )
        ) {
            return false;
        }

        linha.remove();

        this.normalizar();

        return true;
    }

    /**
     * Move uma ligação uma posição para cima ou para baixo.
     *
     * @param {HTMLButtonElement} botao Botão utilizado.
     * @param {-1|1} direcao Direção pretendida.
     * @returns {boolean} Verdadeiro quando a linha foi movida.
     *
     * @since 2.0.0
     */
    mover(
        botao,
        direcao,
    ) {
        const linha = botao.closest(
            '[data-ligacao-seccao]',
        );

        if (
            !(linha instanceof HTMLElement)
            || !this.lista.contains(
                linha,
            )
            || ![-1, 1].includes(
                direcao,
            )
        ) {
            return false;
        }

        const referencia =
            direcao === -1
                ? linha.previousElementSibling
                : linha.nextElementSibling;

        if (!(referencia instanceof HTMLElement)) {
            return false;
        }

        if (direcao === -1) {
            this.lista.insertBefore(
                linha,
                referencia,
            );
        } else {
            this.lista.insertBefore(
                referencia,
                linha,
            );
        }

        this.normalizar();

        return true;
    }

    /**
     * Normaliza índices, estados e botões de todas as ligações.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    normalizar() {
        const linhas =
            this.obterLinhas();

        linhas.forEach(
            (
                linha,
                indice,
            ) => {
                this.atualizarIdentificadoresLinha(
                    linha,
                    indice,
                );

                this.atualizarEstadoLinha(
                    linha,
                );

                const botaoSubir = linha.querySelector(
                    '[data-acao-ligacao-seccao="subir"]',
                );

                const botaoDescer = linha.querySelector(
                    '[data-acao-ligacao-seccao="descer"]',
                );

                if (botaoSubir instanceof HTMLButtonElement) {
                    botaoSubir.disabled =
                        !this.detalhesAtivos()
                        || indice === 0;
                }

                if (botaoDescer instanceof HTMLButtonElement) {
                    botaoDescer.disabled =
                        !this.detalhesAtivos()
                        || indice === linhas.length - 1;
                }
            },
        );

        this.atualizarEstadoGeral();
    }

    /**
     * Atualiza nomes e identificadores de uma linha.
     *
     * @param {HTMLElement} linha Linha atual.
     * @param {number} indice Índice atual.
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarIdentificadoresLinha(
        linha,
        indice,
    ) {
        linha.dataset.indiceLigacao =
            String(indice);

        const nomeBase =
            `${this.nomeBaseCampo}[ligacoes][${indice}]`;

        const idBase =
            `seccoes-${this.obterIndiceSeccao()}-ligacao-${indice}`;

        this.atualizarCampoTexto(
            linha,
            '[data-campo-url-ligacao]',
            '[data-etiqueta-url-ligacao]',
            '[data-erro-url-ligacao]',
            `${nomeBase}[url]`,
            `${idBase}-url`,
            `${idBase}-erro-url`,
        );

        this.atualizarCampoTexto(
            linha,
            '[data-campo-etiqueta-ligacao]',
            '[data-etiqueta-etiqueta-ligacao]',
            '[data-erro-etiqueta-ligacao]',
            `${nomeBase}[etiqueta]`,
            `${idBase}-etiqueta`,
            `${idBase}-erro-etiqueta`,
        );

        const campoOculto = linha.querySelector(
            '[data-campo-incorporar-ligacao-oculto]',
        );

        const campoIncorporar = linha.querySelector(
            '[data-campo-incorporar-ligacao]',
        );

        const etiquetaIncorporar = linha.querySelector(
            '[data-etiqueta-incorporar-ligacao]',
        );

        if (campoOculto instanceof HTMLInputElement) {
            campoOculto.name =
                `${nomeBase}[incorporar]`;
        }

        if (campoIncorporar instanceof HTMLInputElement) {
            campoIncorporar.name =
                `${nomeBase}[incorporar]`;

            campoIncorporar.id =
                `${idBase}-incorporar`;
        }

        if (etiquetaIncorporar instanceof HTMLLabelElement) {
            etiquetaIncorporar.htmlFor =
                `${idBase}-incorporar`;
        }
    }

    /**
     * Atualiza um campo textual e os elementos que o referenciam.
     *
     * @param {HTMLElement} linha Linha atual.
     * @param {string} seletorCampo Seletor do campo.
     * @param {string} seletorEtiqueta Seletor da etiqueta.
     * @param {string} seletorErro Seletor do feedback.
     * @param {string} nome Nome do campo.
     * @param {string} identificador Identificador do campo.
     * @param {string} identificadorErro Identificador do feedback.
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarCampoTexto(
        linha,
        seletorCampo,
        seletorEtiqueta,
        seletorErro,
        nome,
        identificador,
        identificadorErro,
    ) {
        const campo =
            linha.querySelector(
                seletorCampo,
            );

        const etiqueta =
            linha.querySelector(
                seletorEtiqueta,
            );

        const erro =
            linha.querySelector(
                seletorErro,
            );

        if (campo instanceof HTMLInputElement) {
            campo.name =
                nome;

            campo.id =
                identificador;

            campo.setAttribute(
                'aria-describedby',
                identificadorErro,
            );
        }

        if (etiqueta instanceof HTMLLabelElement) {
            etiqueta.htmlFor =
                identificador;
        }

        if (erro instanceof HTMLElement) {
            erro.id =
                identificadorErro;
        }
    }

    /**
     * Atualiza plataforma, etiqueta e incorporação de uma linha.
     *
     * @param {HTMLElement} linha Linha atual.
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarEstadoLinha(linha) {
        const campoUrl = linha.querySelector(
            '[data-campo-url-ligacao]',
        );

        const plataforma =
            campoUrl instanceof HTMLInputElement
                ? GestorLigacoesSecao
                    .detetarPlataforma(
                        campoUrl.value,
                    )
                : null;

        const elementoPlataforma = linha.querySelector(
            '[data-plataforma-ligacao]',
        );

        if (elementoPlataforma instanceof HTMLElement) {
            elementoPlataforma.textContent =
                plataforma?.etiqueta
                ?? 'Por detetar';
        }

        linha.dataset.plataforma =
            plataforma?.valor
            ?? '';

        const contentorEtiqueta = linha.querySelector(
            '[data-contentor-etiqueta-ligacao]',
        );

        const campoEtiqueta = linha.querySelector(
            '[data-campo-etiqueta-ligacao]',
        );

        const eOutro =
            plataforma?.valor
            === GestorLigacoesSecao
                .PLATAFORMAS
                .outro
                .valor;

        if (contentorEtiqueta instanceof HTMLElement) {
            contentorEtiqueta.hidden =
                !eOutro;
        }

        if (campoEtiqueta instanceof HTMLInputElement) {
            campoEtiqueta.required =
                eOutro;

            campoEtiqueta.disabled =
                !this.detalhesAtivos()
                || !eOutro;
        }

        const campoIncorporar = linha.querySelector(
            '[data-campo-incorporar-ligacao]',
        );

        if (!(campoIncorporar instanceof HTMLInputElement)) {
            return;
        }

        if (
            plataforma !== null
            && !plataforma.suportaIncorporacao
        ) {
            campoIncorporar.checked =
                false;
        }

        campoIncorporar.disabled =
            !this.detalhesAtivos()
            || (
                plataforma !== null
                && !plataforma.suportaIncorporacao
            );
    }

    /**
     * Atualiza o botão de adição e o resumo do número de ligações.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarEstadoGeral() {
        const quantidade =
            this.obterLinhas()
                .length;

        this.botaoAdicionar.disabled =
            !this.detalhesAtivos()
            || quantidade >= this.maximoLigacoes;

        this.estado.textContent =
            `${quantidade} de ${this.maximoLigacoes} ligações.`;
    }

    /**
     * Obtém as linhas atuais na ordem visual.
     *
     * @returns {Array<HTMLElement>} Linhas encontradas.
     *
     * @since 2.0.0
     */
    obterLinhas() {
        return Array.from(
            this.lista.querySelectorAll(
                ':scope > [data-ligacao-seccao]',
            ),
        ).filter(
            (linha) =>
                linha instanceof HTMLElement,
        );
    }

    /**
     * Determina se a secção está num tipo com detalhes musicais.
     *
     * @returns {boolean} Verdadeiro quando o editor está ativo.
     *
     * @since 2.0.0
     */
    detalhesAtivos() {
        const linhaDetalhes =
            this.editor.closest(
                '.linha-detalhes-seccao',
            );

        return linhaDetalhes instanceof HTMLElement
            && !linhaDetalhes.hidden;
    }

    /**
     * Obtém o índice atual da secção.
     *
     * @returns {string} Índice textual.
     *
     * @throws {Error} Quando o índice não existe.
     *
     * @since 2.0.0
     */
    obterIndiceSeccao() {
        const indice =
            this.seccao.dataset.indiceSeccao
            ?? '';

        if (!/^[A-Za-z0-9_-]+$/u.test(indice)) {
            throw new Error(
                'A secção não possui um índice válido para as ligações.',
            );
        }

        return indice;
    }

    /**
     * Normaliza o nome base recebido pelo HTML.
     *
     * @param {unknown} valor Valor recebido.
     * @returns {string} Nome base.
     *
     * @throws {TypeError} Quando o nome é inválido.
     *
     * @since 2.0.0
     */
    normalizarNomeBaseCampo(valor) {
        if (
            typeof valor !== 'string'
            || !/^seccoes\[[A-Za-z0-9_-]+\]$/u.test(
                valor,
            )
        ) {
            throw new TypeError(
                'O nome base do editor de ligações é inválido.',
            );
        }

        return valor;
    }

    /**
     * Normaliza o limite máximo recebido pelo HTML.
     *
     * @param {unknown} valor Valor recebido.
     * @returns {number} Limite máximo.
     *
     * @throws {TypeError} Quando o limite é inválido.
     *
     * @since 2.0.0
     */
    normalizarMaximoLigacoes(valor) {
        if (
            typeof valor !== 'string'
            || !/^[1-9]\d*$/u.test(
                valor,
            )
        ) {
            throw new TypeError(
                'O limite de ligações da secção é inválido.',
            );
        }

        const numero =
            Number(valor);

        if (
            !Number.isSafeInteger(numero)
            || numero <= 0
        ) {
            throw new TypeError(
                'O limite de ligações da secção é inválido.',
            );
        }

        return numero;
    }
}

export default GestorLigacoesSecao;
