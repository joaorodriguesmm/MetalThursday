import { beforeEach, describe, expect, it, vi } from 'vitest';
import GestorResumoValidacaoMetalThursday
    from '../../../resources/js/modulos/GestorResumoValidacaoMetalThursday.js';

describe('A09.22 — Resumo de validação MetalThursday', () => {
    let formulario;
    let resumo;

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="formulario-editar-metal-thursday">
                <div class="grupo-campo-formulario">
                    <label for="nome">Nome</label>
                    <input id="nome" name="nome" value="Original">
                    <div id="erro-nome" class="invalid-feedback"></div>
                </div>
                <div id="contentor-seccoes">
                    <section class="item-seccao">
                        <button type="button" class="botao-alternar-seccao"
                            aria-expanded="false">Expandir</button>
                        <div data-conteudo-seccao hidden>
                            <div class="grupo-campo-formulario">
                                <label for="descricao">Descrição</label>
                                <textarea id="descricao" name="seccoes[0][descricao]">Texto inicial</textarea>
                                <div id="erro-descricao" class="invalid-feedback"></div>
                            </div>
                        </div>
                    </section>
                </div>
                <div id="erro-seccoes" class="invalid-feedback"></div>
                <button type="button" id="botao-adicionar-seccao">Adicionar secção</button>
                <div data-resumo-validacao-metal-thursday hidden>
                    <p data-contador-erros-metal-thursday></p>
                    <ul data-lista-erros-metal-thursday></ul>
                </div>
            </form>
        `;
        formulario = document.querySelector('form');
        resumo = formulario.querySelector('[data-resumo-validacao-metal-thursday]');
    });

    function rejeitar(campo, mensagem) {
        campo.setAttribute('aria-invalid', 'true');
        campo.classList.add('is-invalid');
        document.getElementById(`erro-${campo.id}`).textContent = mensagem;
    }

    function submeter() {
        formulario.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    }

    it('apresenta erros existentes, com a secção correta, apenas após submeter', () => {
        const descricao = formulario.querySelector('#descricao');
        rejeitar(descricao, 'Por favor, insere a descrição.');
        new GestorResumoValidacaoMetalThursday(formulario);
        expect(resumo.hidden).toBe(true);
        submeter();

        expect(resumo.hidden).toBe(false);
        expect(resumo.textContent).toContain('Secção 1 — Descrição');
        expect(resumo.textContent).toContain('Por favor, insere a descrição.');
        expect(resumo.querySelector('button').classList.contains('resumo-validacao-metal-thursday__ligacao')).toBe(true);
        expect(resumo.querySelector('li').classList.contains('resumo-validacao-metal-thursday__item')).toBe(true);
        expect(resumo.querySelector('li span').classList.contains('resumo-validacao-metal-thursday__mensagem')).toBe(true);
        expect(formulario.querySelector('#descricao').value).toBe('Texto inicial');
    });

    it('expande a secção e foca o campo indicado sem alterar o conteúdo', () => {
        const alternador = formulario.querySelector('.botao-alternar-seccao');
        alternador.addEventListener('click', () => {
            alternador.setAttribute('aria-expanded', 'true');
            formulario.querySelector('[data-conteudo-seccao]').hidden = false;
        });
        const descricao = formulario.querySelector('#descricao');
        rejeitar(descricao, 'Descrição inválida');
        new GestorResumoValidacaoMetalThursday(formulario);
        submeter();

        resumo.querySelector('[data-ir-para-erro-metal-thursday]').click();
        expect(alternador.getAttribute('aria-expanded')).toBe('true');
        expect(document.activeElement).toBe(descricao);
        expect(descricao.value).toBe('Texto inicial');
    });

    it('atualiza o resumo quando os erros são corrigidos e repõe no reset', () => {
        const nome = formulario.querySelector('#nome');
        rejeitar(nome, 'Nome inválido');
        new GestorResumoValidacaoMetalThursday(formulario);
        submeter();
        expect(resumo.hidden).toBe(false);

        nome.removeAttribute('aria-invalid');
        nome.dispatchEvent(new Event('input', { bubbles: true }));
        expect(resumo.hidden).toBe(true);

        rejeitar(nome, 'Outro erro');
        nome.dispatchEvent(new Event('change', { bubbles: true }));
        expect(resumo.hidden).toBe(false);
        formulario.dispatchEvent(new Event('reset'));
        expect(resumo.hidden).toBe(true);
    });

    it('não duplica eventos e nunca interpreta mensagens como HTML', () => {
        const instancia = new GestorResumoValidacaoMetalThursday(formulario);
        expect(new GestorResumoValidacaoMetalThursday(formulario)).toBe(instancia);
        rejeitar(formulario.querySelector('#nome'), '<img src=x onerror=alert(1)>');
        submeter();
        expect(resumo.querySelectorAll('[data-ir-para-erro-metal-thursday]')).toHaveLength(1);
        expect(resumo.querySelector('img')).toBeNull();
        expect(resumo.textContent).toContain('<img src=x onerror=alert(1)>');
    });

    it('respeita rascunhos sem validação e apresenta erros gerais de secções', () => {
        new GestorResumoValidacaoMetalThursday(formulario);
        formulario.querySelector('#erro-seccoes').textContent = 'Adiciona uma secção.';
        const rascunho = document.createElement('button');
        rascunho.type = 'submit';
        rascunho.setAttribute('formnovalidate', '');
        formulario.append(rascunho);

        const evento = new SubmitEvent('submit', {
            bubbles: true,
            cancelable: true,
            submitter: rascunho,
        });
        formulario.dispatchEvent(evento);
        expect(resumo.hidden).toBe(true);

        submeter();
        expect(resumo.textContent).toContain('Adiciona uma secção.');
        const foco = vi.spyOn(formulario.querySelector('#botao-adicionar-seccao'), 'focus');
        resumo.querySelector('button').click();
        expect(foco).toHaveBeenCalled();
    });

    it('ignora formulários de integração sem resumo de validação', () => {
        resumo.remove();
        expect(() => new GestorResumoValidacaoMetalThursday(formulario)).not.toThrow();
    });
});
