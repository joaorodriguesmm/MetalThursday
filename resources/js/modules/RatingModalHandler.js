import AjaxFormHandler from './AjaxFormHandler';
import { Tooltip } from 'bootstrap';

/**
 * Gere a interatividade da modal de avaliação.
 *
 * @since 1.0
 * @version 1.0
 */
class RatingModalHandler {
    /**
     * Cria um novo RatingModalHandler.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor() {
        this.modalElement = document.getElementById('ratingModal');
        if (!this.modalElement) return;

        this.form = document.getElementById('rating-form');
        this.starsContainer = document.getElementById('interactive-stars');
        this.hiddenInput = document.getElementById('rating-value-hidden');
        this.feedbackElement = document.getElementById('rating-live-feedback');
        this.rateableNameElement = document.getElementById('rateable-name');

        this.rateableTypeInput = document.getElementById('rateable-type-hidden');
        this.rateableIdInput = document.getElementById('rateable-id-hidden');

        this.currentRating = 0;
        this.selectedRating = 0;
        this.triggerButton = null;

        this.init();
    }

    /**
     * Inicia o RatingModalHandler.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        this.modalElement.addEventListener('show.bs.modal', (event) => {
            this.triggerButton = event.relatedTarget;
            this.configureModal();
            this.setupStarEvents();
            this.setupFormSubmission();
        });

        this.modalElement.addEventListener('hidden.bs.modal', () => {
            this.starsContainer.innerHTML = this.starsContainer.innerHTML;
        });
    }

    /**
     * Configura o modal de avaliação.
     *
     * @since 1.0
     * @version 1.0
     */
    configureModal() {
        const rateableType = this.triggerButton.dataset.rateableType;
        const rateableId = this.triggerButton.dataset.rateableId;
        const rateableName = this.triggerButton.dataset.rateableName;
        const userRating = parseFloat(this.triggerButton.dataset.userRating) || 0;

        this.rateableTypeInput.value = rateableType;
        this.rateableIdInput.value = rateableId;

        this.form.action = `/${rateableType}/${rateableId}/rate`;

        this.rateableNameElement.textContent = rateableName;

        this.selectedRating = userRating;
        this.currentRating = userRating;
        this.updateStars(this.currentRating);
        this.hiddenInput.value = this.currentRating;
    }

    /**
     * Configura os eventos das estrelas.
     *
     * @since 1.0
     * @version 1.0
     */
    setupStarEvents() {
        const stars = this.starsContainer.querySelectorAll('i');
        stars.forEach(star => {
            star.addEventListener('mousemove', (e) => this.handleMouseOver(e));
            star.addEventListener('click', (e) => this.handleClick(e));
        });

        this.starsContainer.addEventListener('mouseout', () => this.handleMouseOut());
    }

    /**
     * Obtem o valor da avaliação a partir do evento.
     *
     * @param Event e - O evento.
     *
     * @since 1.0
     * @version 1.0
     */
    getRatingFromEvent(e) {
        const star = e.target;
        const baseValue = parseInt(star.dataset.value);
        const rect = star.getBoundingClientRect();
        const isHalf = (e.clientX - rect.left) < rect.width / 2;

        return isHalf ? baseValue - 0.5 : baseValue;
    }

    /**
     * Trata o evento de passar o rato sobre uma estrela.
     *
     * @param Event e - O evento de passar o rato sobre uma estrela.
     *
     * @since 1.0
     * @version 1.0
     */
    handleMouseOver(e) {
        this.currentRating = this.getRatingFromEvent(e);
        this.updateStars(this.currentRating);
    }

    /**
     * Trata o evento de sair do rato das estrelas.
     *
     * @since 1.0
     * @version 1.0
     */
    handleMouseOut() {
        this.currentRating = this.selectedRating;
        this.updateStars(this.selectedRating);
    }

