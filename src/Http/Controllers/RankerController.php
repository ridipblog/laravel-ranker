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
        $scope = (array) $request->input('scope', []);
        $targetGroup = $request->input('target_group');

        // Kanban cross-group moving support
        if ($targetGroup && !empty($items) && $modelClass) {
            $movedId = $items[0];
            $modelInstance = $modelClass::find($movedId);

            if ($modelInstance && method_exists($modelInstance, 'moveToGroup')) {
                $targetPosition = $request->has('position') ? (int) $request->input('position') : null;
                $modelInstance->moveToGroup((array) $targetGroup, $targetPosition);

                return response()->json([
                    'success' => true,
                    'message' => 'Item moved across groups successfully.',
                    'item_id' => $movedId,
                ]);
            }
        }

        if ($modelClass && method_exists($modelClass, 'setNewOrder')) {
            $modelClass::setNewOrder($items, $startOrder, $primaryKey, $scope);
        }

        return response()->json([
            'success' => true,
            'message' => 'Items reordered successfully.',
            'count' => count($items),
        ]);
    }
}
