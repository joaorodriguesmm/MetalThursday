# MetalThursday 🤘

![Version](https://img.shields.io/badge/version-1.0.0-red)
![Laravel](https://img.shields.io/badge/Laravel-framework-red?logo=laravel)
![License](https://img.shields.io/badge/license-MIT-blue)

## 📖 Sobre o projeto

O **MetalThursday** é o website de uma rubrica semanal, criada por um grupo de amigos, dedicada à partilha de álbuns e músicas de metal. Todas as quintas-feiras, o grupo reúne e divulga as suas descobertas e recomendações dentro do género, num espaço próprio para preservar e organizar essas partilhas ao longo do tempo.

## 🛠️ Tecnologias

- **Backend:** PHP / Laravel
- **Templates:** Blade
- **Frontend:** JavaScript, SCSS
- **Build tool:** Vite
- **Gestor de dependências:** Composer + NPM

## 🚀 Instalação

### Pré-requisitos

- PHP >= 8.1
- Composer
- Node.js + NPM
- Base de dados (MySQL/MariaDB/SQLite, conforme configurado em `.env`)

### Passos

```bash
# 1. Clonar o repositório
git clone https://github.com/Joao-Rodrigues-Multimedia/MetalThursday.git
cd MetalThursday

# 2. Instalar dependências PHP
composer install

# 3. Instalar dependências JavaScript
npm install

# 4. Configurar o ambiente
cp .env.example .env
php artisan key:generate

# 5. Configurar a base de dados no ficheiro .env e depois correr as migrações
php artisan migrate

# 6. Compilar os assets (frontend)
npm run dev
# ou, para produção:
npm run build

# 7. Iniciar o servidor local
php artisan serve
```

A aplicação ficará disponível em `http://localhost:8000`.

## 📂 Estrutura do projeto

```
MetalThursday/
├── app/          # Lógica da aplicação (Controllers, Models, etc.)
├── bootstrap/    # Ficheiros de arranque da framework
├── config/       # Ficheiros de configuração
├── database/     # Migrações, seeders e factories
├── lang/         # Ficheiros de tradução
├── public/       # Ponto de entrada público
├── resources/    # Views (Blade), CSS/SCSS, JS
├── routes/       # Definição de rotas
└── storage/      # Ficheiros gerados/logs/cache
```

## 🔒 Projeto fechado

Este é um projeto pessoal, mantido apenas pelo grupo de amigos do MetalThursday. Não são aceites pull requests, issues ou outras contribuições externas.

## 📄 Licença

Este projeto está licenciado sob a licença MIT — consulta o ficheiro [LICENSE](LICENSE) para mais detalhes.

## 📌 Versão atual

**v1.0.0** (28/12/2025) — consulta o [CHANGELOG.md](CHANGELOG.md) para o histórico de alterações.
