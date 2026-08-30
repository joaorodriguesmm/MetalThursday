<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o marcador durável da notificação de publicação.
 *
 * @since 2.0.0
 */
final class PublicacaoNotificadaMetalThursdayTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Confirma que a coluna necessária à publicação temporal existe.
     *
     * @since 2.0.0
     */
    #[Test]
    public function possui_coluna_de_publicacao_notificada(): void
    {
        self::assertTrue(
            Schema::hasColumn(
                'metal_thursdays',
                MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM,
            ),
        );
    }

    /**
     * Confirma que uma nova MetalThursday fica pendente de notificação.
     *
     * O preenchimento dos registos antigos ocorre apenas durante a execução
     * da migração. Registos criados posteriormente devem começar a nulo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nova_metal_thursday_inicia_sem_publicacao_notificada(): void
    {
        $metalThursday =
            MetalThursday::factory()
                ->create();

        self::assertNull(
            $metalThursday->publicacao_notificada_em,
        );
    }

    /**
     * Confirma que o momento da notificação é convertido para uma data
     * imutável pelo modelo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function converte_publicacao_notificada_em_para_data_imutavel(): void
    {
        $metalThursday =
            MetalThursday::factory()
                ->create();

        DB::table(
            'metal_thursdays',
        )
            ->where(
                'id',
                $metalThursday->getKey(),
            )
            ->update([
                MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM => CarbonImmutable::parse(
                    '2026-08-29 08:00:00',
                    'Europe/Lisbon',
                ),
            ]);

        $metalThursday->refresh();

        self::assertInstanceOf(
            CarbonImmutable::class,
            $metalThursday->publicacao_notificada_em,
        );
    }

    /**
     * Confirma que uma MetalThursday que já existia antes da introdução do
     * marcador fica assinalada durante a migração.
     *
     * Este comportamento impede que registos históricos ou futuras
     * MetalThursdays criadas pelo fluxo antigo originem notificações
     * retroativas quando o novo processamento agendado entrar em funcionamento.
     *
     * @since 2.0.0
     */
    #[Test]
    public function migracao_marca_metal_thursday_preexistente_como_ja_notificada(): void
    {
        $caminhoMigracao =
            'database/migrations/2026_08_29_081000_adicionar_publicacao_notificada_em_a_metal_thursdays.php';

        $this
            ->artisan(
                'migrate:rollback',
                [
                    '--path' => $caminhoMigracao,

                    '--force' => true,
                ],
            )
            ->assertSuccessful();

        self::assertFalse(
            Schema::hasColumn(
                'metal_thursdays',
                MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM,
            ),
        );

        $metalThursday =
            MetalThursday::factory()
                ->create();

        $identificadorMetalThursday =
            $metalThursday->getKey();

        self::assertNotNull(
            $identificadorMetalThursday,
        );

        $this
            ->artisan(
                'migrate',
                [
                    '--path' => $caminhoMigracao,

                    '--force' => true,
                ],
            )
            ->assertSuccessful();

        self::assertTrue(
            Schema::hasColumn(
                'metal_thursdays',
                MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM,
            ),
        );

        $publicacaoNotificadaEm =
            DB::table(
                'metal_thursdays',
            )
                ->where(
                    'id',
                    $identificadorMetalThursday,
                )
                ->value(
                    MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM,
                );

        self::assertNotNull(
            $publicacaoNotificadaEm,
        );
    }
}
