/**
 * Gere a limpeza dos formulários existentes nas janelas modais do Bootstrap.
 *
 * O evento `hidden.bs.modal` permanece em inglês por corresponder ao evento
 * disponibilizado pelo Bootstrap.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
class LimpadorFormulariosModais {
    /**
     * Cria um limpador de formulários de janelas modais.
     *
     * @param {string} seletorModais Seletor CSS das janelas modais.
     *
     * @throws {TypeError} Quando o seletor é inválido.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor(seletorModais = '.modal') {
        if (
            typeof seletorModais !== 'string'
            || seletorModais.trim() === ''
        ) {
            throw new TypeError(
                'O seletor das janelas modais é obrigatório.',
            );
        }

        try {
            this.modais = Array.from(
                document.querySelectorAll(seletorModais),
            ).filter(
                (modal) => modal instanceof HTMLElement,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletorModais}" é inválido.`,
            );
        }

        this.iniciado = false;

        this.aoOcultarModal = (evento) => {
            this.limparFormulariosModal(evento);
        };

        if (this.modais.length > 0) {
            this.iniciar();
        }
    }

    /**
     * Inicia a limpeza automática das janelas modais.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        if (this.iniciado) {
            return;
        }

        this.modais.forEach((modal) => {
            modal.addEventListener(
                'hidden.bs.modal',
                this.aoOcultarModal,
            );
        });

        this.iniciado = true;
    }

    /**
     * Limpa todos os formulários da janela modal que foi fechada.
     *
     * @param {Event} evento Evento de fecho da janela modal.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    limparFormulariosModal(evento) {
        const modal =
            evento.currentTarget instanceof HTMLElement
                ? evento.currentTarget
                : null;

        if (modal === null) {
            return;
        }

        modal.querySelectorAll('form').forEach((formulario) => {
            if (formulario instanceof HTMLFormElement) {
                this.limparFormulario(formulario);
            }
        });
    }

    /**
     * Limpa um formulário e os respetivos estados de validação.
     *
     * @param {HTMLFormElement} formulario Formulário a limpar.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    limparFormulario(formulario) {
        formulario.reset();

        this.sincronizarTomSelect(formulario);
        this.limparCamposInvalidos(formulario);
        this.limparMensagensValidacao(formulario);
    }

    /**
     * Sincroniza as instâncias do Tom Select com os valores repostos.
     *
     * @param {HTMLFormElement} formulario Formulário limpo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    sincronizarTomSelect(formulario) {
        formulario
            .querySelectorAll('.tomselected')
            .forEach((elemento) => {
                const instancia = elemento.tomselect;

                if (!instancia) {
                    return;
                }

                if (typeof instancia.sync === 'function') {
                    instancia.sync();

                    return;
                }

                if (typeof instancia.clear === 'function') {
                    instancia.clear(true);
                }
            });
    }

    /**
     * Remove o estado inválido dos campos do formulário.
     *
     * @param {HTMLFormElement} formulario Formulário limpo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    limparCamposInvalidos(formulario) {
        formulario
            .querySelectorAll('.is-invalid')
            .forEach((elemento) => {
                elemento.classList.remove('is-invalid');
                elemento.removeAttribute('aria-invalid');
            });
    }

    /**
     * Limpa as mensagens de validação do formulário.
     *
     * @param {HTMLFormElement} formulario Formulário limpo.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    limparMensagensValidacao(formulario) {
        formulario
            .querySelectorAll('.invalid-feedback')
            .forEach((elemento) => {
                elemento.textContent = '';
                elemento.classList.remove('d-block');
                elemento.style.removeProperty('display');
            });
    }

    /**
     * Remove os eventos associados às janelas modais.
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    destruir() {
        if (!this.iniciado) {
            return;
        }

        this.modais.forEach((modal) => {
            modal.removeEventListener(
                'hidden.bs.modal',
                this.aoOcultarModal,
            );
        });

        this.iniciado = false;
    }
}

export default LimpadorFormulariosModais;
