import axios from 'axios';

/**
 * Gere os campos enriquecidos do perfil de artista.
 *
 * Inclui a apresentação dos campos adicionais, a edição de N ligações e a
 * importação assistida de dados através do MusicBrainz e do TheAudioDB.
 * O identificador Discogs pode ser associado através dos dados do MusicBrainz.
 *
 * Os dados externos são aplicados apenas aos campos editáveis. A persistência
 * continua dependente da submissão normal do formulário e os géneros nunca
 * são alterados pela importação.
 *
 * @since 2.0.0
 */

const SELETOR_FORMULARIO =
    '[data-formulario-perfil-artista]';

const LIMITE_LIGACOES =
    50;

/**
 * Verifica se um valor é um objeto simples.
 *
 * @param {unknown} valor Valor recebido.
 *
 * @returns {boolean} Verdadeiro quando é um objeto simples.
 *
 * @since 2.0.0
 */
function eObjeto(valor) {
    return typeof valor === 'object'
        && valor !== null
        && !Array.isArray(valor);
}

/**
 * Obtém o formulário associado a um elemento.
 *
 * @param {Element} elemento Elemento pertencente ao formulário.
 *
 * @returns {HTMLFormElement|null} Formulário encontrado.
 *
 * @since 2.0.0
 */
function obterFormulario(elemento) {
    const formulario =
        elemento.closest(
            'form',
        );

    return formulario instanceof HTMLFormElement
        ? formulario
        : null;
}

/**
 * Obtém um campo do formulário pelo nome.
 *
 * @param {HTMLFormElement} formulario Formulário.
 * @param {string} nome Nome do campo.
 *
 * @returns {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement|null}
 *     Campo encontrado.
 *
 * @since 2.0.0
 */
function obterCampoFormulario(
    formulario,
    nome,
) {
    const campo =
        formulario.elements.namedItem(
            nome,
        );

    if (
        campo instanceof HTMLInputElement
        || campo instanceof HTMLSelectElement
        || campo instanceof HTMLTextAreaElement
    ) {
        return campo;
    }

    return null;
}

/**
 * Atualiza o texto do botão dos campos adicionais.
 *
 * @param {HTMLButtonElement} botao Botão do alternador.
 * @param {boolean} apresentados Indica se os campos estão apresentados.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function atualizarTextoCamposAdicionais(
    botao,
    apresentados,
) {
    const textoApresentar =
        botao.dataset.textoApresentar
        ?? 'Apresentar campos adicionais';

    const textoOcultar =
        botao.dataset.textoOcultar
        ?? 'Ocultar campos adicionais';

    botao.textContent =
        apresentados
            ? textoOcultar
            : textoApresentar;

    botao.setAttribute(
        'aria-expanded',
        apresentados
            ? 'true'
            : 'false',
    );
}

/**
 * Inicializa o botão que apresenta ou oculta os metadados opcionais.
 *
 * @param {HTMLFormElement} formulario Formulário do artista.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function inicializarCamposAdicionais(
    formulario,
) {
    const botao =
        formulario.querySelector(
            '[data-alternar-campos-adicionais]',
        );

    const contentor =
        formulario.querySelector(
            '[data-campos-adicionais-artista]',
        );

    if (
        !(botao instanceof HTMLButtonElement)
        || !(contentor instanceof HTMLElement)
    ) {
        return;
    }

    atualizarTextoCamposAdicionais(
        botao,
        contentor.classList.contains(
            'show',
        ),
    );

    contentor.addEventListener(
        'shown.bs.collapse',
        () => {
            atualizarTextoCamposAdicionais(
                botao,
                true,
            );
        },
    );

    contentor.addEventListener(
        'hidden.bs.collapse',
        () => {
            atualizarTextoCamposAdicionais(
                botao,
                false,
            );
        },
    );
}

/**
 * Reindexa os nomes enviados pelas linhas de ligações.
 *
 * @param {HTMLFormElement} formulario Formulário do artista.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function reindexarLigacoes(
    formulario,
) {
    const lista =
        formulario.querySelector(
            '[data-lista-ligacoes]',
        );

    if (!(lista instanceof HTMLElement)) {
        return;
    }

    lista
        .querySelectorAll(
            '[data-ligacao]',
        )
        .forEach(
            (
                linha,
                indice,
            ) => {
                if (!(linha instanceof HTMLElement)) {
                    return;
                }

                [
                    'titulo',
                    'url',
                ].forEach(
                    (campo) => {
                        const elemento =
                            linha.querySelector(
                                `[data-campo-ligacao="${campo}"]`,
                            );

                        if (
                            elemento instanceof HTMLInputElement
                        ) {
                            elemento.name =
                                `ligacoes[${indice}][${campo}]`;
                        }
                    },
                );
            },
        );
}

/**
 * Limpa a única linha restante de ligações.
 *
 * @param {HTMLElement} linha Linha da ligação.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function limparLinhaLigacao(
    linha,
) {
    linha
        .querySelectorAll(
            '[data-campo-ligacao]',
        )
        .forEach(
            (campo) => {
                if (
                    campo instanceof HTMLInputElement
                ) {
                    campo.value = '';
                }
            },
        );
}

/**
 * Cria uma linha de ligação através do modelo do formulário.
 *
 * @param {HTMLFormElement} formulario Formulário.
 * @param {number} indice Índice da ligação.
 * @param {{titulo: string, url: string}} dados Dados iniciais.
 *
 * @returns {HTMLElement|null} Linha criada.
 *
 * @since 2.0.0
 */
