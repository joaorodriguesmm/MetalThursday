<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use App\Servicos\MetalThursday\ServicoMusicasFavoritasEdicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a sincronização transacional das músicas favoritas de uma edição.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoMusicasFavoritasEdicaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que apenas os utilizadores recebidos são sincronizados.
     *
     * Os nomes das músicas são normalizados e as escolhas dos restantes
     * utilizadores permanecem inalteradas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function sincroniza_apenas_utilizadores_recebidos(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $primeiroUtilizador = Utilizador::factory()
            ->create();

        $segundoUtilizador = Utilizador::factory()
            ->create();

        $registador = Utilizador::factory()
            ->create();

        $this->criarMusicaFavorita(
            $edicao,
            $primeiroUtilizador,
            $registador,
            1,
            'Escolha antiga',
        );

        $escolhaPreservada = $this->criarMusicaFavorita(
            $edicao,
            $segundoUtilizador,
            $registador,
            1,
            'Escolha preservada',
        );

        $this
            ->servico()
            ->sincronizar(
                edicao: $edicao,
                musicasFavoritas: [
                    (int) $primeiroUtilizador->getKey() => [
                        "  Banda\t—\nMúsica principal  ",
                        null,
                        'Outra música',
                    ],
                ],
                registador: $registador,
            );

        $this->assertDatabaseMissing(
            'musicas_favoritas_edicao',
            [
                'edicao_id' => $edicao->getKey(),

                'utilizador_id' => $primeiroUtilizador->getKey(),

                'musica' => 'Escolha antiga',
            ],
        );

        $this->assertDatabaseHas(
            'musicas_favoritas_edicao',
            [
                'edicao_id' => $edicao->getKey(),

                'utilizador_id' => $primeiroUtilizador->getKey(),

                'posicao' => 1,

                'musica' => 'Banda — Música principal',

                'registado_por_id' => $registador->getKey(),
            ],
        );

        $this->assertDatabaseHas(
            'musicas_favoritas_edicao',
            [
                'edicao_id' => $edicao->getKey(),

                'utilizador_id' => $primeiroUtilizador->getKey(),

                'posicao' => 3,

                'musica' => 'Outra música',

                'registado_por_id' => $registador->getKey(),
            ],
        );

        $this->assertDatabaseMissing(
            'musicas_favoritas_edicao',
            [
                'edicao_id' => $edicao->getKey(),

                'utilizador_id' => $primeiroUtilizador->getKey(),

                'posicao' => 2,
            ],
        );

        $this->assertDatabaseHas(
            'musicas_favoritas_edicao',
            [
                'id' => $escolhaPreservada->getKey(),

                'edicao_id' => $edicao->getKey(),

                'utilizador_id' => $segundoUtilizador->getKey(),

                'musica' => 'Escolha preservada',
            ],
        );
    }

    /**
     * Confirma que uma lista vazia remove todas as escolhas do utilizador.
     *
     * As três posições são representadas por valores nulos, preservando o
     * contrato fixo do pedido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function remove_escolhas_quando_todas_as_posicoes_estao_vazias(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $utilizador = Utilizador::factory()
            ->create();

        $registador = Utilizador::factory()
            ->create();

        $this->criarMusicaFavorita(
            $edicao,
            $utilizador,
            $registador,
            1,
            'Primeira escolha',
        );

        $this->criarMusicaFavorita(
            $edicao,
            $utilizador,
            $registador,
            2,
            'Segunda escolha',
        );

        $this
            ->servico()
            ->sincronizar(
                edicao: $edicao,
                musicasFavoritas: [
                    (int) $utilizador->getKey() => [
                        null,
                        null,
                        null,
                    ],
                ],
                registador: $registador,
            );

        $this->assertDatabaseMissing(
            'musicas_favoritas_edicao',
            [
                'edicao_id' => $edicao->getKey(),

                'utilizador_id' => $utilizador->getKey(),
            ],
        );
    }

    /**
     * Confirma que a validação prévia respeita a collation da base de dados.
     *
     * `utf8mb4_unicode_ci` não distingue maiúsculas nem acentos. O serviço
     * deve, por isso, devolver um erro de validação antes de alcançar o índice
     * único da tabela.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_musica_repetida_sem_distinguir_acentos_ou_maiusculas(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $utilizador = Utilizador::factory()
            ->create();

        $registador = Utilizador::factory()
            ->create();

        $escolhaAnterior = $this->criarMusicaFavorita(
            $edicao,
            $utilizador,
            $registador,
            1,
            'Escolha anterior',
        );

        try {
            $this
                ->servico()
                ->sincronizar(
                    edicao: $edicao,
                    musicasFavoritas: [
                        (int) $utilizador->getKey() => [
                            'Beyoncé',
                            'BEYONCE',
                            null,
                        ],
                    ],
                    registador: $registador,
                );

            self::fail(
                'Era esperado um erro de validação para uma música repetida.',
            );
        } catch (ValidationException $excecao) {
            self::assertArrayHasKey(
                sprintf(
                    'musicas_favoritas.%d.1',
                    $utilizador->getKey(),
                ),
                $excecao->errors(),
            );
        }

        $this->assertDatabaseHas(
            'musicas_favoritas_edicao',
            [
                'id' => $escolhaAnterior->getKey(),

                'musica' => 'Escolha anterior',
            ],
        );
    }

    /**
     * Confirma que um utilizador indisponível não altera escolhas existentes.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_inexistente_nao_altera_escolhas_existentes(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $utilizador = Utilizador::factory()
            ->create();

        $registador = Utilizador::factory()
            ->create();

        $escolhaAnterior = $this->criarMusicaFavorita(
            $edicao,
            $utilizador,
            $registador,
            1,
            'Escolha anterior',
        );

        $identificadorInexistente =
            (int) Utilizador::query()->max('id') + 100;

        try {
            $this
                ->servico()
                ->sincronizar(
                    edicao: $edicao,
                    musicasFavoritas: [
                        $identificadorInexistente => [
                            'Primeira música',
                            null,
                            null,
                        ],
                    ],
                    registador: $registador,
                );

            self::fail(
                'Era esperado um erro de validação para um utilizador inexistente.',
            );
        } catch (ValidationException $excecao) {
            self::assertArrayHasKey(
                'musicas_favoritas',
                $excecao->errors(),
            );
        }

        $this->assertDatabaseHas(
            'musicas_favoritas_edicao',
            [
                'id' => $escolhaAnterior->getKey(),

                'musica' => 'Escolha anterior',
            ],
        );
    }

    /**
     * Cria uma música favorita conhecida.
     *
     * @param  Edicao  $edicao  Edição relacionada.
     * @param  Utilizador  $proprietario  Proprietário da escolha.
     * @param  Utilizador  $registador  Responsável pelo registo.
     * @param  int  $posicao  Posição da escolha.
     * @param  string  $musica  Identificação da música.
     * @return MusicaFavoritaEdicao Música favorita criada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarMusicaFavorita(
        Edicao $edicao,
        Utilizador $proprietario,
        Utilizador $registador,
        int $posicao,
        string $musica,
    ): MusicaFavoritaEdicao {
        return MusicaFavoritaEdicao::factory()
            ->paraEdicao(
                $edicao,
            )
            ->pertencenteA(
                $proprietario,
            )
            ->registadaPor(
                $registador,
            )
            ->comPosicao(
                $posicao,
            )
            ->comMusica(
                $musica,
            )
            ->create();
    }

    /**
     * Cria o serviço testado.
     *
     * @return ServicoMusicasFavoritasEdicao Serviço criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function servico(): ServicoMusicasFavoritasEdicao
    {
        return new ServicoMusicasFavoritasEdicao;
    }
}
