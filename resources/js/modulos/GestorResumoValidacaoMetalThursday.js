/**
 * Resume os erros já identificados pelos validadores do formulário.
 * Não cria regras adicionais nem envia dados para o servidor.
 *
 * @since 2.0.0
 */
class GestorResumoValidacaoMetalThursday {
    /** @type {WeakMap<HTMLFormElement, GestorResumoValidacaoMetalThursday>} */
    static instancias = new WeakMap();

    /**
     * @param {HTMLFormElement} formulario Formulário de criação ou edição.
     * @returns {GestorResumoValidacaoMetalThursday|undefined} Instância existente.
     */
    constructor(formulario) {
        if (!(formulario instanceof HTMLFormElement)) {
            throw new TypeError('O formulário de MetalThursday é inválido.');
        }

        const existente = GestorResumoValidacaoMetalThursday.instancias.get(formulario);
        if (existente) {
            return existente;
        }

        const resumo = formulario.querySelector('[data-resumo-validacao-metal-thursday]');
        if (!(resumo instanceof HTMLElement)) {
            // Os testes legados e formulários reduzidos não incluem este componente.
            return;
        }

        const lista = resumo.querySelector('[data-lista-erros-metal-thursday]');
        const contador = resumo.querySelector('[data-contador-erros-metal-thursday]');
        if (!(lista instanceof HTMLElement) || !(contador instanceof HTMLElement)) {
            throw new TypeError('A estrutura do resumo de validação está incompleta.');
        }

        this.formulario = formulario;
        this.resumo = resumo;
        this.lista = lista;
        this.contador = contador;
        this.ativo = false;
        this.alvos = new WeakMap();

        // O validador principal é inicializado primeiro e já assinalou os
        // campos inválidos quando este listener de submissão é executado.
        formulario.addEventListener('submit', (evento) => {
            if (
                evento instanceof SubmitEvent
                && evento.submitter instanceof HTMLElement
                && evento.submitter.hasAttribute('formnovalidate')
            ) {
                return;
            }

            const erros = this.obterErros();
            this.ativo = erros.length > 0;
            this.apresentar(erros);
        });

        const atualizar = () => {
            if (this.ativo) {
                this.apresentar(this.obterErros());
            }
        };
        formulario.addEventListener('input', atualizar);
        formulario.addEventListener('change', atualizar);

        formulario.addEventListener('reset', () => {
            this.ativo = false;
            this.apresentar([]);
        });

        resumo.addEventListener('click', (evento) => {
            if (!(evento.target instanceof Element)) {
                return;
            }

            const botao = evento.target.closest('[data-ir-para-erro-metal-thursday]');
            if (!(botao instanceof HTMLButtonElement)) {
                return;
            }

            const alvo = this.alvos.get(botao);
            if (!(alvo instanceof HTMLElement) || !formulario.contains(alvo)) {
                return;
            }

            const seccao = alvo.closest('.item-seccao');
            const alternador = seccao?.querySelector('.botao-alternar-seccao');
            if (
                alternador instanceof HTMLButtonElement
                && alternador.getAttribute('aria-expanded') === 'false'
            ) {
                alternador.click();
            }

            if (
                alvo instanceof HTMLSelectElement
                && typeof alvo.tomselect?.focus === 'function'
            ) {
                alvo.tomselect.focus();
            } else {
                alvo.focus();
            }

            if (typeof alvo.scrollIntoView === 'function') {
                alvo.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        GestorResumoValidacaoMetalThursday.instancias.set(formulario, this);
    }

    /**
     * Recolhe apenas erros que o validador já assinalou na DOM.
     *
     * @returns {Array<{campo: HTMLElement, etiqueta: string, mensagem: string}>}
     */
    obterErros() {
        const erros = [];
        const seccoes = Array.from(this.formulario.querySelectorAll('.item-seccao'));

        for (const campo of this.formulario.querySelectorAll('[aria-invalid="true"]')) {
            if (
                !(
                    campo instanceof HTMLInputElement
                    || campo instanceof HTMLSelectElement
                    || campo instanceof HTMLTextAreaElement
                )
                || !campo.name
                || campo.disabled
            ) {
                continue;
            }

            const seccao = campo.closest('.item-seccao');
            const indice = seccao instanceof HTMLElement ? seccoes.indexOf(seccao) : -1;
            const etiquetaCampo = this.obterEtiqueta(campo);
            const etiqueta = indice >= 0
                ? `Secção ${indice + 1} — ${etiquetaCampo}`
                : etiquetaCampo;

            erros.push({
                campo,
                etiqueta,
                mensagem: this.obterMensagem(campo),
            });
        }

        const erroSeccoes = this.formulario.querySelector('#erro-seccoes');
        const botaoAdicionar = this.formulario.querySelector('#botao-adicionar-seccao');
        if (
            erroSeccoes instanceof HTMLElement
            && erroSeccoes.textContent.trim() !== ''
            && botaoAdicionar instanceof HTMLButtonElement
        ) {
            erros.push({
                campo: botaoAdicionar,
                etiqueta: 'Secções da MetalThursday',
                mensagem: erroSeccoes.textContent.trim(),
            });
        }

        return erros;
    }

    /**
     * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} campo Campo inválido.
     * @returns {string} Etiqueta legível do campo.
     */
    obterEtiqueta(campo) {
        const etiqueta = campo.id
            ? Array.from(this.formulario.querySelectorAll('label[for]'))
                .find((elemento) => elemento.htmlFor === campo.id)
            : null;

        const texto = etiqueta?.textContent?.replace(/\s*\*\s*/gu, ' ')
            .replace(/\s+/gu, ' ').trim();
        if (texto) {
            return texto;
        }

        return campo.getAttribute('aria-label')?.trim()
            || campo.name.replace(/\[|\]|_/gu, ' ').trim()
            || 'Campo por corrigir';
    }

    /**
     * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} campo Campo inválido.
     * @returns {string} Mensagem de validação existente.
     */
    obterMensagem(campo) {
        const ids = (campo.getAttribute('aria-describedby') ?? '').split(/\s+/u);
        for (const id of ids) {
            const elemento = id ? document.getElementById(id) : null;
            if (
                elemento instanceof HTMLElement
                && this.formulario.contains(elemento)
                && elemento.classList.contains('invalid-feedback')
                && elemento.textContent.trim()
            ) {
                return elemento.textContent.trim();
            }
        }

        const grupo = campo.closest('.grupo-campo-formulario');
        const feedback = grupo?.querySelector('.invalid-feedback');
        return feedback?.textContent?.trim() || 'Verifica o valor indicado.';
    }

    /**
     * Apresenta os erros sem criar HTML a partir de valores dos campos.
     *
     * @param {Array<{campo: HTMLElement, etiqueta: string, mensagem: string}>} erros Erros atuais.
     * @returns {void}
     */
    apresentar(erros) {
        this.lista.replaceChildren();
        this.alvos = new WeakMap();
        this.resumo.hidden = erros.length === 0;

        if (erros.length === 0) {
            this.contador.textContent = '';
            return;
        }

        this.contador.textContent = erros.length === 1
            ? 'Foi encontrado 1 campo por corrigir.'
            : `Foram encontrados ${erros.length} campos por corrigir.`;

        for (const erro of erros) {
            const item = document.createElement('li');
            item.className = 'resumo-validacao-metal-thursday__item';

            const botao = document.createElement('button');
            botao.type = 'button';
            botao.className = 'resumo-validacao-metal-thursday__ligacao';
            botao.dataset.irParaErroMetalThursday = '';
            botao.textContent = erro.etiqueta;

            const mensagem = document.createElement('span');
            mensagem.className = 'resumo-validacao-metal-thursday__mensagem';
            mensagem.textContent = erro.mensagem;

            item.append(botao, mensagem);
            this.lista.append(item);
            this.alvos.set(botao, erro.campo);
        }
    }
}

export default GestorResumoValidacaoMetalThursday;
