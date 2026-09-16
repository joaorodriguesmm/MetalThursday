/**
 * Gere os campos editáveis de um lançamento associado a uma secção.
 *
 * O editor trabalha apenas sobre o estado do formulário. A persistência das
 * correções é efetuada quando a MetalThursday é guardada.
 *
 * @since 2.0.0
 */
class EditorLancamentoDiscogs {
    /**
     * Cria o editor e regista os eventos delegados do formulário.
     *
     * @param {HTMLFormElement} formulario Formulário principal.
     *
     * @throws {TypeError} Quando o formulário é inválido.
     *
     * @since 2.0.0
     */
    constructor(formulario) {
        if (!(formulario instanceof HTMLFormElement)) {
            throw new TypeError(
                'O formulário do editor de lançamentos é inválido.',
            );
        }

        this.formulario = formulario;

        this.formulario.addEventListener(
            'click',
            (evento) => this.tratarClique(evento),
        );

        this.formulario.addEventListener(
            'input',
            (evento) => this.tratarEdicaoMetadados(evento),
        );
    }

    /**
     * Inicializa o editor já renderizado numa secção.
     *
     * @param {HTMLElement} seccao Secção inicializada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    inicializarSeccao(seccao) {
        const editor = this.obterEditor(
            seccao,
        );

        if (!(editor instanceof HTMLElement)) {
            return;
        }

        this.reindexarFaixas(
            seccao,
        );

        editor.hidden = false;

        this.sincronizarCamposSecao(
            seccao,
        );
    }

    /**
     * Confirma o contrato completo da resposta de importação.
     *
     * @param {unknown} lancamento Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando os dados são editáveis.
     *
     * @since 2.0.0
     */
    dadosImportacaoValidos(lancamento) {
        if (
            typeof lancamento !== 'object'
            || lancamento === null
            || !Number.isSafeInteger(lancamento.id)
            || lancamento.id < 1
            || !Number.isSafeInteger(
                lancamento.discogs_release_id,
            )
            || lancamento.discogs_release_id < 1
            || typeof lancamento.titulo !== 'string'
            || lancamento.titulo.trim() === ''
            || (
                lancamento.tipo !== null
                && typeof lancamento.tipo !== 'string'
            )
            || (
                lancamento.ano_original !== null
                && (
                    !Number.isSafeInteger(lancamento.ano_original)
                    || lancamento.ano_original < 1
                )
            )
            || !Array.isArray(lancamento.faixas)
        ) {
            return false;
        }

        return lancamento.faixas.every(
            (faixa) => this.faixaImportadaValida(
                faixa,
            ),
        );
    }

    /**
     * Carrega no editor o snapshot devolvido pela importação.
     *
     * @param {HTMLElement} seccao Secção atual.
     * @param {object} lancamento Lançamento importado.
     *
     * @returns {void}
     *
     * @throws {TypeError} Quando os dados não cumprem o contrato esperado.
     *
     * @since 2.0.0
     */
    carregarLancamento(
        seccao,
        lancamento,
    ) {
        if (!this.dadosImportacaoValidos(lancamento)) {
            throw new TypeError(
                'Os dados editáveis do lançamento são inválidos.',
            );
        }

        const editor = this.obterEditor(
            seccao,
        );

        if (!(editor instanceof HTMLElement)) {
            throw new TypeError(
                'Não foi encontrado o editor do lançamento.',
            );
        }

        this.definirValorCampo(
            editor,
            '[data-campo-titulo-lancamento]',
            lancamento.titulo,
        );

        this.definirValorCampo(
            editor,
            '[data-campo-tipo-lancamento]',
            lancamento.tipo ?? '',
        );

        this.definirValorCampo(
            editor,
            '[data-campo-ano-original-lancamento]',
            lancamento.ano_original === null
                ? ''
                : String(lancamento.ano_original),
        );

        const lista = editor.querySelector(
            '[data-lista-faixas-lancamento]',
        );

        if (!(lista instanceof HTMLElement)) {
            throw new TypeError(
                'Não foi encontrada a tracklist do lançamento.',
            );
        }

        lista.replaceChildren();

        lancamento.faixas.forEach((faixa) => {
            lista.append(
                this.criarLinhaFaixa(
                    faixa,
                ),
            );
        });

        editor.hidden = false;

        this.reindexarFaixas(
            seccao,
        );

        this.sincronizarCamposSecao(
            seccao,
        );
    }

