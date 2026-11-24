import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
} from 'vitest';

import GestorAlteracoesMetalThursday
    from '../../../resources/js/modulos/GestorAlteracoesMetalThursday.js';

/**
 * Exercita a deteção de alterações sem depender de pedidos HTTP.
 *
 * @since 2.0.0
 */
describe('Proteção de alterações da MetalThursday', () => {
    /** @type {HTMLFormElement} */
    let formulario;

    const simularSaida = () => {
        const evento = new Event('beforeunload', { cancelable: true });
        window.dispatchEvent(evento);
        return evento.defaultPrevented;
    };

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="formulario-criar-metal-thursday">
                <input type="hidden" name="_token" value="token">
                <input name="nome" value="MetalThursday">
                <input type="checkbox" name="destaque" value="1">
                <div id="contentor-seccoes">
                    <section class="item-seccao">
                        <input name="seccoes[0][titulo]" value="Álbum A">
                        <textarea name="seccoes[0][descricao]">Descrição A</textarea>
                    </section>
                </div>
                <p data-estado-alteracoes-metal-thursday></p>
                <button type="submit">Guardar</button>
            </form>
        `;
        formulario = document.querySelector('form');
    });

    afterEach(() => {
        document.body.replaceChildren();
    });

    it('deteta alterações manuais, ignora o token e limpa o aviso ao repor valores', () => {
        const gestor = new GestorAlteracoesMetalThursday(formulario);
        const nome = formulario.elements.namedItem('nome');
        const estado = formulario.querySelector('[data-estado-alteracoes-metal-thursday]');

        expect(gestor.possuiAlteracoes()).toBe(false);
        expect(simularSaida()).toBe(false);
        expect(estado.textContent).toBe('Sem alterações por guardar');

        formulario.querySelector('[name="_token"]').value = 'outro-token';
        expect(gestor.possuiAlteracoes()).toBe(false);

        nome.value = 'MetalThursday atualizada';
        nome.dispatchEvent(new Event('input', { bubbles: true }));
        expect(gestor.possuiAlteracoes()).toBe(true);
        expect(estado.textContent).toBe('Alterações por guardar');
        expect(simularSaida()).toBe(true);

        nome.value = 'MetalThursday';
        nome.dispatchEvent(new Event('input', { bubbles: true }));
        expect(gestor.possuiAlteracoes()).toBe(false);
        expect(estado.textContent).toBe('Sem alterações por guardar');
        expect(simularSaida()).toBe(false);
    });

    it('deteta checkbox e campos preenchidos por JavaScript com eventos', () => {
        const gestor = new GestorAlteracoesMetalThursday(formulario);
        const destaque = formulario.querySelector('[name="destaque"]');
        const titulo = formulario.querySelector('[name="seccoes[0][titulo]"]');

        destaque.checked = true;
        destaque.dispatchEvent(new Event('change', { bubbles: true }));
        expect(gestor.possuiAlteracoes()).toBe(true);

        destaque.checked = false;
        destaque.dispatchEvent(new Event('change', { bubbles: true }));
        expect(gestor.possuiAlteracoes()).toBe(false);

        titulo.value = 'Álbum importado do Discogs';
        titulo.dispatchEvent(new Event('change', { bubbles: true }));
        expect(gestor.possuiAlteracoes()).toBe(true);
    });

    it('deteta secções e ligações adicionadas, removidas e reordenadas', async () => {
        const gestor = new GestorAlteracoesMetalThursday(formulario);
        const contentor = formulario.querySelector('#contentor-seccoes');
        const estado = formulario.querySelector('[data-estado-alteracoes-metal-thursday]');
        const secaoAdicionada = document.createElement('section');
        secaoAdicionada.className = 'item-seccao';
        secaoAdicionada.innerHTML = `
            <input name="seccoes[1][titulo]" value="Álbum B">
            <input name="seccoes[1][ligacoes][0][url]" value="https://example.com">
        `;
        contentor.append(secaoAdicionada);

        await Promise.resolve();
        await Promise.resolve();
        expect(gestor.possuiAlteracoes()).toBe(true);
        expect(estado.textContent).toBe('Alterações por guardar');

        const inicial = contentor.querySelector('.item-seccao');
        contentor.prepend(secaoAdicionada);
        expect(gestor.possuiAlteracoes()).toBe(true);
        expect(simularSaida()).toBe(true);

        secaoAdicionada.remove();
        await Promise.resolve();
        await Promise.resolve();
        expect(contentor.firstElementChild).toBe(inicial);
        expect(gestor.possuiAlteracoes()).toBe(false);
        expect(estado.textContent).toBe('Sem alterações por guardar');
    });

    it('não desativa o aviso perante uma submissão impedida pela validação', async () => {
        formulario.addEventListener('submit', (evento) => evento.preventDefault());
        const gestor = new GestorAlteracoesMetalThursday(formulario);
        const nome = formulario.elements.namedItem('nome');
        nome.value = 'Alterado';
        nome.dispatchEvent(new Event('input', { bubbles: true }));

        formulario.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true,
        }));
        await Promise.resolve();

        expect(gestor.emSubmissao).toBe(false);
        expect(simularSaida()).toBe(true);
        expect(formulario.querySelector('[data-estado-alteracoes-metal-thursday]').textContent)
            .toBe('Alterações por guardar');
    });

    it('permite sair numa submissão válida e não instala listeners duplicados', () => {
        const gestor = new GestorAlteracoesMetalThursday(formulario);
        expect(new GestorAlteracoesMetalThursday(formulario)).toBe(gestor);

        const nome = formulario.elements.namedItem('nome');
        nome.value = 'Pronta para guardar';
        nome.dispatchEvent(new Event('input', { bubbles: true }));
        expect(simularSaida()).toBe(true);

        formulario.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true,
        }));

        expect(gestor.emSubmissao).toBe(true);
        expect(simularSaida()).toBe(false);
        expect(formulario.querySelector('[data-estado-alteracoes-metal-thursday]').textContent)
            .toBe('A guardar…');
    });
});
