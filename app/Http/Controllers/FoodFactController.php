<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodFact;

class FoodFactController extends Controller
{
    /**
     * Display the current month's diet needs message for the authenticated user's child.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function currentMonthDiet(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $child = $user->child;

        if (!$child) {
            return response()->json(['error' => 'No child associated with this user'], 404);
        }

        $ageInMonths = $child->age_in_months;

        $foodFact = FoodFact::where('age_group_start_month', '<=', $ageInMonths)
            ->where('age_group_end_month', '>=', $ageInMonths)
            ->first();

        if (!$foodFact) {
            return response()->json(['message' => 'No diet information available for this age group.']);
        }

        return response()->json([
            'age_group_start_month' => $foodFact->age_group_start_month,
            'age_group_end_month' => $foodFact->age_group_end_month,
            'message' => $foodFact->message,
        ]);
    }
}
