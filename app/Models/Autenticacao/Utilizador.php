<?php

declare(strict_types=1);

namespace App\Models\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Comment;
use App\Models\EditionRanking;
use App\Models\EmailPermission;
use App\Models\Like;
use App\Models\Listen;
use App\Models\MetalThursday;
use App\Models\MtEdition;
use App\Models\Rating;
use App\Notifications\CustomResetPasswordNotification;
use App\Notifications\CustomVerifyEmailNotification;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\ObjetosValor\Utilizadores\NomeUtilizador;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Representa um utilizador autenticável da aplicação.
 *
 * O modelo mantém temporariamente a ligação à tabela física `users`, enquanto
 * as classes, relações, métodos e atributos públicos do projeto passam a
 * utilizar nomenclatura portuguesa.
 *
 * Os nomes `email`, `password`, `remember_token` e `email_verified_at`
 * permanecem como contratos técnicos do sistema de autenticação do Laravel.
 *
 * @property int $id
 * @property string $name
 * @property string $nome
 * @property string|null $email
 * @property string|null $email_verified_at
 * @property string|null $password
 * @property string|null $photo
 * @property string|null $fotografia
 * @property PapelUtilizador $papel
 * @property string|null $remember_token
 * @property-read string|null $url_fotografia
 * @property-read string $iniciais
 * @property-read string $primeiro_nome
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class Utilizador extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'users';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * `nome` e `fotografia` são atributos portugueses que escrevem,
     * respetivamente, nas colunas físicas `name` e `photo`.
     *
     * `password` é mantido por ser um contrato técnico da autenticação do
     * Laravel.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $fillable = [
        'nome',
        'email',
        'password',
        'fotografia',
        'papel',
    ];

    /**
     * Atributos omitidos nas representações serializadas.
     *
     * As colunas físicas `name` e `photo` são ocultadas porque são expostas
     * através dos atributos portugueses `nome` e `fotografia`.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $hidden = [
        'name',
        'photo',
        'password',
        'remember_token',
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
        'nome',
        'fotografia',
        'url_fotografia',
        'iniciais',
        'primeiro_nome',
    ];

    /**
     * Define as conversões dos atributos do modelo.
     *
     * @return array<string, string> - Conversões dos atributos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
            'papel' => PapelUtilizador::class,
        ];
    }

    /**
     * Expõe a coluna física `name` através do atributo `nome`.
     *
     * @return Attribute<string, string> - Atributo do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            get: static fn (
                mixed $valor,
                array $atributos,
            ): string => (string) ($atributos['name'] ?? ''),

            set: static fn (mixed $valor): array => [
                'name' => NomeUtilizador::deTexto(
                    (string) $valor,
                )->valor(),
            ],
        );
    }

    /**
     * Normaliza o endereço de e-mail antes da persistência.
     *
     * O valor nulo permanece permitido temporariamente devido aos registos
     * históricos que ainda possam não ter concluído o registo.
     *
     * @return Attribute<string|null, string|null> - Atributo do e-mail.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $valor): ?string => is_string($valor) && $valor !== ''
                ? $valor
                : null,

            set: static function (mixed $valor): ?string {
                if (
                    $valor === null
                    || (
                        is_string($valor)
                        && trim($valor) === ''
                    )
                ) {
                    return null;
                }

                return EnderecoEmail::deTexto(
                    (string) $valor,
                )->valor();
            },
        );
    }

    /**
     * Expõe a coluna física `photo` através do atributo `fotografia`.
     *
     * @return Attribute<string|null, string|null> - Atributo do caminho da
     *                                             fotografia.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function fotografia(): Attribute
    {
        return Attribute::make(
            get: static fn (
                mixed $valor,
                array $atributos,
            ): ?string => self::normalizarCaminhoFotografia(
                $atributos['photo'] ?? null,
            ),

            set: static fn (mixed $valor): array => [
                'photo' => self::normalizarCaminhoFotografia(
                    $valor,
                ),
            ],
        );
    }

    /**
     * Obtém a ligação pública da fotografia.
     *
     * @return Attribute<string|null, never> - Ligação da fotografia.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function urlFotografia(): Attribute
    {
        return Attribute::get(
            static function (
                mixed $valor,
                array $atributos,
            ): ?string {
                $caminho = self::normalizarCaminhoFotografia(
                    $atributos['photo'] ?? null,
                );

                if ($caminho === null) {
                    return null;
                }

                /** @var FilesystemAdapter $discoPublico */
                $discoPublico = Storage::disk('public');

                return $discoPublico->url($caminho);
            },
        );
    }

    /**
     * Obtém as iniciais do nome do utilizador.
     *
     * @return Attribute<string, never> - Iniciais do nome.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function iniciais(): Attribute
    {
        return Attribute::get(
            static function (
                mixed $valor,
                array $atributos,
            ): string {
                try {
                    return NomeUtilizador::deTexto(
                        (string) ($atributos['name'] ?? ''),
                    )->iniciais();
                } catch (InvalidArgumentException) {
                    return '?';
                }
            },
        );
    }

    /**
     * Obtém o primeiro nome do utilizador.
     *
     * @return Attribute<string, never> - Primeiro nome.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function primeiroNome(): Attribute
    {
        return Attribute::get(
            static function (
                mixed $valor,
                array $atributos,
            ): string {
                try {
                    return NomeUtilizador::deTexto(
                        (string) ($atributos['name'] ?? ''),
                    )->primeiroNome();
                } catch (InvalidArgumentException) {
                    return 'Utilizador';
                }
            },
        );
    }

    /**
     * Envia a notificação de verificação do endereço de e-mail.
     *
     * O nome deste método permanece em inglês porque substitui um ponto de
     * extensão definido pelo Laravel.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(
            new CustomVerifyEmailNotification,
        );
    }

    /**
     * Envia a notificação de reposição da palavra-passe.
     *
     * O nome deste método permanece em inglês porque substitui um ponto de
     * extensão definido pelo Laravel.
     *
     * @param  string  $token  - Token de reposição da palavra-passe.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function sendPasswordResetNotification(
        #[SensitiveParameter]
        $token,
    ): void {
        $this->notify(
            new CustomResetPasswordNotification(
                $token,
            ),
        );
    }

    /**
     * Obtém as permissões de e-mail do utilizador.
     *
     * @return BelongsToMany<EmailPermission, Utilizador> - Relação com as
     *                                                    permissões de e-mail.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function permissoesEmail(): BelongsToMany
    {
        return $this->belongsToMany(
            EmailPermission::class,
            'email_permission_user',
            'user_id',
            'email_permission_id',
        );
    }

    /**
     * Determina se o utilizador possui uma permissão de e-mail.
     *
     * Quando a relação já está carregada, a verificação é realizada em
     * memória. Caso contrário, é executada uma consulta `exists`, evitando
     * carregar todas as permissões.
     *
     * @param  string  $slug  - Identificador textual da permissão.
     * @return bool - Verdadeiro quando a permissão está atribuída.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function temPermissaoEmail(string $slug): bool
    {
        if ($this->relationLoaded('permissoesEmail')) {
            return $this
                ->getRelation('permissoesEmail')
                ->contains('slug', $slug);
        }

        return $this
            ->permissoesEmail()
            ->where('slug', $slug)
            ->exists();
    }

    /**
     * Determina se o utilizador possui o papel indicado.
     *
     * @param  PapelUtilizador  $papel  - Papel pretendido.
     * @return bool - Verdadeiro quando os papéis coincidem.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function possuiPapel(
        PapelUtilizador $papel,
    ): bool {
        return $this->papel === $papel;
    }

    /**
     * Determina se o utilizador possui privilégios administrativos.
     *
     * @return bool - Verdadeiro para administradores e
     *              superadministradores.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function possuiPrivilegiosAdministrativos(): bool
    {
        return $this
            ->papel
            ->possuiPrivilegiosAdministrativos();
    }

    /**
     * Determina se o utilizador é o superadministrador.
     *
     * @return bool - Verdadeiro apenas para o superadministrador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function eSuperAdministrador(): bool
    {
        return $this->papel->eSuperAdministrador();
    }

    /**
     * Limita a consulta aos utilizadores selecionáveis.
     *
     * O superadministrador deixa de ser identificado através do valor fixo
     * `id = 1`.
     *
     * @param  Builder<Utilizador>  $consulta  - Consulta dos utilizadores.
     * @return Builder<Utilizador> - Consulta ordenada dos utilizadores
     *                             selecionáveis.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function scopeSelecionaveis(
        Builder $consulta,
    ): Builder {
        return $consulta
            ->where(
                'papel',
                '!=',
                PapelUtilizador::SuperAdministrador->value,
            )
            ->orderBy('name');
    }

    /**
     * Obtém os convites criados pelo utilizador.
     *
     * @return HasMany<Convite, Utilizador> - Convites criados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function convitesCriados(): HasMany
    {
        return $this->hasMany(
            Convite::class,
            'criado_por',
        );
    }

    /**
     * Obtém as edições criadas pelo utilizador.
     *
     * A chave estrangeira é explicitada porque a tabela `mt_editions` utiliza
     * `created_by`, e não a convenção `user_id`.
     *
     * @return HasMany<MtEdition, Utilizador> - Edições criadas.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function edicoesCriadas(): HasMany
    {
        return $this->hasMany(
            MtEdition::class,
            'created_by',
        );
    }

    /**
     * Obtém as MetalThursdays em que o utilizador foi autor.
     *
     * @return HasMany<MetalThursday, Utilizador> - MetalThursdays como autor.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function metalThursdaysComoAutor(): HasMany
    {
        return $this->hasMany(
            MetalThursday::class,
            'author_id',
        );
    }

    /**
     * Obtém as MetalThursdays em que o utilizador foi nomeado.
     *
     * @return HasMany<MetalThursday, Utilizador> - MetalThursdays como
     *                                            nomeado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function metalThursdaysComoNomeado(): HasMany
    {
        return $this->hasMany(
            MetalThursday::class,
            'next_nominee_id',
        );
    }

    /**
     * Obtém as MetalThursdays criadas pelo utilizador.
     *
     * @return HasMany<MetalThursday, Utilizador> - MetalThursdays criadas.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function metalThursdaysCriadas(): HasMany
    {
        return $this->hasMany(
            MetalThursday::class,
            'created_by',
        );
    }

    /**
     * Obtém os comentários publicados pelo utilizador.
     *
     * @return HasMany<Comment, Utilizador> - Comentários do utilizador.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function comentarios(): HasMany
    {
        return $this->hasMany(
            Comment::class,
            'user_id',
        );
    }

    /**
     * Obtém os gostos registados pelo utilizador.
     *
     * @return HasMany<Like, Utilizador> - Gostos do utilizador.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function gostos(): HasMany
    {
        return $this->hasMany(
            Like::class,
            'user_id',
        );
    }

    /**
     * Obtém as audições registadas pelo utilizador.
     *
     * @return HasMany<Listen, Utilizador> - Audições do utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function audicoes(): HasMany
    {
        return $this->hasMany(
            Listen::class,
            'user_id',
        );
    }

    /**
     * Obtém as classificações atribuídas pelo utilizador.
     *
     * @return HasMany<Rating, Utilizador> - Classificações do utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function classificacoes(): HasMany
    {
        return $this->hasMany(
            Rating::class,
            'user_id',
        );
    }

    /**
     * Obtém as entradas de classificação associadas ao utilizador.
     *
     * @return HasMany<EditionRanking, Utilizador> - Entradas associadas ao
     *                                             utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function entradasClassificacaoEdicoes(): HasMany
    {
        return $this->hasMany(
            EditionRanking::class,
            'user_id',
        );
    }

    /**
     * Obtém as entradas de classificação submetidas pelo utilizador.
     *
     * @return HasMany<EditionRanking, Utilizador> - Entradas submetidas pelo
     *                                             utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function entradasClassificacaoEdicoesSubmetidas(): HasMany
    {
        return $this->hasMany(
            EditionRanking::class,
            'submitted_by',
        );
    }

    /**
     * Normaliza o caminho da fotografia.
     *
     * @param  mixed  $caminho  - Caminho recebido.
     * @return string|null - Caminho normalizado ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static function normalizarCaminhoFotografia(
        mixed $caminho,
    ): ?string {
        if (! is_string($caminho)) {
            return null;
        }

        $caminhoNormalizado = trim($caminho);

        return $caminhoNormalizado !== ''
            ? $caminhoNormalizado
            : null;
    }
}
