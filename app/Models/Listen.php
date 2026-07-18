<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Gere a tabela 'listens' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class Listen extends Model
{
    use HasFactory;
    protected $fillable = ['user_id'];

    /**
     * Obtém o item que foi ouvido.
     *
     * @return MorphTo - Relação com a tabela do item ouvido.
     *
     * @since 1.0
     * @version 1.0
     */
    public function listenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Obtém o utilizador que ouviu o item.
     *
     * @return BelongsTo - Relação com a tabela users.
     *
     * @since 1.0
     * @version 1.0
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
