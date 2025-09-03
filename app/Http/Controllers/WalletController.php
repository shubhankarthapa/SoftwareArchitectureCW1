<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    protected $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    public function getBalance(Request $request): JsonResponse
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0, 'currency' => 'USD']
        );

        return response()->json([
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'user_id' => $wallet->user_id
        ]);
    }

    public function deposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $request->user()->id],
                ['balance' => 0, 'currency' => 'USD']
            );

            $wallet->increment('balance', $request->amount);

            // Create transaction record
            $transaction = $wallet->transactions()->create([
                'type' => 'deposit',
                'amount' => $request->amount,
                'description' => 'Wallet deposit',
                'reference_id' => 'DEPOSIT_' . time(),
                'status' => 'completed',
            ]);

            DB::commit();

            // Log transaction
            $this->loggingService->logTransaction('deposit', $transaction->toArray(), $request->user()->id);

            return response()->json([
                'message' => 'Deposit successful',
                'new_balance' => $wallet->fresh()->balance,
                'amount_deposited' => $request->amount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Deposit failed: ' . $e->getMessage()], 500);
        }
    }

    public function withdraw(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $wallet = Wallet::where('user_id', $request->user()->id)->first();

            if (!$wallet || $wallet->balance < $request->amount) {
                return response()->json(['error' => 'Insufficient balance'], 400);
            }

            $wallet->decrement('balance', $request->amount);

            // Create transaction record
            $transaction = $wallet->transactions()->create([
                'type' => 'withdrawal',
                'amount' => $request->amount,
                'description' => 'Wallet withdrawal',
                'reference_id' => 'WITHDRAW_' . time(),
                'status' => 'completed',
            ]);

            DB::commit();

            // Log transaction
            $this->loggingService->logTransaction('withdrawal', $transaction->toArray(), $request->user()->id);

            return response()->json([
                'message' => 'Withdrawal successful',
                'new_balance' => $wallet->fresh()->balance,
                'amount_withdrawn' => $request->amount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Withdrawal failed: ' . $e->getMessage()], 500);
        }
    }

    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'to_user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $fromWallet = Wallet::where('user_id', $request->user()->id)->first();
            $toWallet = Wallet::firstOrCreate(
                ['user_id' => $request->to_user_id],
                ['balance' => 0, 'currency' => 'USD']
            );

            if (!$fromWallet || $fromWallet->balance < $request->amount) {
                return response()->json(['error' => 'Insufficient balance'], 400);
            }

            if ($request->user()->id == $request->to_user_id) {
                return response()->json(['error' => 'Cannot transfer to yourself'], 400);
            }

            // Deduct from sender
            $fromWallet->decrement('balance', $request->amount);
            
            // Add to recipient
            $toWallet->increment('balance', $request->amount);

            // Create transaction records
            $fromTransaction = $fromWallet->transactions()->create([
                'type' => 'transfer',
                'amount' => $request->amount,
                'description' => "Transfer to user #{$request->to_user_id}",
                'reference_id' => 'TRANSFER_OUT_' . time(),
                'status' => 'completed',
            ]);

            $toTransaction = $toWallet->transactions()->create([
                'type' => 'transfer',
                'amount' => $request->amount,
                'description' => "Transfer from user #{$request->user()->id}",
                'reference_id' => 'TRANSFER_IN_' . time(),
                'status' => 'completed',
            ]);

            DB::commit();

            // Log transactions
            $this->loggingService->logTransaction('transfer_out', $fromTransaction->toArray(), $request->user()->id);
            $this->loggingService->logTransaction('transfer_in', $toTransaction->toArray(), $request->to_user_id);

            return response()->json([
                'message' => 'Transfer successful',
                'amount_transferred' => $request->amount,
                'from_balance' => $fromWallet->fresh()->balance,
                'to_user_id' => $request->to_user_id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Transfer failed: ' . $e->getMessage()], 500);
        }
    }

    public function getTransactions(Request $request): JsonResponse
    {
        $wallet = Wallet::where('user_id', $request->user()->id)->first();

        if (!$wallet) {
            return response()->json(['transactions' => []]);
        }

        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['transactions' => $transactions]);
    }
}
