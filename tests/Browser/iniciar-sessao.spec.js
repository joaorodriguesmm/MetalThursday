import AxeBuilder from '@axe-core/playwright';
import {
    expect,
    test,
} from '@playwright/test';

/**
 * Confirma que a página pública de autenticação é apresentada num browser
 * real e contém os controlos fundamentais do formulário.
 *
 * @since 2.0.0
 */
test(
    'apresenta a página de início de sessão',
    async ({
        page,
    }) => {
        const resposta =
            await page.goto(
                '/entrar',
            );

        expect(
            resposta,
        ).not.toBeNull();

        expect(
            resposta.ok(),
        ).toBe(
            true,
        );

        await expect(
            page.getByRole(
                'heading',
                {
                    name: 'Iniciar sessão',
                    level: 1,
                },
            ),
        ).toBeVisible();

        await expect(
            page.getByLabel(
                'E-mail *',
                {
                    exact: true,
                },
            ),
        ).toBeVisible();

        await expect(
            page.getByLabel(
                'Palavra-passe *',
                {
                    exact: true,
                },
            ),
        ).toBeVisible();

        await expect(
            page.getByRole(
                'button',
                {
                    name: 'Iniciar sessão',
                },
            ),
        ).toBeVisible();
    },
);

/**
 * Confirma que a página de início de sessão não apresenta violações
 * automáticas de acessibilidade detetadas pelo axe-core.
 *
 * @since 2.0.0
 */
test(
    'não apresenta violações automáticas de acessibilidade',
    async ({
        page,
    }) => {
        await page.goto(
            '/entrar',
        );

        const resultados =
            await new AxeBuilder({
                page,
            }).analyze();

        expect(
            resultados.violations,
        ).toEqual(
            [],
        );
    },
);

/**
 * Confirma num browser real o ciclo completo de uma tentativa de autenticação
 * inválida, incluindo o redirecionamento, a mensagem genérica e os valores
 * repostos pelo servidor.
 *
 * @since 2.0.0
 */
test(
    'rejeita credenciais invalidas sem expor a palavra-passe',
    async ({
        page,
    }) => {
        const campoEmail =
            page.getByLabel(
                'E-mail *',
                {
                    exact: true,
                },
            );

        const campoPalavraPasse =
            page.getByLabel(
                'Palavra-passe *',
                {
                    exact: true,
                },
            );

        await page.goto(
            '/entrar',
        );

        await campoEmail.fill(
            'utilizador-inexistente@example.com',
        );

        await campoPalavraPasse.fill(
            'PalavraPasseIncorreta!123',
        );

        await page.getByRole(
            'button',
            {
                name: 'Iniciar sessão',
            },
        ).click();

        await expect(
            page,
        ).toHaveURL(
            /\/entrar$/u,
        );

        await expect(
            page.getByText(
                'As credenciais indicadas estão incorretas.',
                {
                    exact: true,
                },
            ),
        ).toBeVisible();

        await expect(
            campoEmail,
        ).toHaveValue(
            'utilizador-inexistente@example.com',
        );

        await expect(
            campoPalavraPasse,
        ).toHaveValue('');
    },
);
