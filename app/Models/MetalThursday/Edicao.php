<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use App\Traits\Auditoria\RegistaAutoria;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\EdicaoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * Representa uma edição do MetalThursday.
 *
 * Uma edição delimita um período temporal, agrega várias MetalThursdays
 * e pode possuir uma ligação para a respetiva compilação.
 *
 * @property int $id
 * @property string $nome
 * @property CarbonImmutable $data_inicio
 * @property CarbonImmutable|null $data_fim
 * @property string|null $ligacao_compilacao
 * @property int|null $criado_por_id
 * @property int|null $atualizado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Collection<int, MetalThursday> $metalThursdays
 * @property-read Collection<int, MusicaFavoritaEdicao> $musicasFavoritas
 * @property-read string $texto_apresentacao
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class Edicao extends Model
{
    /** @use HasFactory<EdicaoFactory> */
    use HasFactory;

    use RegistaAutoria;
    use SoftDeletes;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'edicoes';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * Os identificadores de auditoria são preenchidos automaticamente pelo
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
        'data_inicio',
        'data_fim',
        'ligacao_compilacao',
    ];

    /**
     * Atributos calculados incluídos nas representações serializadas.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $appends = [
        'texto_apresentacao',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function casts(): array
    {
        return [
            'data_inicio' => 'immutable_date',
            'data_fim' => 'immutable_date',
            'criado_por_id' => 'integer',
            'atualizado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * A associação é explícita porque o modelo e a factory se encontram
     * em namespaces próprios.
     *
     * @return EdicaoFactory Factory das edições.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): EdicaoFactory
    {
        return EdicaoFactory::new();
    }

    /**
     * Normaliza o nome da edição antes da persistência.
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
                        'O nome da edição não pode estar vazio.',
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Normaliza a ligação da compilação antes da persistência.
     *
     * Uma ligação vazia é convertida em nulo. A validação do formato da
     * ligação deve ser efetuada no pedido responsável pela edição.
     *
     * @return Attribute<string|null, string|null> Atributo da ligação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function ligacaoCompilacao(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): ?string {
                if (! is_string($valor)) {
                    return null;
                }

                $ligacaoNormalizada = trim(
                    $valor,
                );

                return $ligacaoNormalizada !== ''
                    ? $ligacaoNormalizada
                    : null;
            },
        );
    }

    /**
     * Obtém as MetalThursdays pertencentes à edição.
     *
     * As MetalThursdays são devolvidas por ordem cronológica.
     *
     * @return HasMany<MetalThursday, $this> Relação com as MetalThursdays.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function metalThursdays(): HasMany
    {
        return $this
            ->hasMany(
                MetalThursday::class,
                'edicao_id',
            )
            ->orderBy('data')
            ->orderBy('id');
    }

    /**
     * Obtém as músicas favoritas escolhidas para a edição.
     *
     * Cada registo representa uma das três escolhas de um utilizador. Os
     * resultados são agrupados pelo utilizador e ordenados pela posição.
     *
     * @return HasMany<MusicaFavoritaEdicao, $this> Relação com as músicas
     *                                              favoritas.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function musicasFavoritas(): HasMany
    {
        return $this
            ->hasMany(
                MusicaFavoritaEdicao::class,
                'edicao_id',
            )
            ->orderBy('utilizador_id')
            ->orderBy('posicao')
            ->orderBy('id');
    }

    /**
     * Obtém o texto formatado de apresentação da edição.
     *
     * Quando a edição ainda não possui uma data de fim, é apresentada como
     * estando atualmente em curso.
     *
     * @return Attribute<string, never> Texto de apresentação da edição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function textoApresentacao(): Attribute
    {
        return Attribute::get(
            function (): string {
                $dataInicio = $this
                    ->data_inicio
                    ->format('d/m/Y');

                $dataFim = $this
                    ->data_fim
                    ?->format('d/m/Y')
                    ?? 'Atualmente';

                return sprintf(
                    '%s - (%s - %s)',
                    $this->nome,
                    $dataInicio,
                    $dataFim,
                );
            },
        );
    }
}
