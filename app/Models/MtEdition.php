<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Gere a tabela 'mt_editions' da base de dados.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class MtEdition extends Model
{
    use Blameable, SoftDeletes;

    protected $table = 'mt_editions';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'compilation_link',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $appends = ['display_text'];

    /**
     * Obtém as MetalThursdays da Edição.
     *
     * @return HasMany - Relação com a tabela metal_thursdays.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function metalThursdays(): HasMany
    {
        return $this->hasMany(MetalThursday::class, 'edition_id');
    }

    /**
     * Obtém a edição com a formatação correta.
     *
     * @return Attribute - Atributo virtual.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected function displayText(): Attribute
    {
        return Attribute::make(
            get: fn () => sprintf(
                '%s - (%s - %s)',
                $this->name,
                $this->start_date->format('d/m/Y'),
                $this->end_date ? $this->end_date->format('d/m/Y') : 'Atualmente'
            ),
        );
    }

    public function rankings()
    {
        return $this->hasMany(EditionRanking::class, 'edition_id');
    }
}
