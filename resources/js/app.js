/**
 * Ponto de entrada principal para o JavaScript global da aplicação.
 * Inicializa funcionalidades que devem estar disponíveis em todas as páginas.
 *
 * @since 1.0
 * @version 1.0
 */
/**
 * Importa o Bootstrap, o TomSelect e o SweetAlert.
 *
 * @since 1.0
 * @version 1.0
 */
import './bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;
import TomSelect from 'tom-select';
window.TomSelect = TomSelect;
import Swal from 'sweetalert2';
window.Swal = Swal;

/**
 * Importa os módulos AjaxFormHandler e InteractionHandler.
 *
 * @since 1.0
 * @version 1.0
 */
import AjaxFormHandler from './modules/AjaxFormHandler';
import InteractionHandler from './modules/InteractionHandler';
import ModalFormCleaner from './modules/ModalFormCleaner';

/**
 * Define comportamentos globais da aplicação após o carregamento do DOM.
 *
 * @since 1.0
 * @version 1.0
 */
document.addEventListener('DOMContentLoaded', () => {
    /**
     * Inicia os módulos AjaxFormHandler e InteractionHandler.
     *
     * @since 1.0
     * @version 1.0
     */
    new AjaxFormHandler();
    new InteractionHandler();
    new ModalFormCleaner();

    /**
     * Define o comportamento dos links de logout.
     *
     * @since 1.0
     * @version 1.0
     */
    document.querySelectorAll('.logout-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            document.getElementById('logout-form').submit();
        });
    });

    /**
     * Ativa o lazy loading para os vídeos do YouTube.
     *
     * @since 1.0
     * @version 1.0
     */
    document.body.addEventListener('click', function(e) {
        const lazyLoadContainer = e.target.closest('.video-lazy-load');
        if (lazyLoadContainer) {
            const videoUrl = lazyLoadContainer.dataset.videoUrl;
            const iframe = `
                <div class='ratio ratio-16x9'>
                    <iframe src='${videoUrl}' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe>
                </div>
            `;
            lazyLoadContainer.innerHTML = iframe;
        }
    });
});
