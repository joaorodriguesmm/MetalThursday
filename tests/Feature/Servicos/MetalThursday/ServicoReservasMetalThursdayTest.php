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
     * Confirma que o fallback cria a reserva seguinte quando a quinta-feira
     * anterior ficou pendente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_reserva_seguinte_quando_anterior_ficou_pendente(): void
    {
        $responsavelAnterior = Utilizador::factory()
            ->create([
                'nome' => 'Carlos',
            ]);

        $primeiroElegivel = Utilizador::factory()
            ->create([
                'nome' => 'Ana',
            ]);

        Utilizador::factory()
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
                $responsavelAnterior,
            )
            ->create();

        $reserva =
            $this
                ->servicoReservas
                ->criarReservaSemanal(
                    CarbonImmutable::parse(
                        '2026-08-21 00:00:00',
                        'Europe/Lisbon',
                    ),
                );

        self::assertInstanceOf(
            ReservaMetalThursday::class,
            $reserva,
        );

        self::assertSame(
            '2026-08-27',
            $reserva->data->toDateString(),
        );

        self::assertSame(
            $primeiroElegivel->getKey(),
            $reserva->responsavel_id,
        );

        self::assertTrue(
            $reserva->estaPendente(),
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            2,
        );
    }

    /**
     * Confirma que a criação automática não altera um slot já existente e
     * sinaliza que não criou uma nova reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_altera_reserva_automatica_quando_slot_ja_existe(): void
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

        self::assertInstanceOf(
            ReservaMetalThursday::class,
            $primeira,
        );

        $segunda =
            $this
                ->servicoReservas
                ->criarReservaAutomatica(
                    $data,
                );

        self::assertNull(
            $segunda,
        );

        self::assertSame(
            1,
            ReservaMetalThursday::query()->count(),
        );
    }

    /**
     * Confirma que o fallback não cria um slot quando não existe reserva para a
     * quinta-feira anterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_cria_fallback_sem_reserva_anterior(): void
    {
        Utilizador::factory()
            ->create();

        $resultado =
            $this
                ->servicoReservas
                ->criarReservaSemanal(
                    CarbonImmutable::parse(
                        '2026-08-21 00:00:00',
                        'Europe/Lisbon',
                    ),
                );

        self::assertNull(
            $resultado,
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            0,
        );
    }

    /**
     * Confirma que o fallback não cria uma nova reserva quando a quinta-feira
     * anterior já foi cumprida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_cria_fallback_quando_reserva_anterior_foi_cumprida(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        Utilizador::factory()
            ->create();

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
            $responsavel,
            $edicao,
            '2026-08-20',
        );

        $resultado =
            $this
                ->servicoReservas
                ->criarReservaSemanal(
                    CarbonImmutable::parse(
                        '2026-08-21 00:00:00',
                        'Europe/Lisbon',
                    ),
                );

        self::assertNull(
            $resultado,
        );

        $this->assertDatabaseMissing(
            'reservas_metal_thursday',
            [
                'data' => '2026-08-27',
            ],
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            1,
        );
    }

    /**
     * Confirma que o fallback preserva integralmente uma reserva seguinte que já
     * exista.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_reserva_seguinte_ja_existente_no_fallback(): void
    {
        $responsavelAnterior = Utilizador::factory()
            ->create();

        $responsavelSeguinte = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                ),
            )
            ->comResponsavel(
                $responsavelAnterior,
            )
            ->create();

        $reservaSeguinte = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $responsavelSeguinte,
            )
            ->create();

        $resultado =
            $this
                ->servicoReservas
                ->criarReservaSemanal(
                    CarbonImmutable::parse(
                        '2026-08-21 00:00:00',
                        'Europe/Lisbon',
                    ),
                );

        self::assertNull(
            $resultado,
        );

        self::assertSame(
            $responsavelSeguinte->getKey(),
            $reservaSeguinte
                ->refresh()
                ->responsavel_id,
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            2,
        );
    }

    /**
     * Confirma que uma nomeação explícita cria a reserva para o utilizador
     * indicado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_reserva_para_nomeado_explicito(): void
    {
        $nomeado = Utilizador::factory()
            ->create();

        $reserva =
            $this
                ->servicoReservas
                ->criarReservaParaNomeado(
                    CarbonImmutable::parse(
                        '2026-08-27',
                    ),
                    (int) $nomeado->getKey(),
                );

        self::assertInstanceOf(
            ReservaMetalThursday::class,
            $reserva,
        );

        self::assertSame(
            '2026-08-27',
            $reserva->data->toDateString(),
        );

        self::assertSame(
            $nomeado->getKey(),
            $reserva->responsavel_id,
        );

        self::assertTrue(
            $reserva->estaPendente(),
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            1,
        );
    }

    /**
     * Confirma que uma nomeação explícita nunca substitui um slot já
     * existente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_substitui_reserva_existente_ao_nomear(): void
    {
        $responsavelExistente = Utilizador::factory()
            ->create();

        $novoNomeado = Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create();

        $reservaExistente = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $responsavelExistente,
            )
            ->create();

        $resultado =
            $this
                ->servicoReservas
                ->criarReservaParaNomeado(
                    CarbonImmutable::parse(
                        '2026-08-27',
                    ),
                    (int) $novoNomeado->getKey(),
                );

        self::assertNull(
            $resultado,
        );

        self::assertSame(
            $responsavelExistente->getKey(),
            $reservaExistente
                ->refresh()
                ->responsavel_id,
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            1,
        );
    }

    /**
     * Confirma que a camada de serviço não permite atribuir uma segunda
     * reserva pendente ao mesmo utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_nomeado_com_reserva_pendente(): void
    {
        $nomeado = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                ),
            )
            ->comResponsavel(
                $nomeado,
            )
            ->create();

        try {
            $this
                ->servicoReservas
                ->criarReservaParaNomeado(
                    CarbonImmutable::parse(
                        '2026-08-27',
                    ),
                    (int) $nomeado->getKey(),
                );

            self::fail(
                'Era esperada uma exceção para um utilizador com reserva pendente.',
            );
        } catch (InvalidArgumentException $excecao) {
            self::assertSame(
                'O utilizador nomeado não está disponível para uma nova nomeação.',
                $excecao->getMessage(),
            );
        }

        $this->assertDatabaseMissing(
            'reservas_metal_thursday',
            [
                'data' => '2026-08-27',
            ],
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            1,
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
