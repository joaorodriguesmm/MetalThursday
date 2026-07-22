<?php

namespace App\Providers;

use App\Models\Band;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\MetalThursday;
use App\Models\MtEdition;
use App\Policies\BandPolicy;
use App\Policies\CommentPolicy;
use App\Policies\GenrePolicy;
use App\Policies\MetalThursdayPolicy;
use App\Policies\MtEditionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Define as permissões dependentes de autenticação para executar ações na aplicação.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Define as permissões para ações nos modelos.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    protected $policies = [
        Band::class => BandPolicy::class,
        Comment::class => CommentPolicy::class,
        Genre::class => GenrePolicy::class,
        MetalThursday::class => MetalThursdayPolicy::class,
        MtEdition::class => MtEditionPolicy::class,
    ];

    /**
     * Regista os serviços de autenticação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function boot(): void
    {
        //
    }
}