function criarLinhaLigacao(
    formulario,
    indice,
    dados,
) {
    const modelo =
        formulario.querySelector(
            'template[data-modelo-ligacao]',
        );

    if (!(modelo instanceof HTMLTemplateElement)) {
        return null;
    }

    const fragmento =
        modelo.content.cloneNode(
            true,
        );

    const linha =
        fragmento.querySelector(
            '[data-ligacao]',
        );

    if (!(linha instanceof HTMLElement)) {
        return null;
    }

    const campoTitulo =
        linha.querySelector(
            '[data-campo-ligacao="titulo"]',
        );

    const campoUrl =
        linha.querySelector(
            '[data-campo-ligacao="url"]',
        );

    if (
        !(campoTitulo instanceof HTMLInputElement)
        || !(campoUrl instanceof HTMLInputElement)
    ) {
        return null;
    }

    campoTitulo.name =
        `ligacoes[${indice}][titulo]`;

    campoUrl.name =
        `ligacoes[${indice}][url]`;

    campoTitulo.value =
        dados.titulo;

    campoUrl.value =
        dados.url;

    return linha;
}

/**
 * Acrescenta uma nova linha de ligação ao formulário.
 *
 * @param {HTMLFormElement} formulario Formulário do artista.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function adicionarLigacao(
    formulario,
) {
    const lista =
        formulario.querySelector(
            '[data-lista-ligacoes]',
        );

    if (!(lista instanceof HTMLElement)) {
        return;
    }

    const quantidade =
        lista.querySelectorAll(
            '[data-ligacao]',
        ).length;

    if (quantidade >= LIMITE_LIGACOES) {
        return;
    }

    const novaLinha =
        criarLinhaLigacao(
            formulario,
            quantidade,
            {
                titulo: '',
                url: '',
            },
        );

    if (!(novaLinha instanceof HTMLElement)) {
        return;
    }

    lista.append(
        novaLinha,
    );

    reindexarLigacoes(
        formulario,
    );

    const primeiroCampo =
        novaLinha.querySelector(
            '[data-campo-ligacao="titulo"]',
        );

    if (
        primeiroCampo instanceof HTMLInputElement
    ) {
        primeiroCampo.focus();
    }
}

/**
 * Remove uma linha de ligação, mantendo sempre uma linha vazia disponível.
 *
 * @param {HTMLFormElement} formulario Formulário.
 * @param {HTMLButtonElement} botao Botão utilizado.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function removerLigacao(
    formulario,
    botao,
) {
    const linha =
        botao.closest(
            '[data-ligacao]',
        );

    const lista =
        formulario.querySelector(
            '[data-lista-ligacoes]',
        );

    if (
        !(linha instanceof HTMLElement)
        || !(lista instanceof HTMLElement)
    ) {
        return;
    }

    const linhas =
        lista.querySelectorAll(
            '[data-ligacao]',
        );

    if (linhas.length <= 1) {
        limparLinhaLigacao(
            linha,
        );

        return;
    }

    linha.remove();

    reindexarLigacoes(
        formulario,
    );
}

/**
 * Obtém as ligações atualmente preenchidas pelo utilizador.
 *
 * @param {HTMLFormElement} formulario Formulário.
 *
 * @returns {Array<{titulo: string, url: string}>} Ligações preenchidas.
 *
 * @since 2.0.0
 */
function obterLigacoesAtuais(
    formulario,
) {
    const lista =
        formulario.querySelector(
            '[data-lista-ligacoes]',
        );

    if (!(lista instanceof HTMLElement)) {
        return [];
    }

    const ligacoes = [];

    lista
        .querySelectorAll(
            '[data-ligacao]',
        )
        .forEach(
            (linha) => {
                if (!(linha instanceof HTMLElement)) {
                    return;
                }

                const titulo =
                    linha.querySelector(
                        '[data-campo-ligacao="titulo"]',
                    );

                const url =
                    linha.querySelector(
                        '[data-campo-ligacao="url"]',
                    );

                if (
                    !(titulo instanceof HTMLInputElement)
                    || !(url instanceof HTMLInputElement)
                ) {
                    return;
                }

                const tituloNormalizado =
                    titulo.value.trim();

                const urlNormalizado =
                    url.value.trim();

                if (
                    tituloNormalizado === ''
                    && urlNormalizado === ''
                ) {
                    return;
                }

                ligacoes.push({
                    titulo: tituloNormalizado,
                    url: urlNormalizado,
                });
            },
        );

    return ligacoes;
}

