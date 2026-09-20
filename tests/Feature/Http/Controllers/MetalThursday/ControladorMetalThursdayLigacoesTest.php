<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o contrato HTTP das ligações múltiplas das secções musicais.
 *
 * @since 2.0.0
 */
final class ControladorMetalThursdayLigacoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara os testes sem depender dos ficheiros produzidos pelo Vite.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Notification::fake();
    }

    /**
     * Confirma que uma secção de música aceita várias ligações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function publica_musica_com_varias_ligacoes(): void
    {
        [
            $administrador,
            $proximoNomeado,
            $tipoSeccao,
            $artista,
        ] = $this->prepararContexto();

        $resposta = $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                $this->dadosPublicacao(
                    $administrador,
                    $proximoNomeado,
                    $tipoSeccao,
                    $artista,
                    [
                        [
                            'url' => 'https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC',
                            'etiqueta' => null,
                            'incorporar' => true,
                        ],
                        [
                            'url' => 'https://www.metal-archives.com/bands/Metallica/125',
                            'etiqueta' => 'Metal Archives',
                            'incorporar' => false,
                        ],
                    ],
                ),
            );

        $resposta->assertCreated();

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'tipo_seccao_id' => $tipoSeccao->getKey(),
                'artista_id' => $artista->getKey(),
                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'ligacoes_seccao_metal_thursday',
            [
                'plataforma' => 'spotify',
                'url' => 'https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC',
                'incorporar' => true,
                'ordem' => 1,
            ],
        );

        $this->assertDatabaseHas(
            'ligacoes_seccao_metal_thursday',
            [
                'plataforma' => 'outro',
                'etiqueta' => 'Metal Archives',
                'url' => 'https://www.metal-archives.com/bands/Metallica/125',
                'incorporar' => false,
                'ordem' => 2,
            ],
        );

        $this->assertDatabaseCount(
            'ligacoes_seccao_metal_thursday',
            2,
        );
    }

    /**
     * Confirma que uma secção musical final pode não possuir ligações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function publica_musica_sem_ligacoes(): void
    {
        [
            $administrador,
            $proximoNomeado,
            $tipoSeccao,
            $artista,
        ] = $this->prepararContexto();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                $this->dadosPublicacao(
                    $administrador,
                    $proximoNomeado,
                    $tipoSeccao,
                    $artista,
                    [],
                ),
            )
            ->assertCreated();

        $this->assertDatabaseCount(
            'ligacoes_seccao_metal_thursday',
            0,
        );
    }

    /**
     * Confirma que uma ligação personalizada final exige uma etiqueta.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_ligacao_personalizada_sem_etiqueta(): void
    {
        [
            $administrador,
            $proximoNomeado,
            $tipoSeccao,
            $artista,
        ] = $this->prepararContexto();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                $this->dadosPublicacao(
                    $administrador,
                    $proximoNomeado,
                    $tipoSeccao,
                    $artista,
                    [
                        [
                            'url' => 'https://example.com/mais-informacoes',
                            'etiqueta' => null,
                            'incorporar' => false,
                        ],
                    ],
                ),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'seccoes.0.ligacoes.0.etiqueta',
            ]);

        $this->assertDatabaseCount(
            'ligacoes_seccao_metal_thursday',
            0,
        );
    }

    /**
     * Cria as entidades comuns aos testes.
     *
     * @return array{0: Utilizador, 1: Utilizador, 2: TipoSeccao, 3: Artista}
     *
     * @since 2.0.0
     */
    private function prepararContexto(): array
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $proximoNomeado = Utilizador::factory()
            ->create();

        Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-03-01',
                ),
                CarbonImmutable::parse(
                    '2026-03-31',
                ),
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->comDados(
                'musica',
                'Música',
                'Música recomendada.',
            )
            ->comDetalhes()
            ->create();

        $artista = Artista::factory()
            ->create();

        return [
            $administrador,
            $proximoNomeado,
            $tipoSeccao,
            $artista,
        ];
    }

    /**
     * Cria o pedido base de publicação com uma secção de música.
     *
     * @param  list<array{url: string, etiqueta: string|null, incorporar: bool}>  $ligacoes
     * @return array<string, mixed>
     *
     * @since 2.0.0
     */
    private function dadosPublicacao(
        Utilizador $administrador,
        Utilizador $proximoNomeado,
        TipoSeccao $tipoSeccao,
        Artista $artista,
        array $ligacoes,
    ): array {
        return [
            'data' => '2026-03-12',
            'nome' => null,
            'autor_id' => $administrador->getKey(),
            'proximo_nomeado_id' => $proximoNomeado->getKey(),
            'seccoes' => [
                [
                    'id' => null,
                    'tipo_seccao_id' => $tipoSeccao->getKey(),
                    'titulo' => 'Faixa da semana',
                    'descricao' => 'Descrição da faixa selecionada.',
                    'artista_id' => $artista->getKey(),
                    'lancamento_id' => null,
                    'lancamento' => null,
                    'ligacoes' => $ligacoes,
                    'ano' => 2026,
                ],
            ],
        ];
    }
}
