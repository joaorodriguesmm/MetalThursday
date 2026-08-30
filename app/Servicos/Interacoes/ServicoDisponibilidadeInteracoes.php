<?php

declare(strict_types=1);

namespace App\Servicos\Interacoes;

use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Garante a disponibilidade temporal das entidades que suportam interações.
 *
 * Todas as interações pertencem, direta ou indiretamente, a uma
 * MetalThursday. Enquanto a respetiva data de publicação ainda não chegou, a
 * entidade é tratada como indisponível e não pode expor ou aceitar interações.
 *
 * @since 2.0.0
 */
final class ServicoDisponibilidadeInteracoes
{
    /**
     * Obtém a MetalThursday publicada à qual pertence uma entidade.
     *
     * @param  MetalThursday|SeccaoMetalThursday|Comentario  $entidade  Entidade
     *                                                                  verificada.
     * @return MetalThursday MetalThursday raiz já publicada.
     *
     * @throws NotFoundHttpException Quando a entidade não possui uma
     *                               MetalThursday disponível ou esta ainda não
     *                               foi publicada.
     *
     * @since 2.0.0
     */
    public function obterMetalThursdayPublicada(
        MetalThursday|SeccaoMetalThursday|Comentario $entidade,
    ): MetalThursday {
        return $this->resolverMetalThursdayPublicada(
            $entidade,
            false,
        );
    }

    /**
     * Obtém e bloqueia a MetalThursday publicada associada a uma entidade.
     *
     * Este método destina-se às operações executadas dentro de uma transação,
     * impedindo que a data da MetalThursday seja alterada concorrentemente
     * depois da verificação temporal e antes da escrita da interação.
     *
     * @param  MetalThursday|SeccaoMetalThursday|Comentario  $entidade  Entidade
     *                                                                  verificada.
     * @return MetalThursday MetalThursday raiz bloqueada e publicada.
     *
     * @throws NotFoundHttpException Quando a entidade não possui uma
     *                               MetalThursday disponível ou esta ainda não
     *                               foi publicada.
     *
     * @since 2.0.0
     */
    public function obterMetalThursdayPublicadaComBloqueio(
        MetalThursday|SeccaoMetalThursday|Comentario $entidade,
    ): MetalThursday {
        return $this->resolverMetalThursdayPublicada(
            $entidade,
            true,
        );
    }

    /**
     * Resolve a MetalThursday raiz e valida o respetivo estado temporal.
     *
     * @param  MetalThursday|SeccaoMetalThursday|Comentario  $entidade  Entidade
     *                                                                  verificada.
     * @param  bool  $bloquear  Indica se a MetalThursday deve ser bloqueada.
     * @return MetalThursday MetalThursday disponível.
     *
     * @throws NotFoundHttpException Quando não existe uma raiz publicada
     *                               válida.
     *
     * @since 2.0.0
     */
    private function resolverMetalThursdayPublicada(
        MetalThursday|SeccaoMetalThursday|Comentario $entidade,
        bool $bloquear,
    ): MetalThursday {
        $identificador =
            $this->obterIdentificadorMetalThursday(
                $entidade,
            );

        $construtor =
            MetalThursday::query()
                ->whereKey(
                    $identificador,
                );

        if ($bloquear) {
            $construtor->lockForUpdate();
        }

        $metalThursday =
            $construtor->first();

        if (
            ! $metalThursday instanceof MetalThursday
            || ! $metalThursday->estaPublicada()
        ) {
            throw new NotFoundHttpException;
        }

        return $metalThursday;
    }

    /**
     * Obtém o identificador da MetalThursday raiz.
     *
     * @param  MetalThursday|SeccaoMetalThursday|Comentario  $entidade  Entidade
     *                                                                  verificada.
     * @return int Identificador da MetalThursday.
     *
     * @throws NotFoundHttpException Quando a associação não é válida.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorMetalThursday(
        MetalThursday|SeccaoMetalThursday|Comentario $entidade,
    ): int {
        if ($entidade instanceof MetalThursday) {
            return $this->normalizarIdentificador(
                $entidade->getKey(),
            );
        }

        if ($entidade instanceof SeccaoMetalThursday) {
            return $this->normalizarIdentificador(
                $entidade->metal_thursday_id,
            );
        }

        $comentavel =
            $entidade
                ->comentavel()
                ->first();

        if (
            ! $comentavel instanceof MetalThursday
            && ! $comentavel instanceof SeccaoMetalThursday
        ) {
            throw new NotFoundHttpException;
        }

        return $this->obterIdentificadorMetalThursday(
            $comentavel,
        );
    }

    /**
     * Normaliza um identificador persistido.
     *
     * @param  mixed  $identificador  Identificador recebido.
     * @return int Identificador positivo.
     *
     * @throws NotFoundHttpException Quando o identificador não é válido.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificador(
        mixed $identificador,
    ): int {
        if (
            ! is_numeric(
                $identificador,
            )
            || (int) $identificador < 1
        ) {
            throw new NotFoundHttpException;
        }

        return (int) $identificador;
    }
}
