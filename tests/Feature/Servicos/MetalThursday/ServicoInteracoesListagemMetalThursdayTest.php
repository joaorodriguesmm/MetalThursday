<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Servicos\MetalThursday\ServicoInteracoesListagemMetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a leitura leve das interações apresentadas na listagem.
 *
 * @since 2.0.0
 */
final class ServicoInteracoesListagemMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function carrega_interacoes_da_pagina_em_duas_consultas_planas(): void
    {
        $utilizadorAtual = Utilizador::factory()
            ->create([
                'nome' => 'Utilizador Atual',
            ]);

        $outroUtilizador = Utilizador::factory()
            ->create([
                'nome' => 'Outro Utilizador',
            ]);

        $metalThursday = MetalThursday::factory()
            ->create();

        $metalThursdaySemInteracoes = MetalThursday::factory()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->create();

        $metalThursday
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizadorAtual->id,

                'pontuacao' => 8.5,
            ]);

        $metalThursday
            ->avaliacoes()
            ->create([
                'utilizador_id' => $outroUtilizador->id,

                'pontuacao' => 7.5,
            ]);

        $metalThursday
            ->audicoes()
            ->create([
                'utilizador_id' => $outroUtilizador->id,
            ]);

        $metalThursday
            ->audicoes()
            ->create([
                'utilizador_id' => $utilizadorAtual->id,
            ]);

        $seccao
            ->avaliacoes()
            ->create([
                'utilizador_id' => $outroUtilizador->id,

                'pontuacao' => 9.0,
            ]);

        $seccao
            ->audicoes()
            ->create([
                'utilizador_id' => $utilizadorAtual->id,
            ]);

        $numeroConsultas = 0;

        DB::listen(
            static function () use (
                &$numeroConsultas,
            ): void {
                $numeroConsultas++;
            },
        );

        $dados =
            (new ServicoInteracoesListagemMetalThursday)
                ->obter(
                    [
                        $metalThursday->id,
                        $metalThursdaySemInteracoes->id,
                    ],
                    [
                        $seccao->id,
                    ],
                    $utilizadorAtual->id,
                );

        self::assertSame(
            2,
            $numeroConsultas,
        );

        self::assertSame(
            [
                'pontuacaoUtilizador' => 8.5,

                'ouvido' => true,

                'quantidadeAudicoes' => 2,

                'quantidadeAvaliacoes' => 2,

                'mediaAvaliacoes' => 8.0,

                'audicoes' => [
                    'Outro Utilizador',
                    'Utilizador Atual',
                ],

                'avaliacoes' => [
                    [
                        'nome' => 'Utilizador Atual',

                        'pontuacao' => 8.5,
                    ],
                    [
                        'nome' => 'Outro Utilizador',

                        'pontuacao' => 7.5,
                    ],
                ],
            ],
            $dados[
                'metal-thursday'
            ][
                $metalThursday->id
            ],
        );

        self::assertSame(
            [
                'pontuacaoUtilizador' => 0.0,

                'ouvido' => false,

                'quantidadeAudicoes' => 0,

                'quantidadeAvaliacoes' => 0,

                'mediaAvaliacoes' => 0.0,

                'audicoes' => [],

                'avaliacoes' => [],
            ],
            $dados[
                'metal-thursday'
            ][
                $metalThursdaySemInteracoes->id
            ],
        );

        self::assertSame(
            [
                'pontuacaoUtilizador' => 0.0,

                'ouvido' => true,

                'quantidadeAudicoes' => 1,

                'quantidadeAvaliacoes' => 1,

                'mediaAvaliacoes' => 9.0,

                'audicoes' => [
                    'Utilizador Atual',
                ],

                'avaliacoes' => [
                    [
                        'nome' => 'Outro Utilizador',

                        'pontuacao' => 9.0,
                    ],
                ],
            ],
            $dados[
                'seccao-metal-thursday'
            ][
                $seccao->id
            ],
        );
    }

    #[Test]
    public function lista_vazia_nao_executa_consultas(): void
    {
        $numeroConsultas = 0;

        DB::listen(
            static function () use (
                &$numeroConsultas,
            ): void {
                $numeroConsultas++;
            },
        );

        $dados =
            (new ServicoInteracoesListagemMetalThursday)
                ->obter(
                    [],
                    [],
                    1,
                );

        self::assertSame(
            0,
            $numeroConsultas,
        );

        self::assertSame(
            [
                'metal-thursday' => [],

                'seccao-metal-thursday' => [],
            ],
            $dados,
        );
    }
}
