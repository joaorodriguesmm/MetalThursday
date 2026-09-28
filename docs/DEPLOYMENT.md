# Deployment em produção

Este documento descreve os requisitos operacionais do MetalThursday em
produção. Não assume um fornecedor de alojamento, servidor web ou gestor de
processos específico.

## Requisitos

O servidor de produção deve disponibilizar:

- PHP compatível com a versão indicada em `composer.json`;
- Composer;
- MariaDB;
- um servidor web cujo document root aponte para `public/`;
- HTTPS;
- um processo persistente para a fila Laravel;
- uma entrada de cron que execute o scheduler Laravel a cada minuto.

Node.js e npm são necessários no servidor apenas quando os assets são
compilados durante o próprio deployment. Se `public/build` for produzido por
CI ou noutro ambiente e distribuído como artefacto, essa compilação pode ser
feita antes da publicação.

## Variáveis de ambiente

O ficheiro `.env` de produção não deve ser versionado. Deve partir da estrutura
de `.env.example`, mas com valores próprios do ambiente.

Configuração mínima recomendada:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dominio.example
APP_TIMEZONE=Europe/Lisbon

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
```

`APP_URL` deve corresponder ao endereço público efetivo da aplicação. O
MetalThursday utiliza hosts confiáveis do Laravel e gera ligações absolutas em
vários fluxos, incluindo notificações.

`APP_KEY` deve ser gerada e guardada de forma persistente no primeiro
deployment. Não deve ser regenerada em deployments normais, porque é utilizada
pela encriptação da aplicação e das sessões.

Devem ser configuradas credenciais reais para:

- MariaDB;
- servidor SMTP;
- `DISCOGS_TOKEN`;
- restantes integrações externas que sejam utilizadas no ambiente.

O mailer `registo` existente em `.env.example` destina-se ao desenvolvimento.
Para envio real de correio eletrónico deve ser utilizado o mailer `smtp` com
`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
`MAIL_FROM_ADDRESS` e restantes opções necessárias ao fornecedor escolhido.

## Primeiro deployment

Depois de disponibilizar o código no servidor e criar o `.env` de produção:

```bash
composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

npm ci --strict-allow-scripts
npm run compilar

php artisan key:generate --force
php artisan optimize:clear
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

`php artisan key:generate --force` deve ser executado apenas no primeiro
deployment, enquanto `APP_KEY` ainda não possuir um valor persistente. A chave
gerada deve ser preservada nos deployments seguintes.

A ligação `public/storage` é necessária para os ficheiros guardados no disco
`publico` e deve ser criada uma vez com `php artisan storage:link`.

Os diretórios `storage/` e `bootstrap/cache/` têm de ser graváveis pelo
utilizador que executa a aplicação.

Se a compilação dos assets for efetuada fora do servidor, `public/build` deve
ser incluído no artefacto publicado e os comandos npm podem ser omitidos no
servidor.

## Fila

A aplicação utiliza a ligação `database` e a fila `principal`. As notificações
da aplicação implementam `ShouldQueue`, pelo que é necessário manter pelo menos
um worker ativo em produção.

Comando base:

```bash
php artisan queue:work --queue=principal
```

O worker deve ser supervisionado por um gestor de processos do sistema
operativo, com arranque automático e reinício em caso de falha.

Depois de publicar uma nova versão da aplicação, os workers existentes devem
ser instruídos a terminar de forma graciosa e arrancar novamente com o código
novo:

```bash
php artisan queue:restart
```

## Scheduler

O servidor deve executar o scheduler Laravel uma vez por minuto. Exemplo de
entrada cron, substituindo o diretório e o executável PHP pelos valores reais
do servidor:

```cron
* * * * * cd <diretorio-da-aplicacao> && <php> artisan schedule:run >> /dev/null 2>&1
```

O scheduler utiliza o fuso horário configurado em `APP_TIMEZONE`. Atualmente
estão definidas as seguintes tarefas:

- notificações de publicações: diariamente às 00:00;
- criação semanal de reservas: sexta-feira às 00:00;
- lembretes de tarefas do dia: diariamente às 08:00;
- lembretes de atrasos: diariamente às 08:05.

As quatro tarefas utilizam `withoutOverlapping()`.

## Deployment de uma nova versão

Num deployment normal, depois de colocar a nova versão do código no servidor:

```bash
composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

npm ci --strict-allow-scripts
npm run compilar

php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

`APP_KEY` não deve ser regenerada. `php artisan storage:link` só precisa de ser
executado novamente se a ligação `public/storage` não existir no ambiente
publicado.

Quando os assets forem construídos fora do servidor, os comandos npm são
substituídos pela publicação do `public/build` produzido pelo processo de
build.

## Verificações após deployment

Depois da publicação devem ser confirmados, pelo menos:

```bash
php artisan schedule:list
php artisan migrate:status
```

Adicionalmente:

- `https://<dominio>/up` deve responder com sucesso;
- a aplicação deve abrir exclusivamente pelo domínio e protocolo esperados;
- deve ser possível iniciar sessão;
- deve ser possível gravar e servir um ficheiro do disco público;
- o worker da fila deve permanecer ativo;
- um envio real de notificação/e-mail deve ser processado pela fila;
- os registos em `storage/logs/` devem ser graváveis e não apresentar erros de
  configuração.

## Segurança operacional

Em produção:

- `APP_DEBUG` deve permanecer `false`;
- HTTPS deve ser obrigatório;
- `SESSION_SECURE_COOKIE` deve permanecer `true`;
- `.env`, `storage/` privado e outros ficheiros internos nunca devem ser
  servidos diretamente pelo servidor web;
- o document root deve ser exclusivamente `public/`;
- credenciais e tokens não devem ser guardados no repositório;
- devem existir cópias de segurança regulares da base de dados e dos ficheiros
  persistentes de `storage/app/`.
