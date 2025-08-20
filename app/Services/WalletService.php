<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WalletService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.wallet_service.url', 'http://localhost:8004');
    }

    public function getBalance($userId)
    {
        $cacheKey = "wallet_balance_{$userId}";
        
        return Cache::remember($cacheKey, 60, function () use ($userId) {
            $response = Http::get("{$this->baseUrl}/api/wallets/{$userId}/balance");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        });
    }

    public function deposit($userId, $amount)
    {
        $response = Http::post("{$this->baseUrl}/api/wallets/{$userId}/deposit", [
            'amount' => $amount
        ]);
        
        if ($response->successful()) {
            // Clear balance cache
            Cache::forget("wallet_balance_{$userId}");
            return $response->json();
        }
        
        throw new \Exception('Deposit failed: ' . $response->body());
    }

    public function withdraw($userId, $amount)
    {
        $response = Http::post("{$this->baseUrl}/api/wallets/{$userId}/withdraw", [
            'amount' => $amount
        ]);
        
        if ($response->successful()) {
            // Clear balance cache
            Cache::forget("wallet_balance_{$userId}");
            return $response->json();
        }
        
        throw new \Exception('Withdrawal failed: ' . $response->body());
    }

    public function transfer($fromUserId, $toUserId, $amount)
    {
        $response = Http::post("{$this->baseUrl}/api/wallets/transfer", [
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'amount' => $amount
        ]);
        
        if ($response->successful()) {
            // Clear balance caches for both users
            Cache::forget("wallet_balance_{$fromUserId}");
            Cache::forget("wallet_balance_{$toUserId}");
            return $response->json();
        }
        
        throw new \Exception('Transfer failed: ' . $response->body());
    }

    public function getTransactions($userId)
    {
        $cacheKey = "wallet_transactions_{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $response = Http::get("{$this->baseUrl}/api/wallets/{$userId}/transactions");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        });
    }
}
