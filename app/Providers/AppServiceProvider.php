<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Regras\Autenticacao\PoliticaPalavraPasse;
use App\View\Composers\NavigationComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * Define e inicia os serviços gerais da aplicação.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Mapa dos aliases utilizados nas relações polimórficas.
     *
     * Os aliases são persistidos nas colunas:
     *
     * - `tipo_comentavel`;
     * - `tipo_avaliavel`;
     * - `tipo_audivel`.
     *
     * @var array<string, class-string<Model>>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MAPA_TIPOS_POLIMORFICOS = [
        'metal_thursday' => MetalThursday::class,
        'seccao_metal_thursday' => SeccaoMetalThursday::class,
    ];

    /**
     * Regista os serviços da aplicação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function register(): void {}

    /**
     * Inicia os serviços e configurações gerais da aplicação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function boot(): void
    {
        $this->configurarMapaPolimorfico();

        Password::defaults(
            static fn (): Password => PoliticaPalavraPasse::regra(),
        );

        Paginator::useBootstrap();

        View::composer(
            'layouts.navigation',
            NavigationComposer::class,
        );
    }

    /**
     * Configura os aliases obrigatórios das relações polimórficas.
     *
     * A utilização de aliases impede que as tabelas dependam diretamente dos
     * namespaces PHP dos modelos, permitindo reorganizar o código sem alterar
     * os valores persistidos na base de dados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function configurarMapaPolimorfico(): void
    {
        Relation::enforceMorphMap(
            self::MAPA_TIPOS_POLIMORFICOS,
        );
    }
}
