# Gestão Clínica — Agendamento de Salas

Sistema web para gerenciamento e agendamento de salas de uma clínica. Profissionais consultam a disponibilidade das salas e reservam horários; o administrador gerencia profissionais, salas e todos os agendamentos.

> Projeto em desenvolvimento incremental. Arquitetura, modelo de dados e regras de negócio: [docs/ARQUITETURA.md](docs/ARQUITETURA.md).

## Stack

- PHP 8.3+
- Laravel 13
- MySQL 8
- Blade (interface)
- Node.js + Vite (build de assets)

## Requisitos

- PHP 8.3 ou superior com extensões `pdo_mysql`, `mbstring`, `openssl`
- Composer 2
- Node.js 20+ e npm
- MySQL 8 (ex.: via Laragon)

## Instalação

```bash
git clone https://github.com/alanfelipeVB/gestao-clinica.git
cd gestao-clinica

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Crie os bancos de dados no MySQL (aplicação e testes):

```sql
CREATE DATABASE gestao_clinica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE gestao_clinica_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

No `.env`, ajuste as variáveis `DB_*` se necessário e defina o administrador inicial:

```dotenv
ADMIN_NOME="Administrador"
ADMIN_EMAIL=admin@suaclinica.com
ADMIN_SENHA=uma-senha-forte
```

Rode as migrations e o seeder (cria o administrador e, com `APP_ENV=local`, salas de exemplo):

```bash
php artisan migrate --seed
```

## Executando em desenvolvimento

```bash
npm run build      # ou: npm run dev (com hot reload)
php artisan serve
```

Acesse http://localhost:8000.

## Testes

Os testes usam o banco MySQL `gestao_clinica_testing` (configurado no `phpunit.xml`), que é recriado a cada execução.

```bash
php artisan test
```

## Convenção de commits

| Prefixo     | Uso                               |
|-------------|-----------------------------------|
| `feat:`     | nova funcionalidade               |
| `fix:`      | correção                          |
| `refactor:` | refatoração sem mudar comportamento |
| `chore:`    | configuração e tarefas internas   |
| `docs:`     | documentação                      |
| `test:`     | testes                            |
