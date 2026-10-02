# Gestão Clínica — Agendamento de Salas

Sistema web para gerenciamento e agendamento de salas de uma clínica. Profissionais consultam a disponibilidade das salas e reservam horários; o administrador gerencia profissionais, salas e todos os agendamentos.

> Projeto em desenvolvimento incremental.

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

Crie o banco de dados no MySQL:

```sql
CREATE DATABASE gestao_clinica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Ajuste as variáveis `DB_*` no `.env` se necessário e rode as migrations:

```bash
php artisan migrate
```

## Executando em desenvolvimento

```bash
npm run build      # ou: npm run dev (com hot reload)
php artisan serve
```

Acesse http://localhost:8000.

## Testes

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
