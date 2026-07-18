<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


/**
 * Gere a tabela 'genres' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class Genre extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    /**
     * Obtém os géneros pais.
     *
     * @return BelongsToMany - Relação com a tabela genre_parent_genre.
     *
     * @since 1.0
     * @version 1.0
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'genre_parent_genre', 'genre_id', 'parent_genre_id');
    }

    /**
     * Obtém os géneros filhos.
     *
     * @return BelongsToMany - Relação com a tabela genre_parent_genre.
     *
     * @since 1.0
     * @version 1.0
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'genre_parent_genre', 'parent_genre_id', 'genre_id');
    }

    /**
     * Obtém as bandas que pertencem ao género.
     *
     * @return BelongsToMany - Relação com a tabela bands.
     *
     * @since 1.0
     * @version 1.0
     */
    public function bands(): BelongsToMany
    {
        return $this->belongsToMany(Band::class);
    }

    /**
     * Obtém todos os géneros filhos, de forma recursiva.
     *
     * @return BelongsToMany - Relação com a tabela genre_parent_genre.
     *
     * @since 1.0
     * @version 1.0
     */
    public function allChildren(): BelongsToMany
    {
        return $this->children()->with('allChildren');
    }
}
