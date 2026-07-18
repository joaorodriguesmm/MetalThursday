<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/**
 * Gere a tabela 'comments' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'commentable_id',
        'commentable_type',
        'parent_id',
    ];

    protected $with = ['user'];

    /**
     * Obtém o utilizador que fez o comentário.
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

    /**
     * Obtém o objeto comentado.
     *
     * @return MorphTo - Relação com a tabela do objeto comentado.
     *
     * @since 1.0
     * @version 1.0
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Obtém os likes do comentário.
     *
     * @return HasMany - Relação com a tabela likes.
     *
     * @since 1.0
     * @version 1.0
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class, 'comment_id');
    }

    /**
     * Obtém o comentário pai.
     *
     * @return BelongsTo - Relação com a tabela comments.
     *
     * @since 1.0
     * @version 1.0
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Obtém os comentários filhos.
     *
     * @return HasMany - Relação com a tabela comments.
     *
     * @since 1.0
     * @version 1.0
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Obtém o número de likes do comentário.
     *
     * @return Attribute - Atributo virtual.
     *
     * @since 1.0
     * @version 1.0
     */
    protected function likesCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->likes->count(),
        );
    }

    /**
     * Obtém o MetalThursday ao qual o comentário pertence.
     *
     * @return BelongsTo - Relação com a tabela metal_thursdays.
     *
     * @since 1.0
     * @version 1.0
     */
    public function metalThursday(): BelongsTo
    {
        if ($this->commentable instanceof MetalThursday) {
            return $this->commentable();
        }

        if ($this->commentable instanceof MtSection) {
            return $this->commentable->metalThursday();
        }

        return new BelongsTo($this->newQuery(), $this, '', '', '');
    }

    /**
     * Obtém se o comentário foi feito pelo utilizador autenticado.
     *
     * @return Attribute - Atributo virtual.
     *
     * @since 1.0
     * @version 1.0
     */
    protected function isLikedByUser(): Attribute
    {
        return Attribute::make(
            get: fn () => Auth::check() ? $this->likes->contains('user_id', Auth::id()) : false
        );
    }
}
