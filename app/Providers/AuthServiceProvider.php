<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\Musica\Banda;
use App\Models\Musica\Genero;
use App\Policies\PoliticaBanda;
use App\Policies\PoliticaComentario;
use App\Policies\PoliticaEdicao;
use App\Policies\PoliticaGenero;
use App\Policies\PoliticaMetalThursday;
use App\Policies\PoliticaUtilizador;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Regista as políticas de autorização da aplicação.
 *
 * O nome desta classe permanece em inglês por corresponder ao provider
 * convencional de autorização do Laravel.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class AuthServiceProvider extends ServiceProvider
{
    /**
     * Associa os modelos às respetivas políticas de autorização.
     *
     * O nome da propriedade permanece em inglês porque pertence ao contrato
     * disponibilizado pelo provider de autorização do Laravel.
     *
     * @var array<class-string, class-string>
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected $policies = [
        Utilizador::class => PoliticaUtilizador::class,

        Banda::class => PoliticaBanda::class,

        Comentario::class => PoliticaComentario::class,

        Genero::class => PoliticaGenero::class,

        Edicao::class => PoliticaEdicao::class,

        MetalThursday::class => PoliticaMetalThursday::class,
    ];
}
