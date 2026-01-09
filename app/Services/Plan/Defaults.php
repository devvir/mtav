<?php

namespace App\Services\Plan;

use App\Models\Plan;

class Defaults
{
    private const HALF_GAP = 5;         // Multiple of 2.5
    private const UNITS_PER_ROW = 11;

    /**
     * Calculate the next available position for a Unit in a grid layout.
     * Positions units with centroids at multiples of 5 for grid alignment.
     *
     * Algorithm:
     * - HI = floor((BW-2*HG)/5/UNITS_PER_ROW) * 2.5    // Half the item space
     * - IS = 2 * (HI - HG)                             // Item size
     * - Cx = HG + HI * (2x + 1)                        // Centroid at multiple of 5
     * - Top-left = Centroid - IS/2
     *
     * @return array<array{0: float, 1: float}>
     */
    public function getNextAvailablePosition(Plan $plan): array
    {
        // Assumes all existing units have default positioning
        // Once an admin starts adding units, they're responsible for positioning them
        $existingUnits = $plan->items()->whereType('unit')->count();

        // Calculate plan boundaries from polygon (Point[] format: [[x,y], [x,y], ...])
        $polygon = $plan->polygon;
        $xs = array_column($polygon, 0);
        $ys = array_column($polygon, 1);
        $minX = min($xs);
        $minY = min($ys);
        $maxX = max($xs);

        $boundaryWidth = $maxX - $minX;

        // Item size (IS) = 2 * (HI - HG)
        $halfItemSpace = floor(($boundaryWidth - 2 * self::HALF_GAP) / 5 / self::UNITS_PER_ROW) * 2.5;

        // Calculate row and column (0-based)
        $row = floor($existingUnits / self::UNITS_PER_ROW);
        $col = $existingUnits % self::UNITS_PER_ROW;

        // Calculate centroid: Cx = HG + HI * (2x + 1)
        // This ensures centroid is at a multiple of 5
        $centroidX = $minX + 3 * self::HALF_GAP + $halfItemSpace * (2 * $col + 1);
        $centroidY = $minY + 3 * self::HALF_GAP + $halfItemSpace * (2 * $row + 1);

        // Item Width / Height (these are squares)
        $itemSide = 2 * ($halfItemSpace - self::HALF_GAP);

        // Calculate top-left from centroid
        $x = 5 * round($centroidX / 5) - $itemSide / 2;
        $y = 5 * round($centroidY / 5) - $itemSide / 2;

        return [
            [$x, $y],                                      // Top-left
            [$x + $itemSide, $y],                         // Top-right
            [$x + $itemSide, $y + $itemSide],           // Bottom-right
            [$x, $y + $itemSide],                        // Bottom-left
        ];
    }
}
