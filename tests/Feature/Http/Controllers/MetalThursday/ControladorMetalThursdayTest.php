<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os pedidos HTTP associados às MetalThursdays.
 *
 * @since 2.0.0
 */
final class ControladorMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste sem depender dos ficheiros produzidos pelo Vite.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Confirma que a vista completa apresenta a posição de cada registo na
     * edição sem depender de um accessor com consultas implícitas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function listagem_completa_apresenta_numero_semana_na_edicao(): void
    {
        $utilizador = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-01',
        );

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-08',
        );

        $this->get(
            route(
                'inicio',
            ),
        )
            ->assertOk()
            ->assertSee(
                'Semana 1',
            )
            ->assertSee(
                'Semana 2',
            );
    }

    /**
     * Confirma que a página de detalhes carrega explicitamente a posição na
     * edição e reutiliza o valor durante toda a renderização.
     *
     * @since 2.0.0
     */
    #[Test]
    public function detalhes_apresentam_numero_semana_na_edicao(): void
    {
        $utilizador = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-01',
        );

        $segunda = $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-08',
        );

        $this->get(
            route(
                'metal-thursday.detalhes',
                $segunda,
            ),
        )
            ->assertOk()
            ->assertSee(
                'Semana 2',
            );
    }

    /**
     * Confirma que o utilizador nunca nomeado com precedência alfabética é
     * apresentado antes dos utilizadores já nomeados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_utilizador_ha_mais_tempo_sem_nomeacao(): void
    {
        $utilizadorAutenticado = Utilizador::factory()
            ->create([
                'nome' => 'Zelda',
            ]);

        $primeiroNuncaNomeado = Utilizador::factory()
            ->create([
                'nome' => 'Ana',
            ]);

        Utilizador::factory()
            ->create([
                'nome' => 'Beatriz',
            ]);

        $utilizadorJaNomeado = Utilizador::factory()
            ->create([
                'nome' => 'Carlos',
            ]);

        $this->actingAs(
            $utilizadorAutenticado,
            'sessao',
        );

        $this->criarMetalThursday(
            $this->criarEdicao(),
            $utilizadorJaNomeado,
            '2026-01-01',
        );

        $this
            ->getJson(
                route(
                    'utilizadores.ha-mais-tempo-sem-nomeacao',
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'identificador',
                $primeiroNuncaNomeado->getKey(),
            );
    }

    /**
     * Confirma que um utilizador sem autorização não elimina a
     * MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_sem_autorizacao_nao_elimina_metal_thursday(): void
    {
        $criador = $this->criarUtilizador();
        $outroUtilizador = $this->criarUtilizador();

        $this->actingAs(
            $criador,
            'sessao',
        );

        $metalThursday = $this->criarMetalThursday(
            $this->criarEdicao(),
            $criador,
            '2026-01-01',
        );

        $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'metal-thursday.eliminar',
                    $metalThursday,
                ),
            )
            ->assertForbidden();

        $this->assertNotSoftDeleted(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),
            ],
        );
    }

    /**
     * Confirma que o criador pode eliminar logicamente a MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criador_elimina_metal_thursday(): void
    {
        $criador = $this->criarUtilizador();

        $this->actingAs(
            $criador,
            'sessao',
        );

        $metalThursday = $this->criarMetalThursday(
            $this->criarEdicao(),
            $criador,
            '2026-01-01',
        );

        $this
            ->deleteJson(
                route(
                    'metal-thursday.eliminar',
                    $metalThursday,
                ),
            )
            ->assertNoContent();

        $this->assertSoftDeleted(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),
            ],
        );
    }

    /**
     * Cria um utilizador autenticável e verificado.
     *
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     */
    private function criarUtilizador(): Utilizador
    {
        return Utilizador::factory()
            ->create();
    }

    /**
     * Cria a edição utilizada nos testes.
     *
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     */
    private function criarEdicao(): Edicao
    {
        return Edicao::factory()
            ->comNome(
                'Edição de Teste',
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
     * Cria uma MetalThursday associada à edição e ao utilizador indicados.
     *
     * @param  Edicao  $edicao  Edição relacionada.
     * @param  Utilizador  $utilizador  Autor e próximo nomeado.
     * @param  string  $data  Data no formato AAAA-MM-DD.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Edicao $edicao,
        Utilizador $utilizador,
        string $data,
    ): MetalThursday {
        return MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    $data,
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $utilizador,
            )
            ->comProximoNomeado(
                $utilizador,
            )
            ->create();
    }
}
