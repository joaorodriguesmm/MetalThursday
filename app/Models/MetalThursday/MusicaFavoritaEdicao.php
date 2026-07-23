<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\MusicaFavoritaEdicaoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Representa uma música favorita escolhida por um utilizador numa edição.
 *
 * Cada utilizador pode escolher até três músicas favoritas por edição,
 * atribuindo-lhes uma posição de preferência entre um e três.
 *
 * A música permanece temporariamente guardada como texto livre enquanto
 * não existir uma entidade própria para representar músicas.
 *
 * @property int $id
 * @property int $edicao_id
 * @property int|null $utilizador_id
 * @property int $posicao
 * @property string $musica
 * @property int|null $registado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Edicao $edicao
 * @property-read Utilizador|null $utilizador
 * @property-read Utilizador|null $registadoPor
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class MusicaFavoritaEdicao extends Model
{
    /** @use HasFactory<MusicaFavoritaEdicaoFactory> */
    use HasFactory;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $table =
        'musicas_favoritas_edicao';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * As relações com a edição e com os utilizadores devem ser estabelecidas
     * explicitamente através dos respetivos métodos Eloquent.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    protected $fillable = [
        'posicao',
        'musica',
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
            'edicao_id' => 'integer',
            'utilizador_id' => 'integer',
            'posicao' => 'integer',
            'registado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * A associação é explícita porque o modelo e a factory se encontram em
     * namespaces próprios.
     *
     * @return MusicaFavoritaEdicaoFactory Factory das músicas favoritas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): MusicaFavoritaEdicaoFactory
    {
        return MusicaFavoritaEdicaoFactory::new();
    }

    /**
     * Valida a posição da música favorita.
     *
     * Cada utilizador pode definir três músicas, com posições entre um e três.
     *
     * @return Attribute<int, int> Atributo da posição.
     *
     * @throws InvalidArgumentException Quando a posição não está compreendida
     *                                  entre um e três.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function posicao(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): int {
                $posicao = (int) $valor;

                if ($posicao < 1 || $posicao > 3) {
                    throw new InvalidArgumentException(
                        'A posição da música favorita deve estar compreendida entre um e três.',
                    );
                }

                return $posicao;
            },
        );
    }

    /**
     * Normaliza o nome da música antes da persistência.
     *
     * @return Attribute<string, string> Atributo da música.
     *
     * @throws InvalidArgumentException Quando o nome está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function musica(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                $musicaNormalizada = trim(
                    (string) $valor,
                );

                if ($musicaNormalizada === '') {
                    throw new InvalidArgumentException(
                        'A música favorita não pode estar vazia.',
                    );
                }

                return $musicaNormalizada;
            },
        );
    }

    /**
     * Obtém a edição à qual pertence a escolha.
     *
     * A edição continua acessível caso tenha sido eliminada logicamente,
     * preservando o histórico das escolhas.
     *
     * @return BelongsTo<Edicao, $this> Relação com a edição.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function edicao(): BelongsTo
    {
        return $this
            ->belongsTo(
                Edicao::class,
                'edicao_id',
            )
            ->withTrashed();
    }

    /**
     * Obtém o utilizador a quem pertence a escolha.
     *
     * A relação pode ser nula para preservar o histórico caso o utilizador
     * seja eliminado fisicamente.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém o utilizador que registou a escolha.
     *
     * Este utilizador pode ser diferente do proprietário da escolha quando
     * um administrador regista as músicas em nome de outra pessoa.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador que
     *                                      registou a escolha.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function registadoPor(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'registado_por_id',
        );
    }
}
