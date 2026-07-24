<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use App\View\Composers\NavigationComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * Regista e configura os serviços gerais da aplicação.
 *
 * O nome desta classe permanece em inglês por corresponder ao provider geral
 * convencional do Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Mapa dos aliases utilizados nas relações polimórficas.
     *
     * Estes aliases são persistidos nas colunas:
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
     * Inicia os serviços e as configurações gerais da aplicação.
     *
     * O nome permanece em inglês por corresponder ao método definido pelo
     * ciclo de vida dos providers do Laravel.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function boot(): void
    {
        $this->configurarMapaPolimorfico();
        $this->configurarRequisitosPalavraPasse();
        $this->configurarPaginacao();
        $this->registarCompositoresVistas();
    }

    /**
     * Configura os aliases obrigatórios das relações polimórficas.
     *
     * Os aliases impedem que os valores persistidos dependam diretamente dos
     * namespaces PHP dos modelos.
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

    /**
     * Configura os requisitos predefinidos das palavras-passe.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function configurarRequisitosPalavraPasse(): void
    {
        Password::defaults(
            static fn (): Password => RequisitosPalavraPasse::regra(),
        );
    }

    /**
     * Configura as vistas de paginação para Bootstrap 5.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function configurarPaginacao(): void
    {
        Paginator::useBootstrapFive();
    }

    /**
     * Regista os compositores das vistas da aplicação.
     *
     * O nome `NavigationComposer` será tratado durante a revisão dos
     * compositores das vistas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function registarCompositoresVistas(): void
    {
        View::composer(
            'layouts.navigation',
            NavigationComposer::class,
        );
    }
}
