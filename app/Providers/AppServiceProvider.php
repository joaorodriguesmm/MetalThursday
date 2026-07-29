<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
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
 * @version 3.0.0
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Alias polimórfico persistido para os utilizadores notificáveis.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ALIAS_POLIMORFICO_UTILIZADOR =
        'utilizador';

    /**
     * Inicia os serviços e as configurações gerais da aplicação.
     *
     * O nome permanece em inglês por corresponder ao método definido pelo
     * ciclo de vida dos providers do Laravel.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function boot(): void
    {
        $this->configurarMapaPolimorfico();
        $this->configurarRequisitosPalavraPasse();
        $this->configurarPaginacao();
    }

    /**
     * Configura os aliases obrigatórios das relações polimórficas.
     *
     * Os aliases impedem que os valores persistidos dependam diretamente dos
     * namespaces PHP dos modelos.
     *
     * O alias do utilizador pertence ao mapa geral porque é utilizado pelas
     * notificações persistidas, mas não representa uma entidade de interação.
     *
     * @since 2.0.0
     *
     * @version 3.0.0
     */
    private function configurarMapaPolimorfico(): void
    {
        Relation::enforceMorphMap([
            ...TipoEntidadeInteracao::obterMapaPolimorfico(),

            self::ALIAS_POLIMORFICO_UTILIZADOR => Utilizador::class,
        ]);
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
}
