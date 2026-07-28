<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\AceitarConviteRequest;
use App\Models\Comunicacao\PermissaoEmail;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use App\Servicos\Autenticacao\ServicoConvites;
use App\Servicos\Autenticacao\ServicoRegistoPorConvite;
use DomainException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SensitiveParameter;
use Throwable;

/**
 * Gere a apresentação e o processamento do registo por convite.
 *
 * O controlador coordena exclusivamente o fluxo HTTP, delegando a consulta
 * e utilização dos convites e o registo do utilizador nos respetivos
 * serviços de aplicação.
 *
 * @since 1.0.0
 *
 * @version 3.2.0
 */
final class ControladorRegistoConvite extends Controller
{
    /**
     * Identificador da permissão que ativa todas as comunicações por e-mail.
     *
     * @var string
     *
     * @since 3.2.0
     *
     * @version 1.0.0
     */
    private const IDENTIFICADOR_PERMISSAO_TODAS =
        'todas';

    /**
     * Cria uma nova instância do controlador.
     *
     * @param  ServicoConvites  $servicoConvites  Serviço responsável pelos
     *                                            convites.
     * @param  ServicoRegistoPorConvite  $servicoRegistoPorConvite  Serviço
     *                                                              responsável
     *                                                              pelo
     *                                                              registo.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly ServicoConvites $servicoConvites,
        private readonly ServicoRegistoPorConvite $servicoRegistoPorConvite,
    ) {}

    /**
     * Apresenta o formulário de aceitação de um convite disponível.
     *
     * A mesma mensagem é apresentada para convites inexistentes, utilizados,
     * revogados ou expirados, evitando revelar o estado interno do convite.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  string  $codigoConvite  Código original do convite.
     * @return View|RedirectResponse Formulário ou redirecionamento.
     *
     * @since 1.0.0
     *
     * @version 3.2.0
     */
    public function apresentar(
        Request $pedido,
        #[SensitiveParameter]
        string $codigoConvite,
    ): View|RedirectResponse {
        $convite =
            $this->servicoConvites
                ->encontrarDisponivelPorCodigo(
                    $codigoConvite,
                );

        if ($convite === null) {
            return to_route(
                'login',
            )->with(
                'erro',
                'Este convite é inválido ou já não está disponível.',
            );
        }

        $permissoesEmail =
            PermissaoEmail::query()
                ->select([
                    'id',
                    'identificador',
                    'nome',
                    'descricao',
                ])
                ->orderBy(
                    'nome',
                )
                ->get();

        $permissaoTodas =
            $permissoesEmail
                ->firstWhere(
                    'identificador',
                    self::IDENTIFICADOR_PERMISSAO_TODAS,
                );

        $outrasPermissoes =
            $permissoesEmail
                ->where(
                    'identificador',
                    '!=',
                    self::IDENTIFICADOR_PERMISSAO_TODAS,
                )
                ->values();

        $emailConvite =
            trim(
                (string) (
                    $convite->email_destino
                    ?? ''
                ),
            );

        return view(
            'autenticacao.aceitar-convite',
            [
                'convite' => $convite,

                'codigoConvite' => $codigoConvite,

                'iniciaisConvidado' => $this->obterIniciais(
                    $convite->nome_convidado,
                ),

                'emailConvite' => $emailConvite,

                'emailBloqueado' => $emailConvite !== '',

                'permissaoTodas' => $permissaoTodas,

                'outrasPermissoes' => $outrasPermissoes,

                'permissoesSelecionadas' => $this->obterPermissoesSelecionadas(
                    $pedido,
                ),

                'comprimentoMinimoPalavraPasse' => RequisitosPalavraPasse::comprimentoMinimo(),

                'comprimentoMaximoPalavraPasse' => RequisitosPalavraPasse::comprimentoMaximo(),
            ],
        );
    }

    /**
     * Cria o utilizador e marca o convite como utilizado.
     *
     * O armazenamento da fotografia, a criação do utilizador, a atribuição
     * das permissões e a utilização do convite são responsabilidade do
     * serviço de registo.
     *
     * As falhas de domínio esperadas são convertidas numa resposta segura,
     * sem apresentar ao visitante os detalhes internos da exceção.
     *
     * @param  AceitarConviteRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento após a tentativa de registo.
     *
     * @throws Throwable Quando ocorre uma falha técnica inesperada.
     *
     * @since 1.0.0
     *
     * @version 3.2.0
     */
    public function registar(
        AceitarConviteRequest $pedido,
    ): RedirectResponse {
        try {
            $utilizador =
                $this
                    ->servicoRegistoPorConvite
                    ->registar(
                        codigoConvite: $pedido->codigoConvite(),

                        nome: $pedido->nome(),

                        email: $pedido->email(),

                        palavraPasse: $pedido->palavraPasse(),

                        fotografia: $pedido->fotografia(),

                        identificadoresPermissoesEmail: $pedido->identificadoresPermissoesEmail(),
                    );
        } catch (DomainException) {
            return to_route(
                'convites.aceitar',
                [
                    'codigoConvite' => $pedido->codigoConvite(),
                ],
            )
                ->withInput([
                    'nome' => $pedido->nome(),

                    'email' => $pedido->email(),

                    'permissoes_email' => $pedido->identificadoresPermissoesEmail(),
                ])
                ->with(
                    'erro',
                    'Não foi possível concluir o registo. Confirma os dados e verifica se o convite continua disponível.',
                );
        }

        event(
            new Registered(
                $utilizador,
            ),
        );

        return to_route(
            'login',
        )->with(
            'sucesso',
            'Registo concluído. Consulta o teu e-mail para confirmares a conta.',
        );
    }

    /**
     * Obtém as permissões de e-mail anteriormente submetidas.
     *
     * Os identificadores são normalizados para texto para permitir uma
     * comparação consistente com os identificadores apresentados na view.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return Collection<int, string> Identificadores selecionados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterPermissoesSelecionadas(
        Request $pedido,
    ): Collection {
        $identificadores =
            $pedido->old(
                'permissoes_email',
                [],
            );

        if (! is_array($identificadores)) {
            return collect();
        }

        return collect(
            $identificadores,
        )
            ->filter(
                static fn (mixed $identificador): bool => is_int($identificador)
                    || (
                        is_string($identificador)
                        && ctype_digit(
                            $identificador,
                        )
                    ),
            )
            ->map(
                static fn (int|string $identificador): string => (string) $identificador,
            )
            ->unique()
            ->values();
    }

    /**
     * Obtém as iniciais de um nome.
     *
     * Para nomes compostos são utilizadas a primeira e a última palavras.
     * Quando o nome está vazio, é devolvido um ponto de interrogação.
     *
     * @param  string  $nome  Nome a abreviar.
     * @return string Iniciais do nome.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterIniciais(
        string $nome,
    ): string {
        $partesNome =
            Str::of(
                $nome,
            )
                ->squish()
                ->explode(
                    ' ',
                )
                ->filter()
                ->values();

        if ($partesNome->isEmpty()) {
            return '?';
        }

        $partesSelecionadas =
            $partesNome->count() > 1
            ? collect([
                $partesNome->first(),
                $partesNome->last(),
            ])
            : $partesNome;

        return $partesSelecionadas
            ->map(
                static fn (string $parte): string => Str::upper(
                    Str::substr(
                        $parte,
                        0,
                        1,
                    ),
                ),
            )
            ->implode(
                '',
            );
    }
}
