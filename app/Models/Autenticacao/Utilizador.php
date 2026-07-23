<?php

declare(strict_types=1);

namespace App\Models\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\Interacoes\Audicao;
use App\Models\Interacoes\Avaliacao;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\Gosto;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use App\Notifications\CustomResetPasswordNotification;
use App\Notifications\CustomVerifyEmailNotification;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\ObjetosValor\Utilizadores\NomeUtilizador;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\Autenticacao\UtilizadorFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Representa um utilizador autenticável da aplicação.
 *
 * Os nomes `email`, `password`, `remember_token` e `email_verified_at`
 * permanecem por fazerem parte dos contratos técnicos de autenticação,
 * recuperação de palavra-passe e verificação de e-mail do Laravel.
 *
 * @property int $id
 * @property string $nome
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $fotografia
 * @property PapelUtilizador $papel
 * @property string|null $remember_token
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read string|null $url_fotografia
 * @property-read string $iniciais
 * @property-read string $primeiro_nome
 * @property-read Collection<int, PermissaoEmail> $permissoesEmail
 * @property-read Collection<int, Convite> $convitesCriados
 * @property-read Convite|null $conviteUtilizado
 * @property-read Collection<int, Edicao> $edicoesCriadas
 * @property-read Collection<int, MetalThursday> $metalThursdaysComoAutor
 * @property-read Collection<int, MetalThursday> $metalThursdaysComoNomeado
 * @property-read Collection<int, MetalThursday> $metalThursdaysCriadas
 * @property-read Collection<int, Comentario> $comentarios
 * @property-read Collection<int, Gosto> $gostos
 * @property-read Collection<int, Audicao> $audicoes
 * @property-read Collection<int, Avaliacao> $avaliacoes
 * @property-read Collection<int, MusicaFavoritaEdicao> $musicasFavoritasEdicao
 * @property-read Collection<int, MusicaFavoritaEdicao> $musicasFavoritasEdicaoRegistadas
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class Utilizador extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UtilizadorFactory> */
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
    protected $table = 'utilizadores';

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
        'email',
        'password',
        'fotografia',
        'papel',
    ];

    /**
     * Atributos omitidos nas representações serializadas.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $hidden = [
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
        'url_fotografia',
        'iniciais',
        'primeiro_nome',
    ];

    /**
     * Define as conversões dos atributos do modelo.
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
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
            'papel' => PapelUtilizador::class,
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * A associação é explícita porque o modelo e a factory se encontram em
     * namespaces próprios.
     *
     * @return UtilizadorFactory Factory dos utilizadores.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): UtilizadorFactory
    {
        return UtilizadorFactory::new();
    }

    /**
     * Normaliza o nome antes da persistência.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static fn (mixed $valor): string => NomeUtilizador::deTexto(
                (string) $valor,
            )->valor(),
        );
    }

    /**
     * Normaliza o endereço de e-mail antes da persistência.
     *
     * A estrutura não permite endereços de e-mail nulos ou vazios.
     *
     * @return Attribute<string, string> Atributo do endereço de e-mail.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: static fn (mixed $valor): string => EnderecoEmail::deTexto(
                (string) $valor,
            )->valor(),
        );
    }

    /**
     * Normaliza o caminho da fotografia.
     *
     * @return Attribute<string|null, string|null> Atributo da fotografia.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function fotografia(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $valor): ?string => self::normalizarCaminhoFotografia(
                $valor,
            ),

            set: static fn (mixed $valor): ?string => self::normalizarCaminhoFotografia(
                $valor,
            ),
        );
    }

    /**
     * Obtém a ligação pública da fotografia.
     *
     * @return Attribute<string|null, never> Ligação pública da fotografia.
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
                $caminho =
                    self::normalizarCaminhoFotografia(
                        $atributos['fotografia'] ?? null,
                    );

                if ($caminho === null) {
                    return null;
                }

                /** @var FilesystemAdapter $discoPublico */
                $discoPublico =
                    Storage::disk('public');

                return $discoPublico->url(
                    $caminho,
                );
            },
        );
    }

    /**
     * Obtém as iniciais do nome do utilizador.
     *
     * @return Attribute<string, never> Iniciais do nome.
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
                        (string) (
                            $atributos['nome']
                            ?? ''
                        ),
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
     * @return Attribute<string, never> Primeiro nome.
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
                        (string) (
                            $atributos['nome']
                            ?? ''
                        ),
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
     * O nome permanece em inglês por substituir um ponto de extensão
     * definido pelo Laravel.
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
     * O nome permanece em inglês por substituir um ponto de extensão
     * definido pelo Laravel.
     *
     * @param  mixed  $token  Token de reposição da palavra-passe.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function sendPasswordResetNotification(
        #[SensitiveParameter]
        mixed $token,
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
     * @return BelongsToMany<PermissaoEmail, $this> Permissões de e-mail.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function permissoesEmail(): BelongsToMany
    {
        return $this->belongsToMany(
            PermissaoEmail::class,
            'permissao_email_utilizador',
            'utilizador_id',
            'permissao_email_id',
        );
    }

    /**
     * Determina se o utilizador possui uma permissão de e-mail.
     *
     * @param  string  $identificador  Identificador da permissão.
     * @return bool Verdadeiro quando a permissão está atribuída.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function temPermissaoEmail(
        string $identificador,
    ): bool {
        $identificadorNormalizado =
            strtolower(
                trim(
                    $identificador,
                ),
            );

        if ($identificadorNormalizado === '') {
            return false;
        }

        if ($this->relationLoaded('permissoesEmail')) {
            return $this
                ->getRelation('permissoesEmail')
                ->contains(
                    'identificador',
                    $identificadorNormalizado,
                );
        }

        return $this
            ->permissoesEmail()
            ->where(
                'permissoes_email.identificador',
                $identificadorNormalizado,
            )
            ->exists();
    }

    /**
     * Determina se o utilizador possui o papel indicado.
     *
     * @param  PapelUtilizador  $papel  Papel pretendido.
     * @return bool Verdadeiro quando os papéis coincidem.
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
     * @return bool Verdadeiro para administradores e superadministradores.
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
     * @return bool Verdadeiro apenas para o superadministrador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function eSuperAdministrador(): bool
    {
        return $this
            ->papel
            ->eSuperAdministrador();
    }

    /**
     * Limita a consulta aos utilizadores selecionáveis.
     *
     * @param  Builder<Utilizador>  $consulta  Consulta dos utilizadores.
     * @return Builder<Utilizador> Consulta dos utilizadores selecionáveis.
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
            ->orderBy('nome');
    }

    /**
     * Obtém os convites criados pelo utilizador.
     *
     * @return HasMany<Convite, $this> Convites criados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function convitesCriados(): HasMany
    {
        return $this->hasMany(
            Convite::class,
            'criado_por_id',
        );
    }

    /**
     * Obtém o convite utilizado para criar o utilizador.
     *
     * @return HasOne<Convite, $this> Convite utilizado pelo utilizador.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function conviteUtilizado(): HasOne
    {
        return $this->hasOne(
            Convite::class,
            'utilizado_por_id',
        );
    }

    /**
     * Obtém as edições criadas pelo utilizador.
     *
     * @return HasMany<Edicao, $this> Edições criadas.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function edicoesCriadas(): HasMany
    {
        return $this->hasMany(
            Edicao::class,
            'criado_por_id',
        );
    }

    /**
     * Obtém as MetalThursdays em que o utilizador foi autor.
     *
     * @return HasMany<MetalThursday, $this> MetalThursdays como autor.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function metalThursdaysComoAutor(): HasMany
    {
        return $this->hasMany(
            MetalThursday::class,
            'autor_id',
        );
    }

    /**
     * Obtém as MetalThursdays em que o utilizador foi o próximo nomeado.
     *
     * @return HasMany<MetalThursday, $this> MetalThursdays como próximo
     *                                       nomeado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function metalThursdaysComoNomeado(): HasMany
    {
        return $this->hasMany(
            MetalThursday::class,
            'proximo_nomeado_id',
        );
    }

    /**
     * Obtém as MetalThursdays criadas pelo utilizador.
     *
     * @return HasMany<MetalThursday, $this> MetalThursdays criadas.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function metalThursdaysCriadas(): HasMany
    {
        return $this->hasMany(
            MetalThursday::class,
            'criado_por_id',
        );
    }

    /**
     * Obtém os comentários publicados pelo utilizador.
     *
     * @return HasMany<Comentario, $this> Comentários do utilizador.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function comentarios(): HasMany
    {
        return $this->hasMany(
            Comentario::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém os gostos registados pelo utilizador.
     *
     * @return HasMany<Gosto, $this> Gostos do utilizador.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function gostos(): HasMany
    {
        return $this->hasMany(
            Gosto::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém as audições registadas pelo utilizador.
     *
     * @return HasMany<Audicao, $this> Audições do utilizador.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function audicoes(): HasMany
    {
        return $this->hasMany(
            Audicao::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém as avaliações atribuídas pelo utilizador.
     *
     * @return HasMany<Avaliacao, $this> Avaliações do utilizador.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function avaliacoes(): HasMany
    {
        return $this->hasMany(
            Avaliacao::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém as músicas favoritas escolhidas pelo utilizador.
     *
     * @return HasMany<MusicaFavoritaEdicao, $this> Músicas favoritas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function musicasFavoritasEdicao(): HasMany
    {
        return $this->hasMany(
            MusicaFavoritaEdicao::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém as músicas favoritas registadas pelo utilizador.
     *
     * @return HasMany<MusicaFavoritaEdicao, $this> Músicas favoritas
     *                                              registadas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function musicasFavoritasEdicaoRegistadas(): HasMany
    {
        return $this->hasMany(
            MusicaFavoritaEdicao::class,
            'registado_por_id',
        );
    }

    /**
     * Normaliza o caminho da fotografia.
     *
     * O caminho deve ser relativo ao disco público. Ligações externas,
     * caminhos absolutos e segmentos de travessia não são aceites.
     *
     * @param  mixed  $caminho  Caminho recebido.
     * @return string|null Caminho normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o caminho não é seguro.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private static function normalizarCaminhoFotografia(
        mixed $caminho,
    ): ?string {
        if (! is_string($caminho)) {
            return null;
        }

        $caminhoNormalizado =
            str_replace(
                '\\',
                '/',
                trim($caminho),
            );

        if ($caminhoNormalizado === '') {
            return null;
        }

        if (
            str_contains(
                $caminhoNormalizado,
                "\0",
            )
            || preg_match(
                '/^[a-z][a-z0-9+.-]*:/i',
                $caminhoNormalizado,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'O caminho da fotografia deve ser relativo ao disco público.',
            );
        }

        $caminhoNormalizado =
            ltrim(
                $caminhoNormalizado,
                '/',
            );

        $segmentosNormalizados = [];

        foreach (
            explode(
                '/',
                $caminhoNormalizado,
            ) as $segmento
        ) {
            if (
                $segmento === ''
                || $segmento === '.'
            ) {
                continue;
            }

            if ($segmento === '..') {
                throw new InvalidArgumentException(
                    'O caminho da fotografia não pode conter travessias de diretórios.',
                );
            }

            $segmentosNormalizados[] =
                $segmento;
        }

        return $segmentosNormalizados !== []
            ? implode(
                '/',
                $segmentosNormalizados,
            )
            : null;
    }
}
