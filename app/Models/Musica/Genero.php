<?php

declare(strict_types=1);

namespace App\Models\Musica;

use App\Traits\Auditoria\RegistaAutoria;
use Carbon\CarbonInterface;
use Database\Factories\Musica\GeneroFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * Representa um género musical e a respetiva posição hierárquica.
 *
 * Um género pode possuir vários géneros pais, vários géneros filhos e estar
 * associado a várias bandas.
 *
 * A base de dados impede que um género seja diretamente pai de si próprio.
 * A aplicação é responsável por impedir ciclos mais extensos durante a
 * sincronização da hierarquia.
 *
 * @property int $id
 * @property string $nome
 * @property int|null $criado_por_id
 * @property int|null $atualizado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Collection<int, Genero> $generosPais
 * @property-read Collection<int, Genero> $generosFilhos
 * @property-read Collection<int, Banda> $bandas
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
class Genero extends Model
{
    /** @use HasFactory<GeneroFactory> */
    use HasFactory;

    use RegistaAutoria;
    use SoftDeletes;

    /**
     * Comprimento máximo permitido para o nome.
     *
     * Este valor coincide com o comprimento definido na tabela `generos`.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_NOME = 100;

    /**
     * Nome da tabela intermédia da hierarquia dos géneros.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELA_HIERARQUIA =
        'hierarquia_generos';

    /**
     * Nome da tabela intermédia entre bandas e géneros.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELA_BANDA_GENERO =
        'banda_genero';

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'generos';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * Os campos de auditoria são preenchidos pelo trait
     * {@see RegistaAutoria}.
     *
     * @var list<string>
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected $fillable = [
        'nome',
    ];

    /**
     * Define as conversões automáticas dos identificadores de auditoria.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function casts(): array
    {
        return [
            'criado_por_id' => 'integer',

            'atualizado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return GeneroFactory Factory dos géneros.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): GeneroFactory
    {
        return GeneroFactory::new();
    }

    /**
     * Normaliza e valida o nome do género.
     *
     * Os espaços exteriores e consecutivos são normalizados. Quebras de
     * linha, tabulações e restantes caracteres de controlo não são aceites.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O nome do género deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome do género contém texto inválido.',
                    );
                }

                if (
                    preg_match(
                        '/[\x00-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O nome do género contém caracteres inválidos.',
                    );
                }

                $nomeNormalizado = Str::squish(
                    $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome do género é obrigatório.',
                    );
                }

                if (
                    mb_strlen(
                        $nomeNormalizado,
                    ) > self::COMPRIMENTO_MAXIMO_NOME
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O nome do género não pode ter mais de %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_NOME,
                        ),
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Obtém os géneros pais diretos.
     *
     * Os géneros são devolvidos por ordem alfabética.
     *
     * @return BelongsToMany<Genero, $this> Relação com os géneros pais.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function generosPais(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                self::class,
                self::TABELA_HIERARQUIA,
                'genero_id',
                'genero_pai_id',
            )
            ->orderBy(
                'generos.nome',
            )
            ->orderBy(
                'generos.id',
            );
    }

    /**
     * Obtém os géneros filhos diretos.
     *
     * Os géneros são devolvidos por ordem alfabética.
     *
     * @return BelongsToMany<Genero, $this> Relação com os géneros filhos.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function generosFilhos(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                self::class,
                self::TABELA_HIERARQUIA,
                'genero_pai_id',
                'genero_id',
            )
            ->orderBy(
                'generos.nome',
            )
            ->orderBy(
                'generos.id',
            );
    }

    /**
     * Obtém as bandas associadas ao género.
     *
     * As bandas são devolvidas por ordem alfabética.
     *
     * @return BelongsToMany<Banda, $this> Relação com as bandas.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function bandas(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Banda::class,
                self::TABELA_BANDA_GENERO,
                'genero_id',
                'banda_id',
            )
            ->orderBy(
                'bandas.nome',
            )
            ->orderBy(
                'bandas.id',
            );
    }

    /**
     * Obtém os identificadores do género e de todos os seus descendentes.
     *
     * A travessia utiliza um conjunto de identificadores já processados para
     * impedir repetições e ciclos infinitos caso exista uma hierarquia
     * inválida na base de dados.
     *
     * @return non-empty-list<int> Identificadores do género e descendentes.
     *
     * @throws LogicException Quando algum género ainda não foi persistido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function obterIdentificadoresComDescendentes(): array
    {
        /** @var array<int, int> $identificadores */
        $identificadores = [];

        /** @var list<Genero> $generosPorProcessar */
        $generosPorProcessar = [
            $this,
        ];

        while ($generosPorProcessar !== []) {
            /** @var Genero $generoAtual */
            $generoAtual = array_pop(
                $generosPorProcessar,
            );

            $identificador = $generoAtual->getKey();

            if (
                ! is_numeric($identificador)
                || (int) $identificador < 1
            ) {
                throw new LogicException(
                    'Não é possível percorrer a hierarquia de um género ainda não persistido.',
                );
            }

            $identificadorInteiro =
                (int) $identificador;

            if (
                isset(
                    $identificadores[$identificadorInteiro],
                )
            ) {
                continue;
            }

            $identificadores[$identificadorInteiro] = $identificadorInteiro;

            $generoAtual->loadMissing(
                'generosFilhos',
            );

            foreach (
                $generoAtual->generosFilhos as $generoFilho
            ) {
                $generosPorProcessar[] =
                    $generoFilho;
            }
        }

        return array_values(
            $identificadores,
        );
    }
}
