<?php

namespace App\Http\Controllers;

use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LogsController extends Controller
{
    protected $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Get all logs from the second Laravel app
     */
    public function getAllLogs(Request $request): JsonResponse
    {
        $filters = $request->only([
            'application_name',
            'level',
            'user_id',
            'source',
            'date_from',
            'date_to',
            'page',
            'per_page'
        ]);

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $result = $this->loggingService->fetchLogs($filters);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => 'Logs fetched successfully',
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
                'details' => $result['message'] ?? null
            ], $result['status'] ?? 500);
        }
    }

    /**
     * Get logs with pagination
     */
    public function getLogsPaginated(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);
        
        $filters = $request->only([
            'application_name',
            'level',
            'user_id',
            'source',
            'date_from',
            'date_to'
        ]);

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $result = $this->loggingService->fetchLogsPaginated($page, $perPage, $filters);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => 'Logs fetched successfully',
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
                'details' => $result['message'] ?? null
            ], $result['status'] ?? 500);
        }
    }

    /**
     * Get logs by application name
     */
    public function getLogsByApplication(Request $request, string $applicationName): JsonResponse
    {
        $filters = $request->only([
            'level',
            'user_id',
            'source',
            'date_from',
            'date_to',
            'page',
            'per_page'
        ]);

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $result = $this->loggingService->fetchLogsByApplication($applicationName, $filters);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => "Logs for application '{$applicationName}' fetched successfully",
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
                'details' => $result['message'] ?? null
            ], $result['status'] ?? 500);
        }
    }

    /**
     * Get logs by level
     */
    public function getLogsByLevel(Request $request, string $level): JsonResponse
    {
        $request->validate([
            'level' => 'required|string|in:info,warning,error,debug'
        ]);

        $filters = $request->only([
            'application_name',
            'user_id',
            'source',
            'date_from',
            'date_to',
            'page',
            'per_page'
        ]);

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $result = $this->loggingService->fetchLogsByLevel($level, $filters);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => "Logs with level '{$level}' fetched successfully",
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
                'details' => $result['message'] ?? null
            ], $result['status'] ?? 500);
        }
    }

    /**
     * Get logs by user ID
     */
    public function getLogsByUser(Request $request, string $userId): JsonResponse
    {
        $filters = $request->only([
            'application_name',
            'level',
            'source',
            'date_from',
            'date_to',
            'page',
            'per_page'
        ]);

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $result = $this->loggingService->fetchLogsByUser($userId, $filters);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => "Logs for user '{$userId}' fetched successfully",
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
                'details' => $result['message'] ?? null
            ], $result['status'] ?? 500);
        }
    }

    /**
     * Get logs for the current user
     */
    public function getMyLogs(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $filters = $request->only([
            'application_name',
            'level',
            'source',
            'date_from',
            'date_to',
            'page',
            'per_page'
        ]);

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $result = $this->loggingService->fetchLogsByUser($userId, $filters);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => 'Your logs fetched successfully',
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
                'details' => $result['message'] ?? null
            ], $result['status'] ?? 500);
        }
    }

    /**
     * Get logs statistics
     */
    public function getLogsStats(Request $request): JsonResponse
    {
        $filters = $request->only([
            'application_name',
            'user_id',
            'source',
            'date_from',
            'date_to'
        ]);

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        // Add stats parameter to get statistics
        $filters['stats'] = true;

        $result = $this->loggingService->fetchLogs($filters);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => 'Logs statistics fetched successfully',
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
                'details' => $result['message'] ?? null
            ], $result['status'] ?? 500);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): JsonResponse
    {
        $stats = $this->loggingService->getCacheStats();

        return response()->json([
            'status' => 'success',
            'message' => 'Cache statistics fetched successfully',
            'data' => $stats
        ]);
    }

    /**
     * Clear all logs cache
     */
    public function clearCache(): JsonResponse
    {
        $this->loggingService->clearAllLogsCache();

        return response()->json([
            'status' => 'success',
            'message' => 'Logs cache cleared successfully'
        ]);
    }

    /**
     * Invalidate cache for specific filters
     */
    public function invalidateCache(Request $request): JsonResponse
    {
        $filters = $request->only([
            'application_name',
            'level',
            'user_id',
            'source',
            'date_from',
            'date_to'
        ]);

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $this->loggingService->invalidateCacheByFilters($filters);

        return response()->json([
            'status' => 'success',
            'message' => 'Cache invalidated successfully',
            'filters' => $filters
        ]);
    }

    /**
     * Force refresh logs (bypass cache)
     */
    public function refreshLogs(Request $request): JsonResponse
    {
        $filters = $request->only([
            'application_name',
            'level',
            'user_id',
            'source',
            'date_from',
            'date_to',
            'page',
            'per_page'
        ]);

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        // Temporarily disable cache for this request
        $this->loggingService->setCacheEnabled(false);
        $result = $this->loggingService->fetchLogs($filters);
        $this->loggingService->setCacheEnabled(true);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => 'Logs refreshed successfully (cache bypassed)',
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'],
                'details' => $result['message'] ?? null
            ], $result['status'] ?? 500);
        }
    }
}
