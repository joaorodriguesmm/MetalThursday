import axios from 'axios';

/**
 * Cliente HTTP partilhado pela aplicação.
 *
 * As predefinições são configuradas apenas quando um módulo que efetua
 * pedidos HTTP importa este cliente, evitando carregar Axios em páginas que
 * não realizam pedidos assíncronos.
 *
 * @since 2.0.0
 */

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common.Accept = 'application/json';

export default axios;
