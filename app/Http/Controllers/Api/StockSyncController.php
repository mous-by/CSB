<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materiel;
use App\Services\Synchro\StockSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API du logiciel de stock :
 *   GET  /api/v1/stock  → références connues du site (pour faire correspondre les articles) ;
 *   POST /api/v1/stock  → {"items": [{"reference": "HP-840-G6", "quantity": 4, "price": 285000}]} (prix facultatif).
 */
class StockSyncController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Materiel::whereNotNull('reference')->orderBy('reference')->get(['reference', 'designation', 'stock_quantity', 'price', 'availability'])
            ->map(fn (Materiel $m) => ['reference' => $m->reference, 'designation' => $m->designation, 'quantity' => $m->stock_quantity, 'price' => $m->price, 'availability' => $m->availability->value])]);
    }

    public function store(Request $request, StockSyncService $service): JsonResponse
    {
        $sync = $service->apply((array) $request->input('items', []), 'api', token: $request->attributes->get('api_token'));

        return response()->json([
            'sync_id' => $sync->id, 'received' => $sync->items_count, 'matched' => $sync->matched_count,
            'updated' => $sync->updated_count, 'unknown_references' => $sync->unknown_references ?? [],
        ]);
    }
}
