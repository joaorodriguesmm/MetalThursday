<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gere a tabela 'email_permission_user' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class EmailPermissionUser extends Model
{
    protected $table = 'email_permission_user';

    protected $fillable = [
        'user_id',
        'email_permission_id',
    ];

    /**
     * Obtém o utilizador.
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
     * Obtém a permissão de e-mail.
     *
     * @return BelongsTo - Relação com a tabela email_permissions.
     *
     * @since 1.0
     * @version 1.0
     */
     public function emailpermission(): BelongsTo
    {
        return $this->belongsTo(EmailPermission::class, 'email_permission_id');
    }
}
