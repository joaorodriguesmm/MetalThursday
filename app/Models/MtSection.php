<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Gere a tabela 'mt_sections' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class MtSection extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'mt_sections';

    protected $fillable = [
        'metal_thursday_id',
        'section_type_id',
        'title',
        'description',
        'band_id',
        'link',
        'embed_type',
        'year',
        'created_by',
        'updated_by',
    ];

    /**
     * Obtém a MetalThursday da Secção.
     *
     * @return BelongsTo - Relação com a tabela metal_thursdays.
     *
     * @since 1.0
     * @version 1.0
     */
    public function metalThursday(): BelongsTo
    {
        return $this->belongsTo(MetalThursday::class, 'metal_thursday_id');
    }

    /**
     * Obtém o tipo da Secção.
     *
     * @return BelongsTo - Relação com a tabela mt_section_types.
     *
     * @since 1.0
     * @version 1.0
     */
    public function sectionType(): BelongsTo
    {
        return $this->belongsTo(MtSectionType::class, 'section_type_id');
    }

    /**
     * Obtém a banda da Secção.
     *
     * @return BelongsTo - Relação com a tabela bands.
     *
     * @version 1.0
     * @since 1.0
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    /**
     * Obtém os comentários da Secção.
     *
     * @return MorphMany - Relação com a tabela comments.
     *
     * @since 1.0
     * @version 1.0
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Obtém as avaliações da Secção.
     *
     * @return MorphMany - Relação com a tabela ratings.
     *
     * @since 1.0
     * @version 1.0
     */
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    /**
     * Obtém os ouvintes da Secção.
     *
     * @return MorphMany - Relação com a tabela listens.
     *
     * @since 1.0
     * @version 1.0
     */
    public function listens()
    {
        return $this->morphMany(Listen::class, 'listenable');
    }

    /**
     * Define a relação para a avaliação do utilizador autenticado.
     *
     * @return MorphOne - Relação com a tabela ratings.
     *
     * @since 1.0
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
     * @version 1.0
     */
    protected function userRating(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->userRatingRelation->rating ?? 0,
        );
    }

    /**
     * Verifica se o utilizador autenticado ouviu esta secção.
     *
     * @return Attribute - Atributo virtual.
     *
     * @since 1.0
     * @version 1.0
     */
    protected function userHasListened(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->userListenRelation !== null,
        );
    }
}
