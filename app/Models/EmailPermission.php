<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Gere a tabela 'email_permissions' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class EmailPermission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'description'
    ];

    /**
     * Obtém os utilizadores com a permissão de e-mail.
     *
     * @return BelongsToMany - Relação com a tabela users.
     *
     * @since 1.0
     * @version 1.0
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'email_permission_user');
    }
}
