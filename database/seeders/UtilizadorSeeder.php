<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Regista o superadministrador inicial da aplicação.
 *
 * O nome `Seeder` permanece em inglês por corresponder à convenção utilizada
 * pelo Laravel.
 *
 * Este seeder não cria utilizadores comuns nem utilizadores associados a
 * convites.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class UtilizadorSeeder extends Seeder
{
    /**
     * Nome apresentado para o superadministrador inicial.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const NOME_SUPERADMINISTRADOR =
        'Administrador';

    /**
     * Endereço de email do superadministrador inicial.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const EMAIL_SUPERADMINISTRADOR =
        'metal-thursday@joaorodrigues-multimedia.pt';

    /**
     * Comprimento da palavra-passe temporária gerada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_PALAVRA_PASSE =
        32;

    /**
     * Regista ou atualiza o superadministrador inicial.
     *
     * Quando o utilizador ainda não existe, é gerada uma palavra-passe
     * temporária aleatória, apresentada uma única vez no terminal.
     *
     * Quando o utilizador já existe, o nome, o papel e a verificação do email
     * são atualizados, mas a palavra-passe existente não é substituída.
     *
     * O nome `run` permanece em inglês por corresponder ao método
     * convencional dos seeders do Laravel.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function run(): void
    {
        $utilizador =
            Utilizador::query()->firstOrNew(
                [
                    'email' => self::EMAIL_SUPERADMINISTRADOR,
                ],
            );

        $palavraPasseAtual =
            $utilizador->getRawOriginal(
                'password',
            );

        $necessitaPalavraPasse =
            ! $utilizador->exists
            || ! is_string(
                $palavraPasseAtual,
            )
            || trim(
                $palavraPasseAtual,
            ) === '';

        $palavraPasseTemporaria =
            null;

        $utilizador->fill(
            [
                'nome' => self::NOME_SUPERADMINISTRADOR,

                'email' => self::EMAIL_SUPERADMINISTRADOR,

                'papel' => PapelUtilizador::SuperAdministrador,
            ],
        );

        if ($utilizador->email_verified_at === null) {
            $utilizador->email_verified_at =
                now();
        }

        if ($necessitaPalavraPasse) {
            $palavraPasseTemporaria =
                Str::password(
                    self::COMPRIMENTO_PALAVRA_PASSE,
                );

            $utilizador->password =
                $palavraPasseTemporaria;
        }

        $utilizador->save();

        if ($palavraPasseTemporaria === null) {
            $this
                ->command
                ?->info(
                    'O superadministrador já existia e foi atualizado sem alterar a palavra-passe.',
                );

            return;
        }

        $this
            ->command
            ?->newLine();

        $this
            ->command
            ?->warn(
                'Superadministrador criado. Guarda os dados seguintes antes de fechares o terminal.',
            );

        $this
            ->command
            ?->line(
                sprintf(
                    'Email: %s',
                    self::EMAIL_SUPERADMINISTRADOR,
                ),
            );

        $this
            ->command
            ?->line(
                sprintf(
                    'Palavra-passe temporária: %s',
                    $palavraPasseTemporaria,
                ),
            );

        $this
            ->command
            ?->warn(
                'Altera a palavra-passe depois do primeiro início de sessão.',
            );

        $this
            ->command
            ?->newLine();
    }
}
