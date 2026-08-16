<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Comunicacao;

use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos do modelo das permissões de e-mail.
 *
 * @since 2.0.0
 */
final class PermissaoEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a normalização dos campos textuais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function normaliza_campos_textuais(): void
    {
        $permissao = new PermissaoEmail;

        $permissao->identificador =
            '  AVISOS_SEMANAIS  ';

        $permissao->nome =
            '  Avisos   semanais  ';

        $permissao->descricao =
            '  Recebe   os avisos da semana.  ';

        self::assertSame(
            'avisos_semanais',
            $permissao->identificador,
        );

        self::assertSame(
            'Avisos semanais',
            $permissao->nome,
        );

        self::assertSame(
            'Recebe os avisos da semana.',
            $permissao->descricao,
        );
    }

    /**
     * Confirma que o identificador não converte valores não textuais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_identificador_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $permissao = new PermissaoEmail;

        $permissao->identificador = 123;
    }

    /**
     * Confirma que o nome não converte valores não textuais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_nome_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $permissao = new PermissaoEmail;

        $permissao->nome = 123;
    }

    /**
     * Confirma que a descrição não converte valores não textuais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_descricao_nao_textual(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $permissao = new PermissaoEmail;

        $permissao->descricao = 123;
    }

    /**
     * Confirma que o nome rejeita caracteres de controlo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_caracteres_de_controlo_no_nome(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $permissao = new PermissaoEmail;

        $permissao->nome =
            "Avisos\nsemanais";
    }

    /**
     * Confirma que a descrição rejeita caracteres de controlo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_caracteres_de_controlo_na_descricao(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $permissao = new PermissaoEmail;

        $permissao->descricao =
            "Descrição\tinválida";
    }

    /**
     * Confirma que a base de dados rejeita a ordem zero.
     *
     * @since 2.0.0
     */
    #[Test]
    public function base_de_dados_rejeita_ordem_zero(): void
    {
        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'permissoes_email',
        )->insert([
            'identificador' => 'ordem_invalida',

            'nome' => 'Ordem inválida',

            'descricao' => 'Descrição válida.',

            'ordem' => 0,

            'created_at' => now(),

            'updated_at' => now(),
        ]);
    }
}
