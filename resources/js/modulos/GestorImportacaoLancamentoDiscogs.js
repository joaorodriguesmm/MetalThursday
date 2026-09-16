import axios from 'axios';
import EditorLancamentoDiscogs from './EditorLancamentoDiscogs';

/**
 * Gere a associação de edições concretas do Discogs às secções de lançamento.
 *
 * A edição é indicada através da ligação da respetiva página `release` no
 * Discogs, evitando uma pesquisa paralela dentro do MetalThursday.
 *
 * @since 2.0.0
 */
class GestorImportacaoLancamentoDiscogs {
    /**
     * Marcador presente no endereço de importação preparado pelo servidor.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static MARCADOR_IDENTIFICADOR_DISCOGS =
        '__IDENTIFICADOR_DISCOGS__';

    /**
     * Tipos de secção que podem associar um lançamento Discogs.
     *
     * @type {ReadonlySet<string>}
     *
     * @since 2.0.0
     */
    static TIPOS_SECCAO_LANCAMENTO = new Set([
        'lancamento',
    ]);

    /**
     * Cria o gestor da integração Discogs.
     *
     * @param {HTMLFormElement} formulario Formulário principal.
     * @param {object} configuracao Configuração recebida.
     * @param {string} configuracao.urlImportacao Endereço-modelo de importação.
     *
     * @throws {TypeError} Quando a configuração recebida é inválida.
     *
     * @since 2.0.0
     */
    constructor(
        formulario,
        {
            urlImportacao,
        },
    ) {
        if (!(formulario instanceof HTMLFormElement)) {
            throw new TypeError(
                'O formulário da integração Discogs é inválido.',
            );
        }

        if (
            typeof urlImportacao !== 'string'
            || !urlImportacao.includes(
                GestorImportacaoLancamentoDiscogs
                    .MARCADOR_IDENTIFICADOR_DISCOGS,
            )
        ) {
            throw new TypeError(
                'A configuração da integração Discogs é inválida.',
            );
        }

        this.formulario = formulario;
        this.urlImportacao = urlImportacao;
        this.identificadorMetalThursday =
            this.obterIdentificadorMetalThursday();
        this.editorLancamentos = new EditorLancamentoDiscogs(
            formulario,
        );

        this.normalizarSeccoesExistentes();

        this.formulario.addEventListener(
            'click',
            (evento) => this.tratarClique(evento),
        );

        this.formulario.addEventListener(
            'change',
            (evento) => this.tratarAlteracao(evento),
        );

        this.formulario.addEventListener(
            'keydown',
            (evento) => this.tratarTecla(evento),
        );
    }