/**
 * Constrói a chave utilizada para eliminar URLs duplicadas.
 *
 * O protocolo e o domínio não distinguem maiúsculas de minúsculas. O caminho,
 * a pesquisa e o fragmento preservam a respetiva capitalização porque podem
 * participar na identidade do recurso remoto.
 *
 * @param {string} endereco URL.
 *
 * @returns {string} Chave normalizada.
 *
 * @since 2.0.0
 */
function normalizarChaveLigacao(
    endereco,
) {
    const valor =
        endereco.trim();

    if (valor === '') {
        return '';
    }

    try {
        const url =
            new URL(
                valor,
            );

        const caminho =
            url.pathname.replace(
                /\/+$/u,
                '',
            );

        return `${url.protocol.toLocaleLowerCase()}//${url.host.toLocaleLowerCase()}${caminho}${url.search}${url.hash}`;
    } catch {
        return valor.replace(
            /\/+$/u,
            '',
        );
    }
}

/**
 * Reescreve as linhas de ligações do formulário.
 *
 * @param {HTMLFormElement} formulario Formulário.
 * @param {Array<{titulo: string, url: string}>} ligacoes Ligações.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function escreverLigacoes(
    formulario,
    ligacoes,
) {
    const lista =
        formulario.querySelector(
            '[data-lista-ligacoes]',
        );

    if (!(lista instanceof HTMLElement)) {
        return;
    }

    lista.replaceChildren();

    const valores =
        ligacoes.length > 0
            ? ligacoes.slice(
                0,
                LIMITE_LIGACOES,
            )
            : [
                {
                    titulo: '',
                    url: '',
                },
            ];

    valores.forEach(
        (
            ligacao,
            indice,
        ) => {
            const linha =
                criarLinhaLigacao(
                    formulario,
                    indice,
                    ligacao,
                );

            if (linha instanceof HTMLElement) {
                lista.append(
                    linha,
                );
            }
        },
    );

    reindexarLigacoes(
        formulario,
    );
}

/**
 * Funde as ligações externas importadas com as ligações locais existentes.
 *
 * As ligações previamente introduzidas pelo utilizador são preservadas.
 *
 * @param {HTMLFormElement} formulario Formulário.
 * @param {unknown} ligacoesImportadas Ligações externas.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function fundirLigacoesImportadas(
    formulario,
    ligacoesImportadas,
) {
    if (!Array.isArray(ligacoesImportadas)) {
        return;
    }

    const candidatas = [
        ...obterLigacoesAtuais(
            formulario,
        ),
    ];

    ligacoesImportadas.forEach(
        (ligacao) => {
            if (
                !eObjeto(ligacao)
                || typeof ligacao.titulo !== 'string'
                || typeof ligacao.url !== 'string'
            ) {
                return;
            }

            const titulo =
                ligacao.titulo.trim();

            const url =
                ligacao.url.trim();

            if (
                titulo === ''
                || url === ''
            ) {
                return;
            }

            candidatas.push({
                titulo,
                url,
            });
        },
    );

    const resultado = [];
    const chaves = new Set();

    candidatas.forEach(
        (ligacao) => {
            if (
                typeof ligacao.titulo !== 'string'
                || typeof ligacao.url !== 'string'
            ) {
                return;
            }

            const titulo =
                ligacao.titulo.trim();

            const url =
                ligacao.url.trim();

            if (
                titulo === ''
                || url === ''
            ) {
                return;
            }

            const chave =
                normalizarChaveLigacao(
                    url,
                );

            if (
                chave === ''
                || chaves.has(
                    chave,
                )
            ) {
                return;
            }

            chaves.add(
                chave,
            );

            resultado.push({
                titulo,
                url,
            });
        },
    );

    escreverLigacoes(
        formulario,
        resultado,
    );
}

/**
 * Inicializa o editor de N ligações.
 *
 * @param {HTMLFormElement} formulario Formulário.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function inicializarLigacoes(
    formulario,
) {
    const botaoAdicionar =
        formulario.querySelector(
            '[data-adicionar-ligacao]',
        );

    if (
        botaoAdicionar instanceof HTMLButtonElement
    ) {
        botaoAdicionar.addEventListener(
            'click',
            () => {
                adicionarLigacao(
                    formulario,
                );
            },
        );
    }

    formulario.addEventListener(
        'click',
        (evento) => {
            const alvo =
                evento.target;

            if (!(alvo instanceof Element)) {
                return;
            }

            const botao =
                alvo.closest(
                    '[data-remover-ligacao]',
                );

            if (
                botao instanceof HTMLButtonElement
            ) {
                removerLigacao(
                    formulario,
                    botao,
                );
            }
        },
    );

    reindexarLigacoes(
        formulario,
    );
}

/**
 * Obtém uma mensagem utilizável a partir de um erro Axios.
 *
 * @param {unknown} erro Erro recebido.
 * @param {string} mensagemPredefinida Mensagem de recurso.
 *
 * @returns {string} Mensagem final.
 *
 * @since 2.0.0
 */
function obterMensagemErro(
    erro,
    mensagemPredefinida,
) {
    if (
        axios.isAxiosError(
            erro,
        )
        && typeof erro.response?.data?.mensagem
            === 'string'
        && erro.response.data.mensagem.trim()
            !== ''
    ) {
        return erro.response.data.mensagem.trim();
    }

    return mensagemPredefinida;
}

