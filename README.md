# Orderflow

Um simples projeto, simulando arquitetura de microserviços, para estudar e relembrar conceitos de mensageria, usando **Laravel** e **RabbitMQ**.

O fluxo principal é:

1. O **order-service** recebe um `POST` e cria um pedido.
2. Na mesma transação, grava um evento na tabela **outbox** (Transactional Outbox).
3. O processo **`outbox:relay`** publica o evento no RabbitMQ.
4. O **notification-service** consome o evento de forma assíncrona e envia um e-mail (visível localmente no Mailpit).

Não há chamada HTTP entre os dois serviços para executar esse fluxo — a integração é apenas por mensageria.

## Arquitetura

```
                    ┌─────────────────────┐
  POST /api/orders  │   order-service     │
 ─────────────────► │  (API + outbox)     │
                    └─────────┬───────────┘
                              │ mesma TX
                              ▼
                       outbox_events
                              │
                    php artisan outbox:relay
                              │
                              ▼
                    ┌─────────────────────┐
                    │  RabbitMQ exchange  │
                    │     orders (topic)  │
                    └─────────┬───────────┘
                              │ routing key: order.created
                              ▼
                    notification.order_created
                              │
              php artisan notifications:consume
                              ▼
                    ┌─────────────────────┐
                    │ notification-service│
                    │  (e-mail + inbox)   │
                    └─────────┬───────────┘
                              ▼
                         Mailpit UI
```

| Serviço | Responsabilidade | Database |
|---|---|---|
| `order-service` | Criar pedidos e emitir `order.created` | `orders` |
| `notification-service` | Consumir o evento e enviar e-mail | `notifications` |

## Pré-requisitos

- Docker + Docker Compose
- PHP 8.3+ (extensões: `pdo_pgsql`, `sockets`, `bcmath` se necessário)
- Composer

## Estrutura do repositório

```
orderflow/
├── contracts/events/order-created.v1.json   # contrato do evento (JSON Schema)
├── infra/
│   ├── docker-compose.yml                   # RabbitMQ, Postgres, Mailpit
│   └── postgres/init.sql                    # cria DBs orders e notifications
├── services/
│   ├── order-service/
│   └── notification-service/
└── README.md
```

## Contrato do evento

O evento de integração está documentado em:

[`contracts/events/order-created.v1.json`](contracts/events/order-created.v1.json)

Envelope esperado (resumo):

```json
{
  "event_id": "uuid",
  "event_type": "order.created",
  "event_version": 1,
  "occurred_at": "2026-10-12T20:00:00+00:00",
  "data": {
    "order_id": 1,
    "customer_email": "cliente@exemplo.com",
    "total_amount": 36.0,
    "items": [
      { "sku": "CAMISETA-P", "price": 18.0, "quantity": 2 }
    ]
  }
}
```

- **Exchange:** `orders` (topic)
- **Routing key:** `order.created`
- **Fila do consumer:** `notification.order_created`

O `event_id` é a chave de idempotência no notification-service (`processed_events` + `notifications.event_id` unique).

## Subir a infraestrutura

Na pasta `infra/`:

```bash
cd infra
docker compose up -d
```

Serviços:

| Serviço | URL / porta | Credenciais |
|---|---|---|
| RabbitMQ AMQP | `localhost:5672` | user `app` / pass `secret` |
| RabbitMQ Management UI | http://localhost:15672 | `app` / `secret` |
| Postgres | `localhost:5432` | user `app` / pass `secret` |
| Mailpit SMTP | `localhost:1025` | — |
| Mailpit UI | http://localhost:8025 | — |

Databases criados pelo `init.sql`: `orders` e `notifications`.

> O `init.sql` só roda na **primeira** inicialização do volume do Postgres. Se precisar recriar do zero: `docker compose down -v && docker compose up -d`.

## Configurar os serviços

### order-service

```bash
cd services/order-service
composer install
cp .env.example .env   # se ainda não tiver .env
php artisan key:generate
php artisan migrate
```