    /**
     * Remove todo o estado estruturado do lançamento de uma secção.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    limpar(seccao) {
        const editor = this.obterEditor(
            seccao,
        );

        if (!(editor instanceof HTMLElement)) {
            return;
        }

        editor.querySelectorAll(
            'input, select',
        ).forEach((campo) => {
            if (
                campo instanceof HTMLInputElement
                || campo instanceof HTMLSelectElement
            ) {
                campo.value = '';
            }
        });

        const lista = editor.querySelector(
            '[data-lista-faixas-lancamento]',
        );

        if (lista instanceof HTMLElement) {
            lista.replaceChildren();
        }

        editor.hidden = true;

        this.limparCamposEspelhoSecao(
            seccao,
        );
    }

    /**
     * Trata os botões de edição da tracklist.
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
            '[data-acao-faixa-lancamento]',
        );

        if (!(botao instanceof HTMLButtonElement)) {
            return;
        }

        const seccao = botao.closest(
            '.item-seccao',
        );

        if (!(seccao instanceof HTMLElement)) {
            return;
        }

        const acao = botao.dataset.acaoFaixaLancamento;

        if (acao === 'adicionar') {
            this.adicionarFaixa(
                seccao,
            );

            return;
        }

        const linha = botao.closest(
            '[data-faixa-lancamento]',
        );

        if (!(linha instanceof HTMLElement)) {
            return;
        }

        if (acao === 'remover') {
            linha.remove();
        } else if (acao === 'subir') {
            const anterior = linha.previousElementSibling;

            if (anterior instanceof HTMLElement) {
                linha.parentElement?.insertBefore(
                    linha,
                    anterior,
                );
            }
        } else if (acao === 'descer') {
            const seguinte = linha.nextElementSibling;

            if (seguinte instanceof HTMLElement) {
                linha.parentElement?.insertBefore(
                    seguinte,
                    linha,
                );
            }
        } else {
            return;
        }

        this.reindexarFaixas(
            seccao,
        );
    }

    /**
     * Sincroniza título e ano com os campos históricos da secção.
     *
     * @param {Event} evento Evento recebido.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    tratarEdicaoMetadados(evento) {
        if (!(evento.target instanceof Element)) {
            return;
        }

        if (
            !evento.target.matches(
                '[data-campo-titulo-lancamento], [data-campo-ano-original-lancamento]',
            )
        ) {
            return;
        }

        const seccao = evento.target.closest(
            '.item-seccao',
        );

        if (seccao instanceof HTMLElement) {
            this.sincronizarCamposSecao(
                seccao,
            );
        }
    }

    /**
     * Adiciona uma nova faixa vazia ao fim da tracklist.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    adicionarFaixa(seccao) {
        const editor = this.obterEditor(
            seccao,
        );

        const lista = editor?.querySelector(
            '[data-lista-faixas-lancamento]',
        );

        if (!(lista instanceof HTMLElement)) {
            return;
        }

        const linha = this.criarLinhaFaixa({
            id: null,
            musica_id: null,
            titulo: '',
            posicao: null,
            ordem: null,
        });

        lista.append(
            linha,
        );

        this.reindexarFaixas(
            seccao,
        );

        linha.querySelector(
            '[data-campo-titulo-faixa]',
        )?.focus();
    }

    /**
     * Cria uma linha editável de tracklist.
     *
     * @param {object} faixa Dados da faixa.
     * @returns {HTMLElement} Linha criada.
     *
     * @since 2.0.0
     */
    criarLinhaFaixa(faixa) {
        const linha = document.createElement('div');
        linha.className =
            'row g-2 align-items-end border rounded p-2 mb-2';
        linha.dataset.faixaLancamento = '';

        linha.append(
            this.criarCampoOculto(
                'id',
                faixa.id,
            ),
            this.criarCampoOculto(
                'musica_id',
                faixa.musica_id,
            ),
            this.criarCampoOculto(
                'ordem',
                faixa.ordem,
            ),
        );

        const colunaPosicao = document.createElement('div');
        colunaPosicao.className = 'col-sm-2';

        const rotuloPosicao = document.createElement('label');
        rotuloPosicao.className = 'form-label small mb-1';
        rotuloPosicao.textContent = 'Posição';

        const campoPosicao = document.createElement('input');
        campoPosicao.className = 'form-control form-control-sm';
        campoPosicao.type = 'text';
        campoPosicao.maxLength = 100;
        campoPosicao.value = faixa.posicao ?? '';
        campoPosicao.dataset.campoPosicaoFaixa = '';

        colunaPosicao.append(
            rotuloPosicao,
            campoPosicao,
        );

        const colunaTitulo = document.createElement('div');
        colunaTitulo.className = 'col';

        const rotuloTitulo = document.createElement('label');
        rotuloTitulo.className = 'form-label small mb-1';
        rotuloTitulo.textContent = 'Título';

        const campoTitulo = document.createElement('input');
        campoTitulo.className = 'form-control form-control-sm';
        campoTitulo.type = 'text';
        campoTitulo.maxLength = 255;
        campoTitulo.required = true;
        campoTitulo.value = faixa.titulo;
        campoTitulo.dataset.campoTituloFaixa = '';

        colunaTitulo.append(
            rotuloTitulo,
            campoTitulo,
        );

        const colunaAcoes = document.createElement('div');
        colunaAcoes.className =
            'col-auto d-flex gap-1 align-items-center';

        const indicador = document.createElement('span');
        indicador.className = 'badge text-bg-secondary align-self-center me-1';
        indicador.dataset.ordemFaixaLancamento = '';

        colunaAcoes.append(
            indicador,
            this.criarBotaoAcao(
                'subir',
                'bi-arrow-up',
                'Subir faixa',
            ),
            this.criarBotaoAcao(
                'descer',
                'bi-arrow-down',
                'Descer faixa',
            ),
            this.criarBotaoAcao(
                'remover',
                'bi-trash',
                'Remover faixa',
                'btn-danger',
            ),
        );

        linha.append(
            colunaPosicao,
            colunaTitulo,
            colunaAcoes,
        );

        return linha;
    }

