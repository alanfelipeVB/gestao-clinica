# Arquitetura — Gestão Clínica

Documento de referência do sistema de agendamento de salas. Registra a arquitetura, o modelo de dados e as regras de negócio acordadas. Deve ser atualizado sempre que uma decisão mudar.

## 1. Visão geral

Aplicação Laravel monolítica com interface renderizada no servidor (Blade). A agenda visual consome endpoints JSON internos da própria aplicação.

**Perfis**

| Perfil | Acesso |
|---|---|
| Administrador | Tudo: profissionais, salas, configurações, todos os agendamentos |
| Profissional | Consulta salas e agenda; cria, edita e cancela os próprios agendamentos |

## 2. Camadas

| Camada | Responsabilidade |
|---|---|
| Controllers | Finos: recebem a requisição, delegam e retornam a resposta |
| Form Requests | Validação de entrada (formato, obrigatoriedade, tipos) |
| Policies | Autorização por ação (admin × dono do agendamento) |
| Middleware `perfil` | Bloqueia grupos de rotas exclusivos do administrador |
| Services | Regras de negócio. `AgendamentoService` concentra criação, edição, cancelamento e verificação de conflitos |
| Enums | `PerfilUsuario` (`admin`, `profissional`), `StatusAgendamento` (`agendado`, `cancelado`) |
| Events | `AgendamentoCriado`, `AgendamentoAtualizado`, `AgendamentoCancelado` — ponto de extensão para notificações/e-mail |

## 3. Banco de dados

### users
Profissionais e administradores na mesma tabela.

| Campo | Tipo | Observação |
|---|---|---|
| id | bigint PK | |
| nome | varchar | |
| email | varchar único | login |
| telefone | varchar null | |
| instagram | varchar null | |
| profissao | varchar null | |
| perfil | varchar | `admin` / `profissional` |
| ativo | boolean | inativo não faz login |
| password | varchar | hash bcrypt |
| remember_token | varchar null | |
| created_at / updated_at | timestamp | `created_at` = data de cadastro |

### salas

| Campo | Tipo | Observação |
|---|---|---|
| id | bigint PK | |
| nome | varchar único | |
| descricao | text null | |
| capacidade | smallint null | |
| cor | char(7) | cor na agenda (`#RRGGBB`) |
| ativa | boolean | |
| created_at / updated_at | timestamp | |

### agendamentos

| Campo | Tipo | Observação |
|---|---|---|
| id | bigint PK | |
| user_id | FK users | profissional responsável |
| sala_id | FK salas | |
| inicio | datetime | data + hora de início |
| fim | datetime | data + hora de término |
| descricao | text | o que será realizado |
| status | varchar | `agendado` / `cancelado` |
| criado_por | FK users | quem criou (admin pode criar em nome do profissional) |
| cancelado_por | FK users null | |
| cancelado_em | datetime null | |
| motivo_cancelamento | varchar null | |
| created_at / updated_at | timestamp | |

Índices: `(sala_id, status, inicio, fim)` e `(user_id, status, inicio, fim)` para a verificação de conflitos.

Chaves estrangeiras com `restrict on delete`: registros não são apagados, apenas desativados ou cancelados.

### configuracoes

Parâmetros ajustáveis pelo administrador, sem alterar código.

| Campo | Tipo | Observação |
|---|---|---|
| id | bigint PK | |
| chave | varchar único | |
| valor | varchar | |
| created_at / updated_at | timestamp | |

Valores iniciais:

| chave | valor | significado |
|---|---|---|
| `antecedencia_maxima_dias` | `30` | até quantos dias à frente é possível agendar |

Lida por um `ConfiguracaoService` com cache, invalidado ao salvar.

### Relacionamentos

```
users 1 ── N agendamentos (user_id)
salas 1 ── N agendamentos (sala_id)
users 1 ── N agendamentos (criado_por, cancelado_por)
```

**Por que `inicio`/`fim` em datetime:** a verificação de sobreposição vira uma única comparação, e recorrência e integração com calendários externos ficam simples no futuro. Na interface os campos aparecem separados (Data, Início, Término).

## 4. Regras de negócio

### 4.1 Conflito de sala (regra principal)
Existe conflito quando há outro agendamento **na mesma sala**, com status `agendado`, tal que:

```
existente.inicio < novo.fim  AND  existente.fim > novo.inicio
```

| Cenário | Resultado |
|---|---|
| Novo totalmente dentro de existente | conflito |
| Começa antes e termina durante | conflito |
| Começa durante e termina depois | conflito |
| Mesmo horário exato | conflito |
| Novo envolve totalmente o existente | conflito |
| Consecutivos (14:00–15:00 e 15:00–16:00) | **permitido** (comparação estrita) |
| Edição | o próprio agendamento é excluído da verificação |
| Cancelado | ignorado; libera o horário |

