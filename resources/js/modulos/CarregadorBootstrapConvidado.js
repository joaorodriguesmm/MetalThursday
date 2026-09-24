/**
 * Carrega apenas os componentes Bootstrap necessários nas páginas de
 * visitante.
 *
 * @since 2.0.0
 */

/**
 * Seletor dos alertas que podem ser dispensados pelo utilizador.
 *
 * @type {string}
 *
 * @since 2.0.0
 */
const SELETOR_ALERTA_DISPENSAVEL =
    '[data-bs-dismiss="alert"]';

/**
 * Carregador predefinido do componente Alert do Bootstrap.
 *
 * A importação do módulo é suficiente para registar o comportamento Data API
 * associado a `data-bs-dismiss="alert"`.
 *
 * @type {() => Promise<object>}
 *
 * @since 2.0.0
 */
const CARREGAR_ALERTA_PREDEFINIDO =
    () => import(
        'bootstrap/js/dist/alert'
    );

/**
 * Inicia os componentes Bootstrap necessários para a página atual.
 *
 * @param {() => Promise<object>} carregarAlerta Carregador do componente
 *     Alert.
 *
 * @returns {Promise<void>}
 *
 * @since 2.0.0
 */
async function iniciarBootstrapConvidado(
    carregarAlerta = CARREGAR_ALERTA_PREDEFINIDO,
) {
    if (
        document.querySelector(
            SELETOR_ALERTA_DISPENSAVEL,
        ) === null
    ) {
        return;
    }

    await carregarAlerta();
}

export {
    iniciarBootstrapConvidado,
};
