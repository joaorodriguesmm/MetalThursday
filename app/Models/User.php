<?php

namespace App\Models;

use App\Notifications\CustomResetPasswordNotification;
use App\Notifications\CustomVerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * Gere a tabela 'users' da base de dados.
 *
 * @since 1.0
 * @version 1.0
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'invite_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = [
        'photo_url',
        'initials',
        'first_name',
    ];

    /**
     * Envia a notificação de verificação de email.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomVerifyEmailNotification);
    }

    /**
     * Envia a notificação de reposição de password.
     *
     * @param mixed $token - Token de reposição de password.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function sendPasswordResetNotification(mixed $token): void
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    /**
     * Obtém a URL da foto do utilizador.
     *
     * @return string|null - URL da foto do utilizador ou null.
     *
     * @since 1.0
     * @version 1.0
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    /**
     * Obtém as iniciais do nome.
     *
     * @return string - Iniciais do nome.
     *
     * @since 1.0
     * @version 1.0
     */
    public function getInitialsAttribute(): string
    {
        if (empty($this->name)) {
            return '?';
        }
        $nameParts = explode(' ', trim($this->name));

        $initials = '';

        if (count($nameParts) > 0) {
            $initials .= strtoupper(substr($nameParts[0], 0, 1));
        }

        if (count($nameParts) > 1) {
            $initials .= strtoupper(substr(end($nameParts), 0, 1));
        }

        return $initials ?: strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Obtém o primeiro nome do utilizador.
     *
     * @return string - Primeiro nome do utilizador.
     *
     * @since 1.0
     * @version 1.0
     */
    public function getFirstNameAttribute(): string
    {
        if (empty($this->name)) {
            return 'Utilizador';
        }
        return explode(' ', trim($this->name))[0] ?? '';
    }

    /**
     * Obtém as permissões de email do utilizador.
     *
     * @return BelongsToMany - Relação com a tabela email_permissions.
     *
     * @since 1.0
     * @version 1.0
     */
    public function emailPermissions(): BelongsToMany
    {
        return $this->belongsToMany(EmailPermission::class, 'email_permission_user');
    }

    /**
     * Obtém se o utilizador tem a permissão de email.
     *
     * @param string $slug - Slug da permissão.
     * @return bool - Verdadeiro se o utilizador tiver a permissão, falso caso contrário.
     *
     * @since 1.0
     * @version 1.0
     */
    public function hasEmailPermission(string $slug): bool
    {
        return $this->emailPermissions->contains('slug', $slug);
    }

    /**
     * Obtém os utilizadores que podem ser selecionados.
     *
     * @param Builder $query - Query a ser modificada.
     * @return Builder - Query modificada.
     *
     * @since 1.0
     * @version 1.0
     */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('id', '!=', 1)->orderBy('name');
    }

    /**
     * Obtém as edições que o utilizador criou.
     *
     * @return HasMany - Relação com a tabela mt_editions.
     *
     * @since 1.0
     * @version 1.0
     */
    public function editions(): HasMany
    {
        return $this->hasMany(MtEdition::class);
    }

    /**
     * Obtém as MetalThursdays em que o utilizador foi o autor.
     *
     * @return HasMany - Relação com a tabela metal_thursdays.
     *
     * @since 1.0
     * @version 1.0
     */
    public function metalThursdaysAsAuthor(): HasMany
    {
        return $this->hasMany(MetalThursday::class, 'author_id');
    }

    /**
     * Obtém as MetalThursdays em que o utilizador foi nomeado.
     *
     * @return HasMany - Relação com a tabela metal_thursdays.
     *
     * @since 1.0
     * @version 1.0
     */
    public function metalThursdaysAsNominee(): HasMany
    {
        return $this->hasMany(MetalThursday::class, 'next_nominee_id');
    }

    /**
     * Obtém as MetalThursdays que o utilizador criou.
     *
     * @return HasMany - Relação com a tabela metal_thursdays.
     *
     * @since 1.0
     * @version 1.0
     */
    public function metalThursdaysAsCreator(): HasMany
    {
        return $this->hasMany(MetalThursday::class, 'created_by');
    }

    /**
     * Obtém os comentários que o utilizador fez.
     *
     * @return HasMany - Relação com a tabela comments.
     *
     * @since 1.0
     * @version 1.0
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Obtém os likes que o utilizador fez.
     *
     * @return HasMany - Relação com a tabela likes.
     *
     * @since 1.0
     * @version 1.0
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }
}
