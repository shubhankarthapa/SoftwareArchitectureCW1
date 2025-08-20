<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class UserService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.user_service.url', 'http://localhost:8001');
    }

    public function register($data)
    {
        $response = Http::post("{$this->baseUrl}/api/auth/register", $data);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        throw new \Exception('User registration failed: ' . $response->body());
    }

    public function login($credentials)
    {
        $response = Http::post("{$this->baseUrl}/api/auth/login", $credentials);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        throw new \Exception('Login failed: ' . $response->body());
    }

    public function getUser($userId)
    {
        $cacheKey = "user_{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $response = Http::get("{$this->baseUrl}/api/users/{$userId}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        });
    }

    public function updateUser($userId, $data)
    {
        $response = Http::put("{$this->baseUrl}/api/users/{$userId}", $data);
        
        if ($response->successful()) {
            Cache::forget("user_{$userId}");
            return $response->json();
        }
        
        throw new \Exception('User update failed: ' . $response->body());
    }
}
