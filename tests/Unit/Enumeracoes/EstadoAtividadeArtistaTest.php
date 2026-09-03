<?php

declare(strict_types=1);

namespace Tests\Unit\Enumeracoes;

use App\Enumeracoes\EstadoAtividadeArtista;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testa o contrato público dos estados de atividade dos artistas.
 *
 * @since 2.0.0
 */
final class EstadoAtividadeArtistaTest extends TestCase
{
    /**
     * Confirma os valores persistidos permitidos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function define_estados_de_atividade_validos(): void
    {
        self::assertSame(
            [
                'ativo',
                'em_hiato',
                'terminado',
            ],
            array_map(
                static fn (
                    EstadoAtividadeArtista $estado,
                ): string => $estado->value,
                EstadoAtividadeArtista::cases(),
            ),
        );
    }

    /**
     * Confirma as etiquetas portuguesas apresentadas ao utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function devolve_etiquetas_portuguesas(): void
    {
        self::assertSame(
            'Ativo',
            EstadoAtividadeArtista::Ativo->etiqueta(),
        );

        self::assertSame(
            'Em hiato',
            EstadoAtividadeArtista::EmHiato->etiqueta(),
        );

        self::assertSame(
            'Atividade terminada',
            EstadoAtividadeArtista::Terminado->etiqueta(),
        );
    }
}
