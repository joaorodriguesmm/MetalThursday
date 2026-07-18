<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Gere a tabela 'ratings' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rating'
    ];

    /**
     * Obtém o objeto avaliado.
     *
     * @return MorphTo - Relação com a tabela do objeto avaliado.
     *
     * @since 1.0
     * @version 1.0
     */
    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Obtém o utilizador que fez a avaliação.
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
