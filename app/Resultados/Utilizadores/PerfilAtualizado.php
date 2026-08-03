<?php

declare(strict_types=1);

namespace App\Resultados\Utilizadores;

use App\Models\Autenticacao\Utilizador;

/**
 * Representa o resultado da atualização de um perfil de utilizador.
 *
 * O resultado contém o utilizador atualizado e indica se a alteração efetuada
 * modificou o respetivo endereço de e-mail.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final readonly class PerfilAtualizado
{
    /**
     * Cria o resultado da atualização do perfil.
     *
     * @param  Utilizador  $utilizador  Utilizador atualizado.
     * @param  bool  $emailAlterado  Indica se o endereço de e-mail foi alterado.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function __construct(
        private Utilizador $utilizador,
        private bool $emailAlterado,
    ) {}

    /**
     * Obtém o utilizador atualizado.
     *
     * @return Utilizador Utilizador atualizado.
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
     * @return bool Verdadeiro quando o endereço de e-mail mudou.
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