/**
 * Atualiza a mensagem da importação.
 *
 * @param {HTMLElement} contentor Contentor da integração.
 * @param {string} mensagem Mensagem.
 * @param {boolean} eErro Estado de erro.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function definirEstadoImportacao(
    contentor,
    mensagem,
    eErro = false,
) {
    const elemento =
        contentor.querySelector(
            '[data-estado-importacao]',
        );

    if (!(elemento instanceof HTMLElement)) {
        return;
    }

    elemento.textContent =
        mensagem;

    elemento.classList.toggle(
        'text-danger',
        eErro,
    );

    elemento.classList.toggle(
        'text-muted',
        !eErro,
    );
}

/**
 * Limpa os resultados da pesquisa.
 *
 * @param {HTMLElement} contentor Contentor da integração.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function limparResultadosImportacao(
    contentor,
) {
    const resultados =
        contentor.querySelector(
            '[data-resultados-importacao]',
        );

    if (
        resultados instanceof HTMLElement
    ) {
        resultados.replaceChildren();
    }
}

/**
 * Cria o ícone visual dos resultados MusicBrainz.
 *
 * @returns {HTMLElement} Elemento visual.
 *
 * @since 2.0.0
 */
function criarIconeResultado() {
    const contentor =
        document.createElement(
            'div',
        );

    contentor.className =
        'flex-shrink-0 rounded-3 border border-secondary bg-dark d-flex align-items-center justify-content-center';

    contentor.style.width =
        '56px';

    contentor.style.height =
        '56px';

    const icone =
        document.createElement(
            'i',
        );

    icone.className =
        'bi bi-music-note-beamed fs-4 text-secondary';

    icone.setAttribute(
        'aria-hidden',
        'true',
    );

    contentor.append(
        icone,
    );

    return contentor;
}

/**
 * Obtém a descrição contextual de um resultado MusicBrainz.
 *
 * @param {Record<string, unknown>} resultado Resultado.
 *
 * @returns {string} Descrição.
 *
 * @since 2.0.0
 */
function obterDescricaoResultado(
    resultado,
) {
    const partes = [];

    if (
        typeof resultado.tipo === 'string'
        && resultado.tipo.trim() !== ''
    ) {
        partes.push(
            resultado.tipo.trim(),
        );
    }

    if (
        typeof resultado.area_inicio === 'string'
        && resultado.area_inicio.trim() !== ''
    ) {
        partes.push(
            resultado.area_inicio.trim(),
        );
    } else if (
        typeof resultado.area === 'string'
        && resultado.area.trim() !== ''
    ) {
        partes.push(
            resultado.area.trim(),
        );
    }

    if (
        Number.isInteger(
            resultado.ano_inicio,
        )
    ) {
        const anoFim =
            Number.isInteger(
                resultado.ano_fim,
            )
                ? resultado.ano_fim
                : null;

        partes.push(
            anoFim === null
                ? `Desde ${resultado.ano_inicio}`
                : `${resultado.ano_inicio}–${anoFim}`,
        );
    }

    return partes.join(
        ' · ',
    );
}

/**
 * Apresenta os resultados da pesquisa MusicBrainz.
 *
 * @param {HTMLElement} contentor Contentor.
 * @param {unknown} resultados Resultados.
 *
 * @returns {number} Número de resultados apresentados.
 *
 * @since 2.0.0
 */
function apresentarResultadosImportacao(
    contentor,
    resultados,
) {
    const elemento =
        contentor.querySelector(
            '[data-resultados-importacao]',
        );

    if (!(elemento instanceof HTMLElement)) {
        return 0;
    }

    elemento.replaceChildren();

    if (!Array.isArray(resultados)) {
        return 0;
    }

    const lista =
        document.createElement(
            'div',
        );

    lista.className =
        'd-grid gap-2';

    let quantidade = 0;

    resultados.forEach(
        (resultado) => {
            if (
                !eObjeto(resultado)
                || typeof resultado.mbid !== 'string'
                || resultado.mbid.trim() === ''
                || typeof resultado.nome !== 'string'
                || resultado.nome.trim() === ''
            ) {
                return;
            }

            const item =
                document.createElement(
                    'div',
                );

            item.className =
                'd-flex align-items-center gap-3 rounded-3 border border-secondary bg-black bg-opacity-25 p-3';

            const icone =
                criarIconeResultado();

            const texto =
                document.createElement(
                    'div',
                );

            texto.className =
                'flex-grow-1 overflow-hidden';

            const nome =
                document.createElement(
                    'strong',
                );

            nome.className =
                'd-block';

            nome.textContent =
                resultado.nome.trim();

            texto.append(
                nome,
            );

            const descricao =
                obterDescricaoResultado(
                    resultado,
                );

            if (descricao !== '') {
                const metadados =
                    document.createElement(
                        'div',
                    );

                metadados.className =
                    'small text-secondary mt-1';

                metadados.textContent =
                    descricao;

                texto.append(
                    metadados,
                );
            }

            if (
                typeof resultado.desambiguacao === 'string'
                && resultado.desambiguacao.trim() !== ''
            ) {
                const desambiguacao =
                    document.createElement(
                        'div',
                    );

                desambiguacao.className =
                    'small text-muted mt-1';

                desambiguacao.textContent =
                    resultado.desambiguacao.trim();

                texto.append(
                    desambiguacao,
                );
            }

            const referencia =
                document.createElement(
                    'div',
                );

            referencia.className =
                'small text-muted mt-1 text-truncate';

            referencia.textContent =
                `MusicBrainz ${resultado.mbid.trim()}`;

            texto.append(
                referencia,
            );

            const botao =
                document.createElement(
                    'button',
                );

            botao.className =
                'btn btn-sm btn-primary flex-shrink-0';

            botao.type =
                'button';

            botao.textContent =
                'Selecionar e preencher';

            botao.dataset.musicbrainzId =
                resultado.mbid.trim();

            botao.setAttribute(
                'data-selecionar-importacao',
                '',
            );

            item.append(
                icone,
                texto,
                botao,
            );

            lista.append(
                item,
            );

            quantidade += 1;
        },
    );

    if (quantidade > 0) {
        elemento.append(
            lista,
        );
    }

    return quantidade;
}

