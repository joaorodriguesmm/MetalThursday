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
 * Promessas de inicializacao associadas a cada conjunto de carregadores.
 *
 * Em producao, os carregadores predefinidos mantem uma identidade estavel.
 * Nos testes, cada conjunto simulado possui estado independente.
 *
 * @type {WeakMap<object, Map<string, Promise<void>>>}
 *
 * @since 2.0.0
 */
const TAREFAS_POR_CARREGADORES = new WeakMap();

/**
 * Partilha a inicializacao de um modulo entre chamadas concorrentes.
 *
 * Mantem a promessa depois do sucesso e remove-a em caso de falha,
 * permitindo repetir apenas o modulo que nao foi inicializado.
 *
 * @param {object} carregadores Carregadores dos modulos.
 * @param {string} nomeModulo Nome do modulo.
 *
 * @returns {Promise<void>} Tarefa de inicializacao.
 *
 * @since 2.0.0
 */
function carregarModuloUmaVez(carregadores, nomeModulo) {
    let tarefas = TAREFAS_POR_CARREGADORES.get(
        carregadores,
    );

    if (!tarefas) {
        tarefas = new Map();

        TAREFAS_POR_CARREGADORES.set(
            carregadores,
            tarefas,
        );
    }

    if (tarefas.has(nomeModulo)) {
        return tarefas.get(nomeModulo);
    }

    const tarefa = Promise.resolve()
        .then(() => carregadores[nomeModulo]())
        .then(({default: ConstrutorModulo}) => {
            new ConstrutorModulo();
        })
        .catch((erro) => {
            if (tarefas.get(nomeModulo) === tarefa) {
                tarefas.delete(nomeModulo);
            }

            throw erro;
        });

    tarefas.set(nomeModulo, tarefa);

    return tarefa;
}

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
            carregarModuloUmaVez(
                carregadores,
                'interacoes',
            ),
        );
    }

    if (
        document.querySelector(
            SELETORES.modais,
        ) !== null
    ) {
        tarefas.push(
            carregarModuloUmaVez(
                carregadores,
                'modais',
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