    /**
     * Reindexa as faixas segundo a ordem visual atual.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    reindexarFaixas(seccao) {
        const area = seccao.querySelector(
            '[data-importacao-lancamento]',
        );

        const editor = this.obterEditor(
            seccao,
        );

        const lista = editor?.querySelector(
            '[data-lista-faixas-lancamento]',
        );

        const nomeBase = area?.dataset.nomeBaseCampo?.trim() ?? '';

        if (
            !(lista instanceof HTMLElement)
            || nomeBase === ''
        ) {
            return;
        }

        const linhas = Array.from(
            lista.querySelectorAll(
                '[data-faixa-lancamento]',
            ),
        ).filter(
            (linha) => linha instanceof HTMLElement,
        );

        linhas.forEach((linha, indice) => {
            this.nomearCampoFaixa(
                linha,
                '[data-campo-id-faixa]',
                `${nomeBase}[lancamento][faixas][${indice}][id]`,
            );

            this.nomearCampoFaixa(
                linha,
                '[data-campo-musica-id-faixa]',
                `${nomeBase}[lancamento][faixas][${indice}][musica_id]`,
            );

            this.nomearCampoFaixa(
                linha,
                '[data-campo-titulo-faixa]',
                `${nomeBase}[lancamento][faixas][${indice}][titulo]`,
            );

            this.nomearCampoFaixa(
                linha,
                '[data-campo-posicao-faixa]',
                `${nomeBase}[lancamento][faixas][${indice}][posicao]`,
            );

            const campoOrdem = linha.querySelector(
                '[data-campo-ordem-faixa]',
            );

            if (campoOrdem instanceof HTMLInputElement) {
                campoOrdem.name =
                    `${nomeBase}[lancamento][faixas][${indice}][ordem]`;
                campoOrdem.value = String(indice + 1);
            }

            const indicador = linha.querySelector(
                '[data-ordem-faixa-lancamento]',
            );

            if (indicador instanceof HTMLElement) {
                indicador.textContent = String(indice + 1);
            }

            const botaoSubir = linha.querySelector(
                '[data-acao-faixa-lancamento="subir"]',
            );

            const botaoDescer = linha.querySelector(
                '[data-acao-faixa-lancamento="descer"]',
            );

            if (botaoSubir instanceof HTMLButtonElement) {
                botaoSubir.disabled = indice === 0;
            }

            if (botaoDescer instanceof HTMLButtonElement) {
                botaoDescer.disabled = indice === linhas.length - 1;
            }
        });
    }

    /**
     * Alterna os campos genéricos duplicados quando a secção é um lançamento.
     *
     * O título e o ano do lançamento são editados no bloco estruturado. Os
     * campos genéricos permanecem apenas como espelho interno para manter a
     * compatibilidade das restantes secções.
     *
     * @param {HTMLElement} seccao Secção atual.
     * @param {boolean} eLancamento Indica se o tipo é Lançamento.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarCamposGenericos(
        seccao,
        eLancamento,
    ) {
        const colunaTitulo = seccao.querySelector(
            '.coluna-titulo-seccao',
        );

        const colunaAno = seccao.querySelector(
            '.coluna-ano-seccao',
        );

        const titulo = seccao.querySelector(
            '[data-campo-titulo-seccao]',
        );

        const ano = seccao.querySelector(
            '[data-campo-ano-seccao]',
        );

        const editor = this.obterEditor(
            seccao,
        );

        if (editor instanceof HTMLElement) {
            editor.querySelectorAll(
                'input, select, button',
            ).forEach((controlo) => {
                if (
                    controlo instanceof HTMLInputElement
                    || controlo instanceof HTMLSelectElement
                    || controlo instanceof HTMLButtonElement
                ) {
                    controlo.disabled = !eLancamento;
                }
            });
        }

        if (colunaTitulo instanceof HTMLElement) {
            colunaTitulo.hidden = eLancamento;
        }

        if (colunaAno instanceof HTMLElement) {
            colunaAno.hidden = eLancamento;
        }

        if (
            eLancamento
            && titulo instanceof HTMLInputElement
        ) {
            titulo.required = false;
        }

        if (
            eLancamento
            && ano instanceof HTMLInputElement
        ) {
            ano.required = false;
        }
    }

    /**
     * Sincroniza os campos genéricos da secção com os metadados editáveis.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    sincronizarCamposSecao(seccao) {
        const editor = this.obterEditor(
            seccao,
        );

        if (!(editor instanceof HTMLElement)) {
            return;
        }

        const titulo = editor.querySelector(
            '[data-campo-titulo-lancamento]',
        );

        const anoOriginal = editor.querySelector(
            '[data-campo-ano-original-lancamento]',
        );

        const tituloSecao = seccao.querySelector(
            '[data-campo-titulo-seccao]',
        );

        const anoSecao = seccao.querySelector(
            '[data-campo-ano-seccao]',
        );

        if (
            titulo instanceof HTMLInputElement
            && tituloSecao instanceof HTMLInputElement
        ) {
            tituloSecao.value = titulo.value;
        }

        if (
            anoOriginal instanceof HTMLInputElement
            && anoSecao instanceof HTMLInputElement
        ) {
            anoSecao.value = anoOriginal.value;
        }
    }

    /**
     * Limpa os campos genéricos que receberam valores do lançamento.
     *
     * @param {HTMLElement} seccao Secção atual.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    limparCamposEspelhoSecao(seccao) {
        const titulo = seccao.querySelector(
            '[data-campo-titulo-seccao]',
        );

        const ano = seccao.querySelector(
            '[data-campo-ano-seccao]',
        );

        if (titulo instanceof HTMLInputElement) {
            titulo.value = '';
        }

        if (ano instanceof HTMLInputElement) {
            ano.value = '';
        }
    }

    /**
     * Obtém o editor pertencente à secção.
     *
     * @param {HTMLElement} seccao Secção consultada.
     * @returns {HTMLElement|null} Editor encontrado.
     *
     * @since 2.0.0
     */
    obterEditor(seccao) {
        const editor = seccao.querySelector(
            '[data-editor-lancamento]',
        );

        return editor instanceof HTMLElement
            ? editor
            : null;
    }

