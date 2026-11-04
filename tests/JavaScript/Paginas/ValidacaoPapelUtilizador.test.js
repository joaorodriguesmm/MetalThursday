import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

/**
 * Testa a validação da alteração administrativa de papel no navegador.
 *
 * @since 2.0.0
 */
describe('A08 — validação do papel de um utilizador', () => {
    let formulario;
    let papel;
    let confirmacao;
    let erroPapel;
    let erroConfirmacao;

    beforeEach(async () => {
        document.body.innerHTML = `
            <form id="formulario-alterar-papel" novalidate>
                <select
                    id="papel-utilizador"
                    name="papel"
                    data-papel-atual="utilizador"
                    aria-describedby="erro-papel-utilizador"
                >
                    <option value="utilizador">Utilizador</option>
                    <option value="administrador">Administrador</option>
                </select>
                <div
                    id="erro-papel-utilizador"
                    class="invalid-feedback"
                ></div>
                <input
                    id="confirmar-alteracao-papel"
                    type="checkbox"
                    name="confirmar_alteracao_papel"
                    value="1"
                    aria-describedby="erro-confirmar-alteracao-papel"
                >
                <div
                    id="erro-confirmar-alteracao-papel"
                    class="invalid-feedback"
                ></div>
                <button type="submit">Alterar papel</button>
            </form>
        `;

        vi.resetModules();

        await import(
            '../../../resources/js/paginas/utilizadores.js'
        );

        document.dispatchEvent(new Event('DOMContentLoaded'));

        formulario = document.querySelector('#formulario-alterar-papel');
        papel = formulario.querySelector('[name="papel"]');
        confirmacao = formulario.querySelector(
            '[name="confirmar_alteracao_papel"]',
        );
        erroPapel = formulario.querySelector('#erro-papel-utilizador');
        erroConfirmacao = formulario.querySelector(
            '#erro-confirmar-alteracao-papel',
        );
    });

    afterEach(() => {
        document.body.replaceChildren();
        vi.restoreAllMocks();
    });

    function submeter() {
        const evento = new SubmitEvent('submit', {
            bubbles: true,
            cancelable: true,
        });

        formulario.dispatchEvent(evento);

        return evento;
    }

    it('bloqueia a alteração sem confirmação', () => {
        papel.value = 'administrador';

        expect(submeter().defaultPrevented).toBe(true);
        expect(erroConfirmacao.textContent).toBe(
            'Confirma explicitamente a alteração do papel.',
        );
        expect(confirmacao.classList.contains('is-invalid')).toBe(true);
    });

    it('bloqueia a escolha do papel atual', () => {
        confirmacao.checked = true;

        expect(submeter().defaultPrevented).toBe(true);
        expect(erroPapel.textContent).toBe(
            'O utilizador já possui o papel selecionado.',
        );
        expect(papel.classList.contains('is-invalid')).toBe(true);
    });

    it('permite uma alteração válida', () => {
        papel.value = 'administrador';
        confirmacao.checked = true;

        expect(submeter().defaultPrevented).toBe(false);
        expect(erroPapel.textContent).toBe('');
        expect(erroConfirmacao.textContent).toBe('');
    });

    it('limpa os erros quando os campos são corrigidos', () => {
        expect(submeter().defaultPrevented).toBe(true);

        papel.value = 'administrador';
        papel.dispatchEvent(new Event('change', { bubbles: true }));
        confirmacao.checked = true;
        confirmacao.dispatchEvent(new Event('change', { bubbles: true }));

        expect(erroPapel.textContent).toBe('');
        expect(erroConfirmacao.textContent).toBe('');
        expect(submeter().defaultPrevented).toBe(false);
    });
});