### 4.2 Conflito de profissional
Um profissional não pode ter dois agendamentos `agendado` sobrepostos, mesmo em salas diferentes. Mesma condição, filtrando por `user_id`.

### 4.3 Concorrência
Verificação e gravação ocorrem dentro de uma transação, com `lockForUpdate` na linha da sala. Duas requisições simultâneas para a mesma sala são serializadas e a segunda detecta o conflito.

### 4.4 Validações de horário
- `fim` > `inicio`;
- início e fim no mesmo dia;
- horários em intervalos de 15 minutos; duração mínima de 15 minutos;
- não é permitido agendar no passado;
- início no máximo `antecedencia_maxima_dias` dias à frente (configurável pelo admin).

### 4.5 Salas e profissionais
- Só salas **ativas** podem receber novos agendamentos.
- Só profissionais **ativos** fazem login e agendam.
- Ao desativar sala ou profissional com agendamentos futuros, o sistema **avisa e lista** esses agendamentos; o administrador decide se os cancela.

### 4.6 Edição e cancelamento
- Profissional edita/cancela apenas os **próprios** agendamentos, e somente **até o horário de início**.
- Administrador edita qualquer agendamento que ainda não começou e cancela qualquer agendamento a qualquer momento.
- Agendamentos cancelados não podem ser editados.
- Cancelamento registra quem cancelou, quando e o motivo (opcional). Nada é excluído.

### 4.7 Privacidade na agenda
O profissional vê os agendamentos de colegas apenas como **"Ocupado — nome do profissional"**, sem a descrição (que pode conter dados de paciente). Administrador e o próprio dono veem todos os detalhes.

### 4.8 Registro do atendimento
- Todo agendamento tem uma **situação**: `pendente` (padrão), `realizado` ou `nao_realizado`, com quem marcou, quando e observação opcional.
- Só pode ser registrada a partir do **horário de início** e apenas em agendamentos ativos (não cancelados).
- O profissional registra/corrige os próprios atendimentos até **7 dias após o término**; depois disso, somente o administrador.
- Agendamentos iniciados e ainda pendentes aparecem em "Atendimentos para confirmar" (dashboard do profissional) e em um aviso no dashboard do administrador.

### 4.9 Agendamento recorrente
- Frequências: semanal, quinzenal e mensal (mesmo dia do mês; meses sem esse dia são pulados).
- Término por data final ou número de ocorrências (máximo de 52).
- Limite próprio: `antecedencia_recorrencia_dias` (padrão 90), configurável pelo administrador.
- Antes de criar, o sistema mostra a **prévia** de cada data (livre, conflito ou não será criada). Só as livres são criadas; cada ocorrência é um agendamento comum, criado pelo `AgendamentoService` com todas as regras e a trava de concorrência.
- A série é guardada em `recorrencias`; cada agendamento aponta para ela (`recorrencia_id`).
- Cancelamento: "somente este" ou "este e os próximos da série". A edição continua individual.

## 5. Autenticação
- Login por e-mail e senha usando o `Auth` nativo do Laravel (sem pacote de starter kit).
- Sem cadastro público: contas são criadas pelo administrador.
- Login bloqueado para usuário inativo; limite de tentativas (rate limiting).
- Senha esquecida: o administrador redefine. Recuperação por e-mail fica para o futuro.
- Primeiro administrador criado por seeder a partir de variáveis do `.env` (`ADMIN_NOME`, `ADMIN_EMAIL`, `ADMIN_SENHA`).
- Rotas protegidas por `auth`; área administrativa também por `perfil:admin`; ações individuais por Policies.

## 6. Fluxo de criação de agendamento

```
Profissional escolhe sala ─► vê agenda da sala ─► informa data, início, fim, descrição
   (ou clica num horário livre na agenda, que pré-preenche o formulário)
        │
        ▼
Form Request (formato, campos obrigatórios)
        │
        ▼
AgendamentoService::criar
   ├─ valida regras de horário (4.4) e sala/profissional ativos (4.5)
   ├─ transação + lockForUpdate na sala
   ├─ verifica conflito de sala (4.1) e de profissional (4.2)
   ├─ grava
   └─ dispara AgendamentoCriado
        │
        ▼
Redireciona com mensagem de sucesso
(ou volta ao formulário indicando o agendamento conflitante)
```

## 7. Telas

| Tela | Perfil |
|---|---|
| Login | todos |
| Dashboard admin: totais, agendamentos do dia, próximos, salas ocupadas agora, salas livres | admin |
| Dashboard profissional: meus agendamentos de hoje, próximos, atalho "Novo agendamento" | profissional |
| Agenda (dia/semana/mês, filtros por sala, profissional e data, modal de detalhes) | todos |
| Agendamentos: lista com filtros, criar/editar, cancelar, detalhes | todos (escopo por perfil) |
| Profissionais: tabela, criar/editar, ativar/desativar, redefinir senha | admin |
| Salas: tabela, criar/editar, ativar/desativar | admin |
| Configurações | admin |
| Meu perfil (dados e troca de senha) | todos |

