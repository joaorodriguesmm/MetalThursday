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
 * A música permanece guardada como texto livre enquanto não existir uma
 * entidade própria para representar músicas.
 *
 * @property int $id
 * @property int $edicao_id
 * @property int $utilizador_id
 * @property int $posicao
 * @property string $musica
 * @property int|null $registado_por_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Edicao $edicao
 * @property-read Utilizador $utilizador
 * @property-read Utilizador|null $registadoPor
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
class MusicaFavoritaEdicao extends Model
{
    /** @use HasFactory<MusicaFavoritaEdicaoFactory> */
    use HasFactory;

    /**
     * Posição mínima permitida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const POSICAO_MINIMA = 1;

    /**
     * Posição máxima permitida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const POSICAO_MAXIMA = 3;

    /**
     * Número total de posições disponíveis.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const NUMERO_POSICOES = 3;

    /**
     * Comprimento máximo da identificação da música.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const COMPRIMENTO_MAXIMO_MUSICA = 255;

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
     * As relações com a edição, o proprietário e o utilizador responsável
     * pelo registo devem ser estabelecidas explicitamente através dos
     * respetivos métodos Eloquent.
     *
     * @var list<string>
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected $fillable = [
        'posicao',
        'musica',
    ];

    /**
     * Define as conversões automáticas dos identificadores.
     *
     * A posição é tratada pelo atributo {@see posicao()} para ser validada
     * antes da persistência.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function casts(): array
    {
        return [
            'edicao_id' => 'integer',

            'utilizador_id' => 'integer',

            'registado_por_id' => 'integer',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
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
     * Normaliza e valida a posição da música favorita.
     *
     * Apenas valores inteiros compreendidos entre um e três são aceites.
     * Representações textuais ou valores decimais não são convertidos
     * implicitamente.
     *
     * @return Attribute<int, int> Atributo da posição.
     *
     * @throws InvalidArgumentException Quando a posição não é válida.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function posicao(): Attribute
    {
        return Attribute::make(
            get: static fn (
                mixed $valor,
            ): int => (int) $valor,

            set: static function (
                mixed $valor,
            ): int {
                if (
                    ! is_int($valor)
                    || $valor < self::POSICAO_MINIMA
                    || $valor > self::POSICAO_MAXIMA
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A posição da música favorita deve estar compreendida entre %d e %d.',
                            self::POSICAO_MINIMA,
                            self::POSICAO_MAXIMA,
                        ),
                    );
                }

                return $valor;
            },
        );
    }

    /**
     * Normaliza e valida a identificação da música.
     *
     * Tabulações, quebras de linha e sequências de espaços Unicode são
     * convertidas num único espaço. Os restantes caracteres de controlo não
     * são aceites.
     *
     * @return Attribute<string, string> Atributo da música.
     *
     * @throws InvalidArgumentException Quando a identificação não é válida.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function musica(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'A identificação da música favorita deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match(
                        '//u',
                        $valor,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'A identificação da música favorita contém texto inválido.',
                    );
                }

                if (
                    preg_match(
                        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                        $valor,
                    ) === 1
                ) {
                    throw new InvalidArgumentException(
                        'A identificação da música favorita contém caracteres inválidos.',
                    );
                }

                $musicaNormalizada = preg_replace(
                    '/\s+/u',
                    ' ',
                    $valor,
                );

                if (! is_string($musicaNormalizada)) {
                    throw new InvalidArgumentException(
                        'Não foi possível normalizar a identificação da música favorita.',
                    );
                }

                $musicaNormalizada = trim(
                    $musicaNormalizada,
                );

                if ($musicaNormalizada === '') {
                    throw new InvalidArgumentException(
                        'A identificação da música favorita é obrigatória.',
                    );
                }

                if (
                    mb_strlen(
                        $musicaNormalizada,
                    ) > self::COMPRIMENTO_MAXIMO_MUSICA
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A identificação da música favorita não pode ter mais de %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_MUSICA,
                        ),
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
     * preservando o histórico das escolhas enquanto o registo permanecer na
     * base de dados.
     *
     * @return BelongsTo<Edicao, $this> Relação com a edição.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
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
     * Obtém o utilizador proprietário da escolha.
     *
     * O proprietário é obrigatório e a base de dados impede a sua eliminação
     * física enquanto existirem músicas favoritas associadas.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o proprietário.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
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
     * Este utilizador pode ser diferente do proprietário quando um
     * administrador regista as músicas em nome de outra pessoa.
     *
     * A relação pode ser nula quando o registo não identificou um responsável
     * ou quando esse utilizador foi posteriormente eliminado.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o responsável pelo
     *                                      registo.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function registadoPor(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'registado_por_id',
        );
    }
}
