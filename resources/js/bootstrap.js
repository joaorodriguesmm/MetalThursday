import axios
    from 'axios';

/**
 * Configura globalmente a instância partilhada do Axios.
 *
 * Todos os módulos importam diretamente o cliente HTTP. As predefinições
 * configuradas neste ponto são partilhadas pela mesma instância do módulo,
 * sem expor o Axios através do objeto Window.
 *
 * @since 1.0.0
 * @version 3.0.0
 */

/**
 * Configura os cabeçalhos e a proteção contra pedidos entre origens.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
 */
function configurarClienteHttp() {
    axios.defaults.headers.common[
        'X-Requested-With'
    ] = 'XMLHttpRequest';

    axios.defaults.headers.common.Accept =
        'application/json';

    axios.defaults.withXSRFToken =
        true;
}

configurarClienteHttp();