## 8. Rotas

```
GET    /login                               auth.login
POST   /login
POST   /logout

GET    /dashboard
GET    /agenda                              tela da agenda
GET    /agenda/eventos                      JSON para o calendário (filtros: sala, profissional, inicio, fim)

GET    /agendamentos                        lista
GET    /agendamentos/create
POST   /agendamentos
GET    /agendamentos/{agendamento}
GET    /agendamentos/{agendamento}/edit
PUT    /agendamentos/{agendamento}
PATCH  /agendamentos/{agendamento}/cancelar

GET    /perfil
PUT    /perfil

# prefixo /admin, middleware perfil:admin
resource /admin/profissionais               (index, create, store, edit, update)
PATCH  /admin/profissionais/{user}/status
resource /admin/salas                       (index, create, store, edit, update)
PATCH  /admin/salas/{sala}/status
GET    /admin/configuracoes
PUT    /admin/configuracoes
```

## 9. Organização de pastas

```
app/
  Enums/                 PerfilUsuario, StatusAgendamento
  Events/                AgendamentoCriado, AgendamentoAtualizado, AgendamentoCancelado
  Exceptions/            ConflitoDeHorarioException
  Http/
    Controllers/
      Admin/             ProfissionalController, SalaController, ConfiguracaoController
      Auth/              LoginController
      AgendaController, AgendamentoController, DashboardController, PerfilController
    Middleware/          VerificaPerfil
    Requests/
  Models/                User, Sala, Agendamento, Configuracao
  Policies/              AgendamentoPolicy
  Services/              AgendamentoService, ConfiguracaoService
resources/views/
  layouts/  components/  auth/  dashboard/  agenda/  agendamentos/
  admin/profissionais/  admin/salas/  admin/configuracoes/  perfil/
tests/
  Feature/               autenticação, permissões, CRUDs, agendamentos
  Unit/                  regras do AgendamentoService
```

## 10. Tecnologias

| Item | Motivo |
|---|---|
| Laravel 13, PHP 8.3, MySQL 8 | stack definida |
| Blade | interface renderizada no servidor |
| Bootstrap 5 + Bootstrap Icons (npm/Vite) | interface responsiva e profissional |
| FullCalendar | visões dia/semana/mês, clique em eventos e navegação de datas prontos e responsivos |
| PHPUnit | testes (já incluso no Laravel) |

Qualquer nova biblioteca deve ter sua necessidade justificada antes de ser adicionada.

## 11. Preparação para o futuro

| Funcionalidade futura | Onde se encaixa |
|---|---|
| Notificações / e-mail de confirmação | Listeners dos eventos de agendamento |
| Histórico | status + campos de cancelamento já preservam o histórico; tabela de auditoria se necessário |
| Relatórios / exportação Excel/PDF | consultas sobre `agendamentos`; nova camada de Exporters |
| Bloqueio de horários / feriados | nova tabela `bloqueios`, verificada no `AgendamentoService` |
| Horário de funcionamento | chaves em `configuracoes`, verificadas no `AgendamentoService` |
| Permissões granulares | Policies; migração para `spatie/laravel-permission` se necessário |
| Recorrência | tabela `recorrencias` gerando agendamentos individuais (cada um validado) |
| Calendário externo | `inicio`/`fim` em datetime facilitam exportação iCal / Google Calendar |

## 12. Etapas de desenvolvimento

| # | Etapa | Commit |
|---|---|---|
| 0 | Projeto base | `chore: inicializa projeto Laravel` |
| 1 | Documentação de arquitetura | `docs: adiciona documentacao de arquitetura` |
| 2 | Usuários e perfis | `feat: adiciona estrutura de usuarios e perfis` |
| 3 | Layout base Bootstrap | `feat: adiciona layout base com Bootstrap` |
| 4 | Autenticação e controle de acesso | `feat: implementa autenticacao e controle de acesso` |
| 5 | Cadastro de profissionais | `feat: implementa cadastro de profissionais` |
| 6 | Gerenciamento de salas | `feat: implementa gerenciamento de salas` |
| 7 | Configurações | `feat: adiciona configuracoes do sistema` |
| 8 | Agendamento | `feat: adiciona sistema de agendamento` |
| 9 | Validação de conflitos + testes | `feat: adiciona validacao de conflito de horarios` |
| 10 | Agenda visual | `feat: adiciona visualizacao da agenda` |
| 11 | Dashboards | `feat: adiciona dashboards` |
| 12 | Melhorias, testes finais, correções | `refactor:` / `test:` / `fix:` |
