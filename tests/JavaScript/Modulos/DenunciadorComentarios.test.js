import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorAlertas from '../../../resources/js/modulos/GestorAlertas.js';
import DenunciadorComentarios from '../../../resources/js/modulos/DenunciadorComentarios.js';

vi.mock('../../../resources/js/modulos/ClienteHttp.js', () => ({
    default: { post: vi.fn() },
}));

vi.mock('../../../resources/js/modulos/GestorAlertas.js', () => ({
    default: {
        mostrarSucesso: vi.fn(),
        mostrarErro: vi.fn(),
    },
}));

describe('DenunciadorComentarios', () => {
    let raiz;

    beforeEach(() => {
        vi.clearAllMocks();

        raiz = document.createElement('main');
        raiz.innerHTML = `
            <article class="comentario">
                <button data-abrir-denuncia-comentario
                        aria-controls="denuncia-1" aria-expanded="false">Denunciar</button>
                <div id="denuncia-1" data-contentor-denuncia-comentario hidden>
                    <form data-formulario-denuncia-comentario action="/comentarios/1/denuncias" novalidate>
                        <input type="hidden" name="_token" value="token-teste">
                        <div class="grupo-campo-formulario" data-grupo-campo>
                            <label for="motivo-denuncia-teste">Motivo</label>
                            <select id="motivo-denuncia-teste" name="motivo"
                                    aria-describedby="erro-motivo-denuncia-teste" required>
                                <option value="">Selecionar</option>
                                <option value="spam">Spam</option>
                                <option value="outro">Outro</option>
                            </select>
                            <div class="invalid-feedback" id="erro-motivo-denuncia-teste"></div>
                        </div>
                        <div class="grupo-campo-formulario" data-grupo-campo>
                            <label for="detalhes-denuncia-teste">Detalhes</label>
                            <textarea id="detalhes-denuncia-teste" name="detalhes"
                                      aria-describedby="erro-detalhes-denuncia-teste"></textarea>
                            <div class="invalid-feedback" id="erro-detalhes-denuncia-teste"></div>
                        </div>
                        <button type="submit">Enviar</button>
                    </form>
                </div>
                <div class="d-none" role="status" data-confirmacao-denuncia-comentario>
                    Denúncia enviada com sucesso.
                </div>
            </article>
        `;

        document.body.append(raiz);
        new DenunciadorComentarios(raiz);
    });

    afterEach(() => {
        raiz.remove();
    });

    it('abre o formulário e exige detalhes para outro motivo', () => {
        raiz.querySelector('[data-abrir-denuncia-comentario]').click();

        expect(raiz.querySelector('[data-contentor-denuncia-comentario]').hidden)
            .toBe(false);
        expect(raiz.querySelector('[data-abrir-denuncia-comentario]')
            .getAttribute('aria-expanded')).toBe('true');

        const motivo = raiz.querySelector('select[name="motivo"]');
        motivo.value = 'outro';
        motivo.dispatchEvent(new Event('change', { bubbles: true }));

        expect(raiz.querySelector('textarea[name="detalhes"]').required).toBe(true);
    });

    it('valida o motivo em linha sem usar mensagens nativas do navegador', () => {
        raiz.querySelector('[data-abrir-denuncia-comentario]').click();
        const formulario = raiz.querySelector('form');
        const motivo = raiz.querySelector('select[name="motivo"]');

        formulario.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

        expect(axios.post).not.toHaveBeenCalled();
        expect(motivo.classList.contains('is-invalid')).toBe(true);
        expect(motivo.getAttribute('aria-invalid')).toBe('true');
        expect(raiz.querySelector('#erro-motivo-denuncia-teste').textContent)
            .toBe('Seleciona o motivo da denúncia.');

        motivo.value = 'spam';
        motivo.dispatchEvent(new Event('change', { bubbles: true }));

        expect(motivo.classList.contains('is-invalid')).toBe(false);
        expect(raiz.querySelector('#erro-motivo-denuncia-teste').textContent).toBe('');
    });

    it('exige detalhes para Outro e limpa o erro ao escrever', () => {
        raiz.querySelector('[data-abrir-denuncia-comentario]').click();
        const formulario = raiz.querySelector('form');
        const motivo = raiz.querySelector('select[name="motivo"]');
        const detalhes = raiz.querySelector('textarea[name="detalhes"]');

        motivo.value = 'outro';
        motivo.dispatchEvent(new Event('change', { bubbles: true }));
        formulario.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

        expect(axios.post).not.toHaveBeenCalled();
        expect(detalhes.classList.contains('is-invalid')).toBe(true);
        expect(raiz.querySelector('#erro-detalhes-denuncia-teste').textContent)
            .toBe('Indica os detalhes da denúncia.');

        detalhes.value = 'Explicação do problema';
        detalhes.dispatchEvent(new Event('input', { bubbles: true }));

        expect(detalhes.classList.contains('is-invalid')).toBe(false);
        expect(raiz.querySelector('#erro-detalhes-denuncia-teste').textContent).toBe('');
    });

    it('limita o comprimento dos detalhes com mensagem personalizada', () => {
        raiz.querySelector('[data-abrir-denuncia-comentario]').click();
        const formulario = raiz.querySelector('form');
        const motivo = raiz.querySelector('select[name="motivo"]');
        const detalhes = raiz.querySelector('textarea[name="detalhes"]');

        motivo.value = 'spam';
        detalhes.value = 'A'.repeat(1001);
        formulario.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

        expect(axios.post).not.toHaveBeenCalled();
        expect(raiz.querySelector('#erro-detalhes-denuncia-teste').textContent)
            .toBe('Os detalhes não podem ultrapassar 1000 caracteres.');
    });

    it('envia uma vez e desativa novas denúncias após sucesso', async () => {
        axios.post.mockResolvedValue({ data: { nova: true, mensagem: 'Denúncia registada.' } });
        raiz.querySelector('[data-abrir-denuncia-comentario]').click();
        raiz.querySelector('select[name="motivo"]').value = 'spam';
        const formulario = raiz.querySelector('form');

        formulario.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        formulario.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

        await vi.waitFor(() => expect(axios.post).toHaveBeenCalledTimes(1));
        await vi.waitFor(() => expect(raiz.querySelector('[data-denuncia-ja-enviada]')).not.toBeNull());
        expect(raiz.querySelector('[data-abrir-denuncia-comentario]')).toBeNull();
        expect(raiz.querySelector('[data-denuncia-ja-enviada]').textContent)
            .toBe('Denúncia enviada');

        expect(axios.post).toHaveBeenCalledWith(
            new URL('/comentarios/1/denuncias', document.baseURI).href,
            { _token: 'token-teste', motivo: 'spam', detalhes: '' },
        );
        expect(GestorAlertas.mostrarSucesso).toHaveBeenCalled();
        expect(raiz.querySelector('[data-confirmacao-denuncia-comentario]')
            .classList.contains('d-none')).toBe(false);
    });

    it('não apresenta sucesso quando a denúncia já existia', async () => {
        raiz.querySelector('[data-abrir-denuncia-comentario]').click();
        axios.post.mockResolvedValue({
            data: { nova: false, mensagem: 'Este comentário já foi denunciado por ti.' },
        });
        raiz.querySelector('select[name="motivo"]').value = 'spam';
        raiz.querySelector('form').dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true,
        }));

        await vi.waitFor(() => expect(raiz.querySelector('[data-denuncia-ja-enviada]')).not.toBeNull());

        expect(raiz.querySelector('[data-denuncia-ja-enviada]').textContent)
            .toBe('Já denunciaste este comentário');
        expect(raiz.querySelector('[data-abrir-denuncia-comentario]')).toBeNull();
        expect(raiz.querySelector('[data-confirmacao-denuncia-comentario]')
            .classList.contains('d-none')).toBe(true);
        expect(GestorAlertas.mostrarSucesso).not.toHaveBeenCalled();
    });

    it('apresenta erros de validação do servidor junto dos campos', async () => {
        raiz.querySelector('[data-abrir-denuncia-comentario]').click();
        axios.post.mockRejectedValue({
            response: {
                status: 422,
                data: { errors: { motivo: ['Motivo recusado pelo servidor.'] } },
            },
        });

        raiz.querySelector('select[name="motivo"]').value = 'spam';
        raiz.querySelector('form').dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true,
        }));

        await vi.waitFor(() => expect(raiz.querySelector('#erro-motivo-denuncia-teste')
            .textContent).toBe('Motivo recusado pelo servidor.'));
        expect(GestorAlertas.mostrarErro).not.toHaveBeenCalled();
        expect(raiz.querySelector('button[type="submit"]').disabled).toBe(false);
    });

    it('permite repetir depois de falhar o envio', async () => {
        raiz.querySelector('[data-abrir-denuncia-comentario]').click();
        axios.post.mockRejectedValue(new Error('Sem ligação'));
        raiz.querySelector('select[name="motivo"]').value = 'spam';
        const formulario = raiz.querySelector('form');

        formulario.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

        await vi.waitFor(() => expect(GestorAlertas.mostrarErro).toHaveBeenCalled());
        expect(raiz.querySelector('button[type="submit"]').disabled).toBe(false);
        expect(raiz.querySelector('[data-confirmacao-denuncia-comentario]')
            .classList.contains('d-none')).toBe(true);
    });
});
