<?php

namespace App\Services;

use App\Models\CryptoBalance;
use App\Models\CryptoBalanceLedger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CryptoBalanceService
{
    /**
     * Зачисление средств на крипто-баланс пользователя.
     *
     * Риски и учёт:
     * - Транзакция БД: атомарность изменения баланса и записи в ledger
     * - Блокировка строки (lockForUpdate): защита от гонок при асинхронных зачислениях
     * - Идемпотентность: при повторном вызове с тем же idempotency_key операция не дублируется
     */
    public function credit(
        int $userId,
        string $currency,
        string $amount,
        ?string $reference = null,
        ?string $idempotencyKey = null
    ): CryptoBalanceLedger {
        $amount = $this->parseAmount($amount);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount for credit must be positive.');
        }

        if ($idempotencyKey !== null) {
            $existing = CryptoBalanceLedger::where('idempotency_key', $idempotencyKey)
                ->where('type', CryptoBalanceLedger::TYPE_CREDIT)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($userId, $currency, $amount, $reference, $idempotencyKey) {
            CryptoBalance::firstOrCreate(
                ['user_id' => $userId, 'currency' => $currency],
                ['balance' => 0]
            );
            $balance = CryptoBalance::where('user_id', $userId)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance = $balance->balance + $amount;
            $balance->update(['balance' => $newBalance]);

            return CryptoBalanceLedger::create([
                'user_id' => $userId,
                'currency' => $currency,
                'type' => CryptoBalanceLedger::TYPE_CREDIT,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $reference ?? 'deposit',
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    /**
     * Списание средств с крипто-баланса (вывод, платёж, комиссия).
     *
     * Риски и учёт:
     * - Транзакция + lockForUpdate: защита от двойного списания и гонок
     * - Проверка достаточности баланса перед списанием
     * - Идемпотентность по idempotency_key для безопасной повторной обработки (например, вебхук блокчейна)
     */
    public function debit(
        int $userId,
        string $currency,
        string $amount,
        ?string $reference = null,
        ?string $idempotencyKey = null
    ): CryptoBalanceLedger {
        $amount = $this->parseAmount($amount);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount for debit must be positive.');
        }

        if ($idempotencyKey !== null) {
            $existing = CryptoBalanceLedger::where('idempotency_key', $idempotencyKey)
                ->where('type', CryptoBalanceLedger::TYPE_DEBIT)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($userId, $currency, $amount, $reference, $idempotencyKey) {
            $balance = CryptoBalance::where('user_id', $userId)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first();

            if (!$balance || $balance->balance < $amount) {
                throw new RuntimeException(
                    'Insufficient balance. User: ' . $userId . ', currency: ' . $currency
                );
            }

            $newBalance = $balance->balance - $amount;
            $balance->update(['balance' => $newBalance]);

            return CryptoBalanceLedger::create([
                'user_id' => $userId,
                'currency' => $currency,
                'type' => CryptoBalanceLedger::TYPE_DEBIT,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $reference ?? 'withdrawal',
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    /**
     * Текущий баланс пользователя по валюте (без блокировки).
     */
    public function getBalance(int $userId, string $currency): string
    {
        $balance = CryptoBalance::where('user_id', $userId)
            ->where('currency', $currency)
            ->first();

        return $balance ? (string) $balance->balance : '0';
    }

    private function parseAmount(string $amount): string
    {
        if (!is_numeric($amount) || (float) $amount < 0) {
            throw new InvalidArgumentException('Invalid amount.');
        }
        return (string) $amount;
    }
}
