<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_items' => Item::count(),
                    'available_items' => Item::where('status', 'available')->count(),
                    'borrowed_items' => Transaction::where('status', 'active')->count(),
                    'pending_requests' => Transaction::where('status', 'pending')->count(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => Item::count(),
                'available_items' => Item::where('status', 'available')->count(),
                'borrowed_items' => Transaction::where('user_id', $user->id)->where('status', 'active')->count(),
                'pending_requests' => Transaction::where('user_id', $user->id)->where('status', 'pending')->count(),
            ],
        ]);
    }
}
