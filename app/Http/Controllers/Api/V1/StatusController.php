<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StatusController extends Controller
{
    public function index(): JsonResponse
    {
        try
        {
            DB::connection()->getPdo();
            $database = 'ok';
        }
        catch(Throwable $e)
        {
            $database = 'unavailable';
        }

        $status = $database === 'ok' ? 'ok' : 'degraded';

        return response()->json([
            'status' => $status,
            'database' => $database,
            'timestamp' => now()->toIso8601String(),
        ], $status === 'ok' ? 200 : 503);
    }
}
