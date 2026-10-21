<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItemRequest;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Item::with('category');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('asset_tag', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = (int) $request->input('per_page', 15);
        $items = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
        ]);
    }

    public function store(ItemRequest $request): JsonResponse
    {
        $item = Item::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully.',
            'data' => $item->load('category'),
        ], 201);
    }

    public function show(Item $item): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $item->load(['category', 'transactions']),
        ]);
    }

    public function update(ItemRequest $request, Item $item): JsonResponse
    {
        $item->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully.',
            'data' => $item->load('category'),
        ]);
    }

    public function destroy(Item $item): JsonResponse
    {
        if ($item->transactions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an item with transaction history.',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.',
        ]);
    }
}
