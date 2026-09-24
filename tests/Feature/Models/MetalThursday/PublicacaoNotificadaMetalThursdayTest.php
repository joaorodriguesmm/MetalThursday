<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

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
     * Confirma que a baseline inclui o índice utilizado para localizar
     * publicações ainda por notificar.
     *
     * @since 2.0.0
     */
    #[Test]
    public function possui_indice_de_publicacoes_por_notificar(): void
    {
        $indice = DB::selectOne(
            <<<'SQL'
                SELECT
                    INDEX_NAME AS nome
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'metal_thursdays'
                  AND INDEX_NAME = 'metal_thursdays_publicacao_notificada_data_idx'
                LIMIT 1
                SQL,
        );

        self::assertNotNull(
            $indice,
        );
    }
}
