<?php

declare(strict_types=1);

namespace App\Models\Comunicacao;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonInterface;
use Database\Factories\Comunicacao\PermissaoEmailFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

/**
 * Representa uma permissão de comunicação por e-mail.
 *
 * As permissões permitem controlar os tipos de mensagens que cada utilizador
 * está autorizado ou interessado em receber.
 *
 * @property int $id
 * @property string $nome
 * @property string $identificador
 * @property string|null $descricao
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, Utilizador> $utilizadores
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class PermissaoEmail extends Model
{
    /** @use HasFactory<PermissaoEmailFactory> */
    use HasFactory;

    /**
     * Nome da tabela intermédia entre permissões de e-mail e utilizadores.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELA_PERMISSAO_UTILIZADOR =
        'permissao_email_utilizador';

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'permissoes_email';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $fillable = [
        'nome',
        'identificador',
        'descricao',
    ];

    /**
     * Cria a factory associada ao modelo.
     *
     * A associação é explícita porque o modelo e a factory se encontram
     * em namespaces próprios.
     *
     * @return PermissaoEmailFactory Factory das permissões de e-mail.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): PermissaoEmailFactory
    {
        return PermissaoEmailFactory::new();
    }

    /**
     * Normaliza o nome da permissão antes da persistência.
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
                $nomeNormalizado =
                    trim(
                        (string) $valor,
                    );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome da permissão de e-mail não pode estar vazio.',
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Normaliza o identificador antes da persistência.
     *
     * O identificador é guardado em minúsculas para que as comparações sejam
     * consistentes em todos os ambientes.
     *
     * @return Attribute<string, string> Atributo do identificador.
     *
     * @throws InvalidArgumentException Quando o identificador está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function identificador(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                $identificadorNormalizado =
                    mb_strtolower(
                        trim(
                            (string) $valor,
                        ),
                    );

                if ($identificadorNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O identificador da permissão de e-mail não pode estar vazio.',
                    );
                }

                return $identificadorNormalizado;
            },
        );
    }

    /**
     * Normaliza a descrição antes da persistência.
     *
     * Uma descrição vazia é convertida em nulo.
     *
     * @return Attribute<string|null, string|null> Atributo da descrição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function descricao(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): ?string {
                if (! is_string($valor)) {
                    return null;
                }

                $descricaoNormalizada =
                    trim(
                        $valor,
                    );

                return $descricaoNormalizada !== ''
                    ? $descricaoNormalizada
                    : null;
            },
        );
    }

    /**
     * Obtém os utilizadores associados à permissão de e-mail.
     *
     * A tabela intermédia não possui colunas adicionais nem marcas
     * temporais, pelo que não é necessário utilizar `withPivot()` nem
     * `withTimestamps()`.
     *
     * @return BelongsToMany<Utilizador, $this> Relação com os utilizadores.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function utilizadores(): BelongsToMany
    {
        return $this->belongsToMany(
            Utilizador::class,
            self::TABELA_PERMISSAO_UTILIZADOR,
            'permissao_email_id',
            'utilizador_id',
        );
    }
}
