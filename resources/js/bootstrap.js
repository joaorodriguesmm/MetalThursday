/**
 * Importa bibliotecas externas.
 *
 * @version 1.0
 * @since 1.0
 */
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
