<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável por adicionar um papel explícito aos
 * utilizadores.
 *
 * Esta alteração substitui progressivamente a dependência do identificador
 * fixo do superadministrador por uma propriedade de autorização persistida.
 *
 * @return Migration - Migração dos papéis dos utilizadores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Papel atribuído por omissão aos utilizadores.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PAPEL_UTILIZADOR = 'utilizador';

    /**
     * Papel destinado aos administradores da aplicação.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PAPEL_ADMINISTRADOR = 'administrador';

    /**
     * Papel com acesso global à aplicação.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PAPEL_SUPER_ADMINISTRADOR = 'super_administrador';

    /**
     * Papéis permitidos pela base de dados.
     *
     * @var array<int, string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PAPEIS_PERMITIDOS = [
        self::PAPEL_UTILIZADOR,
        self::PAPEL_ADMINISTRADOR,
        self::PAPEL_SUPER_ADMINISTRADOR,
    ];

    /**
     * Nome do índice utilizado nas consultas por papel.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const NOME_INDICE_PAPEL = 'utilizadores_papel_indice';

    /**
     * Adiciona o papel aos utilizadores e migra o superadministrador atual.
     *
     * Os utilizadores existentes recebem inicialmente o papel de utilizador.
     * O utilizador com o identificador atualmente reconhecido pelas policies
     * como superadministrador recebe o novo papel explícito.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::table(
            'users',
            function (Blueprint $tabela): void {
                $tabela
                    ->enum(
                        'papel',
                        self::PAPEIS_PERMITIDOS,
                    )
                    ->default(self::PAPEL_UTILIZADOR)
                    ->after('invite_code');

                $tabela->index(
                    'papel',
                    self::NOME_INDICE_PAPEL,
                );
            },
        );

        /*
         * A utilização direta do construtor de consultas evita depender do
         * modelo User, que poderá sofrer alterações posteriores durante a
         * refatoração.
         */
        DB::table('users')
            ->where('id', 1)
            ->update([
                'papel' => self::PAPEL_SUPER_ADMINISTRADOR,
            ]);
    }

    /**
     * Remove o papel dos utilizadores.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::table(
            'users',
            function (Blueprint $tabela): void {
                $tabela->dropIndex(
                    self::NOME_INDICE_PAPEL,
                );

                $tabela->dropColumn('papel');
            },
        );
    }
};