/**
 * Define o valor de um campo normal.
 *
 * @param {HTMLFormElement} formulario Formulário.
 * @param {string} nome Nome do campo.
 * @param {string|number} valor Valor.
 *
 * @returns {boolean} Verdadeiro quando o campo foi alterado.
 *
 * @since 2.0.0
 */
function definirValorCampo(
    formulario,
    nome,
    valor,
) {
    const campo =
        obterCampoFormulario(
            formulario,
            nome,
        );

    if (campo === null) {
        return false;
    }

    const valorNormalizado =
        String(
            valor,
        );

    if (campo instanceof HTMLSelectElement) {
        const tomSelect =
            campo.tomselect;

        if (
            eObjeto(tomSelect)
            && typeof tomSelect.setValue === 'function'
        ) {
            tomSelect.setValue(
                valorNormalizado,
            );

            return true;
        }
    }

    campo.value =
        valorNormalizado;

    campo.dispatchEvent(
        new Event(
            'input',
            {
                bubbles: true,
            },
        ),
    );

    campo.dispatchEvent(
        new Event(
            'change',
            {
                bubbles: true,
            },
        ),
    );

    return true;
}

/**
 * Cria uma ligação externa segura para a pré-visualização.
 *
 * @param {string} texto Texto.
 * @param {unknown} endereco Endereço.
 *
 * @returns {HTMLAnchorElement|null} Ligação.
 *
 * @since 2.0.0
 */
function criarLigacaoExterna(
    texto,
    endereco,
) {
    if (
        typeof endereco !== 'string'
        || endereco.trim() === ''
    ) {
        return null;
    }

    try {
        const url =
            new URL(
                endereco.trim(),
            );

        if (
            ![
                'http:',
                'https:',
            ].includes(
                url.protocol,
            )
        ) {
            return null;
        }
    } catch {
        return null;
    }

    const ligacao =
        document.createElement(
            'a',
        );

    ligacao.className =
        'btn btn-sm btn-outline-info';

    ligacao.href =
        endereco.trim();

    ligacao.target =
        '_blank';

    ligacao.rel =
        'noopener noreferrer';

    ligacao.textContent =
        texto;

    return ligacao;
}

