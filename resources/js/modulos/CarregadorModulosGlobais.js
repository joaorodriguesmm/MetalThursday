/**
 * Carrega apenas os módulos globais necessários para o conteúdo presente.
 *
 * @since 2.0.0
 */

/**
 * Seletores que determinam os módulos globais necessários.
 *
 * O formulário de comentários é considerado separadamente porque uma página
 * pode começar sem comentários renderizados e criar interações posteriormente.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 */
const SELETORES = Object.freeze({
    interacoes:
        '[data-tipo-interacao], .formulario-comentario',

    modais:
        '.modal',
});

/**
 * Carregadores predefinidos dos módulos opcionais.
 *
 * @type {Readonly<Record<string, () => Promise<object>>>}
 *
 * @since 2.0.0
 */
const CARREGADORES_PREDEFINIDOS = Object.freeze({
    interacoes:
        () => import(
            './GestorInteracoes'
        ),

    modais:
        () => import(
            './LimpadorFormulariosModais'
        ),
});

/**
 * Inicia os módulos globais necessários para a página atual.
 *
 * Os carregadores podem ser substituídos nos testes sem alterar o
 * comportamento utilizado em produção.
 *
 * @param {{
 *     interacoes: () => Promise<{default: Function}>,
 *     modais: () => Promise<{default: Function}>
 * }} carregadores Carregadores dos módulos opcionais.
 *
 * @returns {Promise<void>}
 *
 * @since 2.0.0
 */
async function iniciarModulosGlobais(
    carregadores = CARREGADORES_PREDEFINIDOS,
) {
    const tarefas = [];

    if (
        document.querySelector(
            SELETORES.interacoes,
        ) !== null
    ) {
        tarefas.push(
            carregadores.interacoes().then(
                ({
                    default:
                        GestorInteracoes,
                }) => {
                    new GestorInteracoes();
                },
            ),
        );
    }

    if (
        document.querySelector(
            SELETORES.modais,
        ) !== null
    ) {
        tarefas.push(
            carregadores.modais().then(
                ({
                    default:
                        LimpadorFormulariosModais,
                }) => {
                    new LimpadorFormulariosModais();
                },
            ),
        );
    }

    await Promise.all(
        tarefas,
    );
}

export {
    iniciarModulosGlobais,
};
