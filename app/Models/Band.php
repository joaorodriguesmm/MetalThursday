<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Gere a tabela 'bands' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class Band extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'name',
        'country_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Obtém o país da banda.
     *
     * @return BelongsTo - Relação com a tabela countries.
     *
     * @since 1.0
     * @version 1.0
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Obtém os géneros da banda.
     *
     * @return BelongsToMany - Relação com a tabela genres.
     *
     * @since 1.0
     * @version 1.0
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }
}