    /**
     * Normaliza a visibilidade da integração nas secções já renderizadas.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    normalizarSeccoesExistentes() {
        this.formulario.querySelectorAll(
            '.item-seccao',
        ).forEach((seccao) => {
            if (seccao instanceof HTMLElement) {
                this.atualizarEstadoSeccao(
                    seccao,
                    false,
                );
            }
        });
    }

    /**
     * Trata cliques no botão de importação do Discogs.
     *
     * @param {MouseEvent} evento Evento recebido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarClique(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        const botao = evento.target.closest(
            '[data-acao-importar-lancamento]',
        );

        if (!(botao instanceof HTMLButtonElement)) {
            return;
        }

        const seccao = botao.closest(
            '.item-seccao',
        );

        if (seccao instanceof HTMLElement) {
            void this.importarPorLigacao(
                seccao,
            );
        }
    }

    /**
     * Trata a alteração do tipo de secção.
     *
     * @param {Event} evento Evento recebido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarAlteracao(evento) {
        const alvo = evento.target;

        if (
            !(alvo instanceof HTMLSelectElement)
            || !alvo.classList.contains(
                'seletor-tipo-seccao',
            )
        ) {
            return;
        }

        const seccao = alvo.closest(
            '.item-seccao',
        );

        if (seccao instanceof HTMLElement) {
            this.atualizarEstadoSeccao(
                seccao,
                true,
            );
        }
    }

    /**
     * Permite iniciar a importação com Enter no campo da ligação.
     *
     * @param {KeyboardEvent} evento Evento recebido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarTecla(evento) {
        if (
            evento.key !== 'Enter'
            || !(evento.target instanceof HTMLInputElement)
            || !evento.target.matches(
                '[data-ligacao-lancamento-discogs]',
            )
        ) {
            return;
        }

        const seccao = evento.target.closest(
            '.item-seccao',
        );

        if (!(seccao instanceof HTMLElement)) {
            return;
        }

        evento.preventDefault();

        void this.importarPorLigacao(
            seccao,
        );
    }

    /**
     * Atualiza a visibilidade da integração consoante o tipo selecionado.
     *
     * Ao abandonar o tipo de lançamento, a associação é removida para impedir
     * que um lançamento seja submetido numa secção incompatível.
     *
     * @param {HTMLElement} seccao Secção atualizada.
     * @param {boolean} limparQuandoInaplicavel Indica se deve limpar a seleção.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarEstadoSeccao(
        seccao,
        limparQuandoInaplicavel,
    ) {
        const area = seccao.querySelector(
            '[data-importacao-lancamento]',
        );

        if (!(area instanceof HTMLElement)) {
            return;
        }

        const aplicavel =
            this.seccaoAceitaLancamento(
                seccao,
            );

        area.hidden = !aplicavel;
        area.setAttribute(
            'aria-hidden',
            aplicavel
                ? 'false'
                : 'true',
        );

        this.definirControlosAtivos(
            area,
            aplicavel,
        );

        this.editorLancamentos.atualizarCamposGenericos(
            seccao,
            aplicavel,
        );

        if (aplicavel) {
            this.editorLancamentos.inicializarSeccao(
                seccao,
            );
        }

        if (!aplicavel) {
            if (limparQuandoInaplicavel) {
                this.limparAssociacao(
                    seccao,
                );
            }

            return;
        }

        this.apresentarAssociacaoExistente(
            seccao,
        );
    }

    /**
     * Determina se a secção selecionada aceita um lançamento.
     *
     * @param {HTMLElement} seccao Secção consultada.
     *
     * @returns {boolean} Verdadeiro quando aceita lançamentos.
     *
     * @since 2.0.0
     */
    seccaoAceitaLancamento(seccao) {
        const selecao = seccao.querySelector(
            '.seletor-tipo-seccao',
        );

        if (!(selecao instanceof HTMLSelectElement)) {
            return false;
        }

        const identificador =
            selecao.selectedOptions.item(0)
                ?.dataset
                .identificadorTipoSeccao
                ?.trim()
            ?? '';

        return GestorImportacaoLancamentoDiscogs
            .TIPOS_SECCAO_LANCAMENTO
            .has(
                identificador,
            );
    }