    /**
     * Obtém o identificador local do lançamento.
     *
     * @param {HTMLElement} seccao Secção consultada.
     * @returns {HTMLInputElement|null} Campo encontrado.
     *
     * @since 2.0.0
     */
    obterCampoLancamento(seccao) {
        const campo = seccao.querySelector(
            '[name$="[lancamento_id]"]',
        );

        return campo instanceof HTMLInputElement
            ? campo
            : null;
    }

    /**
     * Confirma os dados mínimos de uma faixa importada.
     *
     * @param {unknown} faixa Valor recebido.
     * @returns {boolean} Verdadeiro quando a faixa é válida.
     *
     * @since 2.0.0
     */
    faixaImportadaValida(faixa) {
        return typeof faixa === 'object'
            && faixa !== null
            && (
                faixa.id === null
                || (
                    Number.isSafeInteger(faixa.id)
                    && faixa.id > 0
                )
            )
            && (
                faixa.musica_id === null
                || (
                    Number.isSafeInteger(faixa.musica_id)
                    && faixa.musica_id > 0
                )
            )
            && typeof faixa.titulo === 'string'
            && faixa.titulo.trim() !== ''
            && (
                faixa.posicao === null
                || typeof faixa.posicao === 'string'
            )
            && (
                faixa.ordem === null
                || (
                    Number.isSafeInteger(faixa.ordem)
                    && faixa.ordem > 0
                )
            );
    }

