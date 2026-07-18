import Swal from 'sweetalert2';
import axios from 'axios';

/**
 * Gere todas as interações de clique baseadas em AJAX (gostar, ouvir, apagar, etc.).
 *
 * @since 1.0
 * @version 1.1
 */
class InteractionHandler {
    /**
     * Cria um novo InteractionHandler.
     *
     * @param string|null containerSelector - Seletor CSS para o container principal.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(containerSelector = 'body') {
        this.container = document.querySelector(containerSelector);
        if (!this.container) return;
        this.init();
    }

    /**
     * Inicia o InteractionHandler.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        this.container.addEventListener('click', (e) => {
            const button = e.target.closest('[data-interaction-type]');
            if (button) {
                e.preventDefault();
                this.handleInteraction(button);
            }

            const form = e.target.closest('.comment-edit-form form');
            if (form && e.target.type === 'submit') {
                e.preventDefault();
                this.handleEditSubmit(form);
            }
        });

        this.container.addEventListener('mouseover', (e) => {
            const likesSpan = e.target.closest('.likes-count[data-comment-id]');
            if (likesSpan && !likesSpan.dataset.loaded) {
                this.handleLikesTooltip(likesSpan);
            }
        });
    }

    async handleLikesTooltip(element) {
        const commentId = element.dataset.commentId;
        element.dataset.loaded = "loading";

        try {
            const response = await axios.get(`/comments/${commentId}/likers`);
            this.updateTooltip(element, response.data.html);
            element.dataset.loaded = "true";
        } catch (error) {
            element.dataset.loaded = "false";
        }
    }

    /**
     * Trata uma interação.
     *
     * @param HTMLElement button - Botão de interação.
     *
     * @since 1.0
     * @version 1.1
     */
    async handleInteraction(button) {
        const type = button.dataset.interactionType;
        const url = button.dataset.url;

        if (['toggle-reply', 'cancel-reply', 'edit-start', 'edit-cancel'].includes(type)) {
            this.updateUI(button, type);
            return;
        }

        if (!url) return;

        if (type === 'delete') {
            const result = await Swal.fire({
                title: 'Tens a certeza que desejas eliminar?',
                text: 'Não poderás reverter esta ação!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6d3f40',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, eliminar!',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;
        }

        button.disabled = true;

        try {
            const response = await axios.post(url, {
                _method: type === 'delete' ? 'DELETE' : 'POST'
            });

            if (response.data && response.data.message) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: response.data.message,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            }

            this.updateUI(button, type, response.data);
        } catch (error) {
            Swal.fire('Erro', 'Ocorreu um erro ao processar a ação.', 'error');
        } finally {
            button.disabled = false;
        }
    }

    /**
     * Trata a submissão do formulário de edição de comentário.
     *
     * @param HTMLElement form - Formulário de edição de comentário.
     *
     * @since 1.1
     * @version 1.0
     */
    async handleEditSubmit(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> A guardar...`;

        const textarea = form.querySelector('textarea');
        const feedback = form.querySelector('.invalid-feedback');
        textarea.classList.remove('is-invalid');
        feedback.textContent = '';
        feedback.style.display = 'none';

        try {
            const response = await axios.patch(form.dataset.url, {
                content: textarea.value
            });

            const commentContainer = form.closest('.comment-container');
            const contentDisplay = commentContainer.querySelector('.comment-content p');
            contentDisplay.innerHTML = response.data.content_html;

            form.closest('.comment-edit-form').style.display = 'none';
            commentContainer.querySelector('.comment-content').style.display = 'block';

        } catch (error) {
            if (error.response && error.response.status === 422) {
                textarea.classList.add('is-invalid');
                feedback.textContent = error.response.data.errors.content[0];
                feedback.style.display = 'block';
            } else {
                Swal.fire('Erro', 'Ocorreu um erro inesperado.', 'error');
            }
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Guardar';
        }
    }

    /**
     * Atualiza a interface do utilizador.
     *
     * @param HTMLElement button - Botão de interação.
     * @param string type - Tipo de interação.
     * @param object data - Dados da interação.
     *
     * @since 1.0
     * @version 1.1
     */
    updateUI(button, type, data) {
        switch (type) {
            case 'like':
                const countSpan = button.querySelector('.likes-count');
                countSpan.textContent = data.likes_count;
                delete countSpan.dataset.loaded;
                this.updateTooltip(countSpan, "A carregar...");
                const icon = button.querySelector('i');
                icon.classList.toggle('bi-heart', !data.liked);
                icon.classList.toggle('bi-heart-fill', data.liked);
                icon.classList.toggle('text-danger', data.liked);
                break;
            case 'listen':
                const buttonTextSpan = button.querySelector('span');
                const listenableType = button.dataset.listenableType;
                if (listenableType === 'section') {
                    if (buttonTextSpan) buttonTextSpan.textContent = data.has_heard ? 'Ouvido' : 'Marcar como ouvido';
                } else {
                    if (buttonTextSpan) buttonTextSpan.textContent = data.has_heard ? 'Ouvida' : 'Marcar MetalThursday como Ouvida';
                }
                const interactionContainer = button.closest('.d-flex.justify-content-between');
                const listenDisplay = interactionContainer ? interactionContainer.querySelector('.listen-display') : null;
                if (listenDisplay) {
                    listenDisplay.querySelector('.listens-count').textContent = data.listens_count;
                    this.updateTooltip(listenDisplay, data.tooltip_html);
                }
                break;
            case 'delete': {
                const parentSelector = button.dataset.removableParent;
                if (parentSelector) {
                    const elementToRemove = button.closest(parentSelector);
                    if (elementToRemove) {
                        elementToRemove.style.transition = 'opacity 0.3s ease-out';
                        elementToRemove.style.opacity = '0';
                        setTimeout(() => elementToRemove.remove(), 300);
                    }
                }
                break;
            }
            case 'toggle-reply':
            case 'cancel-reply': {
                const actionsContainer = button.closest('.comment-actions, .reply-form-container');
                const replyForm = actionsContainer?.parentNode.querySelector('.reply-form-container');
                if (replyForm) {
                    const show = type === 'toggle-reply' && replyForm.style.display === 'none';
                    replyForm.style.display = show ? 'block' : 'none';
                    if (show) replyForm.querySelector('textarea').focus();
                }
                break;
            }
            case 'edit-start': {
                const commentContainer = button.closest('.comment-container');
                if (commentContainer) {
                    commentContainer.querySelector('.comment-content').style.display = 'none';
                    const editForm = commentContainer.querySelector('.comment-edit-form');
                    editForm.style.display = 'block';
                    editForm.querySelector('textarea').focus();
                }
                break;
            }
            case 'edit-cancel': {
                const commentContainer = button.closest('.comment-container');
                if (commentContainer) {
                    const editForm = commentContainer.querySelector('.comment-edit-form');
                    const originalContent = commentContainer.querySelector('.comment-content p').textContent;
                    editForm.querySelector('textarea').value = originalContent;
                    editForm.style.display = 'none';
                    commentContainer.querySelector('.comment-content').style.display = 'block';
                }
                break;
            }
        }
    }

    /**
     * Atualiza o tooltip.
     *
     * @param HTMLElement element - Elemento do tooltip.
     * @param string newTitle - Novo texto do tooltip.
     *
     * @since 1.0
     * @version 1.0
     */
    updateTooltip(element, newTitle) {
        element.setAttribute('data-bs-title', newTitle);
        const tooltipInstance = bootstrap.Tooltip.getInstance(element);
        if (tooltipInstance) {
            tooltipInstance.dispose();
        }
        new bootstrap.Tooltip(element);
    }
}

export default InteractionHandler;
