<?php

namespace Ranker\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Ranker\Contracts\Sortable;
use Ranker\Http\Requests\RankerOrderRequest;

class RankerController extends Controller
{
    /**
     * Handle incoming AJAX/Fetch request to update item sort order.
     */
    public function reorder(RankerOrderRequest $request): JsonResponse
    {
        $modelClass = $request->input('model');

        if ($modelClass) {
            $allowedModels = config('ranker.allowed_models', []);

            if (!in_array($modelClass, $allowedModels, true) && !is_subclass_of($modelClass, Sortable::class)) {
                return response()->json([
                    'success' => false,
                    'message' => "Model [{$modelClass}] is not authorized for reordering.",
                ], 403);
            }
        }

        $items = $request->input('items', []);
        $startOrder = (int) $request->input('start_order', config('ranker.start_order', 1));
        $primaryKey = $request->input('primary_key');

        if ($modelClass && method_exists($modelClass, 'setNewOrder')) {
            $modelClass::setNewOrder($items, $startOrder, $primaryKey);
        }

        return response()->json([
            'success' => true,
            'message' => 'Items reordered successfully.',
            'count' => count($items),
        ]);
    }
}