    /**
     * Trata o evento de clique na estrela.
     *
     * @param Event e - O evento de clique na estrela.
     *
     * @since 1.0
     * @version 1.0
     */
    handleClick(e) {
        this.selectedRating = this.getRatingFromEvent(e);
        this.currentRating = this.selectedRating;
        this.hiddenInput.value = this.selectedRating;
        this.updateStars(this.selectedRating);
    }

    /**
     * Atualiza as estrelas da avaliação.
     *
     * @param number value - O valor da avaliação.
     *
     * @since 1.0
     * @version 1.0
     */
    updateStars(value) {
        const stars = this.starsContainer.querySelectorAll('i');
        const fullStars = Math.floor(value);
        const hasHalfStar = value % 1 !== 0;

        stars.forEach(star => {
            const starValue = parseInt(star.dataset.value);
            star.className = 'bi';

            if (starValue <= fullStars) {
                star.classList.add('bi-star-fill', 'star-filled');
            } else if (hasHalfStar && starValue === fullStars + 1) {
                star.classList.add('bi-star-half', 'star-filled');
            } else {
                star.classList.add('bi-star');
            }
        });

        if (this.feedbackElement) {
            this.feedbackElement.textContent = value > 0 ? `A tua seleção: ${value.toFixed(1)}/10` : 'Clica numa estrela para avaliar.';
        }
    }

    /**
     * Configura o envio do formulário de avaliação.
     *
     * @since 1.0
     * @version 1.0
     */
    setupFormSubmission() {
        // Garantir que o Bootstrap Modal é importado para fechar o modal
        const { Modal } = window.bootstrap || {};

        this.form.addEventListener('submit', (e) => {
            e.preventDefault();

            // CRÍTICO: Obter a URL de AÇÃO atualizada (definida em configureModal())
            const currentAction = this.form.action;

            // Re-inicializar o AjaxFormHandler AQUI para usar a URL correta (currentAction)
            const ajaxHandler = new AjaxFormHandler(this.form.id, currentAction, (data) => {
                // Lógica de Sucesso: Atualizar o botão/elemento que abriu o modal
                const ratingDisplay = this.triggerButton.closest('.d-flex')?.querySelector('.rating-display');
                const buttonSpan = this.triggerButton.querySelector('span');

                // 1. Atualizar o estado do botão
                if (buttonSpan) {
                    buttonSpan.textContent = `A tua Avaliação: ${data.user_rating.toFixed(1)}`;
                    // CRÍTICO: Atualizar o data-user-rating no botão para a próxima abertura
                    this.triggerButton.dataset.userRating = data.user_rating;

                    this.triggerButton.classList.remove('btn-dark', 'btn-outline-warning');
                    this.triggerButton.classList.add('btn-warning');
                }

                // 2. Atualizar o estado de exibição da avaliação média
                if (ratingDisplay) {
                    ratingDisplay.querySelector('.average-rating').textContent = data.average_rating;
                    ratingDisplay.querySelector('.ratings-count').textContent = data.ratings_count;
                    this.updateTooltip(ratingDisplay, data.tooltip_html);
                }

                // 3. Fechar o modal após sucesso
                if (Modal) {
                    const modalInstance = Modal.getInstance(this.modalElement) || new Modal(this.modalElement);
                    modalInstance.hide();
                }
            });

            // Submeter o formulário
            ajaxHandler.submit();
        });
    }

    /**
     * Atualiza o tooltip do botão de avaliação.
     *
     * @param HTMLElement element - O botão de avaliação.
     * @param string newTitle - O novo texto do tooltip.
     *
     * @since 1.0
     * @version 1.0
     */
    updateTooltip(element, newTitle) {
        if (!element) return;
        element.setAttribute('data-bs-title', newTitle);
        const tooltipInstance = Tooltip.getInstance(element);
        if (tooltipInstance) {
            tooltipInstance.dispose();
        }
        new Tooltip(element);
    }
}

export default RatingModalHandler;
