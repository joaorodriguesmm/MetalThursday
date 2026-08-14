<?php

declare(strict_types=1);

namespace App\Traits\Interacoes;

use App\Models\Interacoes\Comentario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adiciona suporte a comentários polimórficos a um modelo Eloquent.
 *
 * A relação inclui todos os comentários associados à entidade. A hierarquia
 * entre comentários principais e respostas é determinada através do campo
 * `comentario_pai_id` do modelo Comentario.
 *
 * @mixin Model
 *
 * @since 2.0.0
 */
trait TemComentarios
{
    /**
     * Obtém os comentários associados ao modelo.
     *
     * @return MorphMany<Comentario, $this> Relação com os comentários.
     *
     * @since 2.0.0
     */
    public function comentarios(): MorphMany
    {
        return $this->morphMany(
            Comentario::class,
            'comentavel',
            'tipo_comentavel',
            'comentavel_id',
        );
    }
}
