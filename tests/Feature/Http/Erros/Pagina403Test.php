<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Erros;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class Pagina403Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function administrador_recebe_pagina_403_ao_consultar_utilizadores(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::Administrador)
            ->create();

        $this->actingAs($administrador, 'sessao')
            ->get(route('utilizadores.indice'))
            ->assertForbidden()
            ->assertSeeText('Acesso negado')
            ->assertSeeText('Não tens permissão para aceder a esta página.')
            ->assertSeeText('Voltar ao início')
            ->assertSee('href="'.route('inicio').'"', false);
    }

    #[Test]
    public function administrador_recebe_pagina_403_ao_consultar_convites(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::Administrador)
            ->create();

        $this->actingAs($administrador, 'sessao')
            ->get(route('convites.indice'))
            ->assertForbidden()
            ->assertSeeText('Acesso negado')
            ->assertSeeText('Não tens permissão para aceder a esta página.')
            ->assertSeeText('Voltar ao início')
            ->assertSee('href="'.route('inicio').'"', false);
    }
}
