<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\Interacoes\DenunciaComentario;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use App\Notifications\NotificacaoNovaDenunciaComentario;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Impede a entrega de notificações pendentes que perderam a validade.
 *
 * @since 2.0.0
 */
final class NotificacoesPendentesObsoletasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissaoEmailSeeder::class)->run();
    }

    #[Test]
    public function interacao_nao_e_entregue_a_utilizador_nao_envolvido(): void
    {
        $autor = Utilizador::factory()->create();
        $causador = Utilizador::factory()->create();
        $terceiro = Utilizador::factory()->create();

        foreach ([$autor, $causador, $terceiro] as $utilizador) {
            $this->atribuirPermissaoGlobal($utilizador);
        }

        $publicacao = MetalThursday::factory()
            ->comAutor($autor)
            ->comProximoNomeado($causador)
            ->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $causador->id,
            'conteudo' => 'Comentário para validar os destinatários.',
            'comentario_pai_id' => null,
        ]);

        $notificacao = new NotificacaoInteracaoUtilizador(
            $publicacao,
            $causador,
            'comentou',
            $comentario,
        );

        foreach (['database', 'mail'] as $canal) {
            self::assertTrue($notificacao->shouldSend($autor, $canal));
            self::assertFalse($notificacao->shouldSend($terceiro, $canal));
            self::assertFalse($notificacao->shouldSend($causador, $canal));
        }
    }

    #[Test]
    public function autor_nao_recebe_notificacao_do_proprio_comentario(): void
    {
        $autor = Utilizador::factory()->create();

        $this->atribuirPermissaoGlobal($autor);

        $publicacao = MetalThursday::factory()
            ->comAutor($autor)
            ->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário na própria publicação.',
            'comentario_pai_id' => null,
        ]);

        $notificacao = new NotificacaoInteracaoUtilizador(
            $publicacao,
            $autor,
            'comentou',
            $comentario,
        );

        self::assertFalse($notificacao->shouldSend($autor, 'database'));
        self::assertFalse($notificacao->shouldSend($autor, 'mail'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function estadosInvalidosDaDenuncia(): array
    {
        return [
            'rejeitada' => ['rejeitada'],
            'aceite' => ['aceite'],
            'eliminada' => ['eliminada'],
        ];
    }

    #[Test]
    #[DataProvider('estadosInvalidosDaDenuncia')]
    public function denuncia_obsoleta_nao_origina_novo_aviso(string $estado): void
    {
        $autor = Utilizador::factory()->create();
        $denunciante = Utilizador::factory()->create();

        $moderador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $this->atribuirPermissaoGlobal($moderador);

        $publicacao = MetalThursday::factory()
            ->comAutor($autor)
            ->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário denunciado para teste.',
            'comentario_pai_id' => null,
        ]);

        $denuncia = DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
        ]);

        $notificacao = new NotificacaoNovaDenunciaComentario(
            $denuncia->id,
        );

        self::assertTrue($notificacao->shouldSend($moderador, 'database'));
        self::assertTrue($notificacao->shouldSend($moderador, 'mail'));

        if ($estado === 'eliminada') {
            $denuncia->deleteOrFail();
        } else {
            $denuncia->forceFill([
                'estado' => $estado,
                'moderador_id' => $moderador->id,
                'decidida_em' => now(),
            ])->saveOrFail();
        }

        self::assertFalse($notificacao->shouldSend($moderador, 'database'));
        self::assertFalse($notificacao->shouldSend($moderador, 'mail'));
    }

    private function atribuirPermissaoGlobal(Utilizador $utilizador): void
    {
        $permissao = PermissaoEmail::query()
            ->where(
                'identificador',
                IdentificadorPermissaoEmail::TodasNotificacoes->value,
            )
            ->sole();

        $utilizador->permissoesEmail()->attach($permissao->id);
        $utilizador->unsetRelation('permissoesEmail');
    }
}
