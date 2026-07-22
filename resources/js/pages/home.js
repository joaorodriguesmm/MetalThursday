/**
 * Script especifico para a pagina de início (listagem de MetalThursdays).
 *
 * @since 1.0
 * @version 1.0
 */
import AjaxFormHandler from '../modules/AjaxFormHandler';
import DynamicFilterManager from '../modules/DynamicFilterManager';
import ValidadorFormulario from '../modulos/ValidadorFormulario';
import RatingModalHandler from '../modules/RatingModalHandler';
import InicializadorTooltips from '../modulos/InicializadorTooltips';
import ViewToggler from '../modules/ViewToggler';

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
    new InicializadorTooltips();

    /**
     * Inicia o gestor de modais de avaliação.
     *
     * @since 1.0
     * @version 1.0
     */
    new RatingModalHandler();

    /**
     * Inicia o gestor de filtros de pesquisa dinâmicos.
     *
     * @since 1.0
     * @version 1.0
     */
    new DynamicFilterManager({
        dropdownSelector : '#add_filter_dropdown',
        containerSelector: '#active-filters-area',
        filterData       : window.filterData || {},
        availableFilters : window.availableFilters || {}
    });

    /**
     * Submite automaticamente os formulários de pesquisa de ordenação.
     *
     * @since 1.0
     * @version 1.0
     */
    const filterForm = document.getElementById('filter-sort-form');
    if (filterForm) {
        filterForm.querySelectorAll('.auto-submit').forEach(select => {
            select.addEventListener('change', () => {
                filterForm.submit();
            });
        });
    }

    /**
     * Inicia o alternador de vistas.
     *
     * @since 1.0
     * @version 1.0
     */
    new ViewToggler({
        buttonSelector: '#view-toggle-btn',
        inputSelector : '#view-type-input',
        formSelector  : '#filter-sort-form',
        translations  : window.viewTranslations || { full: 'full', simplified: 'simplified' }
    });

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
                new InicializadorTooltips();
            });

            new FormValidator(
                `#${form.id}`, { 'content': ['required'] }, { 'content': { 'required': 'Por favor, insere o texto do comentário.' } },
                () => ajaxHandler.submit()
            );
        });
    };
    initializeCommentForms(document);
});
