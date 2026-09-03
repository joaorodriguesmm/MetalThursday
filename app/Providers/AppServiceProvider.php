<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comum\Ligacao;
use App\Models\Musica\Artista;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Database\Eloquent\Model;
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
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Alias polimórfico persistido para os utilizadores.
     *
     * @since 2.0.0
     */
    private const ALIAS_POLIMORFICO_UTILIZADOR =
        'utilizador';

    /**
     * Alias polimórfico persistido para os artistas.
     *
     * @since 2.0.0
     */
    private const ALIAS_POLIMORFICO_ARTISTA =
        'artista';

    /**
     * Inicia os serviços e as configurações gerais da aplicação.
     *
     * @since 1.0.0
     */
    public function boot(): void
    {
        $this->configurarModelosEloquent();
        $this->configurarMapaPolimorfico();
        $this->configurarRelacoesDinamicas();
        $this->configurarRequisitosPalavraPasse();
        $this->configurarPaginacao();
    }

    /**
     * Ativa as verificações estritas dos modelos fora de produção.
     *
     * @since 2.0.0
     */
    private function configurarModelosEloquent(): void
    {
        Model::shouldBeStrict(
            ! $this->app->environment(
                'production',
            ),
        );
    }

    /**
     * Configura os aliases obrigatórios das relações polimórficas.
     *
     * @since 2.0.0
     */
    private function configurarMapaPolimorfico(): void
    {
        Relation::enforceMorphMap([
            ...TipoEntidadeInteracao::obterMapaPolimorfico(),

            self::ALIAS_POLIMORFICO_UTILIZADOR => Utilizador::class,
            self::ALIAS_POLIMORFICO_ARTISTA => Artista::class,
        ]);
    }

    /**
     * Acrescenta ao utilizador a relação genérica de ligações.
     *
     * A relação é registada dinamicamente para não acoplar a primeira fase da
     * infraestrutura de ligações à interface do perfil de utilizador. Quando
     * essa interface for implementada, a relação poderá passar para o próprio
     * modelo sem alterar o contrato persistido.
     *
     * @since 2.0.0
     */
    private function configurarRelacoesDinamicas(): void
    {
        Utilizador::resolveRelationUsing(
            'ligacoes',
            static fn (
                Utilizador $utilizador,
            ) => $utilizador
                ->morphMany(
                    Ligacao::class,
                    'ligavel',
                    'tipo_ligavel',
                    'ligavel_id',
                )
                ->orderBy(
                    'ordem',
                )
                ->orderBy(
                    'id',
                ),
        );
    }

    /**
     * Configura os requisitos predefinidos das palavras-passe.
     *
     * @since 2.0.0
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
     */
    private function configurarPaginacao(): void
    {
        Paginator::useBootstrapFive();
    }
}