    /**
     * Define o valor de um campo do editor.
     *
     * @param {HTMLElement} editor Editor atual.
     * @param {string} seletor Seletor do campo.
     * @param {string} valor Valor atribuído.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    definirValorCampo(
        editor,
        seletor,
        valor,
    ) {
        const campo = editor.querySelector(
            seletor,
        );

        if (
            campo instanceof HTMLInputElement
            || campo instanceof HTMLSelectElement
        ) {
            campo.value = valor;
        }
    }

    /**
     * Cria um campo oculto da linha de faixa.
     *
     * @param {string} campo Campo representado.
     * @param {number|null} valor Valor inicial.
     * @returns {HTMLInputElement} Campo criado.
     *
     * @since 2.0.0
     */
    criarCampoOculto(
        campo,
        valor,
    ) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.value = valor === null
            ? ''
            : String(valor);

        if (campo === 'id') {
            input.dataset.campoIdFaixa = '';
        } else if (campo === 'musica_id') {
            input.dataset.campoMusicaIdFaixa = '';
        } else {
            input.dataset.campoOrdemFaixa = '';
        }

        return input;
    }

    /**
     * Cria um botão de ação de uma faixa.
     *
     * @param {string} acao Ação executada.
     * @param {string} icone Ícone Bootstrap.
     * @param {string} rotulo Rótulo acessível.
     * @param {string} classe Classe visual adicional.
     * @returns {HTMLButtonElement} Botão criado.
     *
     * @since 2.0.0
     */
    criarBotaoAcao(
        acao,
        icone,
        rotulo,
        classe = 'btn-secondary',
    ) {
        const botao = document.createElement('button');
        botao.className = `btn btn-sm ${classe}`;
        botao.type = 'button';
        botao.dataset.acaoFaixaLancamento = acao;
        botao.setAttribute(
            'aria-label',
            rotulo,
        );
        botao.title = rotulo;

        const elementoIcone = document.createElement('i');
        elementoIcone.className = `bi ${icone}`;
        elementoIcone.setAttribute(
            'aria-hidden',
            'true',
        );

        botao.append(
            elementoIcone,
        );

        return botao;
    }

    /**
     * Atribui o nome de submissão a um campo de faixa.
     *
     * @param {HTMLElement} linha Linha atual.
     * @param {string} seletor Seletor do campo.
     * @param {string} nome Nome atribuído.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    nomearCampoFaixa(
        linha,
        seletor,
        nome,
    ) {
        const campo = linha.querySelector(
            seletor,
        );

        if (campo instanceof HTMLInputElement) {
            campo.name = nome;
        }
    }
}

export default EditorLancamentoDiscogs;
