<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanItem;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::find(12);

        if (!$plan) {
            $this->command->warn('Plan #1 not found. Skipping PlanSeeder.');
            return;
        }

        // Create realistic project boundary with more space for access
        $projectBoundary = [5, 5, 395, 5, 395, 245, 5, 245];

        $plan->update([
            'polygon' => $projectBoundary,
            'width'   => 400,
            'height'  => 250
        ]);

        // Get existing plan items for units
        $planItems = $plan->items()->where('type', 'unit')->orderBy('id')->get();

        // Define realistic housing layout with access roads and proper unit sizes
        $unitPolygons = [
            // Block A - North side units (6 units) - facing main road
            [20, 20, 70, 20, 70, 50, 20, 50],     // A1 - corner unit
            [20, 20, 70, 20, 70, 50, 20, 50],     // A1 - corner unit
            [20, 20, 70, 20, 70, 50, 20, 50],     // A1 - corner unit
            [80, 20, 130, 20, 130, 50, 80, 50],   // A2
            [140, 20, 190, 20, 190, 50, 140, 50], // A3
            [200, 20, 250, 20, 250, 50, 200, 50], // A4
            [260, 20, 310, 20, 310, 50, 260, 50], // A5
            [320, 20, 370, 20, 370, 50, 320, 50], // A6 - corner unit

            // Block B - Central west units (3 units) - along internal road
            [20, 80, 80, 80, 80, 120, 20, 120],   // B1 - larger unit
            [20, 130, 80, 130, 80, 170, 20, 170], // B2 - larger unit
            [20, 180, 80, 180, 80, 220, 20, 220], // B3 - larger unit

            // Block C - Central east units (3 units) - along internal road
            [310, 80, 370, 80, 370, 120, 310, 120],   // C1 - larger unit
            [310, 130, 370, 130, 370, 170, 310, 170], // C2 - larger unit
            [310, 180, 370, 180, 370, 220, 310, 220], // C3 - larger unit

            // Block D - South central units (6 units) - moved higher for park space
            [100, 180, 150, 180, 150, 210, 100, 210], // D1
            [160, 180, 210, 180, 210, 210, 160, 210], // D2
            [220, 180, 270, 180, 270, 210, 220, 210], // D3
            [100, 150, 150, 150, 150, 170, 100, 170], // D4 - smaller units
            [160, 150, 210, 150, 210, 170, 160, 170], // D5 - smaller units
            [220, 150, 270, 150, 270, 170, 220, 170], // D6 - smaller units
        ];

        // Update each existing plan item with new polygon
        foreach ($planItems as $index => $planItem) {
            if (isset($unitPolygons[$index])) {
                $planItem->update([
                    'polygon' => $unitPolygons[$index]
                ]);
            }
        }

        // Create central park in the middle space - positioned with road clearance
        PlanItem::create([
            'plan_id'  => $plan->id,
            'type'     => 'park',
            'polygon'  => [110, 70, 280, 70, 280, 130, 110, 130], // Central park with road spacing
            'floor'    => 0,
            'name'     => 'Central Community Park',
            'metadata' => [
                'fill'   => '#a3a36b',  // Natural park color (greenish-brown)
                'stroke' => '#6b7280'   // Subtle gray stroke
            ]
        ]);

        $this->command->info('Plan #1 updated with realistic housing project layout - 18 properly sized units with access roads and logical block organization.');
    }
}
