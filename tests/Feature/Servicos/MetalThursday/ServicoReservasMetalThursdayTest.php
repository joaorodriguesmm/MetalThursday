<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Servicos\MetalThursday\ServicoReservasMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o serviço responsável pelas reservas de MetalThursday.
 *
 * @since 2.0.0
 */
final class ServicoReservasMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Serviço testado.
     *
     * @since 2.0.0
     */
    private ServicoReservasMetalThursday $servicoReservas;

    /**
     * Prepara cada teste.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->servicoReservas =
            new ServicoReservasMetalThursday;
    }

    /**
     * Confirma que a execução semanal cria o slot da quinta-feira seguinte.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_reserva_da_quinta_feira_seguinte(): void
    {
        $primeiro = Utilizador::factory()
            ->create([
                'nome' => 'Ana',
            ]);

        Utilizador::factory()
            ->create([
                'nome' => 'Beatriz',
            ]);

        $reserva =
            $this
                ->servicoReservas
                ->criarReservaSemanal(
                    CarbonImmutable::parse(
                        '2026-08-21 00:00:00',
                        'Europe/Lisbon',
                    ),
                );

        self::assertSame(
            '2026-08-27',
            $reserva->data->toDateString(),
        );

        self::assertSame(
            $primeiro->getKey(),
            $reserva->responsavel_id,
        );

        self::assertTrue(
            $reserva->estaPendente(),
        );
    }

    /**
     * Confirma que a criação do mesmo slot é idempotente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reutiliza_reserva_quando_slot_ja_existe(): void
    {
        Utilizador::factory()
            ->create();

        $data = CarbonImmutable::parse(
            '2026-08-27',
        );

        $primeira =
            $this
                ->servicoReservas
                ->criarReservaAutomatica(
                    $data,
                );

        $segunda =
            $this
                ->servicoReservas
                ->criarReservaAutomatica(
                    $data,
                );

        self::assertSame(
            $primeira->getKey(),
            $segunda->getKey(),
        );

        self::assertSame(
            1,
            ReservaMetalThursday::query()->count(),
        );
    }

    /**
     * Confirma que é criado um slot sem responsável quando ninguém é elegível.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_reserva_sem_responsavel_quando_ninguem_e_elegivel(): void
    {
        Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $reserva =
            $this
                ->servicoReservas
                ->criarReservaAutomatica(
                    CarbonImmutable::parse(
                        '2026-08-27',
                    ),
                );

        self::assertNull(
            $reserva->responsavel_id,
        );

        self::assertTrue(
            $reserva->estaPendente(),
        );
    }

    /**
     * Confirma que um utilizador com reserva pendente não recebe outra.
     *
     * @since 2.0.0
     */
    #[Test]
    public function exclui_utilizador_com_reserva_pendente(): void
    {
        $comReservaPendente = Utilizador::factory()
            ->create([
                'nome' => 'Ana',
            ]);

        $disponivel = Utilizador::factory()
            ->create([
                'nome' => 'Beatriz',
            ]);

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                ),
            )
            ->comResponsavel(
                $comReservaPendente,
            )
            ->create();

        $novaReserva =
            $this
                ->servicoReservas
                ->criarReservaAutomatica(
                    CarbonImmutable::parse(
                        '2026-08-27',
                    ),
                );

        self::assertSame(
            $disponivel->getKey(),
            $novaReserva->responsavel_id,
        );
    }

    /**
     * Confirma que o histórico real das reservas determina a antiguidade.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utiliza_reservas_como_historico_de_nomeacoes(): void
    {
        $maisRecente = Utilizador::factory()
            ->create([
                'nome' => 'Ana',
            ]);

        $maisAntigo = Utilizador::factory()
            ->create([
                'nome' => 'Beatriz',
            ]);

        $edicao = Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-08-01',
                ),
                CarbonImmutable::parse(
                    '2026-08-31',
                ),
            )
            ->create();

        $this->criarReservaCumprida(
            $maisAntigo,
            $edicao,
            '2026-08-06',
        );

        $this->criarReservaCumprida(
            $maisRecente,
            $edicao,
            '2026-08-13',
        );

        $selecionado =
            $this
                ->servicoReservas
                ->obterUtilizadorHaMaisTempoSemNomeacao();

        self::assertTrue(
            $selecionado?->is(
                $maisAntigo,
            )
                ?? false,
        );
    }

    /**
     * Confirma que `proximo_nomeado_id` já não conta como histórico.
     *
     * @since 2.0.0
     */
    #[Test]
    public function ignora_nomeacao_legada_sem_reserva(): void
    {
        $primeiroAlfabeticamente = Utilizador::factory()
            ->create([
                'nome' => 'Ana',
            ]);

        $outro = Utilizador::factory()
            ->create([
                'nome' => 'Beatriz',
            ]);

        $data = CarbonImmutable::parse(
            '2026-08-06',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                $data->startOfMonth(),
                $data->endOfMonth(),
            )
            ->create();

        MetalThursday::factory()
            ->comData(
                $data,
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $outro,
            )
            ->comProximoNomeado(
                $primeiroAlfabeticamente,
            )
            ->create();

        $selecionado =
            $this
                ->servicoReservas
                ->obterUtilizadorHaMaisTempoSemNomeacao();

        self::assertTrue(
            $selecionado?->is(
                $primeiroAlfabeticamente,
            )
                ?? false,
        );
    }

    /**
     * Confirma que uma reserva automática exige uma quinta-feira.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_reserva_automatica_fora_de_quinta_feira(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this
            ->servicoReservas
            ->criarReservaAutomatica(
                CarbonImmutable::parse(
                    '2026-08-28',
                ),
            );
    }

    /**
     * Cria uma reserva histórica já cumprida.
     *
     * @param  Utilizador  $responsavel  Utilizador historicamente nomeado.
     * @param  Edicao  $edicao  Edição utilizada.
     * @param  string  $data  Quinta-feira reservada.
     *
     * @since 2.0.0
     */
    private function criarReservaCumprida(
        Utilizador $responsavel,
        Edicao $edicao,
        string $data,
    ): void {
        $dataReserva = CarbonImmutable::parse(
            $data,
        );

        $metalThursday = MetalThursday::factory()
            ->comData(
                $dataReserva,
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $responsavel,
            )
            ->comProximoNomeado(
                $responsavel,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comResponsavel(
                $responsavel,
            )
            ->comMetalThursday(
                $metalThursday,
            )
            ->create();
    }
}
