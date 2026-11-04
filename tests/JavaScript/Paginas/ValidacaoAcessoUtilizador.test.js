import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

function submeter(formulario) {
    const evento = new SubmitEvent('submit', {
        bubbles: true,
        cancelable: true,
    });

    formulario.dispatchEvent(evento);

    return evento.defaultPrevented;
}

async function iniciarPagina() {
    await import(
        '../../../resources/js/paginas/utilizadores.js'
    );

    document.dispatchEvent(new Event('DOMContentLoaded'));
}

describe('A08 — validacao administrativa do acesso', () => {
    beforeEach(() => {
        vi.resetModules();

        document.body.innerHTML = `
            <form id="formulario-reativar-acesso" novalidate>
                <input
                    id="confirmar-reativacao"
                    type="checkbox"
                    name="confirmar_reativacao"
                    value="1"
                    aria-describedby="erro-confirmar-reativacao"
                >
                <div
                    id="erro-confirmar-reativacao"
                    class="invalid-feedback"
                ></div>
                <button type="submit">Reativar</button>
            </form>

            <form id="formulario-suspender-acesso" novalidate>
                <textarea
                    id="motivo-suspensao"
                    name="motivo"
                    maxlength="1000"
                    aria-describedby="erro-motivo-suspensao"
                ></textarea>
                <div
                    id="erro-motivo-suspensao"
                    class="invalid-feedback"
                ></div>
                <button type="submit">Suspender</button>
            </form>
        `;
    });

    afterEach(() => {
        document.body.replaceChildren();
    });

    it('bloqueia a reativacao sem confirmacao', async () => {
        await iniciarPagina();

        const formulario = document.querySelector(
            '#formulario-reativar-acesso'
        );

        expect(submeter(formulario)).toBe(true);

        expect(document.querySelector(
            '#erro-confirmar-reativacao'
        ).textContent).toBe(
            'Confirma explicitamente a reativação do acesso.'
        );
    });

    it('permite reativar apos confirmar', async () => {
        await iniciarPagina();

        const formulario = document.querySelector(
            '#formulario-reativar-acesso'
        );
        const campo = document.querySelector(
            '#confirmar-reativacao'
        );

        expect(submeter(formulario)).toBe(true);

        campo.checked = true;
        campo.dispatchEvent(new Event('change', {
            bubbles: true,
        }));

        expect(document.querySelector(
            '#erro-confirmar-reativacao'
        ).textContent).toBe('');

        expect(submeter(formulario)).toBe(false);
    });

    it('rejeita motivos vazios e aceita motivo valido', async () => {
        await iniciarPagina();

        const formulario = document.querySelector(
            '#formulario-suspender-acesso'
        );
        const campo = document.querySelector(
            '#motivo-suspensao'
        );
        const erro = document.querySelector(
            '#erro-motivo-suspensao'
        );

        for (const valor of ['', '   ']) {
            campo.value = valor;

            expect(submeter(formulario)).toBe(true);
            expect(erro.textContent).toBe(
                'Por favor, indica o motivo da suspensão.'
            );
        }

        campo.value = 'Suspensão temporária de teste';
        campo.dispatchEvent(new Event('input', {
            bubbles: true,
        }));

        expect(erro.textContent).toBe('');
        expect(submeter(formulario)).toBe(false);
    });

    it('rejeita caracteres proibidos e motivos longos', async () => {
        await iniciarPagina();

        const formulario = document.querySelector(
            '#formulario-suspender-acesso'
        );
        const campo = document.querySelector(
            '#motivo-suspensao'
        );
        const erro = document.querySelector(
            '#erro-motivo-suspensao'
        );

        campo.value = 'Motivo\u0001invalido';

        expect(submeter(formulario)).toBe(true);
        expect(erro.textContent).toBe(
            'O motivo da suspensão contém caracteres inválidos.'
        );

        campo.value = 'a'.repeat(1001);

        expect(submeter(formulario)).toBe(true);
        expect(erro.textContent).toContain(
            'não pode ter mais de 1000 caracteres'
        );
    });
});

describe('A08 — encerramento administrativo de sessoes', () => {
    beforeEach(() => {
        vi.resetModules();

        document.body.innerHTML = `
            <form id="formulario-encerrar-sessoes" novalidate>
                <input
                    id="confirmar-encerramento-sessoes"
                    type="checkbox"
                    name="confirmar_encerramento_sessoes"
                    value="1"
                    aria-describedby="erro-confirmar-encerramento-sessoes"
                >
                <div
                    id="erro-confirmar-encerramento-sessoes"
                    class="invalid-feedback"
                ></div>
                <button type="submit">Encerrar sessões</button>
            </form>
        `;
    });

    afterEach(() => {
        document.body.replaceChildren();
    });

    it('bloqueia o encerramento sem confirmacao', async () => {
        await iniciarPagina();

        const formulario = document.querySelector(
            '#formulario-encerrar-sessoes',
        );

        expect(submeter(formulario)).toBe(true);

        const erro = document.getElementById(
            'erro-confirmar-encerramento-sessoes',
        );

        expect(erro.textContent).toBe(
            'Confirma explicitamente o encerramento das sessões.',
        );
    });

    it('permite encerrar apos confirmar', async () => {
        await iniciarPagina();

        const formulario = document.querySelector(
            '#formulario-encerrar-sessoes',
        );

        const campo = document.getElementById(
            'confirmar-encerramento-sessoes',
        );

        const erro = document.getElementById(
            'erro-confirmar-encerramento-sessoes',
        );

        expect(submeter(formulario)).toBe(true);

        campo.checked = true;
        campo.dispatchEvent(new Event('change', {
            bubbles: true,
        }));

        expect(erro.textContent).toBe('');
        expect(submeter(formulario)).toBe(false);
    });
});
