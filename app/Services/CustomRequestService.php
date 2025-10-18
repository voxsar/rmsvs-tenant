<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\CustomRequest;

class CustomRequestService
{
    /**
     * Process a custom request based on its type
     *
     * @return CustomRequest
     */
    public function processRequest(CustomRequest $request)
    {
        switch ($request->request_type) {
            case 'LATE_DINNER':
                return $this->processLateDinnerRequest($request);
            case 'ABSENCE':
                return $request; // No special processing needed
            case 'CONSUMABLE':
                return $request; // No special processing needed
            default:
                return $request;
        }
    }

    /**
     * Process a late dinner request
     *
     * @return CustomRequest
     */
    private function processLateDinnerRequest(CustomRequest $request)
    {
        // Check if "Late Dinner" consumable exists
        $lateDinner = Consumable::where('name', 'Late Dinner')->first();

        // Create it if it doesn't exist
        if (! $lateDinner) {
            $lateDinner = Consumable::create([
                'name' => 'Late Dinner',
                'description' => 'Late dinner request by guest',
                'price' => 0, // Can be adjusted if needed
                'is_visible' => false, // This will hide it from the regular consumables list
            ]);
        }

        // Associate the Late Dinner consumable with this request
        $request->consumable_id = $lateDinner->id;
        $request->save();

        return $request;
    }
}