    /**
     * Importa a edição Discogs indicada pela ligação colada pelo utilizador.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {Promise<void>} Promessa da importação.
     *
     * @since 2.0.0
     */
    async importarPorLigacao(seccao) {
        const area = seccao.querySelector(
            '[data-importacao-lancamento]',
        );

        const campoLigacao = seccao.querySelector(
            '[data-ligacao-lancamento-discogs]',
        );

        if (
            !(area instanceof HTMLElement)
            || !(campoLigacao instanceof HTMLInputElement)
            || !this.seccaoAceitaLancamento(
                seccao,
            )
        ) {
            return;
        }

        let identificador;

        try {
            identificador =
                this.extrairIdentificadorDiscogs(
                    campoLigacao.value,
                );
        } catch (erro) {
            this.atualizarEstado(
                area,
                erro instanceof Error
                    ? erro.message
                    : 'A ligação do Discogs não é válida.',
                'text-danger',
            );

            campoLigacao.focus();

            return;
        }

        this.definirOcupado(
            area,
            true,
        );

        this.atualizarEstado(
            area,
            'A importar a edição indicada do Discogs…',
            'text-muted',
        );

        try {
            const endereco = this.urlImportacao.replace(
                GestorImportacaoLancamentoDiscogs
                    .MARCADOR_IDENTIFICADOR_DISCOGS,
                String(identificador),
            );

            const resposta = await axios.post(
                endereco,
                this.criarParametrosContexto(),
            );

            const lancamento = resposta?.data?.lancamento;

            if (!this.lancamentoImportadoValido(lancamento)) {
                throw new TypeError(
                    'A resposta de importação do lançamento é inválida.',
                );
            }

            if (!this.seccaoAceitaLancamento(seccao)) {
                return;
            }

            this.aplicarLancamentoImportado(
                seccao,
                lancamento,
            );

            campoLigacao.value =
                this.criarLigacaoCanonica(
                    lancamento.discogs_release_id,
                );

            this.apresentarAssociacao(
                seccao,
                lancamento,
            );

            this.atualizarEstado(
                area,
                'Lançamento importado e associado à secção.',
                'text-success',
            );
        } catch (erro) {
            this.atualizarEstado(
                area,
                this.obterMensagemErro(
                    erro,
                    'Não foi possível importar o lançamento do Discogs.',
                ),
                'text-danger',
            );
        } finally {
            this.definirOcupado(
                area,
                false,
            );
        }
    }

    /**
     * Extrai o identificador de uma página de edição concreta do Discogs.
     *
     * Ligações de páginas `master` são rejeitadas porque não representam uma
     * edição física/digital concreta.
     *
     * @param {string} ligacao Ligação indicada.
     *
     * @returns {number} Identificador da Release Discogs.
     *
     * @throws {TypeError} Quando a ligação não identifica uma Release válida.
     *
     * @since 2.0.0
     */
    extrairIdentificadorDiscogs(ligacao) {
        const valor = typeof ligacao === 'string'
            ? ligacao.trim()
            : '';

        if (valor === '') {
            throw new TypeError(
                'Cola a ligação da edição no Discogs.',
            );
        }

        let url;

        try {
            url = new URL(valor);
        } catch {
            throw new TypeError(
                'A ligação indicada não é um endereço válido.',
            );
        }

        const dominio = url.hostname.toLowerCase();

        if (
            !['http:', 'https:'].includes(url.protocol)
            || (
                dominio !== 'discogs.com'
                && !dominio.endsWith('.discogs.com')
            )
        ) {
            throw new TypeError(
                'A ligação tem de pertencer ao Discogs.',
            );
        }

        if (/\/master\//u.test(url.pathname)) {
            throw new TypeError(
                'Esse link é de um Master do Discogs. Abre a edição concreta e cola uma ligação /release/.',
            );
        }

        const correspondencia = url.pathname.match(
            /\/release\/([1-9]\d*)(?:[-/]|$)/u,
        );

        if (correspondencia === null) {
            throw new TypeError(
                'A ligação tem de ser de uma edição concreta do Discogs (/release/...).',
            );
        }

        const identificador = Number(
            correspondencia[1],
        );

        if (!Number.isSafeInteger(identificador)) {
            throw new TypeError(
                'O identificador da edição Discogs não é válido.',
            );
        }

        return identificador;
    }

