<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use App\Servicos\MetalThursday\ServicoOpcoesMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os catálogos estáveis utilizados pelo MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoOpcoesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function devolve_catalogos_estaveis_na_ordenacao_esperada(): void
    {
        $edicaoAntiga = Edicao::factory()
            ->comNome(
                'Edição Antiga',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2025-01-01',
                ),
                CarbonImmutable::parse(
                    '2025-06-01',
                ),
            )
            ->create();

        $edicaoRecente = Edicao::factory()
            ->comNome(
                'Edição Recente',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-06-01',
                ),
            )
            ->create();

        $tipoPosterior = TipoSeccao::factory()
            ->create([
                'ordem' => 20,
            ]);

        $tipoAnterior = TipoSeccao::factory()
            ->create([
                'ordem' => 10,
            ]);

        $origemZulu = OrigemGeografica::factory()
            ->comDados(
                'Zulu',
                'ZZ',
            )
            ->create();

        $origemAlfa = OrigemGeografica::factory()
            ->comDados(
                'Alfa',
                'AA',
            )
            ->create();

        $generoZulu = Genero::factory()
            ->comNome(
                'Zulu Metal',
            )
            ->create();

        $generoAlfa = Genero::factory()
            ->comNome(
                'Alfa Metal',
            )
            ->create();

        $servico = new ServicoOpcoesMetalThursday;

        $edicoes = $servico->obterEdicoesParaSelecao();
        $tipos = $servico->obterTiposSeccao();
        $origens = $servico->obterOrigensGeograficas();
        $generos = $servico->obterGenerosParaSelecao();

        self::assertLessThan(
            $edicoes->search(
                fn (Edicao $edicao): bool => $edicao->is(
                    $edicaoAntiga,
                ),
            ),
            $edicoes->search(
                fn (Edicao $edicao): bool => $edicao->is(
                    $edicaoRecente,
                ),
            ),
        );

        self::assertLessThan(
            $tipos->search(
                fn (TipoSeccao $tipo): bool => $tipo->is(
                    $tipoPosterior,
                ),
            ),
            $tipos->search(
                fn (TipoSeccao $tipo): bool => $tipo->is(
                    $tipoAnterior,
                ),
            ),
        );

        self::assertLessThan(
            $origens->search(
                fn (OrigemGeografica $origem): bool => $origem->is(
                    $origemZulu,
                ),
            ),
            $origens->search(
                fn (OrigemGeografica $origem): bool => $origem->is(
                    $origemAlfa,
                ),
            ),
        );

        self::assertLessThan(
            $generos->search(
                fn (Genero $genero): bool => $genero->is(
                    $generoZulu,
                ),
            ),
            $generos->search(
                fn (Genero $genero): bool => $genero->is(
                    $generoAlfa,
                ),
            ),
        );
    }

    #[Test]
    public function selecao_geral_exclui_utilizador_suspenso(): void
    {
        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $utilizadorAtivo = Utilizador::factory()
            ->create([
                'nome' => 'Utilizador Ativo',
            ]);

        $utilizadorSuspenso = Utilizador::factory()
            ->suspensoPor(
                $superAdministrador,
            )
            ->create([
                'nome' => 'Utilizador Suspenso',
            ]);

        $utilizadores = (new ServicoOpcoesMetalThursday)
            ->obterUtilizadoresParaSelecao();

        self::assertTrue(
            $utilizadores->contains(
                fn (Utilizador $utilizador): bool => $utilizador->is(
                    $utilizadorAtivo,
                ),
            ),
        );

        self::assertFalse(
            $utilizadores->contains(
                fn (Utilizador $utilizador): bool => $utilizador->is(
                    $utilizadorSuspenso,
                ),
            ),
        );
    }

    #[Test]
    public function selecao_de_nomeacao_preserva_nomeado_atual_indisponivel(): void
    {
        $utilizadorElegivel = Utilizador::factory()
            ->create([
                'nome' => 'Utilizador Elegível',
            ]);

        $nomeadoAtual = Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create([
                'nome' => 'Nomeado Atual',
            ]);

        $outroIndisponivel = Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create([
                'nome' => 'Outro Indisponível',
            ]);

        $metalThursday = MetalThursday::factory()
            ->comProximoNomeado(
                $nomeadoAtual,
            )
            ->create();

        $utilizadores = (new ServicoOpcoesMetalThursday)
            ->obterUtilizadoresElegiveisNomeacao(
                $metalThursday,
            );

        self::assertTrue(
            $utilizadores->contains(
                fn (Utilizador $utilizador): bool => $utilizador->is(
                    $utilizadorElegivel,
                ),
            ),
        );

        self::assertTrue(
            $utilizadores->contains(
                fn (Utilizador $utilizador): bool => $utilizador->is(
                    $nomeadoAtual,
                ),
            ),
        );

        self::assertFalse(
            $utilizadores->contains(
                fn (Utilizador $utilizador): bool => $utilizador->is(
                    $outroIndisponivel,
                ),
            ),
        );
    }

    #[Test]
    public function selecao_de_artistas_preserva_atual_eliminado_e_exclui_restantes_eliminados(): void
    {
        $artistaAtivo = Artista::factory()
            ->comNome(
                'Artista Ativo',
            )
            ->create();

        $artistaAtualEliminado = Artista::factory()
            ->comNome(
                'Artista Atual Eliminado',
            )
            ->create();

        $outroArtistaEliminado = Artista::factory()
            ->comNome(
                'Outro Artista Eliminado',
            )
            ->create();

        $artistaAtualEliminado->delete();
        $outroArtistaEliminado->delete();

        $seccao = new SeccaoMetalThursday;
        $seccao->artista_id =
            $artistaAtualEliminado->getKey();

        $metalThursday = new MetalThursday;
        $metalThursday->setRelation(
            'seccoes',
            new Collection([
                $seccao,
            ]),
        );

        $artistas = (new ServicoOpcoesMetalThursday)
            ->obterArtistasParaSelecao(
                $metalThursday,
            );

        self::assertTrue(
            $artistas->contains(
                fn (Artista $artista): bool => $artista->is(
                    $artistaAtivo,
                ),
            ),
        );

        self::assertTrue(
            $artistas->contains(
                fn (Artista $artista): bool => $artista->is(
                    $artistaAtualEliminado,
                ),
            ),
        );

        self::assertFalse(
            $artistas->contains(
                fn (Artista $artista): bool => $artista->is(
                    $outroArtistaEliminado,
                ),
            ),
        );

        $artistaPreservado = $artistas->first(
            fn (Artista $artista): bool => $artista->is(
                $artistaAtualEliminado,
            ),
        );

        self::assertInstanceOf(
            Artista::class,
            $artistaPreservado,
        );

        self::assertTrue(
            $artistaPreservado->relationLoaded(
                'origemGeografica',
            ),
        );

        self::assertTrue(
            $artistaPreservado->relationLoaded(
                'generos',
            ),
        );
    }

    #[Test]
    public function selecao_de_artistas_exige_seccoes_carregadas_na_edicao(): void
    {
        $this->expectException(
            LogicException::class,
        );

        (new ServicoOpcoesMetalThursday)
            ->obterArtistasParaSelecao(
                new MetalThursday,
            );
    }

    #[Test]
    public function serializa_opcoes_nomeadas(): void
    {
        $genero = Genero::factory()
            ->comNome(
                'Doom Metal',
            )
            ->create();

        $opcoes = (new ServicoOpcoesMetalThursday)
            ->serializarOpcoesSelecao(
                new Collection([
                    $genero,
                ]),
            );

        self::assertSame(
            [
                [
                    'identificador' => $genero->getKey(),
                    'nome' => 'Doom Metal',
                ],
            ],
            $opcoes,
        );
    }

    #[Test]
    public function rejeita_opcao_sem_identificador_valido(): void
    {
        $genero = new Genero;

        $genero->nome =
            'Doom Metal';

        $this->expectException(
            LogicException::class,
        );

        (new ServicoOpcoesMetalThursday)
            ->serializarOpcoesSelecao(
                new Collection([
                    $genero,
                ]),
            );
    }
}
