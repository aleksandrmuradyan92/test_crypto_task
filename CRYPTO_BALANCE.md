## Установка

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Сидер создаёт тестовых пользователей для работы с API баланса.

### Зачисление (credit)


POST /api/crypto-balance/credit
Content-Type: application/json

{
  "user_id": 1,
  "currency": "USDT",
  "amount": "100.50",
  "reference": "deposit",
  "idempotency_key": "optional-unique-key"
}


Ответ: `{ "success": true, "balance_after": "100.50", "ledger_id": 1 }`

### Списание (debit)


POST /api/crypto-balance/debit
Content-Type: application/json

{
  "user_id": 1,
  "currency": "USDT",
  "amount": "10",
  "reference": "withdrawal",
  "idempotency_key": "optional-unique-key"
}


При недостаточном балансе: HTTP 422, `{ "success": false, "error": "Insufficient balance..." }`.

### Текущий баланс

GET /api/crypto-balance/balance?user_id=1&currency=USDT

Ответ: `{ "user_id": 1, "currency": "USDT", "balance": "90.50" }`

