import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorImportacaoLancamentoDiscogs
    from '../../../resources/js/modulos/GestorImportacaoLancamentoDiscogs.js';
import GestorLigacoesSecao
    from '../../../resources/js/modulos/GestorLigacoesSecao.js';

describe('importação automática de ligações dos lançamentos Discogs', () => {
    let gestor;
    let seccao;
    let editor;
    let lista;

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="formulario-metal-thursday">
                <section class="item-seccao">
                    <select class="seletor-tipo-seccao">
                        <option selected value="1" data-identificador-tipo-seccao="lancamento">
                            Lançamento
                        </option>
                        <option value="2" data-identificador-tipo-seccao="texto">Texto</option>
                    </select>
                    <input type="hidden" name="seccoes[0][lancamento_id]">
                    <input data-campo-titulo-seccao>
                    <input data-ligacao-lancamento-discogs>
                    <div data-importacao-lancamento>
                        <button data-acao-importar-lancamento type="button">Preencher</button>
                        <p data-estado-importacao-lancamento></p>
                    </div>
                    <p data-lancamento-associado hidden></p>
                    <textarea name="seccoes[0][descricao]"></textarea>
                    <div data-lista-ligacoes-seccao></div>
                </section>
            </form>
        `;

        seccao = document.querySelector('.item-seccao');
        lista = seccao.querySelector('[data-lista-ligacoes-seccao]');
        gestor = Object.create(GestorImportacaoLancamentoDiscogs.prototype);
        gestor.formulario = document.querySelector('form');
        gestor.identificadorMetalThursday = null;
        gestor.urlImportacao = '/discogs/lancamentos/__IDENTIFICADOR_DISCOGS__';
        gestor.editorLancamentos = {
            dadosImportacaoValidos: vi.fn().mockReturnValue(true),
            carregarLancamento: vi.fn(),
        };

        editor = {
            obterLinhas: vi.fn(() => Array.from(lista.children)),
            adicionar: vi.fn(() => {
                const linha = document.createElement('div');
                linha.dataset.ligacaoSeccao = '';
                linha.innerHTML = `
                    <input data-campo-url-ligacao>
                    <input data-campo-etiqueta-ligacao>
                `;
                lista.append(linha);
                return linha;
            }),
        };

        GestorLigacoesSecao.instanciasPorSeccao.set(seccao, editor);
    });

    afterEach(() => {
        GestorLigacoesSecao.instanciasPorSeccao.delete(seccao);
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    const ligacoes = [
        { etiqueta: 'Discogs — edição', url: 'https://www.discogs.com/release/123' },
        { etiqueta: 'Apple Music — álbum', url: 'https://music.apple.com/pt/album/exemplo/123456' },
    ];

    it('adiciona automaticamente as ligações sem tocar na descrição', () => {
        const descricao = seccao.querySelector('textarea');
        descricao.value = 'Texto escrito pelo utilizador';

        const resultado = gestor.importarLigacoes(seccao, ligacoes);

        expect(resultado).toBe(2);
        expect(editor.adicionar).toHaveBeenCalledTimes(2);
        expect(Array.from(lista.querySelectorAll('[data-campo-url-ligacao]'))
            .map((campo) => campo.value)).toEqual(ligacoes.map((ligacao) => ligacao.url));
        expect(Array.from(lista.querySelectorAll('[data-campo-etiqueta-ligacao]'))
            .map((campo) => campo.value)).toEqual(ligacoes.map((ligacao) => ligacao.etiqueta));
        expect(descricao.value).toBe('Texto escrito pelo utilizador');
        expect(seccao.querySelector('[data-sugestoes-lancamento]')).toBeNull();
    });

    it('não repete ligações ao importar a mesma edição novamente', () => {
        expect(gestor.importarLigacoes(seccao, ligacoes)).toBe(2);
        expect(gestor.importarLigacoes(seccao, ligacoes)).toBe(0);
        expect(editor.adicionar).toHaveBeenCalledTimes(2);
    });

    it('preserva ligações manuais, incluindo URL equivalente normalizado', () => {
        const linha = editor.adicionar();
        linha.querySelector('[data-campo-url-ligacao]').value = 'https://www.discogs.com/release/123';
        linha.querySelector('[data-campo-etiqueta-ligacao]').value = 'Minha referência';
        editor.adicionar.mockClear();

        expect(gestor.importarLigacoes(seccao, [
            { etiqueta: 'Repetida', url: 'https://www.discogs.com/release/123' },
            ...ligacoes,
        ])).toBe(1);
        expect(editor.adicionar).toHaveBeenCalledTimes(1);
        expect(linha.querySelector('[data-campo-etiqueta-ligacao]').value)
            .toBe('Minha referência');
    });

    it('ignora URLs inseguros e linhas incompletas', () => {
        const importadas = gestor.importarLigacoes(seccao, [
            { etiqueta: 'Script', url: 'javascript:alert(1)' },
            { etiqueta: 'Credenciais', url: 'https://a:b@exemplo.pt/album' },
            { etiqueta: 'Sem etiqueta', url: 'https://exemplo.pt/album' },
            { etiqueta: 'Não é URL', url: 'invalida' },
            { etiqueta: 'Legítima', url: 'https://exemplo.pt/album' },
            { etiqueta: 'Repetição', url: 'https://exemplo.pt/album' },
        ]);
        expect(importadas).toBe(1);
        expect(editor.adicionar).toHaveBeenCalledTimes(1);
    });

    it('respeita o limite do editor e mantém todas as ligações existentes', () => {
        const originalAdicionar = editor.adicionar;
        editor.adicionar = vi.fn(() => {
            if (lista.children.length >= 1) {
                return null;
            }
            return originalAdicionar();
        });

        expect(gestor.importarLigacoes(seccao, ligacoes)).toBe(1);
        expect(lista.children).toHaveLength(1);
    });

    it('ignora respostas antigas e secções não compatíveis', () => {
        expect(gestor.importarLigacoes(seccao, null)).toBe(0);
        seccao.querySelector('select').value = '2';
        expect(gestor.importarLigacoes(seccao, ligacoes)).toBe(0);
        expect(editor.adicionar).not.toHaveBeenCalled();
    });

    it('importa as ligações na própria resposta de Preencher, sem cliques adicionais', async () => {
        const lancamento = {
            id: 8,
            discogs_release_id: 123,
            titulo: 'Álbum de exemplo',
            tipo: 'Album',
            ano_original: 2024,
            faixas: [],
        };
        vi.spyOn(axios, 'post').mockResolvedValue({
            data: {
                lancamento,
                sugestoes: {
                    descricao: 'Texto externo que deve ser ignorado.',
                    ligacoes,
                },
            },
        });
        seccao.querySelector('textarea').value = 'Descrição manual';
        seccao.querySelector('[data-ligacao-lancamento-discogs]').value =
            'https://www.discogs.com/release/123';

        await gestor.importarPorLigacao(seccao);

        expect(editor.adicionar).toHaveBeenCalledTimes(2);
        expect(seccao.querySelector('textarea').value).toBe('Descrição manual');
        expect(seccao.querySelector('[data-sugestoes-lancamento]')).toBeNull();
    });
});
