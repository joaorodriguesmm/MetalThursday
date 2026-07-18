/**
 * Gere os botões de seleção de nomeados.
 *
 * @since 1.0
 * @version 1.0
 */
class NomineeSelector {
    /**
     * Cria um novo NomineeSelector.
     *
     * @param Object options - Opções de configuração.
     * @param string options.randomBtnSelector - Seletor do botão de nomeado aleatório.
     * @param string options.oldestBtnSelector - Seletor do botão de nomeado mais antigo.
     * @param Object options.tomSelectInstance - A instância do TomSelect do campo de nomeados.
     * @param string options.oldestNomineeUrl - URL para obter o nomeado mais antigo.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor({ randomBtnSelector, oldestBtnSelector, tomSelectInstance, oldestNomineeUrl }) {
        this.randomBtn = document.querySelector(randomBtnSelector);
        this.oldestBtn = document.querySelector(oldestBtnSelector);
        this.tomSelect = tomSelectInstance;
        this.url       = oldestNomineeUrl;

        if (!this.tomSelect) return;
        this.init();
    }

    /**
     * Inicia o NomineeSelector.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        if (this.randomBtn) {
            this.randomBtn.addEventListener('click', () => this.selectRandom());
        }
        if (this.oldestBtn) {
            this.oldestBtn.addEventListener('click', () => this.selectOldest());
        }
    }

    /**
     * Seleciona um nomeado aleatório.
     *
     * @since 1.0
     * @version 1.0
     */
    selectRandom() {
        const options = Object.keys(this.tomSelect.options).filter(val => val);
        if (options.length === 0) return;
        const randomId = options[Math.floor(Math.random() * options.length)];
        this.tomSelect.setValue(randomId);
    }

    /**
     * Seleciona o nomeado mais antigo.
     *
     * @since 1.0
     * @version 1.0
     */
    async selectOldest() {
        this.oldestBtn.disabled = true;
        try {
            const response = await axios.get(this.url);
            if (response.data && response.data.id) {
                this.tomSelect.setValue(response.data.id);
            }
        } catch (error) {
            Swal.fire('Erro', 'Não foi possível obter o nomeado mais antigo.', 'error');
        } finally {
            this.oldestBtn.disabled = false;
        }
    }
}

export default NomineeSelector;
