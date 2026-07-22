<?php

declare(strict_types=1);

namespace App\Resultados\Utilizadores;

use App\Models\Autenticacao\Utilizador;

/**
 * Representa o resultado da atualização de um perfil.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final readonly class PerfilAtualizado
{
    /**
     * Utilizador atualizado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private Utilizador $utilizador;

    /**
     * Indica se o endereço de e-mail foi alterado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private bool $emailAlterado;

    /**
     * Cria o resultado da atualização.
     *
     * @param  Utilizador  $utilizador  - Utilizador atualizado.
     * @param  bool  $emailAlterado  - Indicação de alteração do e-mail.
     * @return void
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        Utilizador $utilizador,
        bool $emailAlterado,
    ) {
        $this->utilizador = $utilizador;
        $this->emailAlterado = $emailAlterado;
    }

    /**
     * Obtém o utilizador atualizado.
     *
     * @return Utilizador - Utilizador atualizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterUtilizador(): Utilizador
    {
        return $this->utilizador;
    }

    /**
     * Determina se o endereço de e-mail foi alterado.
     *
     * @return bool - Verdadeiro quando o e-mail mudou.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function emailFoiAlterado(): bool
    {
        return $this->emailAlterado;
    }
}
