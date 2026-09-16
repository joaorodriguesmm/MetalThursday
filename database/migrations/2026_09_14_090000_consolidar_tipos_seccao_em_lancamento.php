<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Consolida os tipos de secção de álbum e EP num único tipo de lançamento.
 *
 * As secções existentes mantêm-se intactas e passam a apontar para o tipo
 * canónico `lancamento`.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Identificador do tipo de secção canónico.
     *
     * @since 2.0.0
     */
    private const IDENTIFICADOR_LANCAMENTO =
        'lancamento';

    /**
     * Identificadores legados substituídos pelo tipo de lançamento.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    private const IDENTIFICADORES_LEGADOS = [
        'album',
        'ep',
    ];

    /**
     * Consolida os tipos existentes sem perder associações.
     *
     * Numa instalação nova os tipos ainda não foram criados pelo seeder, pelo
     * que não existe qualquer dado a migrar.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $tipos = DB::table(
                'tipos_seccao',
            )
                ->whereIn(
                    'identificador',
                    [
                        self::IDENTIFICADOR_LANCAMENTO,
                        ...self::IDENTIFICADORES_LEGADOS,
                    ],
                )
                ->lockForUpdate()
                ->get()
                ->keyBy(
                    'identificador',
                );

            if ($tipos->isEmpty()) {
                return;
            }

            $tipoDestino =
                $tipos->get(
                    self::IDENTIFICADOR_LANCAMENTO,
                )
                ?? $tipos->get('album')
                ?? $tipos->get('ep');

            if (! is_object($tipoDestino)) {
                throw new RuntimeException(
                    'Não foi possível determinar o tipo de secção de lançamento.',
                );
            }

            $identificadorTipoDestino =
                (int) $tipoDestino->id;

            if (
                $tipoDestino->identificador
                !== self::IDENTIFICADOR_LANCAMENTO
            ) {
                DB::table(
                    'tipos_seccao',
                )
                    ->where(
                        'id',
                        $identificadorTipoDestino,
                    )
                    ->update([
                        'identificador' => self::IDENTIFICADOR_LANCAMENTO,
                        'updated_at' => now(),
                    ]);
            }

            foreach (
                self::IDENTIFICADORES_LEGADOS as $identificadorLegado
            ) {
                $tipoLegado =
                    DB::table(
                        'tipos_seccao',
                    )
                        ->where(
                            'identificador',
                            $identificadorLegado,
                        )
                        ->lockForUpdate()
                        ->first();

                if ($tipoLegado === null) {
                    continue;
                }

                $identificadorTipoLegado =
                    (int) $tipoLegado->id;

                DB::table(
                    'seccoes_metal_thursday',
                )
                    ->where(
                        'tipo_seccao_id',
                        $identificadorTipoLegado,
                    )
                    ->update([
                        'tipo_seccao_id' => $identificadorTipoDestino,
                    ]);

                DB::table(
                    'tipos_seccao',
                )
                    ->where(
                        'id',
                        $identificadorTipoLegado,
                    )
                    ->delete();
            }

            $this->garantirValorUnicoDisponivel(
                'nome',
                'Lançamento',
                $identificadorTipoDestino,
            );

            $this->garantirValorUnicoDisponivel(
                'ordem',
                2,
                $identificadorTipoDestino,
            );

            DB::table(
                'tipos_seccao',
            )
                ->where(
                    'id',
                    $identificadorTipoDestino,
                )
                ->update([
                    'nome' => 'Lançamento',
                    'identificador' => self::IDENTIFICADOR_LANCAMENTO,
                    'descricao' => 'Secção destinada à apresentação de um lançamento musical.',
                    'exige_detalhes' => true,
                    'ordem' => 2,
                    'updated_at' => now(),
                ]);

            $tipoMusica =
                DB::table(
                    'tipos_seccao',
                )
                    ->where(
                        'identificador',
                        'musica',
                    )
                    ->lockForUpdate()
                    ->first();

            if ($tipoMusica === null) {
                return;
            }

            $this->garantirValorUnicoDisponivel(
                'ordem',
                3,
                (int) $tipoMusica->id,
            );

            DB::table(
                'tipos_seccao',
            )
                ->where(
                    'id',
                    (int) $tipoMusica->id,
                )
                ->update([
                    'ordem' => 3,
                    'updated_at' => now(),
                ]);
        });
    }

    /**
     * Reverte apenas quando a execução original não teve dados para consolidar.
     *
     * Numa instalação nova, esta migração é executada antes dos seeders e o
     * método `up` não altera qualquer tipo de secção. Nesse cenário, a reversão
     * também pode terminar sem efeitos. Quando existem tipos relacionados, a
     * distinção original entre álbum e EP já não pode ser reconstruída com
     * segurança e a reversão continua explicitamente bloqueada.
     *
     * @throws RuntimeException Quando existem tipos relacionados cuja origem
     *                          não pode ser reconstruída sem inventar dados.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        $possuiTiposRelacionados = DB::table(
            'tipos_seccao',
        )
            ->whereIn(
                'identificador',
                [
                    self::IDENTIFICADOR_LANCAMENTO,
                    ...self::IDENTIFICADORES_LEGADOS,
                ],
            )
            ->exists();

        if (! $possuiTiposRelacionados) {
            return;
        }

        throw new RuntimeException(
            'A consolidação dos tipos de secção não pode ser revertida automaticamente sem perda de informação.',
        );
    }

    /**
     * Garante que um valor sujeito a restrição única não pertence a outro tipo.
     *
     * @param  string  $coluna  Coluna verificada.
     * @param  int|string  $valor  Valor pretendido.
     * @param  int  $identificadorIgnorado  Tipo que pode já possuir o valor.
     *
     * @throws RuntimeException Quando o valor pertence a outro tipo.
     *
     * @since 2.0.0
     */
    private function garantirValorUnicoDisponivel(
        string $coluna,
        int|string $valor,
        int $identificadorIgnorado,
    ): void {
        $ocupado =
            DB::table(
                'tipos_seccao',
            )
                ->where(
                    $coluna,
                    $valor,
                )
                ->where(
                    'id',
                    '!=',
                    $identificadorIgnorado,
                )
                ->exists();

        if (! $ocupado) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'O valor %s da coluna %s encontra-se ocupado por um tipo de secção inesperado.',
                (string) $valor,
                $coluna,
            ),
        );
    }
};
