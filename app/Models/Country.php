<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gere a tabela 'countries' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class Country extends Model
{
    protected $table = 'countries';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'code'
    ];

    /**
     * Obtém as bandas que pertencem ao país.
     *
     * @return HasMany - Relação com a tabela bands.
     *
     * @since 1.0
     * @version 1.0
     */
    public function bands(): HasMany
    {
        return $this->hasMany(Band::class);
    }
}