/**
 * Apresenta um resumo compacto da associação selecionada.
 *
 * @param {HTMLElement} contentor Contentor.
 * @param {Record<string, unknown>} artista Artista.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function apresentarAssociacaoImportacao(
    contentor,
    artista,
) {
    const elemento =
        contentor.querySelector(
            '[data-associacao-importacao]',
        );

    if (!(elemento instanceof HTMLElement)) {
        return;
    }

    elemento.replaceChildren();

    const cartao =
        document.createElement(
            'div',
        );

    cartao.className =
        'rounded-3 border border-secondary bg-black bg-opacity-25 p-3';

    const cabecalho =
        document.createElement(
            'div',
        );

    cabecalho.className =
        'd-flex align-items-center gap-3';

    if (
        typeof artista.imagem === 'string'
        && artista.imagem.trim() !== ''
    ) {
        const imagem =
            document.createElement(
                'img',
            );

        imagem.src =
            artista.imagem.trim();

        imagem.alt =
            '';

        imagem.loading =
            'lazy';

        imagem.referrerPolicy =
            'no-referrer';

        imagem.className =
            'flex-shrink-0 rounded-3 border border-secondary';

        imagem.style.width =
            '64px';

        imagem.style.height =
            '64px';

        imagem.style.objectFit =
            'cover';

        cabecalho.append(
            imagem,
        );
    } else {
        cabecalho.append(
            criarIconeResultado(),
        );
    }

    const identidade =
        document.createElement(
            'div',
        );

    identidade.className =
        'flex-grow-1';

    const nome =
        document.createElement(
            'strong',
        );

    nome.className =
        'd-block';

    nome.textContent =
        typeof artista.nome === 'string'
        && artista.nome.trim() !== ''
            ? artista.nome.trim()
            : 'Artista selecionado';

    identidade.append(
        nome,
    );

    if (
        typeof artista.musicbrainz_id === 'string'
        && artista.musicbrainz_id.trim() !== ''
    ) {
        const referencia =
            document.createElement(
                'div',
            );

        referencia.className =
            'small text-secondary mt-1';

        referencia.textContent =
            `MusicBrainz ${artista.musicbrainz_id.trim()}`;

        identidade.append(
            referencia,
        );
    }

    const fontes =
        eObjeto(
            artista.fontes,
        )
            ? artista.fontes
            : {};

    const nomesFontes = [
        'MusicBrainz',
    ];

    if (fontes.theaudiodb === true) {
        nomesFontes.push(
            'TheAudioDB',
        );
    }

    if (fontes.discogs === true) {
        nomesFontes.push(
            'Discogs',
        );
    }

    const textoFontes =
        document.createElement(
            'div',
        );

    textoFontes.className =
        'small text-muted mt-1';

    textoFontes.textContent =
        `Fontes utilizadas: ${nomesFontes.join(', ')}`;

    identidade.append(
        textoFontes,
    );

    cabecalho.append(
        identidade,
    );

    cartao.append(
        cabecalho,
    );

    const acoes =
        document.createElement(
            'div',
        );

    acoes.className =
        'd-flex flex-wrap gap-2 mt-3';

    const musicBrainz =
        criarLigacaoExterna(
            'Abrir no MusicBrainz',
            artista.url_musicbrainz,
        );

    if (musicBrainz !== null) {
        acoes.append(
            musicBrainz,
        );
    }

    if (
        Number.isInteger(
            artista.discogs_id,
        )
    ) {
        const discogs =
            criarLigacaoExterna(
                'Abrir no Discogs',
                artista.url_discogs,
            );

        if (discogs !== null) {
            acoes.append(
                discogs,
            );
        }
    }

    if (acoes.childElementCount > 0) {
        cartao.append(
            acoes,
        );
    }

    elemento.append(
        cartao,
    );
}

/**
 * Aplica uma proposta externa aos campos editáveis.
 *
 * Valores não disponibilizados pelas fontes não apagam dados que o utilizador
 * já tenha introduzido.
 *
 * @param {HTMLFormElement} formulario Formulário.
 * @param {HTMLElement} contentor Contentor.
 * @param {Record<string, unknown>} artista Proposta.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function aplicarPropostaImportacao(
    formulario,
    contentor,
    artista,
) {
    if (
        typeof artista.musicbrainz_id !== 'string'
        || artista.musicbrainz_id.trim() === ''
    ) {
        throw new TypeError(
            'A proposta não possui um identificador MusicBrainz válido.',
        );
    }

    const campoMusicBrainz =
        formulario.querySelector(
            '[data-musicbrainz-id]',
        );

    const campoDiscogs =
        formulario.querySelector(
            '[data-discogs-id]',
        );

    if (
        !(campoMusicBrainz instanceof HTMLInputElement)
        || !(campoDiscogs instanceof HTMLInputElement)
    ) {
        throw new TypeError(
            'Os identificadores externos do formulário não estão disponíveis.',
        );
    }

    campoMusicBrainz.value =
        artista.musicbrainz_id.trim();

    campoDiscogs.value =
        Number.isInteger(
            artista.discogs_id,
        )
        && artista.discogs_id > 0
            ? String(
                artista.discogs_id,
            )
            : '';

    if (
        typeof artista.nome === 'string'
        && artista.nome.trim() !== ''
    ) {
        definirValorCampo(
            formulario,
            'nome',
            artista.nome.trim(),
        );
    }

    if (
        Number.isInteger(
            artista.origem_geografica_id,
        )
        && artista.origem_geografica_id > 0
    ) {
        definirValorCampo(
            formulario,
            'origem_geografica_id',
            artista.origem_geografica_id,
        );
    }

    if (
        Number.isInteger(
            artista.ano_inicio_atividade,
        )
    ) {
        definirValorCampo(
            formulario,
            'ano_inicio_atividade',
            artista.ano_inicio_atividade,
        );
    }

    if (
        Number.isInteger(
            artista.ano_fim_atividade,
        )
    ) {
        definirValorCampo(
            formulario,
            'ano_fim_atividade',
            artista.ano_fim_atividade,
        );
    }

    if (
        typeof artista.estado_atividade === 'string'
        && artista.estado_atividade.trim() !== ''
    ) {
        definirValorCampo(
            formulario,
            'estado_atividade',
            artista.estado_atividade.trim(),
        );
    }

    if (
        typeof artista.biografia === 'string'
        && artista.biografia.trim() !== ''
    ) {
        definirValorCampo(
            formulario,
            'biografia',
            artista.biografia.trim(),
        );
    }

    if (
        typeof artista.imagem === 'string'
        && artista.imagem.trim() !== ''
    ) {
        definirValorCampo(
            formulario,
            'imagem',
            artista.imagem.trim(),
        );
    }

    fundirLigacoesImportadas(
        formulario,
        artista.ligacoes,
    );

    apresentarAssociacaoImportacao(
        contentor,
        artista,
    );

    const botaoRemover =
        contentor.querySelector(
            '[data-acao-remover-importacao]',
        );

    if (
        botaoRemover instanceof HTMLButtonElement
    ) {
        botaoRemover.hidden =
            false;
    }

    let mensagem =
        'Dados importados. Revê os campos antes de guardar.';

    if (
        artista.origem_geografica_id === null
        && eObjeto(
            artista.origem,
        )
        && typeof artista.origem.codigo_pais === 'string'
        && artista.origem.codigo_pais.trim() !== ''
    ) {
        mensagem +=
            ` Não existe uma origem geográfica local correspondente ao código ${artista.origem.codigo_pais.trim()}.`;
    }

    definirEstadoImportacao(
        contentor,
        mensagem,
    );
}

/**
 * Obtém a proposta agregada para o MusicBrainz selecionado.
 *
 * @param {HTMLElement} contentor Contentor.
 * @param {string} mbid Identificador MusicBrainz.
 * @param {HTMLButtonElement} botao Botão acionado.
 *
 * @returns {Promise<void>}
 *
 * @since 2.0.0
 */
