<?php

namespace App\Models;

use App\Models\Autenticacao\Utilizador;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Gere a tabela 'metal_thursdays' da base de dados.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class MetalThursday extends Model
{
    use Blameable, SoftDeletes;

    protected $table = 'metal_thursdays';

    protected $fillable = [
        'name',
        'date',
        'edition_id',
        'author_id',
        'next_nominee_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Obtém a edição da MetalThursday.
     *
     * @return BelongsTo - Relação com a tabela mt_editions.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(MtEdition::class, 'edition_id');
    }

    /**
     * Obtém o autor da MetalThursday.
     *
     * @return BelongsTo - Relação com a tabela users.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'author_id');
    }

    /**
     * Obtém o próximo nomeado da MetalThursday.
     *
     * @return BelongsTo - Relação com a tabela users.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function nextNominee(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'next_nominee_id');
    }

    /**
     * Obtém as secções da MetalThursday.
     *
     * @return HasMany - Relação com a tabela mt_sections.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function sections(): HasMany
    {
        return $this->hasMany(MtSection::class, 'metal_thursday_id');
    }

    /**
     * Obtém o número da semana da MetalThursday.
     *
     * @return int|null - Número da semana da MetalThursday ou null.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function getWeekNumberInEditionAttribute(): ?int
    {
        if (! $this->relationLoaded('edition') || ! $this->edition) {
            return null;
        }

        return $this->edition->metalThursdays()
            ->orderBy('date', 'asc')
            ->pluck('id')
            ->search($this->id) + 1;
    }

    /**
     * Obtém os comentários da MetalThursday.
     *
     * @return MorphMany - Relação com a tabela comments.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Obtém as avaliações da MetalThursday.
     *
     * @return MorphMany - Relação com a tabela ratings.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    /**
     * Obtém os ouvintes da MetalThursday.
     *
     * @return MorphMany - Relação com a tabela listens.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function listens(): MorphMany
    {
        return $this->morphMany(Listen::class, 'listenable');
    }

    /**
     * Define a relação para a avaliação do utilizador autenticado.
     *
     * @return MorphOne - Relação com a tabela ratings.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function userRatingRelation(): MorphOne
    {
        return $this->morphOne(Rating::class, 'rateable')->where('user_id', Auth::id());
    }

    /**
     * Define a relação para o "ouvido" do utilizador autenticado.
     *
     * @return MorphOne - Relação com a tabela listens.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function userListenRelation(): MorphOne
    {
        return $this->morphOne(Listen::class, 'listenable')->where('user_id', Auth::id());
    }

    /**
     * Obtém a avaliação do utilizador autenticado para esta secção.
     *
     * @return Attribute - Atributo virtual.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected function userRating(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->userRatingRelation->rating ?? 0,
        );
    }

    /**
     * Verifica se o utilizador autenticado ouviu esta secção.
     *
     * @return Attribute - Atributo virtual.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected function userHasListened(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->userListenRelation !== null,
        );
    }
}
