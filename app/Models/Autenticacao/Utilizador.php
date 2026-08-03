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
use App\Models\Notificacoes\NotificacaoPersistida;
use App\Notifications\NotificacaoRedefinicaoPalavraPasse;
use App\Notifications\NotificacaoVerificacaoEmail;
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use SensitiveParameter;

/**
 * Representa um utilizador autenticável da aplicação.
 *
 * Os nomes `email`, `password`, `remember_token`, `email_verified_at`,
 * `notifications`, `readNotifications` e `unreadNotifications` permanecem
 * por corresponderem a contratos técnicos do Laravel.
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
 * @property-read Collection<int, NotificacaoPersistida> $notificacoes
 * @property-read Collection<int, NotificacaoPersistida> $notificacoesLidas
 * @property-read Collection<int, NotificacaoPersistida> $notificacoesPorLer
 * @property-read Collection<int, NotificacaoPersistida> $notifications
 * @property-read Collection<int, NotificacaoPersistida> $readNotifications
 * @property-read Collection<int, NotificacaoPersistida> $unreadNotifications
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
 * @version 3.0.0
 */
class Utilizador extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UtilizadorFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * Nome do disco que contém as fotografias públicas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const DISCO_FOTOGRAFIAS =
        'publico';

    /**
     * Comprimento máximo do caminho persistido da fotografia.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_CAMINHO_FOTOGRAFIA =
        255;

    /**
     * Nome da tabela intermédia entre permissões e utilizadores.
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
    protected $table = 'utilizadores';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * O papel não é preenchível em massa para impedir que dados externos
     * possam atribuir privilégios administrativos. Deve ser definido
     * explicitamente pelo serviço responsável.
     *
     * @var list<string>
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected $fillable = [
        'nome',
        'email',
        'password',
        'fotografia',
    ];

    /**
     * Atributos omitidos nas representações serializadas.
     *
     * @var list<string>
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
     * @var list<string>
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
     * Normaliza e valida o nome antes da persistência.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @throws InvalidArgumentException Quando o valor não é textual ou o nome
     *                                  não é válido.
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
                        'O nome do utilizador deve ser uma sequência de caracteres.',
                    );
                }

                return NomeUtilizador::deTexto(
                    $valor,
                )->valor();
            },
        );
    }

    /**
     * Normaliza e valida o endereço de e-mail antes da persistência.
     *
     * @return Attribute<string, string> Atributo do endereço.
     *
     * @throws InvalidArgumentException Quando o valor não é textual ou o
     *                                  endereço não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                if (! is_string($valor)) {
                    throw new InvalidArgumentException(
                        'O endereço de e-mail deve ser uma sequência de caracteres.',
                    );
                }

                return EnderecoEmail::deTexto(
                    $valor,
                )->valor();
            },
        );
    }

    /**
     * Normaliza o caminho relativo da fotografia.
     *
     * @return Attribute<string|null, string|null> Atributo da fotografia.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function fotografia(): Attribute
    {
        return Attribute::make(
            get: static fn (
                mixed $valor,
            ): ?string => self::normalizarCaminhoFotografia(
                $valor,
            ),

            set: static fn (
                mixed $valor,
            ): ?string => self::normalizarCaminhoFotografia(
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
     * @version 3.0.0
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
                        $atributos['fotografia']
                            ?? null,
                    );

                if ($caminho === null) {
                    return null;
                }

                /** @var FilesystemAdapter $discoPublico */
                $discoPublico = Storage::disk(
                    self::DISCO_FOTOGRAFIAS,
                );

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
     * O nome permanece em inglês por substituir o ponto de extensão definido
     * pelo Laravel.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(
            new NotificacaoVerificacaoEmail,
        );
    }

    /**
     * Envia a notificação de redefinição da palavra-passe.
     *
     * O nome permanece em inglês por substituir o ponto de extensão definido
     * pelo Laravel.
     *
     * @param  mixed  $token  Token gerado pelo gestor de palavras-passe.
     *
     * @throws LogicException Quando não é recebido um token textual válido.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function sendPasswordResetNotification(
        #[SensitiveParameter]
        mixed $token,
    ): void {
        if (
            ! is_string($token)
            || trim($token) === ''
        ) {
            throw new LogicException(
                'Não foi recebido um token válido para redefinir a palavra-passe.',
            );
        }

        $this->notify(
            new NotificacaoRedefinicaoPalavraPasse(
                $token,
            ),
        );
    }

    /**
     * Obtém todas as notificações persistidas do utilizador.
     *
     * @return MorphMany<NotificacaoPersistida, $this> Relação com as
     *                                                 notificações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function notificacoes(): MorphMany
    {
        return $this
            ->morphMany(
                NotificacaoPersistida::class,
                'notifiable',
            )
            ->latest();
    }

    /**
     * Obtém as notificações já lidas.
     *
     * @return MorphMany<NotificacaoPersistida, $this> Relação filtrada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function notificacoesLidas(): MorphMany
    {
        return $this
            ->notificacoes()
            ->read();
    }

    /**
     * Obtém as notificações ainda não lidas.
     *
     * @return MorphMany<NotificacaoPersistida, $this> Relação filtrada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function notificacoesPorLer(): MorphMany
    {
        return $this
            ->notificacoes()
            ->unread();
    }

    /**
     * Obtém todas as notificações persistidas.
     *
     * O nome permanece em inglês porque corresponde ao contrato utilizado
     * internamente pelo canal de notificações de base de dados do Laravel.
     *
     * @return MorphMany<NotificacaoPersistida, $this> Relação com as
     *                                                 notificações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function notifications(): MorphMany
    {
        return $this->notificacoes();
    }

    /**
     * Obtém as notificações já lidas.
     *
     * O nome permanece em inglês por corresponder ao contrato técnico do
     * Laravel.
     *
     * @return MorphMany<NotificacaoPersistida, $this> Relação filtrada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function readNotifications(): MorphMany
    {
        return $this->notificacoesLidas();
    }

    /**
     * Obtém as notificações ainda não lidas.
     *
     * O nome permanece em inglês por corresponder ao contrato técnico do
     * Laravel.
     *
     * @return MorphMany<NotificacaoPersistida, $this> Relação filtrada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function unreadNotifications(): MorphMany
    {
        return $this->notificacoesPorLer();
    }

    /**
     * Obtém as permissões de e-mail do utilizador.
     *
     * @return BelongsToMany<PermissaoEmail, $this> Relação com as permissões.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function permissoesEmail(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                PermissaoEmail::class,
                self::TABELA_PERMISSAO_UTILIZADOR,
                'utilizador_id',
                'permissao_email_id',
            )
            ->orderBy(
                'permissoes_email.ordem',
            )
            ->orderBy(
                'permissoes_email.id',
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
     * @version 3.0.0
     */
    public function temPermissaoEmail(
        string $identificador,
    ): bool {
        $identificadorNormalizado = mb_strtolower(
            trim(
                $identificador,
            ),
        );

        if (
            $identificadorNormalizado === ''
            || preg_match(
                '/\A[a-z0-9]+(?:_[a-z0-9]+)*\z/',
                $identificadorNormalizado,
            ) !== 1
        ) {
            return false;
        }

        if ($this->relationLoaded('permissoesEmail')) {
            return $this
                ->permissoesEmail
                ->contains(
                    static fn (
                        PermissaoEmail $permissao,
                    ): bool => $permissao->identificador
                        === $identificadorNormalizado,
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
     * O superadministrador não pode ser selecionado como autor ou próximo
     * nomeado.
     *
     * @param  Builder<Utilizador>  $construtor  Consulta dos utilizadores.
     * @return Builder<Utilizador> Consulta filtrada.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function scopeSelecionaveis(
        Builder $construtor,
    ): Builder {
        return $construtor
            ->where(
                'papel',
                '!=',
                PapelUtilizador::SuperAdministrador->value,
            )
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * Obtém os convites criados pelo utilizador.
     *
     * @return HasMany<Convite, $this> Relação com os convites.
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
     * @return HasOne<Convite, $this> Relação com o convite utilizado.
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
     * @return HasMany<Edicao, $this> Relação com as edições.
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
     * @return HasMany<MetalThursday, $this> Relação com as MetalThursdays.
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
     * @return HasMany<MetalThursday, $this> Relação com as MetalThursdays.
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
     * @return HasMany<MetalThursday, $this> Relação com as MetalThursdays.
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
     * @return HasMany<Comentario, $this> Relação com os comentários.
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
     * @return HasMany<Gosto, $this> Relação com os gostos.
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
     * @return HasMany<Audicao, $this> Relação com as audições.
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
     * @return HasMany<Avaliacao, $this> Relação com as avaliações.
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
     * @return HasMany<MusicaFavoritaEdicao, $this> Relação com as músicas.
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
     * @return HasMany<MusicaFavoritaEdicao, $this> Relação com as músicas.
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
     * Normaliza e valida o caminho relativo da fotografia.
     *
     * Ligações externas, caminhos absolutos, caracteres de controlo,
     * segmentos vazios e travessias de diretórios não são aceites.
     *
     * @param  mixed  $caminho  Caminho recebido.
     * @return string|null Caminho normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o caminho não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private static function normalizarCaminhoFotografia(
        mixed $caminho,
    ): ?string {
        if ($caminho === null) {
            return null;
        }

        if (! is_string($caminho)) {
            throw new InvalidArgumentException(
                'O caminho da fotografia deve ser uma sequência de caracteres.',
            );
        }

        $caminhoNormalizado = trim(
            str_replace(
                '\\',
                '/',
                $caminho,
            ),
        );

        if ($caminhoNormalizado === '') {
            return null;
        }

        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $caminhoNormalizado,
            ) === 1
            || str_starts_with(
                $caminhoNormalizado,
                '/',
            )
            || preg_match(
                '/\A[a-z][a-z0-9+.-]*:/i',
                $caminhoNormalizado,
            ) === 1
            || str_contains(
                $caminhoNormalizado,
                '?',
            )
            || str_contains(
                $caminhoNormalizado,
                '#',
            )
        ) {
            throw new InvalidArgumentException(
                'O caminho da fotografia deve ser relativo ao disco público.',
            );
        }

        if (
            mb_strlen(
                $caminhoNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_CAMINHO_FOTOGRAFIA
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O caminho da fotografia não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_CAMINHO_FOTOGRAFIA,
                ),
            );
        }

        $segmentos = explode(
            '/',
            $caminhoNormalizado,
        );

        foreach ($segmentos as $segmento) {
            if (
                $segmento === ''
                || $segmento === '.'
                || $segmento === '..'
            ) {
                throw new InvalidArgumentException(
                    'O caminho da fotografia contém segmentos inválidos.',
                );
            }
        }

        return implode(
            '/',
            $segmentos,
        );
    }
}
