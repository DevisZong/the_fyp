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
     */    public function currentMonthDiet(Request $request)
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

        $foodFact = FoodFact::getForAge($ageInMonths);

        if (!$foodFact) {
            return response()->json(['message' => 'No diet information available for this age group.']);
        }

        return response()->json([
            'age_group' => $foodFact->age_group,
            'age_group_start_month' => $foodFact->age_group_start_month,
            'age_group_end_month' => $foodFact->age_group_end_month,
            'message' => $foodFact->message,
            'child_age_months' => $ageInMonths,
        ]);
    }

    /**
     * Display all food facts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $foodFacts = FoodFact::orderBy('age_group_start_month')->get();

        $formattedFacts = $foodFacts->map(function ($fact) {
            return [
                'id' => $fact->id,
                'age_group' => $fact->age_group,
                'age_group_start_month' => $fact->age_group_start_month,
                'age_group_end_month' => $fact->age_group_end_month,
                'message' => $fact->message,
                'created_at' => $fact->created_at,
                'updated_at' => $fact->updated_at,
            ];
        });

        return response()->json([
            'food_facts' => $formattedFacts,
            'total' => $foodFacts->count()
        ]);
    }
}
