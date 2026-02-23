<?php

namespace App\Http\Controllers;

use App\Services\CryptoBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CryptoBalanceController extends Controller
{
    public function __construct(
        private CryptoBalanceService $cryptoBalance
    ) {}

    /**
     * Зачисление на баланс (deposit).
     */
    public function credit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'currency' => 'required|string|max:20',
            'amount' => 'required|string',
            'reference' => 'nullable|string|max:100',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        try {
            $ledger = $this->cryptoBalance->credit(
                (int) $validated['user_id'],
                $validated['currency'],
                $validated['amount'],
                $validated['reference'] ?? null,
                $validated['idempotency_key'] ?? null
            );
            return response()->json([
                'success' => true,
                'balance_after' => (string) $ledger->balance_after,
                'ledger_id' => $ledger->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }
    }

    /**
     * Списание с баланса (withdrawal, payment, fee).
     */
    public function debit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'currency' => 'required|string|max:20',
            'amount' => 'required|string',
            'reference' => 'nullable|string|max:100',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        try {
            $ledger = $this->cryptoBalance->debit(
                (int) $validated['user_id'],
                $validated['currency'],
                $validated['amount'],
                $validated['reference'] ?? null,
                $validated['idempotency_key'] ?? null
            );
            return response()->json([
                'success' => true,
                'balance_after' => (string) $ledger->balance_after,
                'ledger_id' => $ledger->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Текущий баланс по валюте.
     */
    public function balance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'currency' => 'required|string|max:20',
        ]);

        $balance = $this->cryptoBalance->getBalance(
            (int) $validated['user_id'],
            $validated['currency']
        );

        return response()->json([
            'user_id' => (int) $validated['user_id'],
            'currency' => $validated['currency'],
            'balance' => $balance,
        ]);
    }
}
