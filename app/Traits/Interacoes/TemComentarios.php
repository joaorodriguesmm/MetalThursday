<?php

declare(strict_types=1);

namespace App\Traits\Interacoes;

use App\Models\Interacoes\Comentario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adiciona suporte a comentários polimórficos a um modelo Eloquent.
 *
 * A relação principal inclui também os marcadores estruturais necessários
 * para preservar a árvore. A relação `comentariosComConteudo` exclui esses
 * marcadores e deve ser utilizada quando é necessário contabilizar conteúdo
 * efetivamente publicado.
 *
 * @mixin Model
 *
 * @since 2.0.0
 */
trait TemComentarios
{
    /**
     * Obtém todos os comentários estruturais associados ao modelo.
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

    /**
     * Obtém apenas comentários cujo conteúdo continua disponível.
     *
     * Os tombstones permanecem na relação `comentarios`, porque podem ser
     * necessários à árvore, mas não devem aumentar o contador apresentado ao
     * utilizador.
     *
     * @return MorphMany<Comentario, $this> Relação filtrada.
     *
     * @since 2.0.0
     */
    public function comentariosComConteudo(): MorphMany
    {
        return $this
            ->comentarios()
            ->whereNull(
                'conteudo_eliminado_em',
            );
    }
}
