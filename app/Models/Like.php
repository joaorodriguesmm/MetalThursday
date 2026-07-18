<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gere a tabela 'likes' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comment_id',
    ];

    /**
     * Obtém o comentário que foi gostado.
     *
     * @return BelongsTo - Relação com a tabela comments.
     *
     * @since 1.0
     * @version 1.0
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Obtém o utilizador que gostou do comentário.
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
