<?php

declare(strict_types=1);

namespace App\Models\Musica;

use App\Enumeracoes\TipoLancamento;
use App\Traits\Auditoria\RegistaAutoria;
use Carbon\CarbonInterface;
use Database\Factories\Musica\LancamentoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Representa um lançamento musical.
 *
 * O título não identifica univocamente o lançamento e pode, por isso, ser
 * repetido entre registos distintos.
 *
 * @property int $id
 * @property string $titulo
 * @property TipoLancamento|null $tipo
 * @property int|null $ano_original
 * @property int|null $discogs_release_id
 * @property int|null $criado_por_id
 * @property int|null $atualizado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Collection<int, Artista> $artistas
 * @property-read Collection<int, FaixaLancamento> $faixas
 * @property-read string|null $url_discogs
 *
 * @since 2.0.0
 */
class Lancamento extends Model
{
    /** @use HasFactory<LancamentoFactory> */
    use HasFactory;

    use RegistaAutoria;
    use SoftDeletes;

    /**
     * Comprimento máximo do título.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_TITULO = 255;

    /**
     * Nome da tabela intermédia entre artistas e lançamentos.
     *
     * @since 2.0.0
     */
    private const TABELA_ARTISTA_LANCAMENTO =
        'artista_lancamento';

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table = 'lancamentos';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $fillable = [
        'titulo',
        'tipo',
        'ano_original',
        'discogs_release_id',
    ];

    /**
     * Define as conversões automáticas dos atributos persistidos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoLancamento::class,
            'ano_original' => 'integer',
            'discogs_release_id' => 'integer',
            'criado_por_id' => 'integer',
            'atualizado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * @return LancamentoFactory Factory dos lançamentos.
     *
     * @since 2.0.0
     */
    protected static function newFactory(): LancamentoFactory
    {
        return LancamentoFactory::new();
    }

    /**
     * Obtém os artistas associados ao lançamento.
     *
     * @return BelongsToMany<Artista, $this> Relação com os artistas.
     *
     * @since 2.0.0
     */
    public function artistas(): BelongsToMany
    {
        return $this->belongsToMany(
            Artista::class,
            self::TABELA_ARTISTA_LANCAMENTO,
            'lancamento_id',
            'artista_id',
        );
    }

    /**
     * Obtém as faixas pertencentes ao lançamento pela ordem da tracklist.
     *
     * As faixas sem ordem conhecida são apresentadas depois das faixas cuja
     * posição na tracklist é conhecida.
     *
     * @return HasMany<FaixaLancamento, $this> Relação com as faixas.
     *
     * @since 2.0.0
     */
    public function faixas(): HasMany
    {
        return $this
            ->hasMany(
                FaixaLancamento::class,
                'lancamento_id',
            )
            ->orderByRaw(
                'ordem IS NULL',
            )
            ->orderBy(
                'ordem',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * Obtém o endereço público da edição associada no Discogs.
     *
     * @return Attribute<string|null, never> Endereço público ou nulo.
     *
     * @since 2.0.0
     */
    protected function urlDiscogs(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $identificador =
                    $this->getAttributeFromArray(
                        'discogs_release_id',
                    );

                if (
                    ! is_numeric($identificador)
                    || (int) $identificador < 1
                ) {
                    return null;
                }

                return 'https://www.discogs.com/release/'
                    .(int) $identificador;
            },
        );
    }
}
