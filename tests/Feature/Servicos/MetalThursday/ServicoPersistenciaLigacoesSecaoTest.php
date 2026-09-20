<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Enumeracoes\PlataformaLigacao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Servicos\MetalThursday\ServicoPersistenciaMetalThursday;
use App\Servicos\MetalThursday\ServicoReservasMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o cutover da persistência das ligações de secções MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoPersistenciaLigacoesSecaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que várias ligações são persistidas pela ordem recebida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_seccao_com_varias_ligacoes(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $tipoMusica = TipoSeccao::factory()
            ->comDados(
                'musica',
                'Música',
                'Música escolhida para a MetalThursday.',
            )
            ->comDetalhes()
            ->naOrdem(1)
            ->create();

        $artista = Artista::factory()
            ->create();

        $metalThursday = $this
            ->servico()
            ->criar([
                'edicao_id' => (int) $edicao->getKey(),
                'data' => '2026-01-08',
                'nome' => null,
                'autor_id' => (int) $utilizador->getKey(),
                'proximo_nomeado_id' => (int) $utilizador->getKey(),
                'seccoes' => [
                    [
                        'id' => null,
                        'tipo_seccao_id' => (int) $tipoMusica->getKey(),
                        'titulo' => 'Faixa da semana',
                        'descricao' => 'Descrição válida.',
                        'artista_id' => (int) $artista->getKey(),
                        'lancamento_id' => null,
                        'lancamento' => null,
                        'ano' => 2026,
                        'ligacoes' => [
                            [
                                'url' => 'https://open.spotify.com/track/abc123',
                                'etiqueta' => null,
                                'incorporar' => true,
                            ],
                            [
                                'url' => 'https://example.com/critica',
                                'etiqueta' => 'Ler crítica',
                                'incorporar' => true,
                            ],
                            [
                                'url' => 'https://youtu.be/abc123',
                                'etiqueta' => null,
                                'incorporar' => false,
                            ],
                        ],
                    ],
                ],
            ]);

        $seccao = $metalThursday->seccoes->first();

        self::assertNotNull(
            $seccao,
        );

        self::assertSame(
            [
                'https://open.spotify.com/track/abc123',
                'https://example.com/critica',
                'https://youtu.be/abc123',
            ],
            $seccao
                ->ligacoes
                ->pluck(
                    'url',
                )
                ->all(),
        );

        $this->assertDatabaseHas(
            'ligacoes_seccao_metal_thursday',
            [
                'seccao_metal_thursday_id' => $seccao->getKey(),
                'plataforma' => PlataformaLigacao::Spotify->value,
                'url' => 'https://open.spotify.com/track/abc123',
                'incorporar' => 1,
                'ordem' => 1,
            ],
        );

        $this->assertDatabaseHas(
            'ligacoes_seccao_metal_thursday',
            [
                'seccao_metal_thursday_id' => $seccao->getKey(),
                'plataforma' => PlataformaLigacao::Outro->value,
                'etiqueta' => 'Ler crítica',
                'url' => 'https://example.com/critica',
                'incorporar' => 0,
                'ordem' => 2,
            ],
        );

        $this->assertDatabaseCount(
            'ligacoes_seccao_metal_thursday',
            3,
        );
    }

    /**
     * Confirma que uma secção musical válida pode ser publicada sem ligações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_seccao_musical_sem_ligacoes(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $tipoMusica = TipoSeccao::factory()
            ->comDados(
                'musica',
                'Música',
                'Música escolhida para a MetalThursday.',
            )
            ->comDetalhes()
            ->naOrdem(1)
            ->create();

        $artista = Artista::factory()
            ->create();

        $metalThursday = $this
            ->servico()
            ->criar([
                'edicao_id' => (int) $edicao->getKey(),
                'data' => '2026-01-08',
                'nome' => null,
                'autor_id' => (int) $utilizador->getKey(),
                'proximo_nomeado_id' => (int) $utilizador->getKey(),
                'seccoes' => [
                    [
                        'id' => null,
                        'tipo_seccao_id' => (int) $tipoMusica->getKey(),
                        'titulo' => 'Faixa sem ligação',
                        'descricao' => 'Descrição válida.',
                        'artista_id' => (int) $artista->getKey(),
                        'lancamento_id' => null,
                        'lancamento' => null,
                        'ano' => 2026,
                    ],
                ],
            ]);

        $seccao = $metalThursday->seccoes->first();

        self::assertNotNull(
            $seccao,
        );
        self::assertCount(
            0,
            $seccao->ligacoes,
        );

        $this->assertDatabaseMissing(
            'ligacoes_seccao_metal_thursday',
            [
                'seccao_metal_thursday_id' => $seccao->getKey(),
            ],
        );
    }

    /**
     * Confirma a persistência de uma ligação YouTube e a limpeza total das
     * ligações quando uma secção musical passa a Texto.
     *
     * @since 2.0.0
     */
    #[Test]
    public function guarda_ligacao_youtube_e_limpa_tudo_ao_mudar_para_texto(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $tipoMusica = TipoSeccao::factory()
            ->comDados(
                'musica',
                'Música',
                'Música escolhida para a MetalThursday.',
            )
            ->comDetalhes()
            ->naOrdem(1)
            ->create();

        $tipoTexto = TipoSeccao::factory()
            ->comDados(
                'texto',
                'Texto',
                'Conteúdo textual.',
            )
            ->semDetalhes()
            ->naOrdem(2)
            ->create();

        $artista = Artista::factory()
            ->create();

        $servico = $this->servico();

        $metalThursday = $servico->criar([
            'edicao_id' => (int) $edicao->getKey(),
            'data' => '2026-01-08',
            'nome' => null,
            'autor_id' => (int) $utilizador->getKey(),
            'proximo_nomeado_id' => (int) $utilizador->getKey(),
            'seccoes' => [
                [
                    'id' => null,
                    'tipo_seccao_id' => (int) $tipoMusica->getKey(),
                    'titulo' => 'Faixa antiga',
                    'descricao' => 'Descrição inicial.',
                    'artista_id' => (int) $artista->getKey(),
                    'lancamento_id' => null,
                    'lancamento' => null,
                    'ligacoes' => [
                        [
                            'url' => 'https://www.youtube.com/watch?v=abc123',
                            'etiqueta' => null,
                            'incorporar' => true,
                        ],
                    ],
                    'ano' => 2026,
                ],
            ],
        ]);

        $seccao = $metalThursday->seccoes->first();

        self::assertNotNull(
            $seccao,
        );

        $this->assertDatabaseHas(
            'ligacoes_seccao_metal_thursday',
            [
                'seccao_metal_thursday_id' => $seccao->getKey(),
                'plataforma' => PlataformaLigacao::YouTube->value,
                'incorporar' => 1,
                'ordem' => 1,
            ],
        );

        $servico->atualizar(
            $metalThursday,
            [
                'edicao_id' => (int) $edicao->getKey(),
                'data' => '2026-01-08',
                'nome' => null,
                'autor_id' => (int) $utilizador->getKey(),
                'proximo_nomeado_id' => (int) $utilizador->getKey(),
                'seccoes' => [
                    [
                        'id' => (int) $seccao->getKey(),
                        'tipo_seccao_id' => (int) $tipoTexto->getKey(),
                        'titulo' => null,
                        'descricao' => 'Agora é apenas texto.',
                        'artista_id' => null,
                        'lancamento_id' => null,
                        'lancamento' => null,
                        'ano' => null,
                        'ligacoes' => [],
                    ],
                ],
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccao->getKey(),
                'artista_id' => null,
                'lancamento_id' => null,
                'ano' => null,
                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseMissing(
            'ligacoes_seccao_metal_thursday',
            [
                'seccao_metal_thursday_id' => $seccao->getKey(),
            ],
        );
    }

    /**
     * Cria a edição utilizada nos testes.
     *
     * @since 2.0.0
     */
    private function criarEdicao(): Edicao
    {
        return Edicao::factory()
            ->comNome(
                'Edição de janeiro',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();
    }

    /**
     * Obtém o serviço testado.
     *
     * @since 2.0.0
     */
    private function servico(): ServicoPersistenciaMetalThursday
    {
        return new ServicoPersistenciaMetalThursday(
            new ServicoReservasMetalThursday,
        );
    }
}