Confirme no `.env`:

- `DB_DATABASE=orders`
- variáveis `RABBITMQ_*` (host `127.0.0.1`, user/pass `app`/`secret`)

### notification-service

```bash
cd services/notification-service
composer install
cp .env.example .env   # se ainda não tiver .env
php artisan key:generate
php artisan migrate
```

Confirme no `.env`:

- `DB_DATABASE=notifications`
- `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`
- variáveis `RABBITMQ_*` (incluindo `RABBITMQ_QUEUE=notification.order_created`)

## Rodar a aplicação (3 processos)

Abra **três terminais**:

```bash
# Terminal 1 — API do order-service
cd services/order-service
php artisan serve --port=8001

# Terminal 2 — Relay do outbox (publica no RabbitMQ)
cd services/order-service
php artisan outbox:relay

# Terminal 3 — Consumer de e-mails
cd services/notification-service
php artisan notifications:consume
```

| Processo | Função |
|---|---|
| `serve` | HTTP — cria pedido + linha no outbox |
| `outbox:relay` | Lê outbox pendente e publica no RabbitMQ |
| `notifications:consume` | Consome a fila, envia e-mail, registra notificação |

## Testar o fluxo completo

### 1. Criar um pedido

`POST http://localhost:8001/api/orders`

```json
{
  "customer_email": "cliente@exemplo.com",
  "payment_method": "pix",
  "items": [
    { "sku": "CAMISETA-P", "price": 18.0, "quantity": 2 }
  ]
}
```

Resposta esperada: `201` com o pedido criado.

### 2. Verificar cada etapa

1. **Postgres (`orders`)** — tabelas `orders`, `order_items` e `outbox_events` com `published_at` preenchido (após o relay).
2. **RabbitMQ UI** — exchange `orders`, fila `notification.order_created` (mensagem some após o consumer processar).
3. **Postgres (`notifications`)** — linhas em `notifications` (`status = sent`) e `processed_events`.
4. **Mailpit** — http://localhost:8025 — e-mail do pedido.

## Experimentos úteis (PDI)

### Desacoplamento temporal

1. Pare o `notifications:consume`.
2. Crie 2–3 pedidos.
3. Suba o consumer de novo.

Os eventos acumulados na fila devem ser processados e os e-mails devem aparecer no Mailpit.

### Outbox quando o broker cai

1. Pare o RabbitMQ (`docker compose stop rabbitmq` em `infra/`).
2. Crie um pedido — a API deve continuar respondendo `201`; o outbox fica com `published_at` nulo.
3. Suba o RabbitMQ de novo.
4. O `outbox:relay` deve publicar os eventos pendentes.

### Idempotência

1. Pegue o payload de um evento já processado (mesmo `event_id`).
2. Publique de novo na UI do RabbitMQ (exchange `orders`, routing key `order.created`).
3. Esperado: **não** duplicar registro de notificação / não reenviar e-mail se o fluxo já tiver marcado `sent` + `processed_events`.

## Padrões aplicados

- **Transactional Outbox** — o order-service não publica no RabbitMQ dentro do request HTTP; grava o evento na mesma transação do pedido e o relay publica depois.
- **Consumer com ACK manual** — sucesso → `ack`; falha → `nack` (sem requeue por padrão).
- **Idempotência (inbox)** — `processed_events` e `notifications.event_id` único evitam efeito duplicado da mesma mensagem.
- **Contrato versionado** — `order-created.v1.json` descreve o formato compartilhado entre os serviços.

## Credenciais locais (resumo)

| Recurso | Valor |
|---|---|
| Postgres / RabbitMQ user | `app` |
| Postgres / RabbitMQ password | `secret` |
| Order API | http://localhost:8001 |
| RabbitMQ UI | http://localhost:15672 |
| Mailpit UI | http://localhost:8025 |

> Uso apenas local / estudo. Não reutilize essas senhas em ambiente real.
