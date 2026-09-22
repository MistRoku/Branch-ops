<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Special;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromotionController extends Controller
{
    /**
     * Active specials for the caller's branch (map of product_id to price).
     */
    public function specials(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = $request->get('branch_id', $user->branch_id);

        if (! $user->isSuperAdmin() && $branchId != $user->branch_id) {
            $branchId = $user->branch_id;
        }

        $specials = Special::with('product')
            ->active()
            ->forBranch($branchId)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->product_id => [
                'price' => (float) $s->special_price,
                'ends_at' => $s->ends_at?->toISOString(),
            ]]);

        return response()->json(['success' => true, 'data' => $specials]);
    }

    /**
     * Validate a coupon code against a basket subtotal.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $validated['code'])->first();

        if (! $coupon || ! $coupon->isUsableFor((float) $validated['subtotal'])) {
            return response()->json(['success' => false, 'message' => 'Coupon code is not valid for this sale'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
                'discount' => $coupon->discountFor((float) $validated['subtotal']),
            ],
        ]);
    }
}
