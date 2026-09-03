<?php

declare(strict_types=1);

namespace App\Models\Comum;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Representa uma ligação externa pertencente a uma entidade da aplicação.
 *
 * A relação é polimórfica para permitir reutilizar o mesmo modelo em artistas,
 * utilizadores e outras entidades que venham a suportar ligações no futuro.
 *
 * @property int $id
 * @property string $tipo_ligavel
 * @property int $ligavel_id
 * @property string $titulo
 * @property string $url
 * @property int $ordem
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Model $ligavel
 *
 * @since 2.0.0
 */
class Ligacao extends Model
{
    /**
     * Comprimento máximo do título apresentado.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_TITULO = 100;

    /**
     * Comprimento máximo de um endereço web.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO_URL = 2048;

    /**
     * Nome físico da tabela.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $table = 'ligacoes';

    /**
     * Atributos atribuíveis em massa.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    protected $fillable = [
        'titulo',
        'url',
        'ordem',
    ];

    /**
     * Define as conversões automáticas.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     */
    protected function casts(): array
    {
        return [
            'ligavel_id' => 'integer',
            'ordem' => 'integer',
        ];
    }

    /**
     * Normaliza e valida o título da ligação.
     *
     * @return Attribute<string, string> Atributo do título.
     *
     * @throws InvalidArgumentException Quando o título é inválido, está vazio
     *                                  ou excede o limite permitido.
     *
     * @since 2.0.0
     */
    protected function titulo(): Attribute
    {
        return Attribute::make(
            set: static function (mixed $valor): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O título da ligação deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match('//u', $valor) !== 1
                    || preg_match('/[\x00-\x1F\x7F]/', $valor) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O título da ligação contém caracteres inválidos.',
                    );
                }

                $titulo = Str::squish($valor);

                if ($titulo === '') {
                    throw new InvalidArgumentException(
                        'O título da ligação não pode estar vazio.',
                    );
                }

                if (
                    mb_strlen($titulo)
                    > self::COMPRIMENTO_MAXIMO_TITULO
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'O título da ligação não pode exceder %d caracteres.',
                            self::COMPRIMENTO_MAXIMO_TITULO,
                        ),
                    );
                }

                return $titulo;
            },
        );
    }

    /**
     * Normaliza e valida o endereço da ligação.
     *
     * @return Attribute<string, string> Atributo do endereço.
     *
     * @throws InvalidArgumentException Quando o endereço é inválido, utiliza um
     *                                  esquema não permitido ou contém
     *                                  credenciais.
     *
     * @since 2.0.0
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            set: static function (mixed $valor): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O endereço da ligação deve ser uma sequência de caracteres.',
                    );
                }

                if (
                    preg_match('//u', $valor) !== 1
                    || preg_match('/[\x00-\x1F\x7F]/', $valor) === 1
                ) {
                    throw new InvalidArgumentException(
                        'O endereço da ligação contém caracteres inválidos.',
                    );
                }

                $url = trim($valor);

                if (
                    $url === ''
                    || mb_strlen($url) > self::COMPRIMENTO_MAXIMO_URL
                    || filter_var($url, FILTER_VALIDATE_URL) === false
                ) {
                    throw new InvalidArgumentException(
                        'O endereço da ligação não é válido.',
                    );
                }

                $esquema = mb_strtolower(
                    (string) parse_url(
                        $url,
                        PHP_URL_SCHEME,
                    ),
                );

                if (! in_array($esquema, ['http', 'https'], true)) {
                    throw new InvalidArgumentException(
                        'O endereço da ligação deve utilizar HTTP ou HTTPS.',
                    );
                }

                if (
                    parse_url($url, PHP_URL_USER) !== null
                    || parse_url($url, PHP_URL_PASS) !== null
                ) {
                    throw new InvalidArgumentException(
                        'O endereço da ligação não pode incluir credenciais.',
                    );
                }

                return $url;
            },
        );
    }

    /**
     * Obtém a entidade proprietária da ligação.
     *
     * @return MorphTo<Model, $this> Relação polimórfica.
     *
     * @since 2.0.0
     */
    public function ligavel(): MorphTo
    {
        return $this->morphTo(
            'ligavel',
            'tipo_ligavel',
            'ligavel_id',
        );
    }
}
