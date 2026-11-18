<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components\MetalThursday;

use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\View\Components\MetalThursday\ItemSeccaoFormulario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a preparação das ligações múltiplas pelo componente de uma secção.
 *
 * @since 2.0.0
 */
final class ItemSeccaoFormularioLigacoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que as ligações persistidas são disponibilizadas pela ordem
     * definida na relação da secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function prepara_ligacoes_persistidas_para_o_formulario(): void
    {
        $artista = Artista::factory()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->comDetalhes(
                $artista,
            )
            ->create();

        $seccao
            ->ligacoes()
            ->create([
                'url' => 'https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC',
                'etiqueta' => null,
                'incorporar' => true,
                'ordem' => 2,
            ]);

        $seccao
            ->ligacoes()
            ->create([
                'url' => 'https://bandcamp.com/album/exemplo',
                'etiqueta' => 'Comprar',
                'incorporar' => false,
                'ordem' => 1,
            ]);

        $componente = $this->criarComponente(
            $seccao->fresh(),
        );

        self::assertSame(
            [
                [
                    'url' => 'https://bandcamp.com/album/exemplo',
                    'etiqueta' => 'Comprar',
                    'incorporar' => false,
                ],
                [
                    'url' => 'https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC',
                    'etiqueta' => '',
                    'incorporar' => true,
                ],
            ],
            $componente->ligacoes,
        );
    }

    /**
     * Confirma que os dados incompletos de um rascunho continuam disponíveis
     * para renderização e mantêm a ordem recebida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function prepara_ligacoes_incompletas_de_rascunho(): void
    {
        $componente = $this->criarComponente([
            'ligacoes' => [
                [
                    'url' => 'https://',
                    'etiqueta' => 'Por completar',
                    'incorporar' => true,
                ],
                [
                    'url' => '',
                    'etiqueta' => 'Mais tarde',
                    'incorporar' => false,
                ],
            ],
        ]);

        self::assertSame(
            [
                [
                    'url' => 'https://',
                    'etiqueta' => 'Por completar',
                    'incorporar' => true,
                ],
                [
                    'url' => '',
                    'etiqueta' => 'Mais tarde',
                    'incorporar' => false,
                ],
            ],
            $componente->ligacoes,
        );
    }

    /**
     * Cria o componente com uma sessão Laravel válida.
     *
     * @param  SeccaoMetalThursday|array<string, mixed>|null  $seccao
     * @return ItemSeccaoFormulario Componente preparado.
     *
     * @since 2.0.0
     */
    private function criarComponente(
        SeccaoMetalThursday|array|null $seccao,
    ): ItemSeccaoFormulario {
        $pedido = Request::create(
            '/',
        );

        $pedido->setLaravelSession(
            $this->app['session.store'],
        );

        return new ItemSeccaoFormulario(
            $pedido,
            0,
            TipoSeccao::query()->get(),
            Artista::query()->get(),
            $seccao,
        );
    }
}
