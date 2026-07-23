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
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Representa um género musical.
 *
 * Os géneros podem estar associados a bandas e organizados numa hierarquia
 * com múltiplos géneros pais e múltiplos géneros filhos.
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
 * @property-read Collection<int, Genero> $filhosComDescendentes
 * @property-read Collection<int, Banda> $bandas
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class Genero extends Model
{
    /** @use HasFactory<GeneroFactory> */
    use HasFactory;

    use RegistaAutoria;
    use SoftDeletes;

    /**
     * Nome da tabela intermédia da hierarquia dos géneros.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELA_HIERARQUIA_GENEROS =
        'hierarquia_generos';

    /**
     * Nome da tabela intermédia entre bandas e géneros.
     *
     * @var string
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
     * Os identificadores de auditoria são atribuídos automaticamente pelo
     * trait {@see RegistaAutoria}.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $fillable = [
        'nome',
    ];

    /**
     * Define as conversões automáticas dos atributos.
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
     * A associação é explícita porque o modelo e a factory se encontram em
     * namespaces próprios.
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
     * Normaliza o nome do género antes da persistência.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @throws InvalidArgumentException Quando o nome está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                $nomeNormalizado = trim(
                    (string) $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome do género não pode estar vazio.',
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Obtém os géneros pais do género atual.
     *
     * Os géneros são devolvidos por ordem alfabética.
     *
     * @return BelongsToMany<Genero, $this> Relação com os géneros pais.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function generosPais(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                self::class,
                self::TABELA_HIERARQUIA_GENEROS,
                'genero_id',
                'genero_pai_id',
            )
            ->orderBy('generos.nome')
            ->orderBy('generos.id');
    }

    /**
     * Obtém os géneros filhos diretos do género atual.
     *
     * Os géneros são devolvidos por ordem alfabética.
     *
     * @return BelongsToMany<Genero, $this> Relação com os géneros filhos.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function generosFilhos(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                self::class,
                self::TABELA_HIERARQUIA_GENEROS,
                'genero_pai_id',
                'genero_id',
            )
            ->orderBy('generos.nome')
            ->orderBy('generos.id');
    }

    /**
     * Obtém as bandas associadas ao género.
     *
     * As bandas eliminadas logicamente não são incluídas e as restantes são
     * devolvidas por ordem alfabética.
     *
     * @return BelongsToMany<Banda, $this> Relação com as bandas.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
            ->orderBy('bandas.nome')
            ->orderBy('bandas.id');
    }

    /**
     * Obtém os géneros filhos com os respetivos descendentes.
     *
     * O resultado mantém a estrutura hierárquica: a coleção principal contém
     * apenas os filhos diretos, e cada filho contém os seus descendentes na
     * relação `filhosComDescendentes`.
     *
     * Esta relação deve ser utilizada quando forem necessários os modelos
     * completos. Para obter uma lista plana de identificadores, deve
     * utilizar-se {@see obterIdentificadoresComDescendentes()}.
     *
     * A hierarquia deve ser validada na camada responsável pela escrita para
     * impedir relações circulares.
     *
     * @return BelongsToMany<Genero, $this> Relação recursiva com os filhos.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function filhosComDescendentes(): BelongsToMany
    {
        return $this
            ->generosFilhos()
            ->with(
                'filhosComDescendentes',
            );
    }

    /**
     * Obtém o identificador do género atual e dos respetivos descendentes.
     *
     * A hierarquia é percorrida por níveis. O conjunto de identificadores
     * processados impede ciclos e evita resultados repetidos.
     *
     * Os géneros eliminados logicamente não são incluídos.
     *
     * @return array<int, int> Identificadores do género e dos descendentes.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
     */
    public function obterIdentificadoresComDescendentes(): array
    {
        $identificadorAtual = (int) $this->getKey();

        if (
            $identificadorAtual <= 0
            || $this->trashed()
        ) {
            return [];
        }

        $identificadores = [
            $identificadorAtual => $identificadorAtual,
        ];

        $identificadoresPorProcessar = [
            $identificadorAtual,
        ];

        while ($identificadoresPorProcessar !== []) {
            $identificadoresFilhos = DB::table(
                self::TABELA_HIERARQUIA_GENEROS
                    .' as hierarquia',
            )
                ->join(
                    'generos as generos_filhos',
                    'generos_filhos.id',
                    '=',
                    'hierarquia.genero_id',
                )
                ->whereIn(
                    'hierarquia.genero_pai_id',
                    $identificadoresPorProcessar,
                )
                ->whereNull(
                    'generos_filhos.deleted_at',
                )
                ->orderBy(
                    'hierarquia.genero_id',
                )
                ->pluck(
                    'hierarquia.genero_id',
                )
                ->map(
                    static fn (
                        mixed $identificador,
                    ): int => (int) $identificador,
                )
                ->unique()
                ->values()
                ->all();

            $identificadoresPorProcessar = [];

            foreach (
                $identificadoresFilhos as $identificadorFilho
            ) {
                if (
                    isset(
                        $identificadores[$identificadorFilho],
                    )
                ) {
                    continue;
                }

                $identificadores[$identificadorFilho] =
                    $identificadorFilho;

                $identificadoresPorProcessar[] =
                    $identificadorFilho;
            }
        }

        return array_values(
            $identificadores,
        );
    }
}
