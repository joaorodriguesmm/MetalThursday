/**
 * Gere os testes de links embed.
 *
 * @since 1.0
 * @version 1.0
 */
class EmbedTester {
    /**
     * Cria um novo EmbedTester.
     *
     * @param HTMLElement sectionElement - Elemento HTML da secção.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(sectionElement) {
        this.section          = sectionElement;
        this.linkInput        = this.section.querySelector('.link-input');
        this.testBtn          = this.section.querySelector('.link-test-btn');
        this.resultsContainer = this.section.querySelector('.link-test-results');

        if (!this.linkInput || !this.testBtn || !this.resultsContainer) {
            return;
        }

        this.hiddenInput = this.section.querySelector('.embed-type-input');
        this.statusArea  = this.section.querySelector('.test-status');
        this.providers   = window.embedProviders || [];

        this.init();
    }

    /**
     * Inicia o EmbedTester.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        this.testBtn.addEventListener('click', () => this.test());
        this.resultsContainer.addEventListener('change', (e) => this.updateChoice(e));
    }

    /**
     * Testa um link.
     *
     * @since 1.0
     * @version 1.0
     */
    test() {
        const url = this.linkInput.value.trim();
        if (!url) return;

        this.reset();
        this.resultsContainer.style.display = 'block';
        if (this.statusArea) {
            this.statusArea.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> A gerar pré-visualizações...`;
        }

        let foundSomething = false;

        this.providers.forEach(provider => {
            const regex = new RegExp(provider.regex);
            const match = regex.exec(url);

            if (match && match[1]) {
                foundSomething = true;
                this.showPreview(provider.type, match[1]);
            }
        });

        const isYtMusicLink = url.includes('music.youtube.com/watch?v=');
        if (isYtMusicLink && !url.includes('list=')) {
            const videoProvider = this.providers.find(p => p.type === 'youtube_video');
            if (videoProvider) {
                const videoRegex = new RegExp(videoProvider.regex);
                const videoMatch = videoRegex.exec(url);
                if (videoMatch && videoMatch[1]) {
                    foundSomething = true;
                    this.showPreview('youtube_playlist', videoMatch[1]);
                }
            }
        }

        if (this.statusArea) {
            this.statusArea.textContent = foundSomething
                ? 'Teste concluído. Por favor, confirma a seleção correta.'
                : 'Nenhum embed automático detetado. Será guardado como link simples.';
            this.statusArea.className = `test-status small mt-2 ${foundSomething ? 'text-success' : 'text-warning'}`;
        }
    }

    /**
     * Apresenta a pré-visualização para um tipo de embed específico.
     *
     * @param string type - Tipo de embed.
     * @param string id - Id do embed.
     *
     * @since 1.0
     * @version 1.0
     */
    showPreview(type, id) {
        const baseName         = type.replace('youtube_', '');
        const optionContainer  = this.section.querySelector(`.${baseName}-option`);
        const previewContainer = this.section.querySelector(`.${baseName}-preview-container`);

        if (!optionContainer || !previewContainer) return;

        let embedUrl = '';
        if (type === 'youtube_video') {
            embedUrl = `https://www.youtube.com/embed/${id}`;
        } else if (type === 'youtube_playlist') {
            embedUrl = `https://www.youtube.com/embed/videoseries?list=${id}`;
        }

        if (embedUrl) {
            previewContainer.innerHTML = `<iframe class="embed-responsive-item w-100" height="200" src="${embedUrl}" frameborder="0" allowfullscreen></iframe>`;
            optionContainer.style.display = 'block';
        }
    }

    /**
     * Atualiza a opção selecionada.
     *
     * @param Event event - Evento.
     *
     * @since 1.0
     * @version 1.0
     */
    updateChoice(event) {
        if (event.target.classList.contains('embed-choice-radio') && this.hiddenInput) {
            this.hiddenInput.value = event.target.value;
        }
    }

    /**
     * Limpa o teste.
     *
     * @since 1.0
     * @version 1.0
     */
    reset() {
        if (this.resultsContainer) this.resultsContainer.style.display = 'none';
        this.providers.forEach(provider => {
            const baseName        = provider.type.replace('youtube_', '');
            const optionContainer = this.section.querySelector(`.${baseName}-option`);
            if(optionContainer) optionContainer.style.display = 'none';
            const previewContainer = this.section.querySelector(`.${baseName}-preview-container`);
            if(previewContainer) previewContainer.innerHTML = '';
        });
        if (this.statusArea) this.statusArea.textContent = '';
        const linkRadio = this.section.querySelector('input[value="link"]');
        if (linkRadio) linkRadio.checked = true;
        if (this.hiddenInput) this.hiddenInput.value = 'link';
    }
}

export default EmbedTester;