    /**
     * Aplica ao formulário os dados devolvidos pela importação.
     *
     * @param {HTMLElement} seccao Secção atual.
     * @param {object} lancamento Lançamento importado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    aplicarLancamentoImportado(
        seccao,
        lancamento,
    ) {
        const campoLancamento = seccao.querySelector(
            '[name$="[lancamento_id]"]',
        );

        const campoTitulo = seccao.querySelector(
            '[data-campo-titulo-seccao]',
        );

        if (campoLancamento instanceof HTMLInputElement) {
            campoLancamento.value = String(
                lancamento.id,
            );

            this.emitirAlteracao(
                campoLancamento,
            );
        }

        if (campoTitulo instanceof HTMLInputElement) {
            campoTitulo.value = lancamento.titulo;

            this.emitirAlteracao(
                campoTitulo,
            );
        }

        this.editorLancamentos.carregarLancamento(
            seccao,
            lancamento,
        );
    }

    /**
     * Mostra uma associação já existente numa secção em edição.
     *
     * @param {HTMLElement} seccao Secção consultada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarAssociacaoExistente(seccao) {
        const campo = seccao.querySelector(
            '[name$="[lancamento_id]"]',
        );

        const elemento = seccao.querySelector(
            '[data-lancamento-associado]',
        );

        if (
            !(campo instanceof HTMLInputElement)
            || !(elemento instanceof HTMLElement)
            || !/^[1-9]\d*$/u.test(
                campo.value,
            )
        ) {
            return;
        }

        if (!elemento.hidden) {
            return;
        }

        elemento.textContent =
            'Lançamento associado. Cola outra ligação do Discogs para o substituir.';
        elemento.hidden = false;
    }

    /**
     * Apresenta visualmente o lançamento importado.
     *
     * @param {HTMLElement} seccao Secção atual.
     * @param {object} lancamento Dados do lançamento.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarAssociacao(
        seccao,
        lancamento,
    ) {
        const elemento = seccao.querySelector(
            '[data-lancamento-associado]',
        );

        if (!(elemento instanceof HTMLElement)) {
            return;
        }

        const ligacao = document.createElement('a');
        ligacao.href = this.criarLigacaoCanonica(
            lancamento.discogs_release_id,
        );
        ligacao.target = '_blank';
        ligacao.rel = 'noopener noreferrer';
        ligacao.textContent = `Discogs #${lancamento.discogs_release_id}`;

        elemento.replaceChildren(
            document.createTextNode(
                `${lancamento.titulo} — `,
            ),
            ligacao,
        );
        elemento.hidden = false;
    }

    /**
     * Remove da secção a associação ao lançamento e limpa o estado visual.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    limparAssociacao(seccao) {
        const campoLancamento = seccao.querySelector(
            '[name$="[lancamento_id]"]',
        );

        const campoLigacao = seccao.querySelector(
            '[data-ligacao-lancamento-discogs]',
        );

        const associacao = seccao.querySelector(
            '[data-lancamento-associado]',
        );

        const area = seccao.querySelector(
            '[data-importacao-lancamento]',
        );

        if (campoLancamento instanceof HTMLInputElement) {
            campoLancamento.value = '';

            this.emitirAlteracao(
                campoLancamento,
            );
        }

        if (campoLigacao instanceof HTMLInputElement) {
            campoLigacao.value = '';
        }

        if (associacao instanceof HTMLElement) {
            associacao.replaceChildren();
            associacao.hidden = true;
        }

        if (area instanceof HTMLElement) {
            this.atualizarEstado(
                area,
                '',
                'text-muted',
            );
        }

        this.editorLancamentos.limpar(
            seccao,
        );
    }

    /**
     * Ativa ou desativa os controlos da área Discogs.
     *
     * @param {HTMLElement} area Área da integração.
     * @param {boolean} ativos Estado pretendido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    definirControlosAtivos(
        area,
        ativos,
    ) {
        area.querySelectorAll(
            'input, button, select',
        ).forEach((controlo) => {
            if (
                controlo instanceof HTMLInputElement
                || controlo instanceof HTMLButtonElement
                || controlo instanceof HTMLSelectElement
            ) {
                controlo.disabled = !ativos;
            }
        });
    }

    /**
     * Bloqueia temporariamente a interação durante a importação.
     *
     * @param {HTMLElement} area Área da integração.
     * @param {boolean} ocupado Estado pretendido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    definirOcupado(
        area,
        ocupado,
    ) {
        area.setAttribute(
            'aria-busy',
            ocupado
                ? 'true'
                : 'false',
        );

        area.querySelectorAll(
            '[data-ligacao-lancamento-discogs], [data-acao-importar-lancamento]',
        ).forEach((controlo) => {
            if (
                controlo instanceof HTMLInputElement
                || controlo instanceof HTMLButtonElement
            ) {
                controlo.disabled = ocupado;
            }
        });
    }

    /**
     * Atualiza a mensagem de estado da integração.
     *
     * @param {HTMLElement} area Área da integração.
     * @param {string} mensagem Mensagem apresentada.
     * @param {string} classe Classe de cor Bootstrap.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarEstado(
        area,
        mensagem,
        classe,
    ) {
        const estado = area.querySelector(
            '[data-estado-importacao-lancamento]',
        );

        if (!(estado instanceof HTMLElement)) {
            return;
        }

        estado.classList.remove(
            'text-muted',
            'text-danger',
            'text-success',
        );
        estado.classList.add(
            classe,
        );
        estado.textContent = mensagem;
    }

    /**
     * Confirma o contrato mínimo da resposta de importação.
     *
     * @param {unknown} lancamento Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando o contrato é válido.
     *
     * @since 2.0.0
     */
    lancamentoImportadoValido(lancamento) {
        return this.editorLancamentos.dadosImportacaoValidos(
            lancamento,
        );
    }

