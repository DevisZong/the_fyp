<?php

namespace App\Services;

use DateTime;

class ChildGrowthAssessment
{
    // WHO Weight-for-Age standards (boys)
    private static $whoBoysData = [
        0 => ['median' => 3.3, 'sd' => 0.44],
        1 => ['median' => 4.5, 'sd' => 0.55],
        2 => ['median' => 5.6, 'sd' => 0.64],
        3 => ['median' => 6.4, 'sd' => 0.72],
        6 => ['median' => 7.9, 'sd' => 0.88],
        9 => ['median' => 9.2, 'sd' => 0.95],
        12 => ['median' => 10.2, 'sd' => 1.01],
        15 => ['median' => 11.0, 'sd' => 1.06],
        18 => ['median' => 11.7, 'sd' => 1.11],
        24 => ['median' => 12.9, 'sd' => 1.23],
        36 => ['median' => 15.0, 'sd' => 1.52],
        48 => ['median' => 17.3, 'sd' => 1.92],
        60 => ['median' => 19.7, 'sd' => 2.42]
    ];

    // WHO Weight-for-Age standards (girls)
    private static $whoGirlsData = [
        0 => ['median' => 3.2, 'sd' => 0.44],
        1 => ['median' => 4.2, 'sd' => 0.53],
        2 => ['median' => 5.1, 'sd' => 0.61],
        3 => ['median' => 5.8, 'sd' => 0.68],
        6 => ['median' => 7.3, 'sd' => 0.83],
        9 => ['median' => 8.5, 'sd' => 0.90],
        12 => ['median' => 9.5, 'sd' => 0.95],
        15 => ['median' => 10.2, 'sd' => 1.00],
        18 => ['median' => 10.8, 'sd' => 1.04],
        24 => ['median' => 12.0, 'sd' => 1.17],
        36 => ['median' => 14.1, 'sd' => 1.46],
        48 => ['median' => 16.4, 'sd' => 1.85],
        60 => ['median' => 18.7, 'sd' => 2.33]
    ];

    public static function assessGrowth($weight, $birthDate, $gender, $assessmentDate = null)
    {
        // Calculate age in months
        $ageMonths = self::calculateAgeInMonths($birthDate, $assessmentDate);

        // Get reference data
        $referenceData = self::getReferenceData($ageMonths, $gender);

        // Calculate Z-score
        $zScore = ($weight - $referenceData['median']) / $referenceData['sd'];
        $zScore = round($zScore, 2);

        // Get classification
        $classification = self::classifyNutritionalStatus($zScore);

        $result = [
            'age_months' => $ageMonths,
            'z_score' => $zScore,
            'status' => $classification['status'],
            'color' => $classification['color'],
            'priority' => $classification['priority'],
            'recommendation' => $classification['recommendation']
        ];
        logger('Growth assessment result: ' . json_encode($result));
        return $result;
    }

    private static function calculateAgeInMonths($birthDate, $assessmentDate = null)
    {
        $birth = new DateTime($birthDate);
        $assess = $assessmentDate ? new DateTime($assessmentDate) : new DateTime();

        $interval = $birth->diff($assess);
        return ($interval->y * 12) + $interval->m;
    }

    private static function getReferenceData($ageMonths, $gender)
    {
        $data = strtolower($gender) === 'male' ? self::$whoBoysData : self::$whoGirlsData;

        // Find closest age
        $closestAge = null;
        $minDiff = PHP_INT_MAX;

        foreach (array_keys($data) as $age) {
            $diff = abs($age - $ageMonths);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closestAge = $age;
            }
        }

        return $data[$closestAge];
    }

    private static function classifyNutritionalStatus($zScore)
    {
        if ($zScore < -3) {
            return [
                'status' => 'Severe Underweight',
                'color' => 'red',
                'priority' => 'URGENT',
                'recommendation' => 'Immediate medical attention required'
            ];
        } elseif ($zScore < -2) {
            return [
                'status' => 'Underweight',
                'color' => 'light-red',
                'priority' => 'HIGH',
                'recommendation' => 'Medical consultation needed'
            ];
        } elseif ($zScore < -1) {
            return [
                'status' => 'Poor/Weak',
                'color' => 'gray',
                'priority' => 'MEDIUM',
                'recommendation' => 'Monitor closely and improve nutrition'
            ];
        } elseif ($zScore < 2) {
            return [
                'status' => 'Good/Normal',
                'color' => 'green',
                'priority' => 'LOW',
                'recommendation' => 'Continue current practices'
            ];
        } else {
            return [
                'status' => 'Overweight',
                'color' => 'orange',
                'priority' => 'MEDIUM',
                'recommendation' => 'Review feeding practices'
            ];
        }
    }
}
