<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;

/**
 * Define as regras de autorização aplicáveis aos comentários.
 *
 * Qualquer utilizador autenticado pode consultar e publicar comentários.
 * Apenas o respetivo autor pode alterar ou eliminar um comentário, exceto o
 * superadministrador, que é autorizado antecipadamente.
 *
 * @since 1.0.0
 */
final class PoliticaComentario
{
    /**
     * Autoriza antecipadamente todas as ações do superadministrador.
     *
     * O nome permanece em inglês porque corresponde ao método especial
     * reconhecido pelo sistema de autorização do Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $capacidade  Capacidade verificada.
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
     * Determina se o utilizador pode consultar a lista de comentários.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 2.0.0
     */
    public function viewAny(
        Utilizador $utilizador,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode consultar um comentário.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Comentario  $comentario  Comentário consultado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 2.0.0
     */
    public function view(
        Utilizador $utilizador,
        Comentario $comentario,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode publicar um comentário.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 2.0.0
     */
    public function create(
        Utilizador $utilizador,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode alterar um comentário.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Comentario  $comentario  Comentário a alterar.
     * @return bool Verdadeiro quando o utilizador é o autor.
     *
     * @since 1.0.0
     */
    public function update(
        Utilizador $utilizador,
        Comentario $comentario,
    ): bool {
        return $this->utilizadorEAutor(
            $utilizador,
            $comentario,
        );
    }

    /**
     * Determina se o utilizador pode eliminar um comentário.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Comentario  $comentario  Comentário a eliminar.
     * @return bool Verdadeiro quando o utilizador é o autor.
     *
     * @since 1.0.0
     */
    public function delete(
        Utilizador $utilizador,
        Comentario $comentario,
    ): bool {
        return $this->utilizadorEAutor(
            $utilizador,
            $comentario,
        );
    }

    /**
     * Determina se o utilizador indicado é o autor do comentário.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Comentario  $comentario  Comentário verificado.
     * @return bool Verdadeiro quando os identificadores coincidem.
     *
     * @since 2.0.0
     */
    private function utilizadorEAutor(
        Utilizador $utilizador,
        Comentario $comentario,
    ): bool {
        $identificadorUtilizador =
            $utilizador->getKey();

        $identificadorAutor =
            $comentario->utilizador_id;

        return is_numeric($identificadorUtilizador)
            && is_numeric($identificadorAutor)
            && (int) $identificadorUtilizador
            === (int) $identificadorAutor;
    }
}
