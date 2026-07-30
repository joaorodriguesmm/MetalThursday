import AlternadorVistas from '../modulos/AlternadorVistas';
import GestorFiltrosDinamicos from '../modulos/GestorFiltrosDinamicos';
import GestorModalAvaliacao from '../modulos/GestorModalAvaliacao';
import InicializadorTooltips from '../modulos/InicializadorTooltips';
import TratadorFormularioAjax from '../modulos/TratadorFormularioAjax';
import ValidadorFormulario from '../modulos/ValidadorFormulario';

/**
 * Inicializa os comportamentos da página principal de MetalThursdays.
 *
 * Gere os filtros, a ordenação, a alternância de vistas, as avaliações,
 * os tooltips e a publicação de comentários.
 *
 * @since 1.0.0
 * @version 3.0.0
 */

/**
 * Identificador do bloco JSON com a configuração da listagem.
 *
 * @type {string}
 *
 * @since 3.0.0
 * @version 1.0.0
 */
const IDENTIFICADOR_CONFIGURACAO_LISTAGEM =
    'configuracao-listagem-metal-thursday';

/**
 * Determina se um valor é um objeto não nulo.
 *
 * @param {unknown} valor Valor recebido.
 *
 * @returns {boolean} Verdadeiro quando o valor é um objeto.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function eObjeto(valor) {
    return typeof valor === 'object'
        && valor !== null
        && !Array.isArray(valor);
}

/**
 * Obtém a configuração preparada pelo servidor para a listagem.
 *
 * @returns {{
 *     dadosFiltros: Record<string, Array<object>>,
 *     filtrosDisponiveis: Record<string, object>,
 *     vistas: {
 *         completa: string,
 *         simplificada: string
 *     }
 * }} Configuração validada.
 *
 * @throws {Error} Quando o bloco de configuração não existe ou contém JSON
 * inválido.
 * @throws {TypeError} Quando a configuração não respeita o contrato esperado.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterConfiguracaoListagem() {
    const elemento =
        document.getElementById(
            IDENTIFICADOR_CONFIGURACAO_LISTAGEM,
        );

    if (!(elemento instanceof HTMLScriptElement)) {
        throw new Error(
            'Não foi encontrada a configuração da listagem de MetalThursdays.',
        );
    }

    const conteudo =
        elemento.textContent?.trim()
        ?? '';

    if (conteudo === '') {
        throw new Error(
            'A configuração da listagem de MetalThursdays está vazia.',
        );
    }

    let configuracao;

    try {
        configuracao = JSON.parse(
            conteudo,
        );
    } catch (erro) {
        throw new Error(
            'A configuração da listagem de MetalThursdays contém JSON inválido.',
            {
                cause: erro,
            },
        );
    }

    if (!eObjeto(configuracao)) {
        throw new TypeError(
            'A configuração da listagem de MetalThursdays deve ser um objeto.',
        );
    }

    [
        'dadosFiltros',
        'filtrosDisponiveis',
        'vistas',
    ].forEach((chave) => {
        if (!eObjeto(configuracao[chave])) {
            throw new TypeError(
                `A propriedade "${chave}" da configuração da listagem é inválida.`,
            );
        }
    });

    return configuracao;
}

/**
 * Inicializa os formulários de comentário existentes num contentor.
 *
 * Após uma publicação bem-sucedida, a página é recarregada para apresentar
 * o comentário através dos componentes Blade e das autorizações calculadas
 * pelo servidor.
 *
 * @param {Document|Element} contentor Contentor de pesquisa.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
 */
function inicializarFormulariosComentario(
    contentor,
) {
    contentor.querySelectorAll(
        [
            'form.formulario-comentario',
            'form.formulario-resposta-comentario',
        ].join(', '),
    ).forEach((formulario) => {
        if (
            !(formulario instanceof HTMLFormElement)
            || formulario.dataset
                .formularioComentarioInicializado
                === 'true'
        ) {
            return;
        }

        if (
            formulario.id.trim() === ''
            || formulario.action.trim() === ''
        ) {
            throw new Error(
                'Cada formulário de comentário deve possuir identificador e endereço de submissão.',
            );
        }

        formulario.dataset
            .formularioComentarioInicializado =
                'true';

        const tratadorAjax =
            new TratadorFormularioAjax(
                formulario.id,
                formulario.action,
                () => {
                    window.location.reload();
                },
            );

        new ValidadorFormulario(
            formulario,
            {
                regras: {
                    conteudo: [
                        'obrigatorio',
                    ],
                },

                mensagens: {
                    conteudo: {
                        obrigatorio:
                            'Por favor, insere o texto do comentário.',
                    },
                },

                aoSucesso: () => {
                    /*
                     * A promessa não é devolvida porque o
                     * ValidadorFormulario exige um callback síncrono.
                     */
                    tratadorAjax.submeter();
                },
            },
        );
    });
}

/**
 * Configura a submissão automática dos campos de filtro e ordenação.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
 */
function configurarSubmissaoAutomatica() {
    const formulario = document.getElementById(
        'formulario-filtros-ordenacao',
    );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    formulario.querySelectorAll(
        '.submissao-automatica',
    ).forEach((campo) => {
        if (
            !(
                campo instanceof HTMLInputElement
                || campo instanceof HTMLSelectElement
            )
            || campo.dataset
                .submissaoAutomaticaInicializada
                === 'true'
        ) {
            return;
        }

        campo.dataset
            .submissaoAutomaticaInicializada =
                'true';

        campo.addEventListener(
            'change',
            () => {
                if (
                    typeof formulario.requestSubmit
                    === 'function'
                ) {
                    formulario.requestSubmit();

                    return;
                }

                formulario.submit();
            },
        );
    });
}

/**
 * Inicializa os componentes da página principal.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
 */
function inicializarPaginaInicio() {
    const configuracao =
        obterConfiguracaoListagem();

    new InicializadorTooltips();
    new GestorModalAvaliacao();

    new GestorFiltrosDinamicos({
        seletorListaFiltros:
            '#seletor-adicionar-filtro',

        seletorContentorFiltros:
            '#area-filtros-ativos',

        dadosFiltros:
            configuracao.dadosFiltros,

        filtrosDisponiveis:
            configuracao.filtrosDisponiveis,
    });

    configurarSubmissaoAutomatica();

    new AlternadorVistas({
        seletorBotao:
            '#botao-alternar-vista',

        seletorCampoVista:
            '#campo-tipo-vista',

        seletorFormulario:
            '#formulario-filtros-ordenacao',

        vistas:
            configuracao.vistas,
    });

    inicializarFormulariosComentario(
        document,
    );
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        inicializarPaginaInicio,
        {
            once: true,
        },
    );
} else {
    inicializarPaginaInicio();
}
