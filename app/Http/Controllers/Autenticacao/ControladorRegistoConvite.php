<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\AceitarConviteRequest;
use App\Models\Comunicacao\PermissaoEmail;
use App\ObjetosValor\Utilizadores\NomeUtilizador;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use App\Servicos\Autenticacao\ServicoConvites;
use App\Servicos\Autenticacao\ServicoRegistoPorConvite;
use DomainException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use SensitiveParameter;
use Throwable;

/**
 * Gere a apresentação e o processamento do registo por convite.
 *
 * O controlador coordena exclusivamente o fluxo HTTP, delegando a consulta
 * dos convites e o registo transacional do utilizador nos respetivos serviços
 * de aplicação.
 *
 * O registo não inicia automaticamente uma sessão. O novo utilizador deve
 * confirmar o endereço de e-mail antes de se poder autenticar.
 *
 * @since 1.0.0
 *
 * @version 4.0.0
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
     * Mensagem apresentada para qualquer convite indisponível.
     *
     * A mensagem não distingue convites inexistentes, utilizados, revogados
     * ou expirados.
     *
     * @var string
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private const MENSAGEM_CONVITE_INDISPONIVEL =
        'Este convite é inválido ou já não está disponível.';

    /**
     * Mensagem apresentada quando o registo não pode ser concluído.
     *
     * Os detalhes internos da exceção de domínio nunca são expostos ao
     * visitante.
     *
     * @var string
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private const MENSAGEM_REGISTO_NAO_CONCLUIDO =
        'Não foi possível concluir o registo. Confirma os dados e verifica se o convite continua disponível.';

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
     * @version 4.0.0
     */
    public function apresentar(
        Request $pedido,
        #[SensitiveParameter]
        string $codigoConvite,
    ): View|RedirectResponse {
        $convite =
            $this
                ->servicoConvites
                ->encontrarDisponivelPorCodigo(
                    $codigoConvite,
                );

        if ($convite === null) {
            return to_route(
                'login',
            )->with(
                'erro',
                self::MENSAGEM_CONVITE_INDISPONIVEL,
            );
        }

        $permissoesEmail =
            PermissaoEmail::query()
                ->select([
                    'id',
                    'identificador',
                    'nome',
                    'descricao',
                    'ordem',
                ])
                ->orderBy(
                    'ordem',
                )
                ->orderBy(
                    'id',
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

        /*
         * O atributo do modelo já devolve o endereço normalizado ou nulo
         * através do objeto de valor EnderecoEmail.
         */
        $emailConvite =
            $convite->email_destino
            ?? '';

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
     * Falhas técnicas inesperadas não são ocultadas e permanecem entregues ao
     * tratamento global de exceções.
     *
     * @param  AceitarConviteRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento após a tentativa de registo.
     *
     * @throws Throwable Quando ocorre uma falha técnica inesperada.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function registar(
        AceitarConviteRequest $pedido,
    ): RedirectResponse {
        $codigoConvite =
            $pedido->codigoConvite();

        $nome =
            $pedido->nome();

        $email =
            $pedido->email();

        $palavraPasse =
            $pedido->palavraPasse();

        $fotografia =
            $pedido->fotografia();

        $identificadoresPermissoesEmail =
            $pedido->identificadoresPermissoesEmail();

        try {
            $utilizador =
                $this
                    ->servicoRegistoPorConvite
                    ->registar(
                        codigoConvite: $codigoConvite,
                        nome: $nome,
                        email: $email,
                        palavraPasse: $palavraPasse,
                        fotografia: $fotografia,
                        identificadoresPermissoesEmail: $identificadoresPermissoesEmail,
                    );
        } catch (DomainException) {
            return to_route(
                'convites.aceitar',
                [
                    'codigoConvite' => $codigoConvite,
                ],
            )
                ->withInput([
                    'nome' => $nome,

                    'email' => $email,

                    'permissoes_email' => $identificadoresPermissoesEmail,
                ])
                ->with(
                    'erro',
                    self::MENSAGEM_REGISTO_NAO_CONCLUIDO,
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
     * Os identificadores são convertidos para texto canónico para permitir
     * uma comparação consistente com os valores dos checkboxes da vista.
     *
     * Valores inválidos são ignorados apenas durante a reconstrução visual do
     * formulário. A validação definitiva pertence ao Form Request.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return Collection<int, string> Identificadores selecionados.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
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
                static fn (int|string $identificador): int => (int) $identificador,
            )
            ->filter(
                static fn (int $identificador): bool => $identificador > 0,
            )
            ->map(
                static fn (int $identificador): string => (string) $identificador,
            )
            ->unique(
                strict: true,
            )
            ->values();
    }

    /**
     * Obtém as iniciais de um nome.
     *
     * A criação das iniciais pertence ao objeto de valor do nome do
     * utilizador. Um nome inválido neste ponto representa uma violação dos
     * invariantes do modelo persistido.
     *
     * @param  string  $nome  Nome a abreviar.
     * @return string Iniciais do nome.
     *
     * @throws LogicException Quando o nome persistido viola o contrato do
     *                        objeto de valor.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterIniciais(
        string $nome,
    ): string {
        try {
            return NomeUtilizador::deTexto(
                $nome,
            )->iniciais();
        } catch (InvalidArgumentException $excecao) {
            throw new LogicException(
                'O convite disponível contém um nome de convidado inválido.',
                previous: $excecao,
            );
        }
    }
}