    /**
     * Constrói a ligação canónica de uma Release Discogs.
     *
     * @param {number} identificador Identificador da Release.
     *
     * @returns {string} Ligação pública da edição.
     *
     * @since 2.0.0
     */
    criarLigacaoCanonica(identificador) {
        return `https://www.discogs.com/release/${identificador}`;
    }

    /**
     * Cria os parâmetros de contexto necessários aos pedidos autenticados.
     *
     * @returns {object} Parâmetros do pedido.
     *
     * @since 2.0.0
     */
    criarParametrosContexto() {
        if (this.identificadorMetalThursday === null) {
            return {};
        }

        return {
            metal_thursday_id:
                this.identificadorMetalThursday,
        };
    }

    /**
     * Obtém o identificador da MetalThursday quando o formulário está em edição.
     *
     * @returns {number|null} Identificador local ou nulo.
     *
     * @since 2.0.0
     */
    obterIdentificadorMetalThursday() {
        const valor =
            this.formulario.dataset.metalThursdayId
            ?.trim()
            ?? '';

        if (valor === '') {
            return null;
        }

        const identificador = Number(valor);

        return Number.isSafeInteger(identificador)
            && identificador > 0
            ? identificador
            : null;
    }

    /**
     * Extrai uma mensagem útil de um erro HTTP ou JavaScript.
     *
     * @param {unknown} erro Erro recebido.
     * @param {string} alternativa Mensagem utilizada como alternativa.
     *
     * @returns {string} Mensagem adequada ao utilizador.
     *
     * @since 2.0.0
     */
    obterMensagemErro(
        erro,
        alternativa,
    ) {
        if (
            typeof erro === 'object'
            && erro !== null
        ) {
            const mensagemResposta =
                erro.response
                    ?.data
                    ?.mensagem;

            if (
                typeof mensagemResposta === 'string'
                && mensagemResposta.trim() !== ''
            ) {
                return mensagemResposta.trim();
            }

            const errosValidacao =
                erro.response
                    ?.data
                    ?.errors;

            if (
                typeof errosValidacao === 'object'
                && errosValidacao !== null
            ) {
                const primeiraMensagem = Object.values(
                    errosValidacao,
                ).flat().find(
                    (mensagem) =>
                        typeof mensagem === 'string'
                        && mensagem.trim() !== '',
                );

                if (typeof primeiraMensagem === 'string') {
                    return primeiraMensagem.trim();
                }
            }
        }

        if (
            erro instanceof Error
            && erro.message.trim() !== ''
        ) {
            return erro.message.trim();
        }

        return alternativa;
    }

    /**
     * Emite um evento de alteração depois de atualizar um campo programaticamente.
     *
     * @param {HTMLInputElement} campo Campo alterado.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    emitirAlteracao(campo) {
        campo.dispatchEvent(
            new Event(
                'change',
                {
                    bubbles: true,
                },
            ),
        );
    }
}

export default GestorImportacaoLancamentoDiscogs;