async function selecionarImportacao(
    contentor,
    mbid,
    botao,
) {
    const formulario =
        obterFormulario(
            contentor,
        );

    const enderecoModelo =
        contentor.dataset
            .enderecoObterImportacao
            ?.trim()
        ?? '';

    const mbidModelo =
        contentor.dataset
            .mbidModelo
            ?.trim()
        ?? '';

    if (
        formulario === null
        || enderecoModelo === ''
        || mbidModelo === ''
        || mbid.trim() === ''
    ) {
        return;
    }

    definirEstadoImportacao(
        contentor,
        'A obter e combinar os dados disponíveis…',
    );

    botao.disabled =
        true;

    try {
        const resposta =
            await axios.get(
                enderecoModelo.replace(
                    mbidModelo,
                    mbid.trim(),
                ),
            );

        if (
            !eObjeto(
                resposta.data?.artista,
            )
        ) {
            throw new TypeError(
                'A resposta da importação não é válida.',
            );
        }

        aplicarPropostaImportacao(
            formulario,
            contentor,
            resposta.data.artista,
        );

        limparResultadosImportacao(
            contentor,
        );
    } catch (erro) {
        definirEstadoImportacao(
            contentor,
            obterMensagemErro(
                erro,
                'Não foi possível obter os dados do artista selecionado.',
            ),
            true,
        );
    } finally {
        botao.disabled =
            false;
    }
}

/**
 * Pesquisa artistas no MusicBrainz pelo nome atual.
 *
 * @param {HTMLElement} contentor Contentor.
 *
 * @returns {Promise<void>}
 *
 * @since 2.0.0
 */
async function pesquisarImportacao(
    contentor,
) {
    const formulario =
        obterFormulario(
            contentor,
        );

    const endereco =
        contentor.dataset
            .enderecoPesquisaImportacao
            ?.trim()
        ?? '';

    const campoNome =
        formulario === null
            ? null
            : obterCampoFormulario(
                formulario,
                'nome',
            );

    if (
        !(campoNome instanceof HTMLInputElement)
        || endereco === ''
    ) {
        return;
    }

    const pesquisa =
        campoNome.value.trim();

    if (pesquisa === '') {
        definirEstadoImportacao(
            contentor,
            'Indica primeiro o nome do artista.',
            true,
        );

        campoNome.focus();

        return;
    }

    definirEstadoImportacao(
        contentor,
        'A pesquisar no MusicBrainz…',
    );

    limparResultadosImportacao(
        contentor,
    );

    try {
        const resposta =
            await axios.get(
                endereco,
                {
                    params: {
                        pesquisa,
                    },
                },
            );

        const quantidade =
            apresentarResultadosImportacao(
                contentor,
                resposta.data?.resultados,
            );

        definirEstadoImportacao(
            contentor,
            quantidade > 0
                ? 'Seleciona o artista correto. Depois serão procurados automaticamente dados complementares no TheAudioDB.'
                : 'Não foram encontrados artistas para esta pesquisa.',
        );
    } catch (erro) {
        definirEstadoImportacao(
            contentor,
            obterMensagemErro(
                erro,
                'Não foi possível pesquisar artistas.',
            ),
            true,
        );
    }
}

