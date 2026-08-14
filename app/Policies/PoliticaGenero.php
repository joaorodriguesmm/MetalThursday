<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Genero;

/**
 * Define as regras de autorização aplicáveis aos géneros musicais.
 *
 * Qualquer utilizador autenticado pode consultar e criar géneros. A alteração,
 * eliminação e restauração ficam limitadas ao utilizador que criou o registo,
 * exceto para o superadministrador.
 *
 * @since 1.0.0
 */
final class PoliticaGenero
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
     * Determina se o utilizador pode consultar a lista de géneros.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
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
     * Determina se o utilizador pode consultar um género.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Genero  $genero  Género consultado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 1.0.0
     */
    public function view(
        Utilizador $utilizador,
        Genero $genero,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode criar um género.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
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
     * Determina se o utilizador pode alterar um género.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Genero  $genero  Género a alterar.
     * @return bool Verdadeiro quando o utilizador criou o género.
     *
     * @since 1.0.0
     */
    public function update(
        Utilizador $utilizador,
        Genero $genero,
    ): bool {
        return $this->utilizadorCriouGenero(
            $utilizador,
            $genero,
        );
    }

    /**
     * Determina se o utilizador pode eliminar um género.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Genero  $genero  Género a eliminar.
     * @return bool Verdadeiro quando o utilizador criou o género.
     *
     * @since 1.0.0
     */
    public function delete(
        Utilizador $utilizador,
        Genero $genero,
    ): bool {
        return $this->utilizadorCriouGenero(
            $utilizador,
            $genero,
        );
    }

    /**
     * Determina se o utilizador pode restaurar um género eliminado.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Genero  $genero  Género a restaurar.
     * @return bool Verdadeiro quando o utilizador criou o género.
     *
     * @since 2.0.0
     */
    public function restore(
        Utilizador $utilizador,
        Genero $genero,
    ): bool {
        return $this->utilizadorCriouGenero(
            $utilizador,
            $genero,
        );
    }

    /**
     * Determina se o utilizador pode eliminar definitivamente um género.
     *
     * A eliminação definitiva fica reservada ao superadministrador. O método
     * `before` autoriza antecipadamente esse utilizador.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Genero  $genero  Género a eliminar definitivamente.
     * @return bool Falso para os restantes utilizadores.
     *
     * @since 2.0.0
     */
    public function forceDelete(
        Utilizador $utilizador,
        Genero $genero,
    ): bool {
        return false;
    }

    /**
     * Determina se o género foi criado pelo utilizador indicado.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Genero  $genero  Género verificado.
     * @return bool Verdadeiro quando os identificadores coincidem.
     *
     * @since 2.0.0
     */
    private function utilizadorCriouGenero(
        Utilizador $utilizador,
        Genero $genero,
    ): bool {
        $identificadorUtilizador =
            $utilizador->getKey();

        $identificadorCriador =
            $genero->criado_por_id;

        return is_numeric(
            $identificadorUtilizador,
        )
            && is_numeric(
                $identificadorCriador,
            )
            && (int) $identificadorUtilizador
            === (int) $identificadorCriador;
    }
}
