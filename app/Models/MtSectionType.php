<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Gere a tabela 'mt_section_types' da base de dados.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class MtSectionType extends Model
{
    protected $table = 'mt_section_types';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];
}
