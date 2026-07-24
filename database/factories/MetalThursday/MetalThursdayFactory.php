<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para registos MetalThursday.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<MetalThursday>
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class MetalThursdayFactory extends Factory
{
    /**
     * Comprimento máximo do nome.
     *
     * Corresponde ao comprimento predefinido das colunas `string` utilizadas
     * pelo Laravel.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_NOME =
        255;

    /**
     * Modelo associado à factory.
     *
     * @var class-string<MetalThursday>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model =
        MetalThursday::class;

    /**
     * Define os atributos predefinidos de um registo MetalThursday.
     *
     * A data é calculada a partir de um número de dias único durante a
     * execução da factory, reduzindo a possibilidade de colisões com a
     * restrição única existente na base de dados.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do registo MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function definition(): array
    {
        $diasAnteriores =
            $this
                ->faker
                ->unique()
                ->numberBetween(
                    0,
                    10000,
                );

        return [
            'nome' => null,

            'data' => CarbonImmutable::today()
                ->subDays(
                    $diasAnteriores,
                ),

            'edicao_id' => Edicao::factory(),

            'autor_id' => null,

            'proximo_nomeado_id' => null,
        ];
    }

    /**
     * Define um nome para o registo MetalThursday.
     *
     * @param  string  $nome  Nome pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome está vazio ou ultrapassa
     *                                  o comprimento máximo permitido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comNome(
        string $nome,
    ): static {
        $nomeNormalizado =
            Str::squish(
                $nome,
            );

        if ($nomeNormalizado === '') {
            throw new InvalidArgumentException(
                'O nome do registo MetalThursday não pode estar vazio.',
            );
        }

        if (
            mb_strlen(
                $nomeNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome do registo MetalThursday não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_NOME,
                ),
            );
        }

        return $this->state(
            static fn (): array => [
                'nome' => $nomeNormalizado,
            ],
        );
    }

    /**
     * Define a data do registo MetalThursday.
     *
     * A data é normalizada para o início do respetivo dia.
     *
     * @param  CarbonInterface  $data  Data pretendida.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comData(
        CarbonInterface $data,
    ): static {
        $dataNormalizada =
            CarbonImmutable::instance(
                $data,
            )->startOfDay();

        return $this->state(
            static fn (): array => [
                'data' => $dataNormalizada,
            ],
        );
    }

    /**
     * Associa uma edição ao registo MetalThursday.
     *
     * Quando nenhuma edição é indicada, é criada uma através da factory
     * respetiva.
     *
     * @param  Edicao|null  $edicao  Edição pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a edição indicada não está
     *                                  persistida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comEdicao(
        ?Edicao $edicao = null,
    ): static {
        if ($edicao !== null) {
            $this->validarModeloPersistido(
                $edicao,
                'A edição associada ao registo MetalThursday deve estar persistida.',
            );
        }

        return $this->for(
            $edicao
                ?? Edicao::factory(),
            'edicao',
        );
    }

    /**
     * Associa um autor ao registo MetalThursday.
     *
     * Quando nenhum utilizador é indicado, é criado um utilizador através da
     * factory respetiva.
     *
     * @param  Utilizador|null  $utilizador  Autor pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador indicado não está
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comAutor(
        ?Utilizador $utilizador = null,
    ): static {
        if ($utilizador !== null) {
            $this->validarModeloPersistido(
                $utilizador,
                'O autor do registo MetalThursday deve estar persistido.',
            );
        }

        return $this->for(
            $utilizador
                ?? Utilizador::factory(),
            'autor',
        );
    }

    /**
     * Associa o próximo utilizador nomeado ao registo MetalThursday.
     *
     * Quando nenhum utilizador é indicado, é criado um utilizador através da
     * factory respetiva.
     *
     * @param  Utilizador|null  $utilizador  Próximo utilizador nomeado.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador indicado não está
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comProximoNomeado(
        ?Utilizador $utilizador = null,
    ): static {
        if ($utilizador !== null) {
            $this->validarModeloPersistido(
                $utilizador,
                'O próximo utilizador nomeado deve estar persistido.',
            );
        }

        return $this->for(
            $utilizador
                ?? Utilizador::factory(),
            'proximoNomeado',
        );
    }

    /**
     * Valida que um modelo relacionado já se encontra persistido.
     *
     * @param  Model  $modelo  Modelo a validar.
     * @param  string  $mensagem  Mensagem utilizada em caso de erro.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarModeloPersistido(
        Model $modelo,
        string $mensagem,
    ): void {
        if (
            ! $modelo->exists
            || $modelo->getKey() === null
        ) {
            throw new InvalidArgumentException(
                $mensagem,
            );
        }
    }
}
