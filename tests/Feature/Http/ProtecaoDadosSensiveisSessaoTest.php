<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProtecaoDadosSensiveisSessaoTest extends TestCase
{
    /**
     * Impede que palavras-passe sejam guardadas na sessão
     * quando ocorre um erro de validação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_guarda_palavras_passe_na_sessao_apos_erro_de_validacao(): void
    {
        Route::post(
            '/auditoria-b19-16-dados-sensiveis',
            static function (): never {
                throw ValidationException::withMessages([
                    'nome' => 'Erro de validação de auditoria.',
                ]);
            },
        )->middleware('web');

        $resposta = $this->post(
            '/auditoria-b19-16-dados-sensiveis',
            [
                'nome' => 'Utilizador de Teste',
                'palavra_passe' => 'SegredoFicticio123!',
                'confirmacao_palavra_passe' => 'SegredoFicticio123!',
                'palavra_passe_atual' => 'SegredoAtual123!',
                'nova_palavra_passe' => 'SegredoNovo123!',
                'confirmacao_nova_palavra_passe' => 'SegredoNovo123!',
                'password' => 'SegredoIngles123!',
                'password_confirmation' => 'SegredoIngles123!',
                'current_password' => 'SegredoAtualIngles123!',
            ],
        );

        $resposta
            ->assertRedirect()
            ->assertSessionHasErrors('nome')
            ->assertSessionHasInput(
                'nome',
                'Utilizador de Teste',
            )
            ->assertSessionMissingInput([
                'palavra_passe',
                'confirmacao_palavra_passe',
                'palavra_passe_atual',
                'nova_palavra_passe',
                'confirmacao_nova_palavra_passe',
                'password',
                'password_confirmation',
                'current_password',
            ]);
    }
}
