<?php

namespace App\Http\Controllers;

use App\Core\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->database(),
            'cache' => $this->cache(),
            'redis' => $this->redis(),
        ];

        $healthy = $checks['database'];

        return ApiResponse::success([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 'Healthy.' : 'Degraded.', $healthy ? 200 : 503);
    }

    private function database(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function cache(): bool
    {
        try {
            Cache::put('healthcheck', true, 5);

            return Cache::get('healthcheck') === true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function redis(): bool
    {
        try {
            return Redis::connection()->ping() !== false;
        } catch (\Throwable) {
            return false;
        }
    }
}
