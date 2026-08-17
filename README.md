# MetalThursday 🤘

![Version](https://img.shields.io/badge/version-1.0.0-red)
![Laravel](https://img.shields.io/badge/Laravel-framework-red?logo=laravel)

## 📖 Sobre o projeto

O **MetalThursday** é a aplicação web de uma rubrica semanal, criada por um grupo de amigos, dedicada à partilha de álbuns e músicas de metal. Todas as quintas-feiras, o grupo reúne-se para partilhar descobertas e recomendações dentro do género, preservando e organizando essas escolhas ao longo do tempo.

## 🛠️ Tecnologias

- **Backend:** PHP / Laravel
- **Vistas:** Blade
- **Frontend:** JavaScript / SCSS
- **Compilação de assets:** Vite
- **Gestão de dependências:** Composer / npm
- **Base de dados:** MariaDB

## 🚀 Instalação

### Pré-requisitos

- PHP >= 8.2
- Composer
- Node.js 22.13+ (22.x) ou 24.x
- npm
- MariaDB

### Passos

```bash
# 1. Clonar o repositório
git clone https://github.com/joaorodriguesmm/MetalThursday.git
cd MetalThursday

# 2. Instalar dependências PHP
composer install

# 3. Instalar dependências JavaScript
npm ci

# 4. Configurar o ambiente
cp .env.example .env
php artisan key:generate

# 5. Configurar a base de dados no ficheiro .env e executar as migrações
php artisan migrate
```

Para iniciar o ambiente de desenvolvimento completo — servidor Laravel, processamento da fila e Vite — executa:

```bash
composer desenvolver
```

A aplicação ficará disponível em `http://127.0.0.1:8000`.

Para gerar os assets destinados a produção sem iniciar o ambiente de desenvolvimento:

```bash
npm run compilar
```

## ✅ Validação

Antes de integrar alterações, podem ser executadas as principais validações locais:

```bash
./vendor/bin/pint --test
composer testar
npm run validar
```

## 📂 Estrutura do projeto

```text
MetalThursday/
├── app/          # Lógica da aplicação (controladores, modelos, serviços, etc.)
├── bootstrap/    # Arranque da aplicação
├── config/       # Configuração
├── database/     # Migrações, seeders e factories
├── lang/         # Traduções
├── public/       # Ponto de entrada público e assets públicos
├── resources/    # Vistas Blade, SCSS e JavaScript
├── routes/       # Definição de rotas
└── storage/      # Ficheiros gerados, cache e registos
```

## 🔒 Projeto fechado

Este é um projeto pessoal, mantido apenas pelo grupo de amigos do MetalThursday. Não são aceites pull requests, issues ou outras contribuições externas.

## ©️ Direitos de autor

Código proprietário. Todos os direitos reservados.

Não é concedida autorização para utilizar, copiar, modificar, distribuir ou sublicenciar este código fora das permissões estritamente necessárias ao funcionamento dos serviços utilizados para alojar o repositório.

## 📌 Versão publicada

**v1.0.0** (2025-12-28) — consulta o [CHANGELOG.md](CHANGELOG.md) para o histórico de alterações e alterações ainda não publicadas.