/**
 * Remove apenas a associação às bases externas.
 *
 * Os valores que já tenham sido copiados para o formulário permanecem
 * disponíveis para edição e podem ser guardados como dados locais.
 *
 * @param {HTMLElement} contentor Contentor.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function removerAssociacaoImportacao(
    contentor,
) {
    const formulario =
        obterFormulario(
            contentor,
        );

    if (formulario === null) {
        return;
    }

    const musicBrainz =
        formulario.querySelector(
            '[data-musicbrainz-id]',
        );

    const discogs =
        formulario.querySelector(
            '[data-discogs-id]',
        );

    if (
        musicBrainz instanceof HTMLInputElement
    ) {
        musicBrainz.value =
            '';
    }

    if (
        discogs instanceof HTMLInputElement
    ) {
        discogs.value =
            '';
    }

    limparResultadosImportacao(
        contentor,
    );

    const associacao =
        contentor.querySelector(
            '[data-associacao-importacao]',
        );

    if (
        associacao instanceof HTMLElement
    ) {
        associacao.replaceChildren();
    }

    const botao =
        contentor.querySelector(
            '[data-acao-remover-importacao]',
        );

    if (
        botao instanceof HTMLButtonElement
    ) {
        botao.hidden =
            true;
    }

    definirEstadoImportacao(
        contentor,
        'Associação externa removida. Os dados já copiados para os campos foram mantidos.',
    );
}

/**
 * Sincroniza a apresentação inicial da associação.
 *
 * @param {HTMLFormElement} formulario Formulário.
 * @param {HTMLElement} contentor Contentor.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function sincronizarEstadoInicialImportacao(
    formulario,
    contentor,
) {
    const musicBrainz =
        formulario.querySelector(
            '[data-musicbrainz-id]',
        );

    const discogs =
        formulario.querySelector(
            '[data-discogs-id]',
        );

    const botao =
        contentor.querySelector(
            '[data-acao-remover-importacao]',
        );

    const possuiMusicBrainz =
        musicBrainz instanceof HTMLInputElement
        && musicBrainz.value.trim() !== '';

    const possuiDiscogs =
        discogs instanceof HTMLInputElement
        && discogs.value.trim() !== '';

    if (
        botao instanceof HTMLButtonElement
    ) {
        botao.hidden =
            !possuiMusicBrainz
            && !possuiDiscogs;
    }

    if (possuiMusicBrainz) {
        definirEstadoImportacao(
            contentor,
            `Perfil MusicBrainz ${musicBrainz.value.trim()} associado.`,
        );

        return;
    }

    if (possuiDiscogs) {
        definirEstadoImportacao(
            contentor,
            `Existe uma associação anterior ao Discogs #${discogs.value.trim()}. Podes pesquisar no MusicBrainz para enriquecer a ficha.`,
        );

        return;
    }

    definirEstadoImportacao(
        contentor,
        '',
    );
}

/**
 * Inicializa a importação externa de um formulário.
 *
 * @param {HTMLFormElement} formulario Formulário.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function inicializarImportacao(
    formulario,
) {
    const contentor =
        formulario.querySelector(
            '[data-importacao-artista]',
        );

    if (!(contentor instanceof HTMLElement)) {
        return;
    }

    const botaoPesquisar =
        contentor.querySelector(
            '[data-acao-pesquisar-importacao]',
        );

    const botaoRemover =
        contentor.querySelector(
            '[data-acao-remover-importacao]',
        );

    if (
        botaoPesquisar instanceof HTMLButtonElement
    ) {
        botaoPesquisar.addEventListener(
            'click',
            () => {
                void pesquisarImportacao(
                    contentor,
                );
            },
        );
    }

    if (
        botaoRemover instanceof HTMLButtonElement
    ) {
        botaoRemover.addEventListener(
            'click',
            () => {
                removerAssociacaoImportacao(
                    contentor,
                );
            },
        );
    }

    contentor.addEventListener(
        'click',
        (evento) => {
            const alvo =
                evento.target;

            if (!(alvo instanceof Element)) {
                return;
            }

            const botao =
                alvo.closest(
                    '[data-selecionar-importacao]',
                );

            if (!(botao instanceof HTMLButtonElement)) {
                return;
            }

            const mbid =
                botao.dataset
                    .musicbrainzId
                    ?.trim()
                ?? '';

            if (mbid === '') {
                return;
            }

            void selecionarImportacao(
                contentor,
                mbid,
                botao,
            );
        },
    );

    formulario.addEventListener(
        'reset',
        () => {
            window.setTimeout(
                () => {
                    limparResultadosImportacao(
                        contentor,
                    );

                    const associacao =
                        contentor.querySelector(
                            '[data-associacao-importacao]',
                        );

                    if (
                        associacao instanceof HTMLElement
                    ) {
                        associacao.replaceChildren();
                    }

                    sincronizarEstadoInicialImportacao(
                        formulario,
                        contentor,
                    );
                },
                0,
            );
        },
    );

    sincronizarEstadoInicialImportacao(
        formulario,
        contentor,
    );
}

/**
 * Inicializa todos os formulários de perfil de artista existentes.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function iniciarPerfisArtista() {
    document
        .querySelectorAll(
            SELETOR_FORMULARIO,
        )
        .forEach(
            (formulario) => {
                if (
                    !(formulario instanceof HTMLFormElement)
                ) {
                    return;
                }

                inicializarCamposAdicionais(
                    formulario,
                );

                inicializarLigacoes(
                    formulario,
                );

                inicializarImportacao(
                    formulario,
                );
            },
        );
}

if (
    document.readyState === 'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPerfisArtista,
        {
            once: true,
        },
    );
} else {
    iniciarPerfisArtista();
}
