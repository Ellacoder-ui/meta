<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BorrowRequest as BorrowRequestRequest;
use App\Http\Requests\TransactionStatusRequest;
use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Administrators see every transaction; regular users see only their own.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->role === 'admin'
            ? Transaction::with(['user', 'item', 'approver'])
            : $user->transactions()->with(['item', 'approver']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('item', function ($itemQuery) use ($search) {
                    $itemQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('asset_tag', 'like', '%'.$search.'%');
                })->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($user->role === 'admin' && $request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $perPage = (int) $request->input('per_page', 15);
        $transactions = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }

    public function borrow(BorrowRequestRequest $request): JsonResponse
    {
        $transaction = DB::transaction(function () use ($request) {
            // Pessimistic lock prevents two concurrent requests from borrowing
            // the same physical item.
            $item = Item::where('id', $request->integer('item_id'))
                ->lockForUpdate()
                ->firstOrFail();

            if ($item->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'Item is not available for borrowing.',
                ], 422);
            }

            return Transaction::create([
                'user_id' => $request->user()->id,
                'item_id' => $item->id,
                'expected_return_at' => $request->input('expected_return_at'),
                'status' => 'pending',
            ]);
        });

        if ($transaction instanceof JsonResponse) {
            return $transaction;
        }

        return response()->json([
            'success' => true,
            'message' => 'Borrow request submitted successfully.',
            'data' => $transaction->load(['user', 'item']),
        ], 201);
    }

    public function updateStatus(TransactionStatusRequest $request, Transaction $transaction): JsonResponse
    {
        $status = $request->string('status')->toString();

        $allowed = [
            'pending' => ['approved', 'rejected'],
            'approved' => ['active'],
            'active' => ['returned'],
        ];

        if (! in_array($status, $allowed[$transaction->status] ?? [], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition for this transaction.',
            ], 422);
        }

        $attributes = ['status' => $status];

        if ($status === 'approved' || $status === 'active') {
            $attributes['approved_by'] = $request->user()->id;
        }

        if ($status === 'active') {
            $attributes['borrowed_at'] = now();
        }

        if ($status === 'returned') {
            $attributes['returned_at'] = now();
        }

        DB::transaction(function () use ($transaction, $attributes, $status) {
            $transaction->update($attributes);

            if ($status === 'active') {
                $transaction->item()->update(['status' => 'assigned']);
            }

            if ($status === 'returned') {
                $transaction->item()->update(['status' => 'available']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaction status updated.',
            'data' => $transaction->load(['user', 'item', 'approver']),
        ]);
    }
}
