# Gestão Clínica — Agendamento de Salas

Sistema web para gerenciamento e agendamento de salas de uma clínica. Os profissionais consultam a disponibilidade das salas e reservam horários; o administrador gerencia profissionais, salas, regras e todos os agendamentos.

Arquitetura, modelo de dados e regras de negócio em detalhes: [docs/ARQUITETURA.md](docs/ARQUITETURA.md).

## Funcionalidades

**Página inicial pública** (`/`)
- Logo, textos e contatos da clínica editáveis pelo administrador
- Seção "Nossa equipe" com foto, profissão, mini biografia, Instagram e botão de WhatsApp (somente para quem autorizou)
- O sistema fica em `/login`

**Administrador**
- Dashboard com totais, situação das salas em tempo real (ocupada/livre), agendamentos do dia e dos próximos 7 dias
- Cadastro, edição, ativação/desativação e redefinição de senha de profissionais (inclusive outros administradores)
- Cadastro, edição e ativação/desativação de salas (nome, descrição, capacidade, cor na agenda)
- Ao desativar sala ou profissional com agendamentos futuros: lista os agendamentos e permite mantê-los ou cancelá-los
- Visualiza, cria (em nome de qualquer profissional), edita e cancela qualquer agendamento
- Configura a antecedência máxima para agendamento (padrão: 30 dias) e para séries recorrentes (padrão: 90 dias)
- Envia, publica/oculta e exclui tutoriais em vídeo e acompanha quem já assistiu
- Relatório mensal de atendimentos por profissional, com exportação CSV

**Profissional**
- Dashboard com os agendamentos de hoje, os próximos e atalhos
- Consulta das salas disponíveis e da agenda
- Cria agendamentos para si (avulsos ou recorrentes: semanal, quinzenal, mensal), edita e cancela os próprios até o horário de início
- Marca cada atendimento como realizado ou não realizado (até 7 dias após o término)
- Assiste aos tutoriais publicados e marca como assistido
- Relatório mensal com os próprios números
- Na agenda, vê horários de colegas apenas como "Ocupado — nome" (sem descrição)
- Página "Meu perfil" com dados pessoais, foto, mini biografia, autorização para publicar o WhatsApp no site e troca de senha

**Agenda visual**
- Visões de dia, semana, mês e lista (padrão no celular), em português
- Filtros por sala, profissional e data
- Clique em um agendamento abre os detalhes; clique/arrasto em horário livre abre o formulário pré-preenchido

## Regras de negócio principais

- **Uma sala não pode ter dois agendamentos sobrepostos.** Há conflito quando `existente.inicio < novo.fim` e `existente.fim > novo.inicio`; agendamentos consecutivos (14:00–15:00 e 15:00–16:00) são permitidos. A validação é feita no backend, com trava de linha (`lockForUpdate`) contra requisições simultâneas.
- Um profissional não pode estar em duas salas ao mesmo tempo.
- Horários em intervalos de 15 minutos, no mesmo dia, nunca no passado e dentro da antecedência máxima.
- Somente salas e profissionais ativos recebem novos agendamentos.
- Cancelamentos não apagam registros: guardam quem cancelou, quando e o motivo, e liberam o horário.
- Séries recorrentes mostram uma prévia das datas; somente as datas livres são criadas, cada uma validada como um agendamento comum.
- Atendimentos têm situação pendente, realizado ou não realizado; o relatório mensal usa essas marcações.

## Stack

| Item | Uso |
|---|---|
| PHP 8.3+ / Laravel 13 | backend |
| MySQL 8 | banco de dados |
| Blade + Bootstrap 5.3 + Bootstrap Icons | interface responsiva |
| FullCalendar 6 | agenda visual |
| Vite | build dos assets |
| PHPUnit | testes automatizados |

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

Rode as migrations e os seeders:

```bash
php artisan migrate --seed
```

O seeder sempre cria o administrador definido no `.env`. Com `APP_ENV=local`, também cria dados de demonstração:
- as 5 salas de exemplo (Sala 01, Sala 02, Sala de procedimentos, Sala de avaliação, Sala de atendimento);
- 5 profissionais (`*@demo.test`, senha definida em `DemonstracaoSeeder::SENHA`) com agendamentos de alguns dias antes até alguns dias depois da data atual.

> Os dados de demonstração existem apenas para desenvolvimento. Em produção use `APP_ENV=production`, e o seeder cria somente o administrador.

## Executando em desenvolvimento

```bash
npm run build      # ou: npm run dev (com hot reload)
php artisan serve
```

Acesse http://localhost:8000 e entre com o e-mail e a senha do administrador definidos no `.env`.

### Upload de vídeos (tutoriais)

Os vídeos podem ter até 500 MB. Ajuste o `php.ini` (e o servidor web, em produção) para aceitar uploads desse tamanho:

```ini
upload_max_filesize = 512M
post_max_size = 520M
```

Os arquivos ficam em `storage/app/private/tutoriais` (fora da pasta pública) e são servidos apenas para usuários autenticados.

## Testes

Os testes usam o banco MySQL `gestao_clinica_testing` (configurado no `phpunit.xml`), recriado a cada execução.

```bash
php artisan test
```

A suíte cobre autenticação, permissões por perfil, cadastros, regras de agendamento, todos os cenários de conflito de horário, agenda (incluindo privacidade) e dashboards. Fora de produção, consultas N+1 geram exceção (`Model::preventLazyLoading`).

## Estrutura principal

```
app/
  Enums/          PerfilUsuario, StatusAgendamento
  Events/         AgendamentoCriado, AgendamentoAtualizado, AgendamentoCancelado
  Exceptions/     RegraAgendamentoException, ConflitoDeHorarioException
  Http/
    Controllers/  Admin/ (profissionais, salas, configurações), Auth/, agenda, agendamentos, dashboard, perfil
    Middleware/   VerificaPerfil (perfil:admin), DesconectaUsuarioInativo
    Requests/     validação de formulários
  Models/         User, Sala, Agendamento, Configuracao
  Policies/       AgendamentoPolicy
  Services/       AgendamentoService (regras e conflitos), ConfiguracaoService, DashboardService
resources/
  views/          layouts e componentes Blade, telas por módulo
  js/agenda.js    configuração do FullCalendar
docs/ARQUITETURA.md
```

## Preparado para o futuro

| Funcionalidade | Onde se encaixa |
|---|---|
| Notificações / e-mail de confirmação | listeners dos eventos de agendamento |
| Recuperação de senha por e-mail | tabela `password_reset_tokens` já existe |
| Bloqueios, feriados, horário de funcionamento | regras extras no `AgendamentoService` + chaves em `configuracoes` |
| Exportação PDF dos relatórios | dados já agregados pelo `RelatorioService` |
| Calendário externo (iCal/Google) | `inicio`/`fim` em datetime e endpoint de eventos |
| Permissões granulares | Policies (ou `spatie/laravel-permission`) |

## Convenção de commits

| Prefixo     | Uso                                 |
|-------------|-------------------------------------|
| `feat:`     | nova funcionalidade                 |
| `fix:`      | correção                            |
| `refactor:` | refatoração sem mudar comportamento |
| `chore:`    | configuração e tarefas internas     |
| `docs:`     | documentação                        |
| `test:`     | testes                              |
