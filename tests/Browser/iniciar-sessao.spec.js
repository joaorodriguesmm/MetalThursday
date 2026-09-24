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
