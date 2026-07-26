import axios
    from 'axios';

/**
 * Configuração global do cliente HTTP da aplicação.
 *
 * Disponibiliza o Axios através do objeto Window para compatibilidade
 * com scripts que ainda não utilizem importações de módulos e identifica
 * os pedidos como assíncronos.
 *
 * @since 1.0.0
 * @version 2.0.0
 */

/**
 * Configura o cliente HTTP utilizado pela aplicação.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function configurarClienteHttp() {
    axios.defaults.headers.common[
        'X-Requested-With'
    ] = 'XMLHttpRequest';

    window.axios = axios;
}

configurarClienteHttp();
