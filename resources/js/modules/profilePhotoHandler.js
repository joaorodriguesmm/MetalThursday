/**
 * Gere a pré-visualização de foto de perfil.
 *
 * @since 1.0
 * @version 1.0
 */
class ProfilePhotoHandler {
    /**
     * Cria um novo ProfilePhotoHandler.
     *
     * @param string inputSelector - Seletor CSS para o input de ficheiro.
     * @param string previewSelector - Seletor CSS para o elemento de pré-visualização da imagem.
     * @param string initialsSelector - Seletor CSS para o elemento que mostra as iniciais.
     * @param string|null clearButtonSelector - Seletor CSS para o botão de limpar (opcional).
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(inputSelector, previewSelector, initialsSelector, clearButtonSelector = null) {
        this.fileInput       = document.querySelector(inputSelector);
        this.previewElement  = document.querySelector(previewSelector);
        this.initialsElement = document.querySelector(initialsSelector);
        this.clearButton     = clearButtonSelector ? document.querySelector(clearButtonSelector) : null;

        this.avatarCircle = this.initialsElement ? this.initialsElement.closest('.avatar-circle') : null;

        if (!this.fileInput || !this.previewElement || !this.initialsElement) {
            return;
        }

        this.setupEventListeners();
        this.initializeState();
    }

    /**
     * Configura os event listeners.
     *
     * @since 1.0
     * @version 1.0
     */
    setupEventListeners() {
        if (this.clearButton) {
            this.clearButton.addEventListener('click', this.handleClearButtonClick.bind(this));
        }
    }

    /**
     * Lida com o clique no botão de limpar.
     *
     * @param Event e - O evento de clique.
     *
     * @since 1.0
     * @version 1.0
     */
    handleClearButtonClick(e) {
        e.preventDefault();
        this.fileInput.value = '';
        this.resetPreview();
        const customFileText = document.getElementById('custom-file-text');
        if (customFileText) {
            customFileText.textContent = 'Escolher ficheiro';
        }
    }

    /**
     * Inicializa o estado da pré-visualização no carregamento da página.
     *
     * @since 1.0
     * @version 1.0
     */
    initializeState() {
        const hasOldPhotoSrc = this.previewElement.src && this.previewElement.src !== window.location.href + '#';
        if (hasOldPhotoSrc && !this.previewElement.classList.contains('d-none')) {
            if (this.avatarCircle) this.avatarCircle.classList.add('d-none');
            this.previewElement.classList.remove('d-none');
        } else {
            this.resetPreview();
        }
    }

    /**
     * Pré-visualiza uma imagem.
     *
     * @param File|null file - O objeto File a pré-visualizar, ou null para esconder a pré-visualização.
     *
     * @since 1.0
     * @version 1.0
     */
    previewImage(file) {
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewElement.src = e.target.result;
                this.previewElement.classList.remove('d-none');
                if (this.avatarCircle) this.avatarCircle.classList.add('d-none');
                if (this.clearButton) this.clearButton.style.display = 'inline-block';
            };
            reader.readAsDataURL(file);
        } else {
            this.resetPreview();
        }
    }

    /**
     * Reseta a pré-visualização para mostrar as iniciais.
     *
     * @since 1.0
     * @version 1.0
     */
    resetPreview() {
        this.previewElement.src = '#';
        this.previewElement.classList.add('d-none');
        if (this.avatarCircle) this.avatarCircle.classList.remove('d-none');
        if (this.clearButton) this.clearButton.style.display = 'none';
    }
}

export default ProfilePhotoHandler;
