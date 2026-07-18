/**
 * Script especifico para a pagina de detalhes de MetalThursday.
 *
 * @since 1.0
 * @version 1.0
 */
import AjaxFormHandler from '../modules/AjaxFormHandler';
import FormValidator from '../modules/FormValidator';
import RatingModalHandler from '../modules/RatingModalHandler';
import TooltipInitializer from '../modules/TooltipInitializer';

/**
 * Define os comportamentos a executar após o carregamento da página.
 *
 * @since 1.0
 * @version 1.0
 */
document.addEventListener('DOMContentLoaded', function() {
    /**
     * Inicia os tooltips.
     *
     * @since 1.0
     * @version 1.0
     */
    new TooltipInitializer();

    /**
     * Inicia o gestor de modais de avaliação.
     *
     * @since 1.0
     * @version 1.0
     */
    new RatingModalHandler();

    /**
     * Função reutilizável para inicializar os formulários de comentário.
     *
     * @param HTMLElement container - O elemento dentro do qual procurar por formulários.
     *
     * @since 1.0
     * @version 1.0
     */
    const initializeCommentForms = (container) => {
        container.querySelectorAll('.comment-form').forEach(form => {
            if (form.dataset.initialized) return;
            form.dataset.initialized = 'true';

            const ajaxHandler = new AjaxFormHandler(form.id, form.action, (responseData) => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = responseData;
                const newCommentElement = tempDiv.firstElementChild;

                if (form.closest('.reply-form-container')) {
                    const repliesContainer = form.closest('.comment-container').querySelector('.replies-container');
                    repliesContainer.appendChild(newCommentElement);
                    form.closest('.reply-form-container').style.display = 'none';
                } else {
                    const commentsList = form.closest('.card-body, .card-footer + .collapse').querySelector('.comments-list');
                    commentsList.querySelector('.no-comments-placeholder')?.remove();
                    commentsList.appendChild(newCommentElement);
                }

                initializeCommentForms(newCommentElement);
                new TooltipInitializer();
            });

            new FormValidator(
                `#${form.id}`, { 'content': ['required'] }, { 'content': { 'required': 'Por favor, insere o texto do comentário.' } },
                () => ajaxHandler.submit()
            );
        });
    };
    initializeCommentForms(document);
});
