<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Artista;

/**
 * Define as regras de autorização aplicáveis aos artistas.
 *
 * Qualquer utilizador autenticado pode consultar e criar artistas. A alteração
 * e eliminação ficam limitadas ao utilizador que criou o registo, exceto para
 * o superadministrador.
 *
 * @since 1.0.0
 */
final class PoliticaArtista
{
    /**
     * Autoriza antecipadamente todas as ações do superadministrador.
     *
     * O nome permanece em inglês por corresponder ao método especial
     * reconhecido pelo sistema de autorização do Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $capacidade  Capacidade que está a ser verificada.
     * @return bool|null Verdadeiro para o superadministrador ou nulo para
     *                   continuar a avaliação normal.
     *
     * @since 1.0.0
     */
    public function before(
        Utilizador $utilizador,
        string $capacidade,
    ): ?bool {
        return $utilizador->eSuperAdministrador()
            ? true
            : null;
    }

    /**
     * Determina se o utilizador pode consultar a lista de artistas.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 1.0.0
     */
    public function viewAny(
        Utilizador $utilizador,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode consultar um artista.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Artista  $artista  Artista consultado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 1.0.0
     */
    public function view(
        Utilizador $utilizador,
        Artista $artista,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode criar um artista.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 1.0.0
     */
    public function create(
        Utilizador $utilizador,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode alterar um artista.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Artista  $artista  Artista a alterar.
     * @return bool Verdadeiro quando o utilizador criou o artista.
     *
     * @since 1.0.0
     */
    public function update(
        Utilizador $utilizador,
        Artista $artista,
    ): bool {
        return $this->utilizadorCriouArtista(
            $utilizador,
            $artista,
        );
    }

    /**
     * Determina se o utilizador pode eliminar um artista.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Artista  $artista  Artista a eliminar.
     * @return bool Verdadeiro quando o utilizador criou o artista.
     *
     * @since 1.0.0
     */
    public function delete(
        Utilizador $utilizador,
        Artista $artista,
    ): bool {
        return $this->utilizadorCriouArtista(
            $utilizador,
            $artista,
        );
    }

    /**
     * Determina se o utilizador pode restaurar um artista eliminado.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Artista  $artista  Artista a restaurar.
     * @return bool Verdadeiro quando o utilizador criou o artista.
     *
     * @since 2.0.0
     */
    public function restore(
        Utilizador $utilizador,
        Artista $artista,
    ): bool {
        return $this->utilizadorCriouArtista(
            $utilizador,
            $artista,
        );
    }

    /**
     * Determina se o utilizador pode eliminar definitivamente um artista.
     *
     * A eliminação definitiva fica reservada ao superadministrador. Como o
     * método `before` já o autoriza, os restantes utilizadores recebem falso.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Artista  $artista  Artista a eliminar definitivamente.
     * @return bool Falso para utilizadores comuns.
     *
     * @since 2.0.0
     */
    public function forceDelete(
        Utilizador $utilizador,
        Artista $artista,
    ): bool {
        return false;
    }

    /**
     * Determina se um artista foi criado pelo utilizador indicado.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Artista  $artista  Artista verificado.
     * @return bool Verdadeiro quando os identificadores coincidem.
     *
     * @since 2.0.0
     */
    private function utilizadorCriouArtista(
        Utilizador $utilizador,
        Artista $artista,
    ): bool {
        $identificadorUtilizador =
            $utilizador->getKey();

        $identificadorCriador =
            $artista->criado_por_id;

        return is_numeric($identificadorUtilizador)
            && is_numeric($identificadorCriador)
            && (int) $identificadorUtilizador
            === (int) $identificadorCriador;
    }
}
