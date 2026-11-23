<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Product;
use App\Services\ApplicationScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationScheduleController extends Controller
{
    public function __construct(
        protected ApplicationScheduleService $scheduleService,
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_date' => 'required|date',
            'product_id' => 'nullable|integer|exists:products,id',
            'pet_id' => 'nullable|integer|exists:pets,id',
            'item_name' => 'nullable|string|max:255',
            'context' => 'nullable|in:vaccination,parasite_control,medication,general',
            'exclude_id' => 'nullable|integer|min:1',
            'dose' => 'nullable|string|max:100',
        ]);

        $product = !empty($validated['product_id']) ? Product::find($validated['product_id']) : null;
        $pet = !empty($validated['pet_id']) ? Pet::find($validated['pet_id']) : null;

        $result = $this->scheduleService->calculateNextApplication(
            $validated['application_date'],
            $product,
            $pet,
            $validated['item_name'] ?? null,
            $validated['context'] ?? 'general',
            isset($validated['exclude_id']) ? (int) $validated['exclude_id'] : null,
            $validated['dose'] ?? null
        );

        $result['product'] = $product ? [
            'id' => $product->id,
            'name' => $product->name,
            'active_ingredient' => $product->getAttribute('active_ingredient'),
        ] : null;

        return response()->json($result);
    }
}
